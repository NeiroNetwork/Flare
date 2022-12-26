<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

use NeiroNetwork\Flare\utils\PlayerUtil;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Supports {

	protected EntityMoveRecorder $recorder;

	protected MoveDelaySupport $moveDelay;

	protected LagCompensator $lagCompensator;

	public function __construct() {
		$this->recorder = new EntityMoveRecorder(60);
		$this->moveDelay = MoveDelaySupport::default($this->recorder);
		$this->lagCompensator = new LagCompensator($this->recorder);
	}

	/**
	 * Get the value of moveDelay
	 *
	 * @return MoveDelaySupport
	 */
	public function getMoveDelay(): MoveDelaySupport {
		return $this->moveDelay;
	}

	/**
	 * Get the value of recorder
	 *
	 * @return EntityMoveRecorder
	 */
	public function getEntityMoveRecorder(): EntityMoveRecorder {
		return $this->recorder;
	}

	public function fullSupportMove(Player $viewer, int $runtimeId): ?Vector3 {
		$histories = $this->recorder->get($viewer, $runtimeId);
		if (count($histories) <= 0) {
			return null;
		}

		$applied = false;

		$before = $histories[max(array_keys($histories))];
		$diff = Vector3::zero();

		foreach ([
			$this->lagCompensator->compensate($viewer, Utils::getPing($viewer), $runtimeId),
			$this->moveDelay->predict($viewer, $runtimeId)
		] as $result) {
			if (is_null($result)) {
				continue;
			}

			$applied = true;

			$currentDiff = $result->subtractVector($before);

			$diff = $diff->addVector($currentDiff);
		}

		if (!$applied) {
			return null;
		}

		return $before->addVector($diff);
	}

	/**
	 * Get the value of lagCompensator
	 *
	 * @return LagCompensator
	 */
	public function getLagCompensator(): LagCompensator {
		return $this->lagCompensator;
	}
}
