<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\style;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;
use NeiroNetwork\Flare\profile\LogStyle;
use NeiroNetwork\Flare\profile\Profile;

class FlareStyle extends LogStyle {

	public function fail(Profile $profile, ICheck $cause, FailReason $failReason): string {
		$name = $profile->getCommandSender()->getName();

		$type = $cause instanceof BaseCheck ? $cause->getType() : "";
		$typeStr = $type !== "" ? " ({$type})" : "";
		return "§7$name §8/ §f{$cause->getName()}{$typeStr} ";
	}
}
