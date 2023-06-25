<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aura;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\math\EntityRotation;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\Utils;
use NeiroNetwork\Flare\utils\VectorUtil;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AuraA1 extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	protected float $pvlMax = (100 * 2);
	/**
	 * @var array<int, array>
	 */
	protected array $queue;

	protected int $lastRequestTick;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->registerEventHandler($this->handleAttack(...));
		$this->registerPacketHandler($this->handle(...));
		$this->queue = [];
		$this->lastRequestTick = 0;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$data = $this->queue[$this->profile->getMovementData()->getInputCount()] ?? null;

		foreach($this->queue as &$d){
			$d[4][] = clone $this->profile->getMovementData()->getRotation();
		}

		if(is_null($data)){
			return;
		}


		[$event, $eyePosition, $rotation, $bb, $relayPoints] = $data;
		/**
		 * @var PlayerAttackEvent $event
		 * @var EntityRotation    $rotation
		 * @var Vector3           $eyePosition
		 * @var AxisAlignedBB     $bb
		 * @var EntityRotation[]  $relayPoints
		 */
		$entity = $event->getEntity();
		$player = $event->getPlayer();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();
		$this->reward();
		$result = null;

		unset($this->queue[$packet->getTick()]);

		foreach($relayPoints as $nextRotation){
			$delta = $nextRotation->diff($rotation);
			$divisor = max(1, min(ceil(($delta->yaw + $delta->pitch) / 2 / 0.7), 10));

			$yawStep = $delta->yaw / $divisor;
			$pitchStep = $delta->pitch / $divisor;

			$cursor = clone $rotation;

			for($i = 0; $i < $divisor; $i++){
				$cursor = $cursor->rotate($yawStep, $pitchStep, null);
				$direction = VectorUtil::getDirection3D($cursor->yaw, $cursor->pitch);
				$min = $eyePosition->addVector($direction->multiply(-0.4));
				$max = $eyePosition->addVector($direction->multiply(3.0));

				$expandedBB = $bb->expandedCopy(0.85, 1.2, 0.85);
				$r = $expandedBB->calculateIntercept($min, $max);

				if($expandedBB->isVectorInside($min)){
					$r = new RayTraceResult($bb, Facing::DOWN, $min);
				}

				if(!is_null($r)){
					$result = $r;
					break 2;
				}
			}
			$rotation = $nextRotation;
		}

		// phpstorm君しっかりして
		if(is_null($result)){
			if($this->preFail()){
				$this->fail(new ViolationFailReason("Raytracing failed"));
			}
		}else{
			$this->broadcastDebugMessage("result: {$result->getHitVector()} face: {$result->getHitFace()}");
		}
	}

	public function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();

		if($this->profile->getServerTick() - $this->lastRequestTick <= 4){
			return;
		}

		if(
			$this->profile->isTransactionPairingEnabled() ?
				$this->profile->getTransactionPairing()->getServerTick() - $this->profile->getTransactionPairing()->getLatestConfirmedTick() > 10
				:
				Utils::getBestPing($event->getPlayer()) > 500
		){
			return;
		}
		$predictPos = $this->profile->getSupport()->getMoveDelayPredictedPosition($entity->getId());
		$bb = $this->profile->getSupport()->getBoundingBox($entity->getId(), overridePos: $predictPos);

		if(is_null($bb)){
			return;
		}

		$this->queue[$this->profile->getMovementData()->getInputCount() + 2] = [
			$event,
			clone $this->profile->getMovementData()->getEyePosition(),
			clone $this->profile->getMovementData()->getRotation(),
			clone $bb,
			[]
		];

	}

	public function isExperimental() : bool{
		return true;
	}
}
