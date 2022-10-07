<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\network\NACKHandler;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\plugin\PluginBase;


class Main extends PluginBase {

	protected Flare $flare;

	protected function onEnable(): void {
		$this->flare = new Flare($this);

		$this->flare->start();
	}
}
