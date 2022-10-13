<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\reporter\FailReportContent;
use pocketmine\event\EventPriority;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\EventPacket;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginManager;

class Observer {

	protected PlayerProfile $profile;

	/**
	 * @var ICheck[]
	 */
	protected array $list;

	/**
	 * @var bool
	 */
	protected bool $closed;

	public function __construct(PlayerProfile $profile) {
		$this->profile = $profile;
		$this->list = [];
		$this->closed = false;
	}

	public function setEnabled(bool $enabled): void {
		foreach ($this->list as $check) {
			$enabled ? $check->onEnable() : $check->onDisable();
		}
	}

	public function isClosed(): bool {
		return $this->closed;
	}

	public function close(): void {
		if ($this->closed) {
			throw new \Exception("observer already closed");
		}

		$this->closed = true;
		foreach ($this->list as $check) {
			$check->onUnload();
		}

		$this->list = [];
	}

	public function getProfile(): PlayerProfile {
		return $this->profile;
	}

	public function registerCheck(ICheck $check): void {
		if ($this->closed) {
			throw new \Exception("observer closed");
		}

		if (isset($this->list[$check->getFullId()])) {
			throw new \Exception("check \"{$check->getFullId()}\" is already registered");
		}
		$this->list[$check->getFullId()] = $check;

		$check->onLoad();
	}

	public function getCheck(string $fullId): ?ICheck {
		if ($this->closed) {
			throw new \Exception("observer closed");
		}

		return $this->list[$fullId] ?? null;
	}

	/**
	 * @return ICheck[]
	 */
	public function getAllChecks(): array {
		if ($this->closed) {
			throw new \Exception("observer closed");
		}

		return $this->list;
	}

	public function requestPunish(ICheck $cause): bool {
		$vl = true;
		if ($cause instanceof BaseCheck) {
			$vl = $cause->getVL() > $cause->getPunishVL();
		}

		return $vl;
	}

	public function punish(): void {
		$this->profile->getPlayer()->kick("§7(Flare) §cKicked for §lUnfair Advantage");
		$this->profile->close();
	}

	public function requestFail(ICheck $cause, FailReason $reason): bool {
		return true;
	}

	public function reportFail(ICheck $cause, FailReason $reason): void {
		$this->profile->getFlare()->getReporter()->report(new FailReportContent($cause, $reason));
	}
}
