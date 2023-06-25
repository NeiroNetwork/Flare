<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;

class ReachC extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->registerEventHandler($this->handleAttack(...));
	}

	public function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();
		$player = $event->getPlayer();
		$cd = $this->profile->getCombatData();

		if(is_null($cd->getClientAiming())){
			return;
		}

		if($cd->getClientAiming()->getId() !== $entity->getId()){
			return;
		}

		if($cd->getAimRecord()->getLength() <= 2){
			return;
		}

		$clickedPosition = $cd->getClientAimingAt();

		$eyePos = $event->getPlayerPosition()->subtractVector($this->profile->getMovementData()->getRealDelta());

		$reach = $eyePos->distanceSquared($cd->getClientAimingAt());
		$lastReach = $eyePos->distanceSquared($cd->getLastClientAimingAt());

		$finalReach = min($reach, $lastReach);

		if($finalReach > 9.0 + 0.015){
			$this->fail(new ViolationFailReason("Attack Reach: {$finalReach}"));
		}

		$this->broadcastDebugMessage("final: {$finalReach}, curr: {$reach}, last: {$lastReach}");
	}

	public function isExperimental() : bool{
		return true;
	}
}
