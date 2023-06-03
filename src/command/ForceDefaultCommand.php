<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\command;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;

class ForceDefaultCommand extends Command{

	public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);

		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(Main::getInstance() === null){
			$sender->sendMessage(Flare::PREFIX . "Flare プラグインが有効ではありません");
			return;
		}
		$default = Main::getInstance()->getMainFlare()->getConfig()->getProfileDefault()->getAll();

		foreach(Main::getInstance()->getMainFlare()->getConfig()->getPlayerConfigStore()->getAll() as $config){
			$config->setAll($default);
		}

		$sender->sendMessage(Flare::PREFIX . "§a全てのプレイヤーの設定をデフォルトに強制しました。 §7(保存はされていません)");
	}
}
