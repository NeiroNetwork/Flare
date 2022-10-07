<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\event\player;

use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerAttackEvent extends PlayerEvent {

	/**
	 * @var Entity
	 */
	protected Entity $entity;

	public function __construct(Player $player, Entity $entity) {
		$this->player = $player;
		$this->entity = $entity;
	}

	/**
	 * @return Entity
	 */
	public function getEntity(): Entity {
		return $this->entity;
	}
}
