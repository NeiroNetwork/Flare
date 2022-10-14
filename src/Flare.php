<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use Closure;
use Exception;
use NeiroNetwork\Flare\player\WatchBotTask;
use NeiroNetwork\Flare\profile\ConsoleProfile;
use NeiroNetwork\Flare\profile\ProfileManager;
use NeiroNetwork\Flare\reporter\Reporter;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\EventPriority;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLogger;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class Flare {

	protected PluginBase $plugin;

	protected FlareEventEmitter $eventEmitter;

	protected FlareEventListener $eventListener;

	protected ProfileManager $profileManager;

	protected ConsoleProfile $consoleProfile;

	protected Reporter $reporter;

	protected WatchBotTask $watchBotTask;

	protected bool $started;

	public function __construct(PluginBase $plugin) {
		if (!$plugin->isEnabled()) {
			throw new \Exception("plugin not enabled");
		}

		FlarePermissionNames::init();

		$this->started = false;

		$this->plugin = $plugin;

		$this->eventEmitter = new FlareEventEmitter($this);

		$this->eventListener = new FlareEventListener($this);

		$this->profileManager = new ProfileManager($this);

		$this->watchBotTask = new WatchBotTask();
	}

	public function start(): void {
		if (!$this->started) {
			$this->started = true;

			$this->plugin->getServer()->getPluginManager()->registerEvents($this->eventListener, $this->plugin);
			$this->plugin->getScheduler()->scheduleRepeatingTask($this->watchBotTask, 1);
			$console = null;
			foreach ($this->plugin->getServer()->getBroadcastChannelSubscribers(Server::BROADCAST_CHANNEL_ADMINISTRATIVE) as $subscriber) {
				if ($subscriber instanceof ConsoleCommandSender) {
					$console = $subscriber;
					break;
				}
			}
			assert($console instanceof ConsoleCommandSender, new Exception("ConsoleCommandSender not subscribed in ADMINISTRATIVE"));

			$this->consoleProfile = new ConsoleProfile($this, $console);

			$this->reporter = new Reporter($this->plugin, $console);
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
		return $this->started ? $this->profileManager : Utils::mustStartedException();
	}

	public function getConsoleProfile(): ConsoleProfile {
		return $this->started ? $this->consoleProfile : Utils::mustStartedException();
	}

	/**
	 * Get the value of reporter
	 *
	 * @return Reporter
	 */
	public function getReporter(): Reporter {
		return $this->started ? $this->reporter : Utils::mustStartedException();
	}

	/**
	 * Get the value of watchBotTask
	 *
	 * @return WatchBotTask
	 */
	public function getWatchBotTask(): WatchBotTask {
		return $this->started ? $this->watchBotTask : Utils::mustStartedException();
	}
}
