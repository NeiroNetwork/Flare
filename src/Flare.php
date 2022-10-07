<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use Closure;
use NeiroNetwork\Flare\profile\ProfileManager;
use pocketmine\event\EventPriority;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLogger;
use pocketmine\utils\MainLogger;

class Flare {

	protected PluginBase $plugin;

	protected FlareEventEmitter $eventEmitter;

	protected FlareEventListener $eventListener;

	protected ProfileManager $profileManager;

	protected bool $started;

	public function __construct(PluginBase $plugin) {
		if (!$plugin->isEnabled()) {
			throw new \Exception("plugin not enabled");
		}

		$this->started = false;

		$this->plugin = $plugin;

		$this->eventEmitter = new FlareEventEmitter($this);

		$this->eventListener = new FlareEventListener($this);

		$this->profileManager = new ProfileManager($this);
	}

	public function start(): void {
		if (!$this->started) {
			$this->started = true;

			$this->plugin->getServer()->getPluginManager()->registerEvents($this->eventListener, $this->plugin);
		}
	}

	public function getPlugin(): PluginBase {
		return $this->plugin;
	}

	/**
	 * Get the value of eventEmitter
	 */
	public function getEventEmitter(): FlareEventEmitter {
		return $this->eventEmitter;
	}

	public function getProfileManager(): ProfileManager {
		return $this->started ? $this->profileManager : throw new \Exception("must not be called before started");
	}
}
