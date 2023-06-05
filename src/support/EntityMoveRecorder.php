<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use SplFixedArray;

class EntityMoveRecorder{

	/**
	 * @var array<string, array<int, array<int, Vector3>>>
	 */
	protected array $histories;

	public function __construct(
		protected int $size
	){
		$this->histories = [];
	}

	public function record(Player $target, Entity $entity) : void{
		$this->add($target, $entity->getId(), $entity->getPosition(), $entity->getWorld()->getServer()->getTick());
	}

	public function add(Player $target, int $runtimeId, Vector3 $pos, int $tick) : void{
		$uuid = $target->getUniqueId()->toString();
		if(!isset($this->histories[$uuid])){
			$this->histories[$uuid] = [];
		}

		if(!isset($this->histories[$uuid][$runtimeId])){
			$this->histories[$uuid][$runtimeId] = [];
		}

		$this->histories[$uuid][$runtimeId][$tick] = $pos->asVector3();

		if(count($this->histories[$uuid][$runtimeId]) > $this->size){
			unset($this->histories[$uuid][$runtimeId][array_key_first($this->histories[$uuid][$runtimeId])]);
		}
	}

	public function getLatest(Player $target, int $runtimeId) : ?Vector3{
		$histories = $this->get($target, $runtimeId);

		if(count($histories) <= 0){
			return null;
		}

		return $histories[max(array_keys($histories))];
	}

	/**
	 * @param int      $runtimeId
	 * @param int|null $size
	 *
	 * @return array<int, Vector3>
	 */
	public function get(Player $target, int $runtimeId, ?int $size = null) : array{
		$uuid = $target->getUniqueId()->toString();
		if($size !== null){
			$farr = SplFixedArray::fromArray($this->histories[$uuid][$runtimeId] ?? []);
			$farr->setSize($size);

			return $farr->toArray();
		}

		return $this->histories[$uuid][$runtimeId] ?? [];
	}

	/**
	 * Get the value of size
	 */
	public function getSize() : int{
		return $this->size;
	}
}
