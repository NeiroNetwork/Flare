<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils\timings;

use pocketmine\timings\TimingsHandler;

class ProfileDataTimings{

	public TimingsHandler $movement;
	public TimingsHandler $surround;

	public function __construct(protected FlareTimings $parent, protected string $prefix){
		$this->movement = new TimingsHandler($prefix . "Movement");
		$this->surround = new TimingsHandler($prefix . "Surround");
	}
}
