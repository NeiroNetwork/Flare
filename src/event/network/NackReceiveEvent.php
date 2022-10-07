<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\event\network;

use raklib\utils\InternetAddress;

class NackReceiveEvent extends NetworkEvent {

	public function __construct(InternetAddress $address) {
		$this->address = $address;
	}
}
