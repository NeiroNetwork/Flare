<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

class ViolationFailReason extends FailReason {
	public function __construct(
		public string $verbose,
		public int $level = 1
	) {
	}
}
