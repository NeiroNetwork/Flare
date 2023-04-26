<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\support;

use NeiroNetwork\Flare\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;

class LagCompensator{

	public function __construct(
		protected EntityMoveRecorder $recorder
	){}

	public function compensate(Player $viewer, float $ping, int $runtimeId) : ?Vector3{
		$histories = $this->recorder->get($viewer, $runtimeId);
		$currentTick = Server::getInstance()->getTick();
		$tick = (int) floor($ping / 50);

		$regs = Utils::findArrayRange(array_keys($histories), $currentTick - $tick, 1);
		if(count($regs) <= 0){
			return null;
		}

		$results = array_map(function($v) use ($histories){
			return $histories[$v];
		}, $regs);

		$sum = Vector3::sum(...$results);

		return $sum->divide(count($results));
	}
}
