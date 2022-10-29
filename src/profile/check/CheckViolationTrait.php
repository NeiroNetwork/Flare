<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

trait CheckViolationTrait {

	private int $vl = 0;

	protected int $punishVl = (100 * 15);

	public function getPunishVL(): int {
		return $this->punishVl;
	}

	public function getVL(): int {
		return $this->vl;
	}

	public function violate(): void {
		$this->vl = min($this->punishVl, $this->vl + 100);
	}

	public function reward(): void {
		$this->vl = max(0, $this->vl - 1);
	}


	/**
	 * Set the value of punishVl
	 *
	 * @param int $punishVL
	 *
	 * @return self
	 */
	public function setPunishVL(int $punishVL): self {
		$this->punishVl = $punishVL;

		return $this;
	}


	/**
	 * Set the value of vl
	 *
	 * @param int $vl
	 *
	 * @return self
	 */
	public function setVL(int $vl): self {
		$this->vl = $vl;

		return $this;
	}
}
