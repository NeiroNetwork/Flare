<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use Closure;
use Exception;
use NeiroNetwork\Flare\config\FlareConfig;
use NeiroNetwork\Flare\player\WatchBotTask;
use NeiroNetwork\Flare\profile\ConsoleProfile;
use NeiroNetwork\Flare\profile\LogStyle;
use NeiroNetwork\Flare\profile\ProfileManager;
use NeiroNetwork\Flare\profile\style\FlareStyle;
use NeiroNetwork\Flare\profile\style\PeekAntiCheatStyle;
use NeiroNetwork\Flare\reporter\Reporter;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerListManager;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLogger;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class Flare {

	public const PREFIX = "§e★ §r";

	protected PluginBase $plugin;

	protected FlareEventEmitter $eventEmitter;

	protected FlareEventListener $eventListener;

	protected ProfileManager $profileManager;

	protected ConsoleProfile $consoleProfile;

	protected Reporter $reporter;

	protected WatchBotTask $watchBotTask;

	protected FlareConfig $config;

	protected bool $started;

	public function __construct(PluginBase $plugin) {
		if (!$plugin->isEnabled()) {
			throw new \Exception("plugin not enabled");
		}

		FlarePermissionNames::init();

		LogStyle::register(new FlareStyle);
		LogStyle::register(new PeekAntiCheatStyle);

		$this->started = false;

		$this->plugin = $plugin;

		$this->eventEmitter = new FlareEventEmitter($this);

		$this->eventListener = new FlareEventListener($this);

		$this->profileManager = new ProfileManager($this);

		$this->watchBotTask = new WatchBotTask();

		$this->config = new FlareConfig($plugin->getDataFolder());
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

	public function shutdown(bool $saveConfig = true): void {
		if ($this->started) {

			HandlerListManager::global()->unregisterAll($this->eventListener, $this->plugin);

			$this->watchBotTask->getHandler()->cancel();

			// todo: broadcast channel unscribe

			$this->config->close($saveConfig);

			$this->started = false;
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

	/**
	 * Get the value of config
	 *
	 * @return FlareConfig
	 */
	public function getConfig(): FlareConfig {
		return $this->config;
	}
}
