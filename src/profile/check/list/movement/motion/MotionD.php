<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\motion;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MotionD extends BaseCheck implements HandleInputPacketCheck {
	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	public function getCheckGroup(): int {
		return CheckGroup::MOVEMENT;
	}

	public function handle(PlayerAuthInputPacket $packet): void {
	}
}
