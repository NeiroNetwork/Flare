<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\autoclicker;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\utils\NumericalSampling;
use NeiroNetwork\Flare\utils\Statistics;

class AutoClickerA extends BaseCheck{

	use ClassNameAsCheckIdTrait;

	protected NumericalSampling $clickDelta;
	protected NumericalSampling $deviations;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function onLoad() : void{
		$this->clickDelta = new NumericalSampling(40);
		$this->deviations = new NumericalSampling(30);

		$notifier = new ActionNotifier;
		$notifier->notifyAction(function(ActionRecord $record) : void{
			$this->handle();
		});

		$this->profile->getCombatData()->getClickRecord()->notify($notifier);
	}

	public function handle() : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();

		$delta = $md->getInputCount() - $cd->getLastClickInputTick();
		$this->clickDelta->add($delta);

		if($this->clickDelta->isMax()){
			$deviation = Statistics::standardDeviation($this->clickDelta->getAll());
			$this->deviations->add($deviation);

			$this->preReward();

			if($this->deviations->isMax()){
				$deviationAvg = Statistics::average($this->deviations->getAll());

				if($deviationAvg <= 0){
					if($this->preFail()){
						$this->fail(new ViolationFailReason("Deviation Avg: {$deviationAvg}"));
					}
				}

				$this->broadcastDebugMessage("current: {$deviation} (avg: {$deviationAvg})");
			}
		}
	}
}
