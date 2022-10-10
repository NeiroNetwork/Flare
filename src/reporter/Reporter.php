<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use NeiroNetwork\Flare\Flare;

class Reporter {

	public function __construct(protected Flare $flare) {
	}

	/**
	 * Get the value of flare
	 * 
	 * @return Flare
	 */
	public function getFlare(): Flare {
		return $this->flare;
	}
}
