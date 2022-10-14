<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\player;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

class WatchBot {

	const NAMES = [
		"Spring",
		"Winter",
		"Summer",
		"Autumn",
		"Fire",
		"Water",
		"Air",
		"Earth",
		"Light",
		"Dark",
		"Frost",
		"Flame",
		"Storm",
		"Wind",
		"Rain",
		"Snow",
		"Cloud",
		"Machine",
		"Spirit",
		"Lightning",
		"Darkness",
		"Eater",
		"Fog",
		"Zombie",
		"Ghost",
		"Skeleton",
		"Witch",
		"Wizard",
		"Warrior",
		"Knight",
		"Mage",
		"Priest",
		"Rogue",
		"Hunter",
		"Warlock",
		"Sorcerer",
		"Shaman",
		"Druid",
		"Monk",
		"Paladin",
		"Necromancer",
		"Bard"
	];

	protected ?FakePlayer $fakePlayer;
	protected Player $player;

	public function __construct(?FakePlayer $fakePlayer, Player $player) {
		$this->setFakePlayer($fakePlayer);
		$this->player = $player;
	}

	public static function createFakePlayer(Vector3 $position) {
		$name = self::NAMES[array_rand(self::NAMES)];
		$name .= mt_rand(0, 999);
		return FakePlayer::simple($name, $position);
	}

	public function destroyFakePlayer() {
		if ($this->fakePlayer !== null) {
			if ($this->isSpawned()) {
				$this->fakePlayer->despawnFromAll();
			}
			$this->fakePlayer = null;
		}
	}

	public function setFakePlayer(?FakePlayer $fakePlayer) {
		if ($fakePlayer?->isSpawned()) {
			throw new \Exception("please provide a not spawned fake player");
			return;
		}
		$this->fakePlayer = $fakePlayer;
	}

	public function getFakePlayer(): ?FakePlayer {
		return $this->fakePlayer;
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function isSpawned(): bool {
		return $this->fakePlayer !== null ? $this->fakePlayer->isSpawned() : false;
	}

	public function move(Vector3 $to, float $yaw, float $headYaw, float $pitch): void {
		$this->getFakePlayer()?->sendMoveTo($this->getPlayer(), $to, $yaw, $headYaw, $pitch);
	}

	public function spawn(): void {
		$this->getFakePlayer()?->spawnTo($this->getPlayer());
	}

	public function despawn(): void {
		$this->getFakePlayer()?->despawnFrom($this->getPlayer());
	}
}
