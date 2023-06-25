<?php

namespace NeiroNetwork\Flare\utils;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey of (int|string)
 * @template TValue of mixed
 *
 * @implements  IteratorAggregate<TKey, TValue>
 */
class Map implements Countable, IteratorAggregate{

	/**
	 * @var array<TKey, TValue> $map
	 */
	protected array $map;

	public function __construct(array $map = []){
		$this->map = $map;
	}

	/**
	 * @return Traversable<TKey, TValue>
	 */
	public function getIterator() : Traversable{
		return new ArrayIterator($this->map);
	}

	public function count() : int{
		return count($this->map);
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

	public function exists(mixed $key) : bool{
		return isset($this->map[$key]);
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
