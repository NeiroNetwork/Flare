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

class AutoClickerC extends BaseCheck{

	use ClassNameAsCheckIdTrait;

	protected float $pvlMax = (100 * 2);

	protected NumericalSampling $clickDelta;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function onLoad() : void{
		$this->clickDelta = new NumericalSampling(30);

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

		$this->broadcastDebugMessage("delta: {$delta}, inputs: {$md->getInputCount()}");

		if($this->clickDelta->isMax()){
			$samples = $this->clickDelta->getAll();
			$outliersResult = Statistics::outliers($samples);
			$skewness = Statistics::skewness($samples);
			$kurtosis = Statistics::kurtosis($samples);
			$outliers = count($outliersResult->high) + count($outliersResult->low);

			if(
				$skewness < 0.75 &&
				$kurtosis < 0.0 &&
				$outliers < 4
			){
				if($this->preFail()){
					$this->fail(new ViolationFailReason(""));
				}
			}else{
				$this->resetPreVL();
			}

			$this->broadcastDebugMessage("Outliers: {$outliers}, Skewness: {$skewness}, Kurtosis: {$kurtosis}");
			$this->clickDelta->clear();
		}
	}
}
