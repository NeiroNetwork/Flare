<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\support\MoveDelaySupport;
use NeiroNetwork\Flare\utils\Math;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Server;
use SplFixedArray;

class ReachA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	/**
	 * @var AxisAlignedBB[]
	 */
	protected array $list;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
		$this->registerEventHandler($this->handleAttack(...));

		$this->list = [];
	}

	public function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();
		$player = $event->getPlayer();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();

		if($cd->getHitEntity() !== $cd->getLastHitEntity()){
			return;
		}

		$eyePos = $md->getEyePosition();

		$refCount = 6;
		$refs = (SplFixedArray::fromArray(array_reverse($this->list)));
		$refs->setSize(min($refs->getSize(), $refCount));
		$realRefCount = $refs->getSize();

		/**
		 * @var SplFixedArray<AxisAlignedBB> $refs
		 *
		 * ジェネリクス！
		 */

		if($realRefCount >= 2){
			$first = $this->list[array_key_last($this->list)];

			$freach = Math::distanceSquaredBoundingBox($first, $eyePos);
			$reaches = [];

			foreach($refs as $targetBB){
				$reaches[] = Math::distanceSquaredBoundingBox($targetBB, $eyePos);
			}

			if(count($reaches) > 0){
				$minReach = min($reaches);
				$maxReach = max($reaches);

				$rootReach = sqrt($minReach);

				$this->broadcastDebugMessage("reach: {$rootReach} f: {$freach}");

				if($minReach > 9.0){ // (3 ** 2)
					if($this->preFail()){
						$this->fail(new ViolationFailReason("Reach: {$minReach}"));
					}
				}

			}
		}
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$this->preReward();
		$cd = $this->profile->getCombatData();

		if($cd->getHitEntity() !== $cd->getLastHitEntity()){
			$this->list = [];
		}

		$entity = $cd->getHitEntity();
		if($entity !== null){
			$runtimeId = $entity->getId();
			$currentTick = Server::getInstance()->getTick();
			$histories = $this->profile->getSupport()->getActorPositionHistory($runtimeId)->getAll();
			$pos = MoveDelaySupport::getInstance()->predict($histories, $currentTick);

			if($pos !== null){
				$size = $this->profile->getSupport()->getSize($runtimeId);

				if(is_null($size)){
					return;
				}
				
				$h = $size->getHeight();
				$w = $size->getWidth() / 2;
				$bb = new AxisAlignedBB(
					$pos->x - $w,
					$pos->y,
					$pos->z - $w,
					$pos->x + $w,
					$pos->y + $h,
					$pos->z + $w
				);
				$this->list[] = $bb;

				$pk = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $pos, "minecraft:balloon_gas_particle", null);

				$this->profile->getPlayer()->getNetworkSession()->sendDataPacket($pk);
			}
		}
	}
}
