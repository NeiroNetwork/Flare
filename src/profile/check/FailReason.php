<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

class FailReason{

	public function __construct(
		public string $verbose,
	){}
}
