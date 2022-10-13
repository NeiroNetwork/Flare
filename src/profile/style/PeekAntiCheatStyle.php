<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\style;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\ICheck;
use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\LogStyle;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\profile\Profile;

class PeekAntiCheatStyle extends LogStyle {

	public function fail(Profile $profile, ICheck $cause, FailReason $reason): string {
		$percentage = $cause instanceof BaseCheck ? $cause->getVL() / $cause->getPunishVL() : 0.0;
		$perc = round($percentage * 100);
		$ping = $profile instanceof PlayerProfile ? $profile->getPing() : -1;

		$type = $cause instanceof BaseCheck ? $cause->getType() : "";
		$typeStr = $type !== "" ? " ({$type})" : "";
		return "§8[§7{$perc}%§8] §b{$profile->getName()}§8<§9{$ping}ms§8> §ffailed §c{$cause->getName()}{$typeStr}" . ($cause->isExperimental() ? "§l§e*§r" : "");
	}
}
