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

	protected float $pvlMax = (100 * 16);

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

		if($cd->getAttackRecord()->getLastOrSelf()->getTickSinceAction() > 15){
			return;
		}

		if($this->profile->getMovementData()->getRotationDelta()->yaw > 6 || $this->profile->getMovementData()->getRotationDelta()->headYaw > 6){
			return;
		}

		if($this->profile->getMovementData()->getRotationDelta()->pitch > 6){
			return;
		}

		$aiming = $cd->getClientAiming() !== null || $cd->getLastClientAiming() !== null;
		$correct = ($cd->getClientAiming() === $event->getEntity() || $cd->getLastClientAiming() === $event->getEntity()) || ($cd->getClientAiming() === $cd->getLastHitEntity());

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
