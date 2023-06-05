<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils\timings;

use pocketmine\timings\TimingsHandler;

class EventEmitterTimings{

	public TimingsHandler $register;

	public TimingsHandler $listen;

	public TimingsHandler $summarizeHandler;

	public TimingsHandler $handling;

	public TimingsHandler $packetHandling;

	public TimingsHandler $sendPacketHandling;

	public function __construct(protected FlareTimings $parent, protected string $prefix){

		$this->register = new TimingsHandler($prefix . "Register Handler");
		$this->listen = new TimingsHandler($prefix . "Listen Event");
		$this->summarizeHandler = new TimingsHandler($prefix . "Summarize Handler(s)");
		$this->handling = new TimingsHandler($prefix . "Run Handler(s)");
		$this->packetHandling = new TimingsHandler($prefix . "Run Packet Receive Handler(s)");
		$this->sendPacketHandling = new TimingsHandler($prefix . "Run Packet Send handler(s)");
	}
}
