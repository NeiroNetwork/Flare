<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\speed;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedB extends BaseCheck{

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

		if($md->getTeleportRecord()->getTickSinceAction() >= 2 && $md->getFlyRecord()->getTickSinceAction() >= 4){
			$deltaYaw = abs($md->getRotationDelta()->yaw);

			$deltaXZ = $md->getDeltaXZ();
			$lastDeltaXZ = $md->getLastDeltaXZ();

			$accel = abs($deltaXZ - $lastDeltaXZ);

			$base = $md->getMovementSpeed() * 10;

			$sqAccel = $accel * 100;
			if($deltaYaw > 4.0 * $base && $deltaXZ > 0.1 * $base){
				if($sqAccel < 0.01){
					$this->fail(new ViolationFailReason("Accel: $accel, deltaYaw: $deltaYaw"));
				}
			}
		}
	}
}
