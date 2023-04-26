<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

trait HandleInputPacketCheckTrait{

	private string $hash = "";

	public function onUnload() : void{
		$this->unregisterInputPacketHandler();
	}

	protected function unregisterInputPacketHandler() : void{
		$this->profile->getFlare()->getEventEmitter()->unregisterPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			$this->hash,
			EventPriority::MONITOR
		);
	}

	public function onLoad() : void{
		$this->registerInputPacketHandler();
	}

	protected function registerInputPacketHandler() : void{
		$this->hash = $this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			function(PlayerAuthInputPacket $packet) : void{
				assert($this instanceof HandleInputPacketCheck);

				if($this->tryCheck()){
					$this->handle($packet);
				}
			},
			false,
			EventPriority::MONITOR
		);
	}
}
