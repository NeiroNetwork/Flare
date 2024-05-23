<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\IntegerSortSizeMap;
use NeiroNetwork\Flare\utils\Map;
use NeiroNetwork\Flare\utils\Math;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

class ReachA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	/**
	 * @var IntegerSortSizeMap<AxisAlignedBB>
	 */
	protected IntegerSortSizeMap $map;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
		$this->registerEventHandler($this->handleAttack(...));

		$this->map = new IntegerSortSizeMap(3);
	}

	public function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();
		$player = $event->getPlayer();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();

		if($cd->getHitEntity() !== $cd->getLastHitEntity()){
			return;
		}

		$map = $this->map;
		/**
		 * @var Map<AxisAlignedBB> $map
		 */


		$eyePos = $md->getEyePosition();

		$realRefCount = count($this->map);

		if($realRefCount >= 2){
			$reaches = [];

			foreach($map as $targetBB){
				$reaches[] = Math::distanceSquaredBoundingBox($targetBB, $eyePos);
			}

			if(count($reaches) > 0){
				$minReach = min($reaches);
				$maxReach = max($reaches);

				$rootReach = sqrt($minReach);

				$this->broadcastDebugMessage("reach: {$rootReach}");

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
			$this->map->clear();
		}

		$entity = $cd->getHitEntity();
		if($entity !== null){
			$runtimeId = $entity->getId();
			$currentTick = Server::getInstance()->getTick();
			$pos = $this->profile->getSupport()->getMoveDelayPredictedPosition($runtimeId);
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
				$this->map->put($currentTick, $bb);

				//Utils::debugPosition($pos, $this->profile->getPlayer());
			}
		}
	}
}
