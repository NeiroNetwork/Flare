<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aura;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\support\MoveDelaySupport;
use pocketmine\entity\Entity;
use pocketmine\Server;

class AuraB extends BaseCheck{

	use ClassNameAsCheckIdTrait;


	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{

		$notifier = new ActionNotifier();
		$notifier->notifyAction(function(ActionRecord $record) : void{
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

		if(!$this->profile->isTransactionPairingEnabled()){
			return;
		}

		if($aiming instanceof Entity){
			$currentTick = Server::getInstance()->getTick();
			$runtimeId = $aiming->getId();
			$histories = $this->profile->getSupport()->getActorPositionHistory($runtimeId)->getAll();
			$pos = MoveDelaySupport::getInstance()->predict($histories, $currentTick, $this->profile->getSupport()->isPlayer($runtimeId) ?
				-3 :
				0);
			$bb = $this->profile->getSupport()->getBoundingBox($aiming->getId(), overridePos: $pos);
			if(is_null($bb)){
				return;
			}

			$expandedBB = $bb->expandedCopy(0.6, 0.9, 0.6);
			$inside = $expandedBB->isVectorInside($aimingAt) || $expandedBB->isVectorInside($cd->getLastClientAimingAt());
			$this->broadcastDebugMessage("at: {$aimingAt}, bb: {$bb}, inside: " . ($inside ? 'true' :
					'false'));

			// Utils::debugAxisAlignedBB($bb->expandedCopy(0.3, 0.45, 0.3), $player);
			// Utils::debugPosition($aimingAt, $player);


			if(!$inside){
				if($this->preFail()){
					$this->fail(new ViolationFailReason("Aiming position does not inside of bounding box"));
				}
			}

		}

	}

	public function onUnload() : void{}

	public function isExperimental() : bool{
		return true;
	}
}
