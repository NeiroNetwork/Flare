<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\Flare;
use pocketmine\player\Player;

class ProfileManager {

	/**
	 * @var Profile[]
	 */
	protected array $list;

	public function __construct(protected Flare $flare) {
		$this->list = [];
	}

	public function start(Player $player): void {
		$uuid = $player->getUniqueId()->toString();
		$this->list[$uuid] = new Profile($this->flare, $player);
		$this->list[$uuid]->start();
	}

	public function remove(string $uuid) {
		unset($this->list[$uuid]);
	}
}
