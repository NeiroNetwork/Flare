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


		$percText = "";
		if ($cause instanceof BaseCheck) {
			$percentage = $cause->getVL() / $cause->getPunishVL();

			$baseColor = "§f";
			$base = str_repeat("|", 18);
			if ($percentage < 1.0) {
				$body = substr_replace($base, "§8", (int) (strlen($base) * $percentage), 0);
			} else {
				$baseColor = "§c";
				$body = $base;
			}


			$percText = "§7[" . $baseColor . $body . "§7]";
		}
		return "§3$name §8/ §f{$cause->getName()}{$typeStr} {$percText}";
	}

	public function getAliases(): array {
		return ["flare"];
	}
}
