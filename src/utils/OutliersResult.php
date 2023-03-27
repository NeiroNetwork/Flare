<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

class OutliersResult {

	public array $high;
	public array $low;

	public function __construct() {
		$this->high = [];
		$this->low = [];
	}
}
