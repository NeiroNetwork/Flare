<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\motion;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MotionD extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();

		$this->broadcastDebugMessage("Real: {$md->getRealDelta()->y}, Pos: {$md->getClientPredictedDelta()->y}");
	}
}
