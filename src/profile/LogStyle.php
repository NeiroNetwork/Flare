<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;

abstract class LogStyle {

	abstract public function fail(Profile $profile, ICheck $cause, FailReason $reason): string;
}
