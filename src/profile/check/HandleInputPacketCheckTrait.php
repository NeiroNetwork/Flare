<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

trait HandleInputPacketCheckTrait {

	private static string $hash = "";

	protected function registerInputPacketHandler(): void {
		$closure = Closure::fromCallable([$this, "handle"]);
		self::$hash = $this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handle"]),
			false,
			EventPriority::HIGH
		);
	}

	protected function unregisterInputPacketHandler(): void {
		$this->profile->getFlare()->getEventEmitter()->unregisterPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			self::$hash,
			EventPriority::HIGH
		);
	}

	public function onUnload(): void {
		$this->unregisterInputPacketHandler();
	}
}
