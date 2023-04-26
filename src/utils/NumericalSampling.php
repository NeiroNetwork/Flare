<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use Countable;

class NumericalSampling implements Countable{

	/**
	 * @var int
	 */
	public int $max;
	/**
	 * @var float[]
	 */
	protected array $list;

	public function __construct(int $max = -1){
		$this->list = [];
		$this->max = $max;
	}

	public static function fromArray(array $ar, int $max = -1){
		$s = new static($max);
		foreach($ar as $v){
			$s->add($v);
		}
		return $s;
	}

	public function add(float $sample){
		array_unshift($this->list, $sample);
		if($this->max > 0){
			if(count($this->list) > $this->max){
				array_pop($this->list);
			}
		}
	}

	public function count() : int{
		return count($this->list);
	}

	/**
	 * @return float[]
	 */
	public function getAll() : array{
		return $this->list;
	}

	public function clear() : void{
		$this->list = [];
	}

	public function isMax() : bool{
		return count($this->list) >= $this->max;
	}

	public function getFirst() : float{
		return $this->list[array_key_first($this->list)];
	}

	public function getLast() : float{
		return $this->list[array_key_last($this->list)];
	}
}
