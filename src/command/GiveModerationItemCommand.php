<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\command;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\moderation\ModerationItem;
use NeiroNetwork\Flare\moderation\ModerationItemFactory;
use NeiroNetwork\VanillaCommands\parameter\BasicParameters;
use NeiroNetwork\VanillaCommands\parameter\Parameter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class GiveModerationItemCommand extends Command implements ParameterCommand{

	public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);

		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	public function registerParameters() : void{
		Parameter::getInstance()->add($this->getName(), [
			BasicParameters::enum(
				"name",
				"moderation item",
				array_map(
					function(ModerationItem $moderationItem){
						return $moderationItem->getId();
					},
					ModerationItemFactory::getInstance()->getAll()
				)
			)
		]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$sender instanceof Player){
			return;
		}

		$itemName = $args[0] ?? null;

		if($itemName !== null){
			$item = ModerationItemFactory::getInstance()->get($itemName);
			if($item !== null){
				$sender->getInventory()->addItem($item->getItem());
				$sender->sendMessage(Flare::PREFIX . "アイテムを取得しました");
			}else{
				$sender->sendMessage(Flare::PREFIX . "アイテムが見つかりませんでした");
			}
		}
	}
}
