<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\moderation;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;

class ModerationItemListener implements Listener{

	public function __construct(){}

	public function onInteract(PlayerInteractEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();

		$moderationItem = ModerationItemFactory::getInstance()->search($item);

		if($moderationItem !== null){
			$on = $moderationItem->getOnInteract();
			$on($event);
		}
	}

	public function onClickAir(PlayerItemUseEvent $event) : void{
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		$directionVector = $player->getDirectionVector();

		$moderationItem = ModerationItemFactory::getInstance()->search($item);

		if($moderationItem !== null){
			$on = $moderationItem->getOnClickAir();
			$on($event);
		}
	}
}
