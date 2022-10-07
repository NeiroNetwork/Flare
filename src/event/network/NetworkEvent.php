<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\event\network;

use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use raklib\utils\InternetAddress;

class NetworkEvent extends Event {

	/**
	 * @var InternetAddress
	 */
	protected InternetAddress $address;

	/**
	 * @return InternetAddress
	 */
	public function getAddress(): InternetAddress {
		return $this->address;
	}
}
