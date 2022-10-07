<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\event\player;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerPacketLossEvent extends PlayerEvent {

	public function __construct(Player $player) {
		$this->player = $player;
	}
}
