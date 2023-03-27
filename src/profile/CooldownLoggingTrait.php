<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\profile\check\ICheck;

trait CooldownLoggingTrait {

	/**
	 * @var int[]
	 */
	protected array $lastAlertTicks = [];

	/**
	 * @var int
	 */
	protected int $alertCooldown = 0;

	/**
	 * @var int
	 */
	protected int $logCooldown = 0;

	/**
	 * @var int
	 */
	protected int $lastLogTick = 0;

	/**
	 * @var bool
	 */
	protected bool $alertEnabled = false;

	protected bool $logEnabled = false;

	protected bool $verboseEnabled = false;

	protected bool $debugEnabled = false;

	public function isAlertEnabled(): bool {
		return $this->alertEnabled;
	}

	public function isLogEnabled(): bool {
		return $this->logEnabled;
	}

	public function isDebugEnabled(): bool {
		return $this->debugEnabled;
	}

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

	public function tryDebug(): bool {
		if (!$this->debugEnabled) {
			return false;
		}

		return true;
	}

	/**
	 * Get the value of alertCooldown
	 *
	 * @return int
	 */
	public function getAlertCooldown(): int {
		return $this->alertCooldown;
	}

	/**
	 * Get the value of logCooldown
	 *
	 * @return int
	 */
	public function getLogCooldown(): int {
		return $this->logCooldown;
	}


	/**
	 * Set the value of logEnabled
	 *
	 * @param bool $logEnabled
	 *
	 * @return self
	 */
	public function setLogEnabled(bool $logEnabled): self {
		$this->logEnabled = $logEnabled;

		return $this;
	}


	/**
	 * Set the value of alertEnabled
	 *
	 * @param bool $alertEnabled
	 *
	 * @return self
	 */
	public function setAlertEnabled(bool $alertEnabled): self {
		$this->alertEnabled = $alertEnabled;

		return $this;
	}


	/**
	 * Set the value of logCooldown
	 *
	 * @param int $logCooldown
	 *
	 * @return self
	 */
	public function setLogCooldown(int $logCooldown): self {
		$this->logCooldown = $logCooldown;

		return $this;
	}


	/**
	 * Set the value of alertCooldown
	 *
	 * @param int $alertCooldown
	 *
	 * @return self
	 */
	public function setAlertCooldown(int $alertCooldown): self {
		$this->alertCooldown = $alertCooldown;

		return $this;
	}

	/**
	 * Get the value of verboseEnabled
	 *
	 * @return bool
	 */
	public function isVerboseEnabled(): bool {
		return $this->verboseEnabled;
	}

	/**
	 * Set the value of verboseEnabled
	 *
	 * @param bool $verboseEnabled
	 *
	 * @return self
	 */
	public function setVerboseEnabled(bool $verboseEnabled): self {
		$this->verboseEnabled = $verboseEnabled;

		return $this;
	}


	/**
	 * Set the value of debugEnabled
	 *
	 * @param bool $debugEnabled
	 *
	 * @return self
	 */
	public function setDebugEnabled(bool $debugEnabled): self {
		$this->debugEnabled = $debugEnabled;

		return $this;
	}
}
