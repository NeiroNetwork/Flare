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
		$this->interpolationRange = 2;
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
	 *
	 * @return Vector3|null
	 */
	public function predict(array $histories, int $currentTick) : ?Vector3{
		$historyCount = count($histories);
		if($historyCount <= $this->tick){
			return null;
		}

		$keys = array_keys($histories);

		$baseTick = $currentTick - $this->tick;

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

		$regs = Utils::findArrayRange($keys, $baseTick - 1, $this->interpolationRange);
		$results = array_map(function($v) use ($histories){
			return $histories[$v];
		}, $regs);

		$resultCount = count($results);
		if($resultCount <= 0){
			return $base;
		}

		$sum = Vector3::sum(...$results);

		return $sum->divide($resultCount);
	}

	public function isInterpolationEnabled() : bool{
		return $this->interpolate;
	}

	public function getTick() : int{
		return $this->tick;
	}
}
