<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

use NeiroNetwork\Flare\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class Supports{

	protected EntityMoveRecorder $recorder;

	protected LagCompensator $lagCompensator;

	public function __construct(){
		$this->recorder = new EntityMoveRecorder(60);
		$this->lagCompensator = new LagCompensator($this->recorder);
	}

	/**
	 * Get the value of recorder
	 *
	 * @return EntityMoveRecorder
	 */
	public function getEntityMoveRecorder() : EntityMoveRecorder{
		return $this->recorder;
	}

	public function fullSupportMove(Player $viewer, int $runtimeId) : ?Vector3{
		$histories = $this->recorder->get($viewer, $runtimeId);
		if(count($histories) <= 0){
			return null;
		}

		$applied = false;

		$before = $histories[max(array_keys($histories))];
		$diff = Vector3::zero();
		$currentTick = Server::getInstance()->getTick();

		foreach([
					$this->lagCompensator->compensate($viewer, Utils::getPing($viewer), $runtimeId),
					MoveDelaySupport::getInstance()->predict($histories, $currentTick)
				] as $result){
			if(is_null($result)){
				continue;
			}

			$applied = true;

			$currentDiff = $result->subtractVector($before);

			$diff = $diff->addVector($currentDiff);
		}

		if(!$applied){
			return null;
		}

		return $before->addVector($diff);
	}

	/**
	 * Get the value of lagCompensator
	 *
	 * @return LagCompensator
	 */
	public function getLagCompensator() : LagCompensator{
		return $this->lagCompensator;
	}
}
