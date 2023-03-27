<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class InvalidE extends BaseCheck implements HandleInputPacketCheck {
	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	protected ?int $lastClientTick = null;

	public function getCheckGroup(): int {
		return CheckGroup::PACKET;
	}

	public function isExperimental(): bool {
		return true;
	}

	public function handle(PlayerAuthInputPacket $packet): void {
		$this->reward();
		$player = $this->profile->getPlayer();

		if (is_null($this->lastClientTick)) {
			$this->lastClientTick = $packet->getTick();
			return;
		}

		if ($packet->getTick() - $this->lastClientTick !== 1) {
			$this->fail(new ViolationFailReason("Invalid auth tick"));
		}

		$this->lastClientTick = $packet->getTick();
	}
}
