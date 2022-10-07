<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\player;

use Lyrica0954\PeekAntiCheat\utils\VectorUtil;
use pocketmine\scheduler\Task;

class WatchBotTask extends Task {

	/**
	 * @var (array{0: WatchBot, 1: int, 2: int})[]
	 */
	private array $bots;

	private array $sq;

	public function addBot(WatchBot $bot, int $type, int $tick) {
		$this->bots[] = [$bot, $type, $tick];
		$bot->spawn();
	}

	public function __construct() {
		$this->bots = [];
		$this->sq = [];
	}

	public function onRun(): void {
		foreach ($this->bots as $index => $p) {
			$bot = $p[0];
			$type = $p[1];
			$tick = $p[2];
			$this->bots[$index][2]--;
			if ($this->bots[$index][2] <= 0 || !$bot->getPlayer()->isOnline()) {
				$bot->despawn();
				$bot->destroyFakePlayer();
				unset($this->bots[$index]);
				continue;
			}

			$this->moveAround($bot);
		}
	}

	private function moveAround(WatchBot $bot) {
		$hash = spl_object_hash($bot);
		if (isset($this->sq[$hash])) {
			$this->sq[$hash] += 0.5;
		} else {
			$this->sq[$hash] = 0;
		}

		$pos = $bot->getPlayer()->getPosition()->asVector3();
		$pos->y += 2.8;
		$pos->z += sin($this->sq[$hash]) * 1.7;
		$pos->x += cos($this->sq[$hash]) * 1.7;
		$bot->move($pos, 0, 0, 0);
	}
}
