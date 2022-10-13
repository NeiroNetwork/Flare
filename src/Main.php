<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\network\NACKHandler;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;

class Main extends PluginBase {

	protected Flare $flare;

	protected function onEnable(): void {
		$this->flare = new Flare($this);

		// task or sleeper
		$notifier = new SleeperNotifier();
		$this->getServer()->getTickSleeper()->addNotifier($notifier, function () use ($notifier): void {
			$this->flare->start();
			$this->getServer()->getTickSleeper()->removeNotifier($notifier);
		});

		$notifier->wakeupSleeper(); // ??? main -> main
	}
}
