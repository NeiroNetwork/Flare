<?php

namespace NeiroNetwork\Flare\utils;

/**
 * @template TKey of (int|string)
 * @template TValue of mixed
 */
class Map{

	/**
	 * @var array<TKey, TValue> $map
	 */
	protected array $map;

	public function __construct(array $map = []){
		$this->map = $map;
	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function getAll() : array{
		return $this->map;
	}

	public function clear() : void{
		$this->map = [];
	}

	public function putIfAbsent(mixed $key, mixed $value) : void{
		if(is_null($this->get($key))){
			$this->put($key, $value);
		}
	}

	/**
	 * @param TKey $key
	 *
	 * @return TValue|null
	 */
	public function get(mixed $key) : mixed{
		return $this->map[$key] ?? null;
	}

	/**
	 * @param TKey   $key
	 * @param TValue $value
	 *
	 * @return void
	 */
	public function put(mixed $key, mixed $value) : void{
		$this->map[$key] = $value;
	}

	/**
	 * @param TKey $key
	 *
	 * @return void
	 */
	public function remove(mixed $key) : void{
		if(isset($this->map[$key])){
			unset($this->map[$key]);
		}
	}
}
