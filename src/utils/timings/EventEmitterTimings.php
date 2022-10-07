<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils\timings;

use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;

class EventEmitterTimings {

	public TimingsHandler $register;

	public TimingsHandler $listen;

	public TimingsHandler $summarizeHandler;

	public TimingsHandler $handling;

	public TimingsHandler $packetHandling;

	public TimingsHandler $sendPacketHandling;

	public function __construct(protected FlareTimings $parent, protected string $prefix) {
		$icp = Timings::INCLUDED_BY_OTHER_TIMINGS_PREFIX;
		$this->register = new TimingsHandler($prefix . "Register Handler");
		$this->listen = new TimingsHandler($prefix . "Listen Event");
		$this->summarizeHandler = new TimingsHandler($icp . $prefix . "Summarize Handler(s)");
		$this->handling = new TimingsHandler($icp . $prefix . "Run Handler(s)");
		$this->packetHandling = new TimingsHandler($icp . $prefix . "Run Packet Receive Handler(s)");
		$this->sendPacketHandling = new TimingsHandler($icp . $prefix . "Run Packet Send handler(s)");
	}
}
