<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class FailReportContent implements ReportContent{

	public function __construct(
		protected ICheck $cause,
		protected FailReason $failReason
	){
		/*
		tils::validateCallableSignature(function (CommandSender $target, ICheck $cause, FailReason $failReason): string {
		}, $style);
		*/
	}

	public function getText(CommandSender $target) : null|string|Translatable{
		$profile = $this->cause->getObserver()->getProfile();
		$targetProfile = $target instanceof Player ?
			$profile->getFlare()->getProfileManager()->fetch($target->getUniqueId()->toString()) :
			$profile->getFlare()->getConsoleProfile();

		if($targetProfile->tryAlert($this->cause)){
			return $targetProfile->getLogStyle()->fail($profile, $targetProfile, $this->cause, $this->failReason);
		}

		return null;
	}
}
