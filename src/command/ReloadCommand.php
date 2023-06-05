<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\command;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;

class ReloadCommand extends Command{

	public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);

		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}


	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		Main::getInstance()?->getMainFlare()->getConfig()->reloadAll();
		$sender->sendMessage(Flare::PREFIX . "§a全ての設定を再読み込みしました。");
	}
}
