<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

class Supports {

	protected EntityMoveRecorder $recorder;

	protected MoveDelaySupport $moveDelay;

	// protected LagCompensator $lagCompensator;

	public function __construct() {
		$this->recorder = new EntityMoveRecorder(40);
		$this->moveDelay = MoveDelaySupport::default($this->recorder);
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
}
