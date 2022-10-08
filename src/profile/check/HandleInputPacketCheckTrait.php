<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

trait HandleInputPacketCheckTrait {

	protected function registerInputPacketHandler(): void {
		$this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handle"]),
			false,
			EventPriority::HIGH
		);
	}
}
