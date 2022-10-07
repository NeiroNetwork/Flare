<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

trait CheckViolationTrait {

	protected float $vl = 0;

	public function violate(float $level = 1): void {
		$this->vl += $level * 100;
	}

	public function reward(float $level = 0.01): void {
		$this->vl = max(0, $this->vl - $level * 100);
	}
}
