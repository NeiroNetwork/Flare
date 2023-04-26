<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class InvalidA extends BaseCheck implements HandleInputPacketCheck{

	use HandleInputPacketCheckTrait;
	use ClassNameAsCheckIdTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$ki = $this->profile->getKeyInputs();

		if($packet->getMoveVecZ() <= 0 && $ki->getSprintRecord()->getLength() > 4){
			if(
				$md->getTeleportRecord()->getTickSinceAction() >= 6 &&
				$md->getFlyRecord()->getTickSinceAction() >= 20 &&
				$ki->getSwimRecord()->getTickSinceAction() >= 20 &&
				$md->getImmobileRecord()->getTickSinceAction() >= 10
			){
				$this->fail(new ViolationFailReason(""));
			}
		}
	}
}
