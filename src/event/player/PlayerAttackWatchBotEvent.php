<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\event\player;

use NeiroNetwork\Flare\player\FakePlayer;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerAttackWatchBotEvent extends PlayerEvent{

	/**
	 * @var FakePlayer
	 */
	protected FakePlayer $fakePlayer;

	public function __construct(Player $player, FakePlayer $fakePlayer){
		$this->player = $player;
		$this->fakePlayer = $fakePlayer;
	}

	/**
	 * @return FakePlayer
	 */
	public function getFakePlayer() : FakePlayer{
		return $this->fakePlayer;
	}
}
