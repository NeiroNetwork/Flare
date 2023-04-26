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
use pocketmine\math\Vector3;

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
		$aimingAt = $cd->getClientAimingAt();
		$aiming = $cd->getClientAiming();
		$player = $this->profile->getPlayer();

		if($aimingAt instanceof Vector3 && $aiming instanceof Entity){
			$pos = $player->getEyePos();

			$reach = $pos->distanceSquared($aimingAt);

			if($reach > 9.0 + 0.015){ // epsilon or math error
				$this->fail(new ViolationFailReason("Aim Reach: {$reach}"));
			}

			$this->broadcastDebugMessage((string) $reach);
		}
	}

	public function onUnload() : void{}

	public function isExperimental() : bool{
		return true;
	}
}
