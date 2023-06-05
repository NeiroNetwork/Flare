<?php

namespace NeiroNetwork\Flare\profile\check\list\packet\interact;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\event\player\PlayerInteractEvent;

class InteractA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->registerEventHandler($this->handle(...));
	}

	public function handle(PlayerInteractEvent $event) : void{
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$relatedTouchVector = $event->getTouchVector();
		$touchVector = $event->getBlock()->getPosition()->addVector($relatedTouchVector);

		$this->broadcastDebugMessage($touchVector);

		$distance = $touchVector->distanceSquared($md->getEyePosition());
		if($distance > 49){ // 7
			$this->fail(new ViolationFailReason("dist(sq): {$distance}"));
		}
	}

}
