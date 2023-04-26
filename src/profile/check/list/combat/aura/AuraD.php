<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aura;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\event\EventPriority;

class AuraD extends BaseCheck{

	use ClassNameAsCheckIdTrait;

	private string $hashb = "";

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->hashb = $this->profile->getFlare()->getEventEmitter()->registerPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			function(PlayerAttackEvent $event) : void{
				assert($event->getPlayer() === $this->profile->getPlayer());
				if($this->tryCheck()){
					$this->handleAttack($event);
				}
			},
			false,
			EventPriority::MONITOR
		);
	}

	public function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();
		$player = $event->getPlayer();
		$cd = $this->profile->getCombatData();

		if($player->getScale() != 1.0){ // tick diff?
			return;
		}

		$clientPosition = $event->getPlayerPosition()->round(4); // server position is fixed to 4
		$serverPosition = $player->getEyePos();

		if(
			$serverPosition->subtractVector($clientPosition)->lengthSquared() > 0.01
		){
			$this->fail(new ViolationFailReason("Invalid Position"));
		}

		$this->broadcastDebugMessage("c: {$clientPosition}, s: {$serverPosition}");
	}

	public function onUnload() : void{
		$this->profile->getFlare()->getEventEmitter()->unregisterPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			$this->hashb,
			EventPriority::MONITOR
		);
	}

	public function isExperimental() : bool{
		return true;
	}
}
