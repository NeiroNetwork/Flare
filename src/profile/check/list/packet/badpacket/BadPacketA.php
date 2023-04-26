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

class BadPacketA extends BaseCheck implements HandleInputPacketCheck{

	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();

		$md = $this->profile->getMovementData();

		$pitch = abs($md->getRotation()->pitch);
		if($pitch > 90){
			$this->fail(new ViolationFailReason("Pitch: {$pitch}"));
		}
	}
}
