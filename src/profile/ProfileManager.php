<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\Flare;
use pocketmine\player\Player;

class ProfileManager {

	/**
	 * @var PlayerProfile[]
	 */
	protected array $list;

	public function __construct(protected Flare $flare) {
		$this->list = [];
	}

	/**
	 * @return PlayerProfile[]
	 */
	public function getAll(): array {
		return $this->list;
	}

	public function start(Player $player): void {
		$uuid = $player->getUniqueId()->toString();
		$this->list[$uuid] = new PlayerProfile($this->flare, $player);
		$this->list[$uuid]->start();
	}

	public function remove(string $uuid) {
		$this->list[$uuid]?->close();
		unset($this->list[$uuid]);
	}

	public function fetch(string $uuid): ?PlayerProfile {
		return $this->list[$uuid] ?? null;
	}
}
