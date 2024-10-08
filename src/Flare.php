<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\config\FlareConfig;
use NeiroNetwork\Flare\data\report\DataReportManager;
use NeiroNetwork\Flare\player\WatchBotTask;
use NeiroNetwork\Flare\profile\ConsoleProfile;
use NeiroNetwork\Flare\profile\LogStyle;
use NeiroNetwork\Flare\profile\ProfileManager;
use NeiroNetwork\Flare\profile\style\FlareStyle;
use NeiroNetwork\Flare\profile\style\PeekAntiCheatStyle;
use NeiroNetwork\Flare\reporter\Reporter;
use NeiroNetwork\Flare\support\Supports;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\event\HandlerListManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\snooze\SleeperHandlerEntry;
use Symfony\Component\Filesystem\Path;

class Flare{

	public const PREFIX = "§e☄ §r";
	public const DEBUG_PREFIX = "§c☄ §r";

	protected PluginBase $plugin;

	protected FlareEventEmitter $eventEmitter;

	protected FlareEventListener $eventListener;

	protected ProfileManager $profileManager;

	protected ConsoleProfile $consoleProfile;

	protected Reporter $reporter;

	protected WatchBotTask $watchBotTask;

	protected DataReportManager $dataReportManager;

	protected FlareConfig $config;

	protected TickProcessor $tickProcessor;

	protected TaskScheduler $scheduler;

	protected ?TaskHandler $schedulerHeartbeater;

	protected TransactionPairingHost $transactionPairingHost;
	protected SleeperHandlerEntry $heartbeatCheckNotifier;

	protected Supports $supports;

	protected bool $started;

	public function __construct(PluginBase $plugin){
		if(!$plugin->isEnabled()){
			throw new \RuntimeException("plugin not enabled");
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

		$this->dataReportManager = new DataReportManager(Path::join($plugin->getDataFolder(), "data_report"));

		$this->scheduler = new TaskScheduler("Flare");

		$this->tickProcessor = new TickProcessor;

		$this->config = new FlareConfig($plugin->getDataFolder());

		$this->transactionPairingHost = new TransactionPairingHost($this->profileManager);

		$this->schedulerHeartbeater = null;
	}

	public function start() : void{
		if(!$this->started){
			$this->started = true;

			$this->plugin->getServer()->getPluginManager()->registerEvents($this->eventListener, $this->plugin);
			$this->plugin->getScheduler()->scheduleRepeatingTask($this->watchBotTask, 1);

			$this->consoleProfile = new ConsoleProfile($this, $this->plugin->getServer()->getLogger(), $this->plugin->getServer()->getLanguage());

			$this->reporter = new Reporter($this->plugin);
			$this->reporter->autoSubscribe($this->consoleProfile->getCommandSender()); // todo: 

			$this->schedulerHeartbeater = $this->plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(){
				$this->scheduler->mainThreadHeartbeat($this->plugin->getServer()->getTick());
				// PluginManager::tickSchedulers
			}), 1);

			$this->scheduler->scheduleRepeatingTask(new ClosureTask(function(){
				$this->tickProcessor->execute();
			}), 1);

			// transaction pairing


			$this->scheduler->scheduleRepeatingTask(new ClosureTask(function(){
				$this->transactionPairingHost->onStartOfTick($this->plugin->getServer()->getTick());
			}), 1);

			$handlerEntry = $this->plugin->getServer()->getTickSleeper()->addNotifier(function() use (&$handlerEntry) : void{
				//$this->transactionPairingHost->onEndOfTick($this->plugin->getServer()->getTick());
			});

			$this->heartbeatCheckNotifier = $handlerEntry;
		}
	}

	/**
	 * Get the value of scheduler
	 *
	 * @return TaskScheduler
	 */
	public function getScheduler() : TaskScheduler{
		return $this->scheduler;
	}

	public function shutdown(bool $saveConfig = true) : void{
		if($this->started){

			HandlerListManager::global()->unregisterAll($this->eventListener, $this->plugin);

			$this->watchBotTask->getHandler()->cancel();

			// todo: broadcast channel unscribe
			$this->config->close($saveConfig);

			if($saveConfig){
				$this->dataReportManager->save();
			}

			$this->started = false;

			$this->scheduler->shutdown();

			assert($this->schedulerHeartbeater instanceof TaskHandler);

			$this->schedulerHeartbeater->cancel();

			$this->plugin->getServer()->getTickSleeper()->removeNotifier($this->heartbeatCheckNotifier->getNotifierId());
		}
	}

	public function getPlugin() : PluginBase{
		return $this->plugin;
	}

	/**
	 * @return TransactionPairingHost
	 */
	public function getTransactionPairingHost() : TransactionPairingHost{
		return $this->transactionPairingHost;
	}

	/**
	 * Get the value of eventEmitter
	 */
	public function getEventEmitter() : FlareEventEmitter{
		return $this->eventEmitter;
	}

	public function getProfileManager() : ProfileManager{
		return $this->started ? $this->profileManager : throw Utils::mustStartedException();
	}

	public function getConsoleProfile() : ConsoleProfile{
		return $this->started ? $this->consoleProfile : throw Utils::mustStartedException();
	}

	/**
	 * Get the value of reporter
	 *
	 * @return Reporter
	 */
	public function getReporter() : Reporter{
		return $this->started ? $this->reporter : throw Utils::mustStartedException();
	}

	/**
	 * Get the value of watchBotTask
	 *
	 * @return WatchBotTask
	 */
	public function getWatchBotTask() : WatchBotTask{
		return $this->started ? $this->watchBotTask : throw Utils::mustStartedException();
	}

	/**
	 * Get the value of config
	 *
	 * @return FlareConfig
	 */
	public function getConfig() : FlareConfig{
		return $this->config;
	}

	/**
	 * Get the value of dataReportManager
	 *
	 * @return DataReportManager
	 */
	public function getDataReportManager() : DataReportManager{
		return $this->dataReportManager;
	}

	/**
	 * Get the value of tickProcessor
	 *
	 * @return TickProcessor
	 */
	public function getTickProcessor() : TickProcessor{
		return $this->tickProcessor;
	}

}
