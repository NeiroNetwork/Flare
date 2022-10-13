<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use NeiroNetwork\Flare\profile\PlayerProfile;

abstract class BaseCheck implements ICheck {
	use CheckViolationTrait;

	/**
	 * @var bool
	 */
	protected bool $enabled;

	protected float $pvlMax = (100 * 8);
	protected float $pvl = 0;

	/**
	 * @var Observer
	 */
	protected Observer $observer;

	/**
	 * @var PlayerProfile
	 */
	protected PlayerProfile $profile;

	public function __construct(Observer $observer) {
		$this->observer = $observer;
		$this->profile = $observer->getProfile();
		$this->enabled = false;
	}

	public function getObserver(): Observer {
		return $this->observer;
	}

	public function preFail(): bool {
		$this->pvl = max(0, $this->pvl + 100);

		if ($this->pvl >= $this->pvlMax) {
			return true;
		}

		return false;
	}

	public function preReward(int $multiplier = 1): void {
		$this->pvl = max(0, $this->pvl - $multiplier);
	}

	public function fail(FailReason $reason): void {
		$ok = $this->observer->requestFail($this, $reason);

		if (!$ok) {
			return;
		}

		$this->observer->reportFail($this, $reason);

		if ($reason instanceof ViolationFailReason) {
			$this->violate();
		}
	}

	abstract public function getType(): string;

	final public function getFullId(): string {
		return $this->getName() . $this->getType();
	}

	public function setEnabled(bool $enabled = true): void {
		if ($this->enabled !== $enabled) {
			$enabled ? $this->onEnable() : $this->onDisable();
		}

		$this->enabled = $enabled;
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function onDisable(): void {
	}

	public function onEnable(): void {
	}

	public function onLoad(): void {
	}

	public function isExperimental(): bool {
		return false;
	}
}
