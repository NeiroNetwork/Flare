<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\event\EventPriority;

class ReachC extends BaseCheck{

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

		if(is_null($cd->getClientAiming())){
			return;
		}

		if($cd->getClientAiming()->getId() !== $entity->getId()){
			return;
		}

		$clickedPosition = $cd->getClientAimingAt();

		$eyePos = $event->getPlayerPosition();

		$reach = $eyePos->distanceSquared($clickedPosition); // ok, its simple.

		if($reach > 9.0 + 0.015){
			$this->fail(new ViolationFailReason("Attack Reach: {$reach}"));
		}

		$this->broadcastDebugMessage((string) $reach);
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
