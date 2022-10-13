<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

class Utils {

	public static function mustStartedException(): void {
		throw new \Exception("must not be called before started");
	}
}
