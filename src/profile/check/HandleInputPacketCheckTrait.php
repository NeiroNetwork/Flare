<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

trait HandleInputPacketCheckTrait {

	private static string $hash = "";

	protected function registerInputPacketHandler(): void {
		self::$hash = $this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			function (PlayerAuthInputPacket $packet): void {
				if ($this->observer->isClosed()) {
					return;
				}

				$this->handle($packet);
			},
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
