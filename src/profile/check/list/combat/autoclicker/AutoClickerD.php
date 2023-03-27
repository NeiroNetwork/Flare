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

class AutoClickerD extends BaseCheck {
	use ClassNameAsCheckIdTrait;

	protected NumericalSampling $clickDelta;

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
			$outliersResult = Statistics::outliers($samples);
			$duplicates = Statistics::duplicates($samples);
			$outliers = count($outliersResult->high) + count($outliersResult->low);

			if (
				$duplicates > 15 &&
				$outliers < 2
			) {
				if ($this->preFail()) {
					$this->fail(new ViolationFailReason(""));
				}
			} else {
				$this->resetPreVL();
			}

			$this->broadcastDebugMessage("Outliers: {$outliers}, Duplicates: {$duplicates}");
			$this->clickDelta->clear();
		}
	}
}
