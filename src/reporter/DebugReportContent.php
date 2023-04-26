<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use NeiroNetwork\Flare\Flare;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class DebugReportContent implements ReportContent{

	public function __construct(
		public null|Translatable|string $text,
		protected Flare $flare
	){}

	public function getText(CommandSender $target) : null|string|Translatable{
		$targetProfile = $target instanceof Player ?
			$this->flare->getProfileManager()->fetch($target->getUniqueId()->toString()) :
			$this->flare->getConsoleProfile();

		if($targetProfile->tryDebug()){
			return $this->text;
		}

		return null;
	}

	/**
	 * Set the value of text
	 */
	public function setText(string|Translatable|null $text) : self{
		$this->text = $text;

		return $this;
	}
}
