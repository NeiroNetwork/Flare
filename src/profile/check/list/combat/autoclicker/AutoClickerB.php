<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\autoclicker;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\utils\Math;
use NeiroNetwork\Flare\utils\NumericalSampling;
use NeiroNetwork\Flare\utils\Statistics;
use NeiroNetwork\Flare\utils\Utils;
use SplFixedArray;

class AutoClickerB extends BaseCheck {
	use ClassNameAsCheckIdTrait;

	protected float $pvlMax = (100 * 2);

	protected NumericalSampling $clickDelta;

	protected float $lastDeviation = 0;
	protected float $lastKurtosis = 0;
	protected float $lastSkewness = 0;

	public function getCheckGroup(): int {
		return CheckGroup::COMBAT;
	}

	public function isExperimental(): bool {
		return true;
	}

	public function onLoad(): void {
		$this->clickDelta = new NumericalSampling(20);

		$notifier = new ActionNotifier;
		$notifier->notifyAction(function (ActionRecord $record): void {
			$this->handle();
		});

		$this->profile->getCombatData()->getClickRecord()->notify($notifier);
	}

	public function handle(): void {
		$this->reward();
		$player = $this->profile->getPlayer();
		$cd = $this->profile->getCombatData();
		$md = $this->profile->getMovementData();

		$delta = $md->getInputCount() - $cd->getLastClickInputTick();
		$this->clickDelta->add($delta);

		if ($this->clickDelta->isMax()) {
			$samples = $this->clickDelta->getAll();
			$deviation = Statistics::standardDeviation($samples);
			$skewness = Statistics::skewness($samples);
			$kurtosis = Statistics::kurtosis($samples);

			if (
				Math::equals($deviation, $this->lastDeviation) &&
				Math::equals($skewness, $this->lastSkewness) &&
				Math::equals($kurtosis, $this->lastKurtosis)
			) {
				if ($this->preFail()) {
					$this->fail(new ViolationFailReason("Same deviation, skewness, kurtosis"));
				}
			} else {
				$this->resetPreVL();
			}

			$this->broadcastDebugMessage("Deviation: {$deviation}, Skewness: {$skewness}, Kurtosis: {$kurtosis}");

			$this->lastDeviation = $deviation;
			$this->lastSkewness = $skewness;
			$this->lastKurtosis = $kurtosis;

			$this->clickDelta->clear();
		}
	}
}
