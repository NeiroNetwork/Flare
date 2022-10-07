<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils\timings;

use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;

class ProfileDataTimings {

	public TimingsHandler $movement;
	public TimingsHandler $surround;

	public function __construct(protected FlareTimings $parent, protected string $prefix) {
		$icp = Timings::INCLUDED_BY_OTHER_TIMINGS_PREFIX;

		$this->movement = new TimingsHandler($prefix . "Movement");
		$this->surround = new TimingsHandler($prefix . "Surround");
	}
}
