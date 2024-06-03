<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\speed;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$ki = $this->profile->getKeyInputs();

		$dist = $md->getRealDeltaXZ();
		$lastDist = $md->getLastRealDeltaXZ();
		if(
			$md->getAirRecord()->getLength() >= 5 &&
			$md->getMotionRecord()->getTickSinceAction() >= 20 &&
			$md->getTeleportRecord()->getTickSinceAction() >= 8 &&
			$ki->getGlideRecord()->getTickSinceAction() >= 10 &&
			$sd->getFlowRecord()->getTickSinceAction() >= 15 &&
			$sd->getClimbRecord()->getTickSinceAction() >= 5 &&
			$md->getFlyRecord()->getTickSinceAction() >= 6 &&
			$md->getLevitationRecord()->getTickSinceAction() >= 4
		){
			$predict = MinecraftPhysics::nextAirXZ($lastDist);
			$diff = $dist - $predict;
			if($diff > 0.00762){ #0.00745 -> 0.00762
				$this->fail(new ViolationFailReason("Diff: $diff"));
			}

			$this->broadcastDebugMessage("Dist: {$dist}, Diff: {$diff}");
		}
	}
}
