<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class InvalidE extends BaseCheck implements HandleInputPacketCheck {
	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	public function getCheckGroup(): int {
		return CheckGroup::PACKET;
	}

	public function handle(PlayerAuthInputPacket $packet): void {
		$this->reward();
		$player = $this->profile->getPlayer();

		$ki = $this->profile->getKeyInputs();
	}
}
