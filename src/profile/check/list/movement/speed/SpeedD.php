<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\speed;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\profile\data\InstantActionRecord;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedD extends BaseCheck implements HandleInputPacketCheck {
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
		$ki = $this->profile->getKeyInputs();

		$tick = $this->profile->getServerTick();


		// $player->sendMessage((string) abs($md->getRotationDelta()->yaw));

		$diffYaw = abs($md->getRotationDelta()->yaw);
		if (
			$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
			$md->getJumpRecord()->getTickSinceAction() >= 10 &&
			$sd->getSlipRecord()->getTickSinceAction() >= 6 &&
			$sd->getCollideUpdateRecord()->getTickSinceAction() >= 20 &&
			$md->getMotionRecord()->getTickSinceAction() >= 15 &&
			$sd->getFlowRecord()->getTickSinceAction() >= 15 &&
			$md->getSpeedChangeRecord()->getTickSinceAction() >= 7 &&
			$ki->getSneakChangeRecord()->getTickSinceAction() >= 8 &&
			count($sd->getComplexBlocks()) <= 0 &&
			$diffYaw < 20 &&
			$md->getMoveRecord()->getLength() >= 12 &&
			(
				($md->getRonGroundRecord()->getLength() >= 5 && $md->getAirRecord()->getLength() >= 3) || #Motion(D) の説明と同じ
				($md->getOnGroundRecord()->getLength() >= 5 && $md->getRairRecord()->getLength() >= 3) ||
				($md->getRonGroundRecord()->getLength() >= 7 && $md->getOnGroundRecord()->getLength() >= 7)
			)
		) {
			$expected = MinecraftPhysics::moveDistancePerTick($player);
			$diff = sqrt($md->getRealDeltaXZ()) - $expected;
			$diffScaled = $diff * 100;

			// $player->sendMessage("diff: {$diff}");
			if ($diffScaled > 1.5) {
				$this->fail(new ViolationFailReason("Diff: $diff"));
			}
		}
	}
}
