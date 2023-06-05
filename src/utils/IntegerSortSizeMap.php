<?php

namespace NeiroNetwork\Flare\utils;

/**
 * @template TValue of mixed
 * @extends Map<int, TValue>
 */
class IntegerSortSizeMap extends Map{

	const REMOVE_MIN = 0;
	const REMOVE_MAX = 1;

	protected int $removeMethod;
	protected int $size;

	public function __construct(int $size, array $map = [], int $removeMethod = self::REMOVE_MIN){
		parent::__construct($map);
		$this->removeMethod = $removeMethod;
		$this->size = $size;
	}

	/**
	 * @return int
	 */
	public function getSize() : int{
		return $this->size;
	}

	/**
	 * @param int $size
	 */
	public function setSize(int $size) : void{
		$this->size = $size;
	}

	/**
	 * @inheritDoc
	 */
	public function put(mixed $key, mixed $value) : void{
		parent::put($key, $value);

		if(count($this->map) > $this->size){
			$keys = array_keys($this->map);

			$key = null;
			if($this->removeMethod === self::REMOVE_MAX){
				$key = max($keys);
			}elseif($this->removeMethod === self::REMOVE_MIN){
				$key = min($keys);
			}

			if(!is_null($key)){
				$this->remove($key);
			}
		}
	}


}
