<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

use NeiroNetwork\Flare\utils\Utils;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class MoveDelaySupport {

	protected int $interpolationRange;

	public static function default(EntityMoveRecorder $recorder): self {
		return new self($recorder, 4, true);
	}

	public function __construct(
		protected EntityMoveRecorder $recorder,
		protected int $tick,
		protected bool $interpolate
	) {

		if ($recorder->getSize() < $tick) {
			throw new \Exception("EntityMoveRecorder size \"{$recorder->getSize()}\" must be bigger than tick \"$tick\"");
		}

		$this->interpolationRange = 2;
	}

	public function isInterpolationEnabled(): bool {
		return $this->interpolate;
	}

	public function getTick(): int {
		return $this->tick;
	}

	/**
	 * @param Player $viewer
	 * @param int $runtimeId
	 * 
	 * @return Vector3|null
	 */
	public function predict(Player $viewer, int $runtimeId): ?Vector3 {
		$currentTick = Server::getInstance()->getTick();
		$histories = $this->recorder->get($viewer, $runtimeId);
		$historyCount = count($histories);
		if ($historyCount <= $this->tick) {
			return null;
		}

		$baseTick = $currentTick - $this->tick;

		$baseResult = Utils::findAscending(array_keys($histories), $baseTick);

		if (is_null($baseResult)) {
			return null;
		}

		$base = $histories[$baseResult];

		if (!$this->isInterpolationEnabled()) {
			return $base;
		}

		// 補完ではない
		// averaging?

		$regs = Utils::findArrayRange(array_keys($histories), $baseTick - 1, $this->interpolationRange);
		$results = array_map(function ($v) use ($histories) {
			return $histories[$v];
		}, $regs);

		if (count($results) <= 0) {
			return $base;
		}

		$sum = Vector3::sum(...$results);

		return $sum->divide(max(1, count($results)));
	}
}
