<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\command\DebugCommand;
use NeiroNetwork\Flare\command\ForceDefaultCommand;
use NeiroNetwork\Flare\command\GiveModerationItemCommand;
use NeiroNetwork\Flare\command\ParameterCommand;
use NeiroNetwork\Flare\command\ReloadCommand;
use NeiroNetwork\Flare\command\SettingsCommand;
use NeiroNetwork\Flare\moderation\ModerationItemListener;
use NeiroNetwork\Flare\network\NACKHandler;
use NeiroNetwork\Flare\support\MoveDelaySupport;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

	private static ?self $instance = null;

	protected Flare $flare;

	public static function getInstance() : ?self{
		return self::$instance;
	}

	/**
	 * Get the value of flare
	 *
	 * @return Flare
	 */
	public function getMainFlare() : Flare{
		return $this->flare;
	}

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		MoveDelaySupport::init(2, true);

		$this->flare = new Flare($this);

		// task or sleeper
		$handlerEntry = $this->getServer()->getTickSleeper()->addNotifier(function() use (&$handlerEntry) : void{
			$this->flare->start();

			$this->getServer()->getTickSleeper()->removeNotifier($handlerEntry->getNotifierId());
		});

		$handlerEntry->createNotifier()->wakeupSleeper();

		$map = $this->getServer()->getCommandMap();

		$commandPrefix = ";";

		$map->registerAll("flare", $list = [
			new ReloadCommand($commandPrefix . "reload", "フレアの全ての設定を再読み込みします"),
			new ForceDefaultCommand($commandPrefix . "forcedefault", "全てのプレイヤーの設定をデフォルトに強制します"),
			new SettingsCommand($commandPrefix . "settings", "プレイヤーの設定を行います", null, [$commandPrefix . "s"]),
			new GiveModerationItemCommand($commandPrefix . "givemod", "モデレーションアイテムを入手します"),
			new DebugCommand($commandPrefix . "debug", "チェックのデバッグを行います")
		]);

		$vanillaCommands = $this->getServer()->getPluginManager()->getPlugin("VanillaCommands") !== null;

		foreach($list as $command){
			if($command instanceof ParameterCommand && $vanillaCommands){
				$command->registerParameters();
			}
		}

		$this->getServer()->getPluginManager()->registerEvents(new ModerationItemListener, $this);
	}

	protected function onDisable() : void{
		$this->flare->shutdown();
	}
}
