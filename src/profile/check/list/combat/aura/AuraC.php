<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aura;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\utils\NumericalSampling;
use NeiroNetwork\Flare\utils\Statistics;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Server;

class AuraC extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	protected ?Vector3 $lastPosition;
	protected int $lastAimingEntityId;

	protected NumericalSampling $sampling;


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
		$this->lastPosition = null;
		$this->lastAimingEntityId = -1;
		$this->sampling = new NumericalSampling(30);
	}

	public function handleTriggerAim() : void{
		$this->reward();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();
		$aiming = $cd->getClientAiming();
		$player = $this->profile->getPlayer();

		if($aiming instanceof Entity){
			$currentTick = Server::getInstance()->getTick();
			if($this->lastAimingEntityId !== $aiming->getId()){
				$this->lastPosition = null;
			}

			$position = $md->getEyePosition()->subtractVector($this->profile->getMovementData()->getRealDelta());
			$entityPosition = $this->profile->getSupport()->getMoveDelayPredictedPosition($aiming->getId());

			if(!is_null($this->lastPosition) && !is_null($entityPosition)){
				$diff = $position->subtractVector($this->lastPosition);
				$aimDiff = $cd->getClientAimingAt()->subtractVector($cd->getLastClientAimingAt());
				$entityDiff = $entityPosition->subtractVector($cd->getClientAimingAt());

				$this->broadcastDebugMessage("delta: {$diff->distance($aimDiff)}, entityDelta: {$entityDiff->length()}");
			}

			if($this->sampling->isMax()){
				$deviation = Statistics::standardDeviation($this->sampling->getAll());

				$this->broadcastDebugMessage("deviation: {$deviation}");
			}

			$this->lastPosition = $position;
			$this->lastAimingEntityId = $aiming->getId();
		}
	}

	public function onUnload() : void{}

	public function isExperimental() : bool{
		return true;
	}
}
