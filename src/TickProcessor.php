<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\utils\Utils;

class TickProcessor{

	protected float $lastTime;

	protected float $currentTime;

	protected float $deltaTime;

	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $overload;

	public function __construct(){
		$this->lastTime = 0;
		$this->currentTime = 0;
		$this->deltaTime = 0;
		$this->overload = new ActionRecord;
	}

	public function execute() : void{
		$this->lastTime = $this->currentTime;
		$this->currentTime = Utils::getTimeMillis();

		$this->deltaTime = $this->currentTime - $this->lastTime;

		$this->overload->update($this->deltaTime >= 200.0);
	}

	/**
	 * Get the value of lastTime
	 *
	 * @return float
	 */
	public function getLastTime() : float{
		return $this->lastTime;
	}

	/**
	 * Get the value of currentTime
	 *
	 * @return float
	 */
	public function getCurrentTime() : float{
		return $this->currentTime;
	}

	/**
	 * Get the value of deltaTime
	 *
	 * @return float
	 */
	public function getDeltaTime() : float{
		return $this->deltaTime;
	}

	/**
	 * Get the value of overload
	 *
	 * @return ActionRecord
	 */
	public function getOverloadRecord() : ActionRecord{
		return $this->overload;
	}

	public function getTimeSinceLastTick() : float{
		return Utils::getTimeMillis() - $this->currentTime;
	}
}
