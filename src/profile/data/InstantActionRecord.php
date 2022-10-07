<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

class InstantActionRecord extends ActionRecord {

	public function onAction(): void {
		$this->notifyAction();
		$this->flag = true;
	}

	public function update(bool $flag = false, ?int $currentTick = null): void {
		parent::update($flag, $currentTick);
	}
}
