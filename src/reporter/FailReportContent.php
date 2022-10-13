<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use Closure;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;
use NeiroNetwork\Flare\profile\Profile;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use pocketmine\world\biome\IcePlainsBiome;

class FailReportContent implements ReportContent {

	public function __construct(
		protected ICheck $cause,
		protected FailReason $failReason
	) {
		/*
		tils::validateCallableSignature(function (CommandSender $target, ICheck $cause, FailReason $failReason): string {
		}, $style);
		*/
	}

	public function getText(CommandSender $target): string|Translatable {
		$profile = $this->cause->getObserver()->getProfile();
		$targetProfile = $target instanceof Player ? $profile->getFlare()->getProfileManager()->fetch($target->getUniqueId()->toString()) : $profile->getFlare()->getConsoleProfile();

		return $targetProfile->getLogStyle()->fail($profile, $this->cause, $this->failReason);
	}
}
