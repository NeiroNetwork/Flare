<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aura;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;

class AuraA extends BaseCheck{

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
		$this->reward();

		if(is_null($cd->getLastHitEntity())){
			return;
		}

		if($cd->getAttackRecord()->getLast()->getTickSinceAction() > 15){
			return;
		}


		$aiming = $cd->getClientAiming() !== null;
		$correct = ($cd->getClientAiming() === $event->getEntity()) || ($cd->getClientAiming() === $cd->getLastHitEntity());

		$verbose = "aiming: " . ($aiming ? "true" :
				"false") . " correct: " . ($correct ? "true" : "false");
		$this->broadcastDebugMessage($verbose);

		if(!$aiming || !$correct){
			if($this->preFail()){
				$this->fail(new ViolationFailReason($verbose));
			}
		}
	}

	public function isExperimental() : bool{
		return true;
	}
}
