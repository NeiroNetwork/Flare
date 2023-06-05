<?php

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class InvalidF extends BaseCheck{

	use HandleEventCheckTrait;
	use ClassNameAsCheckIdTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$ki = $this->profile->getKeyInputs();
		$forward = $ki->w() && !$ki->s();
		$right = $ki->d() && !$ki->a();

		if($forward && abs($packet->getMoveVecZ()) <= 0.0){
			$this->fail(new ViolationFailReason("Cheating MoveVecZ"));
		}elseif(!$forward && abs($packet->getMoveVecZ()) > 0.0){
			$this->fail(new ViolationFailReason("Cheating key inputs"));
		}
	}
}
