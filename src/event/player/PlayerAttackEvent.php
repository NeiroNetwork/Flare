<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\event\player;

use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class PlayerAttackEvent extends PlayerEvent {

	/**
	 * @var Entity
	 */
	protected Entity $entity;

	protected Vector3 $playerPosition;
	protected Vector3 $clickedPosition;

	public function __construct(Player $player, Entity $entity, Vector3 $playerPosition, Vector3 $clickedPosition) {
		$this->player = $player;
		$this->entity = $entity;
		$this->playerPosition = $playerPosition;
		$this->clickedPosition = $clickedPosition;
	}

	/**
	 * @return Entity
	 */
	public function getEntity(): Entity {
		return $this->entity;
	}

	/**
	 * Get the value of playerPosition
	 *
	 * @return Vector3
	 */
	public function getPlayerPosition(): Vector3 {
		return $this->playerPosition;
	}

	/**
	 * Get the value of clickedPosition
	 *
	 * @return Vector3
	 */
	public function getClickedPosition(): Vector3 {
		return $this->clickedPosition;
	}
}
