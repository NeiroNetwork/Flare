<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\motion;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MotionC extends BaseCheck implements HandleInputPacketCheck {
	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	public function getCheckGroup(): int {
		return CheckGroup::MOVEMENT;
	}

	public function handle(PlayerAuthInputPacket $packet): void {
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();

		$dist = $md->getRealDeltaXZ();
		$lastDist = $md->getLastRealDeltaXZ();

		if (
			$md->getAirRecord()->getLength() >= 3 &&
			$sd->getClimbRecord()->getTickSinceAction() >= 5 &&
			$sd->getCobwebRecord()->getTickSinceAction() >= 5 &&
			$md->getImmobileRecord()->getTickSinceAction() >= 2 &&
			$md->getAirRecord()->getLength() <= 50 &&
			$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
			$md->getMotionRecord()->getTickSinceAction() >= 1 &&
			$dist >= 0.01
		) {
			$this->preReward();
			$accel = abs($dist - $lastDist);
			if ($accel <= 0.0000001) {
				if ($this->preFail()) {
					$this->fail(new ViolationFailReason("Accel: $accel"));
				}
			}
		}
	}
}
