<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\Math;
use pocketmine\event\EventPriority;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use SplFixedArray;

class ReachA extends BaseCheck implements HandleInputPacketCheck{

	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	/**
	 * @var AxisAlignedBB[]
	 */
	protected array $list;

	private string $hashb = "";

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->registerInputPacketHandler();
		$this->list = [];
		$this->hashb = $this->profile->getFlare()->getEventEmitter()->registerPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			function(PlayerAttackEvent $event) : void{
				assert($event->getPlayer() === $this->profile->getPlayer());
				if($this->tryCheck()){
					$this->handleAttack($event);
				}
			},
			false,
			EventPriority::MONITOR
		);
	}

	public function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();
		$player = $event->getPlayer();
		$cd = $this->profile->getCombatData();

		if($player->getScale() != 1.0){ // tick diff?
			return;
		}

		if($cd->getHitEntity() !== $cd->getLastHitEntity()){
			return;
		}

		$eyePos = $player->getEyePos();

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

				if($freach > 9.0 + 1.0){
					if($this->preFail()){
						$this->fail(new ViolationFailReason("Spt-Reach: {$freach}"));
					}
				}
			}
		}
	}

	public function onUnload() : void{
		$this->profile->getFlare()->getEventEmitter()->unregisterPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			$this->hashb,
			EventPriority::MONITOR
		);

		$this->unregisterInputPacketHandler();
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
			$pos = $this->profile->getFlare()->getSupports()->fullSupportMove($this->profile->getPlayer(), $runtimeId);

			if($pos !== null){
				$h = $entity->size->getHeight();
				$w = $entity->size->getWidth() / 2;
				$bb = new AxisAlignedBB(
					$pos->x - $w,
					$pos->y,
					$pos->z - $w,
					$pos->x + $w,
					$pos->y + $h,
					$pos->z + $w
				);
				$this->list[] = $bb;
			}
		}
	}
}
