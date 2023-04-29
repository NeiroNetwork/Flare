<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\step;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class StepA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();

		$delta = $md->getDelta(); // client motion
		$realDelta = $md->getRealDelta(); // position delta

		if(
			$md->getTeleportRecord()->getTickSinceAction() >= 10 &&
			count($sd->getComplexBlocks()) <= 0 &&
			$md->getClientOnGroundRecord()->getLength() > 5
		){

			if(abs($realDelta->y) > 0.601){
				$this->fail(new ViolationFailReason("Pos Delta: {$realDelta->y}, Motion: {$delta->y}"));
			}
		}
	}
}
