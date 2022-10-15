<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\profile\check\ICheck;

trait CooldownLoggingTrait {

	/**
	 * @var int[]
	 */
	protected array $lastAlertTicks;

	/**
	 * @var int
	 */
	protected int $alertCooldown;

	/**
	 * @var int
	 */
	protected int $logCooldown;

	/**
	 * @var int
	 */
	protected int $lastLogTick;

	/**
	 * @var bool
	 */
	protected bool $alertEnabled;

	protected bool $logEnabled;


	public function tryAlert(ICheck $check): bool {
		if (!$this->alertEnabled) {
			return false;
		}

		$fid = $check->getFullId();
		if (!isset($this->lastAlertTicks[$fid])) {
			$this->lastAlertTicks[$fid] = $this->getServerTick();
		}

		$tick = $this->getServerTick();
		$lastTick = $this->lastAlertTicks[$fid];

		if ($tick - $lastTick >= $this->alertCooldown) {
			$this->lastAlertTicks[$fid] = $tick;
			return true;
		}

		return false;
	}

	public function tryLog(): bool {
		if (!$this->logEnabled) {
			return false;
		}

		$tick = $this->getServerTick();

		if ($tick - $this->lastLogTick >= $this->logCooldown) {
			$this->lastLogTick = $tick;
			return true;
		}

		return false;
	}
}
