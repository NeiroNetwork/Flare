<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\speed;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedD extends BaseCheck{

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

		$tick = $this->profile->getServerTick();


		// $player->sendMessage((string) abs($md->getRotationDelta()->yaw));

		$diffYaw = abs($md->getRotationDelta()->yaw);
		if(
			$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
			$ki->getStartJumpRecord()->getTickSinceAction() >= 20 &&
			$sd->getSlipRecord()->getTickSinceAction() >= 6 &&
			$sd->getCollideUpdateRecord()->getTickSinceAction() >= 20 &&
			$md->getMotionRecord()->getTickSinceAction() >= 22 &&
			$sd->getFlowRecord()->getTickSinceAction() >= 15 &&
			$md->getSpeedChangeRecord()->getTickSinceAction() >= 7 &&
			$ki->getSneakChangeRecord()->getTickSinceAction() >= 8 &&
			$md->getFlyRecord()->getTickSinceAction() >= 22 &&
			count($sd->getComplexBlocks()) <= 0 &&
			$diffYaw < 20 &&
			$md->getMoveRecord()->getLength() >= 12 &&
			$md->getOnGroundRecord()->getLength() >= 5
		){
			$expected = MinecraftPhysics::moveDistancePerTick($md->getMovementSpeed(), $ki->sneak());
			$deltaXZ = sqrt($md->getRealDeltaXZ());
			$diff = $deltaXZ - $expected;
			$diffScaled = $diff * 100;

			// $player->sendMessage("diff: {$diff}");
			if($diffScaled > 1.5){
				$this->fail(new ViolationFailReason("Diff: $diff"));
			}

			$this->broadcastDebugMessage("deltaXZ: {$deltaXZ} diff: {$diff}");
		}
	}
}
