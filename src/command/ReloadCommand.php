<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\command;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class ReloadCommand extends Command {

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		Main::getInstance()?->getMainFlare()->getConfig()->reloadAll();
		$sender->sendMessage(Flare::PREFIX . "全ての設定を再読み込みしました。");
	}
}
