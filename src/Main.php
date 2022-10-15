<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\command\ForceDefaultCommand;
use NeiroNetwork\Flare\command\ReloadCommand;
use NeiroNetwork\Flare\network\NACKHandler;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;

class Main extends PluginBase {

	private static ?self $instance = null;

	protected Flare $flare;

	public static function getInstance(): ?self {
		return self::$instance;
	}

	protected function onLoad(): void {
		self::$instance = $this;
	}

	protected function onEnable(): void {
		$this->flare = new Flare($this);

		// task or sleeper
		$notifier = new SleeperNotifier();
		$this->getServer()->getTickSleeper()->addNotifier($notifier, function () use ($notifier): void {
			$this->flare->start();
			$this->getServer()->getTickSleeper()->removeNotifier($notifier);
		});

		$notifier->wakeupSleeper(); // ??? main -> main

		$map = $this->getServer()->getCommandMap();

		$map->register("*reload", new ReloadCommand("*reload", "フレアの全ての設定を再読み込みします"));
		$map->register("*forcedefault", new ForceDefaultCommand("*forcedefault", "全てのプレイヤーの設定をデフォルトに強制します"));
	}

	protected function onDisable(): void {
		$this->flare->shutdown();
	}

	/**
	 * Get the value of flare
	 *
	 * @return Flare
	 */
	public function getMainFlare(): Flare {
		return $this->flare;
	}
}
