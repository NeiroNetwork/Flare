<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\jump;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;

class JumpB extends BaseCheck{

	use ClassNameAsCheckIdTrait;

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function onLoad() : void{
		$notifier = new ActionNotifier();
		$notifier->notifyAction(function(ActionRecord $record) : void{
			$player = $this->profile->getPlayer();
			$md = $this->profile->getMovementData();
			$sd = $this->profile->getSurroundData();
			$deltaY = $this->profile->getMovementData()->getClientPredictedDelta()->y;
			$expectY = $md->getJumpVelocity();

			if(
				$sd->getCobwebRecord()->getTickSinceAction() >= 5 &&
				count($sd->getOverheadBlocks()) <= 0 &&
				count($sd->getAbleToStepBlocks()) <= 0
			){
				if(abs($deltaY - $expectY) > 0.001){
					if($this->preFail()){
						$this->fail(new ViolationFailReason("dy: {$deltaY}, expect: {$expectY}"));
					}
				}
			}
		});
		$this->profile->getKeyInputs()->getStartJumpRecord()->notify($notifier);

	}

}
