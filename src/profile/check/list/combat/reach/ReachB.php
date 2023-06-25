<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use pocketmine\entity\Entity;

class ReachB extends BaseCheck{

	use ClassNameAsCheckIdTrait;


	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{

		$notifier = new ActionNotifier();
		$notifier->notifyEnd(function(ActionRecord $record) : void{
			if($this->tryCheck()){
				$this->handleTriggerAim();
			}
		});

		$this->profile->getCombatData()->getTriggerAimRecord()->notify($notifier);
	}

	public function handleTriggerAim() : void{
		$this->reward();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();
		$aimingAt = $cd->getClientAimingAt();
		$aiming = $cd->getClientAiming();
		$player = $this->profile->getPlayer();

		if($aiming instanceof Entity){
			$pos = $md->getEyePosition()->subtractVector($this->profile->getMovementData()->getRealDelta());

			$reach = $pos->distanceSquared($aimingAt);
			$lastReach = $pos->distanceSquared($cd->getLastClientAimingAt());

			$finalReach = min($reach, $lastReach);

			if($finalReach > 9.0 + 0.015){ // bb error + epsilon
				$this->fail(new ViolationFailReason("Aim Reach: {$finalReach}"));
			}

			$this->broadcastDebugMessage("final: {$finalReach}, curr: {$reach}, last: {$lastReach}");
		}
	}

	public function onUnload() : void{}

	public function isExperimental() : bool{
		return true;
	}
}
