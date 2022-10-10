<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use Closure;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;
use NeiroNetwork\Flare\profile\Profile;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use pocketmine\world\biome\IcePlainsBiome;

class FailReportContext implements ReportContext {

	public function __construct(
		protected BaseCheck $cause,
		protected FailReason $failReason
	) {
		/*
		tils::validateCallableSignature(function (CommandSender $target, ICheck $cause, FailReason $failReason): string {
		}, $style);
		*/
	}

	public function getText(Reporter $reporter, CommandSender $target): string {
		$style = function (Profile $profile, ICheck $cause, FailReason $failReason): string {
			$name = $profile->getPlayer()->getName();

			$type = $cause instanceof BaseCheck ? $cause->getType() : "";
			$typeStr = $type !== "" ? " ({$type})" : "";
			return "§7$name / §f{$cause->getName()}{$typeStr}";
		};

		$profile = $this->cause->getObserver()->getProfile();

		return $style($target, $profile, $this->cause, $this->failReason);
	}
}
