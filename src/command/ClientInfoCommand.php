<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\command;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\form\PlayerClientInfoForm;
use NeiroNetwork\Flare\Main;
use NeiroNetwork\VanillaCommands\parameter\BasicParameters;
use NeiroNetwork\VanillaCommands\parameter\Parameter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class ClientInfoCommand extends Command implements ParameterCommand{


	public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);

		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player){
			return;
		}

		$target = $sender;

		if(count($args) > 0){
			$targetName = $args[0];
			$found = $sender->getServer()->getPlayerExact($targetName);
			if($found !== null){
				$target = $found;
			}
		}

		$profile = Main::getInstance()->getMainFlare()->getProfileManager()->fetch($target->getUniqueId()->toString());

		if(is_null($profile)){
			$sender->sendMessage(Flare::PREFIX . "§cProfile が見つかりませんでした");
			return;
		}

		$form = new PlayerClientInfoForm(Flare::PREFIX . "§aClient Info", $profile);
		$sender->sendForm($form);
	}

	public function registerParameters() : void{
		Parameter::getInstance()->add($this->getName(), [
			BasicParameters::targets("target", optional: true)
		]);
	}
}
