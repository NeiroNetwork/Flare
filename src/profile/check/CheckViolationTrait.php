<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

trait CheckViolationTrait {

	protected int $vl = 0;

	protected int $punishVl = (100 * 15);

	public function getPunishVL(): int {
		return $this->punishVl;
	}

	public function getVL(): int {
		return $this->vl;
	}

	public function violate(): void {
		$this->vl += 100;
	}

	public function reward(): void {
		$this->vl = max(0, $this->vl - 1);
	}
}
