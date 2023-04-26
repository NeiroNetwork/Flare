<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\badpacket;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class BadPacketD extends BaseCheck implements HandleInputPacketCheck{

	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();

		$md = $this->profile->getMovementData();
		$ki = $this->profile->getKeyInputs();

		$epsilon = 0.000001;
		if(abs($packet->getMoveVecX()) > 1 + $epsilon || abs($packet->getMoveVecZ()) > 1 + $epsilon){
			$this->fail(new ViolationFailReason("Invalid Move Vec"));
		}
	}
}
