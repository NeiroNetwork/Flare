<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

use NeiroNetwork\Flare\utils\Utils;
use pocketmine\math\Vector3;

class MoveDelaySupport{

	private static ?self $instance = null;
	protected int $interpolationRange;

	private function __construct(
		protected int $tick,
		protected bool $interpolate
	){
		$this->interpolationRange = 4;
	}

	public static function init(int $tick, bool $interpolate) : void{
		if(!is_null(self::$instance)){
			return;
		}
		self::$instance = new self($tick, $interpolate);
	}

	public static function getInstance() : self{
		if(is_null(self::$instance)){
			throw new \RuntimeException("not initialized");
		}

		return self::$instance;
	}

	/**
	 * @param array<int, Vector3> $histories
	 * @param int                 $currentTick
	 * @param int                 $additionalDelayTick
	 *
	 * @return Vector3|null
	 */
	public function predict(array $histories, int $currentTick, int $additionalDelayTick = 0) : ?Vector3{
		$historyCount = count($histories);
		if($historyCount <= $this->tick){
			return null;
		}

		$keys = array_keys($histories);

		$baseTick = $currentTick - $this->tick - $additionalDelayTick;

		$baseResult = Utils::findAscending($keys, $baseTick);

		if(is_null($baseResult)){
			return null;
		}

		$base = $histories[$baseResult];

		if(!$this->isInterpolationEnabled()){
			return $base;
		}

		// 補完ではない
		// averaging?

		$b = $baseTick - 1;

		$v = Vector3::zero();
		$count = 0;

		for($i = $b - $this->interpolationRange; $i < $b + $this->interpolationRange; $i++){
			if(!isset($histories[$i])){
				continue;
			}

			$count++;

			$v = $v->addVector($histories[$i]);
		}

		if($count <= 0){
			return $base;
		}

		return $v->divide($count);
	}

	public function isInterpolationEnabled() : bool{
		return $this->interpolate;
	}

	public function getTick() : int{
		return $this->tick;
	}
}
