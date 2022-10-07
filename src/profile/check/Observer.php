<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\Profile;
use pocketmine\event\EventPriority;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\EventPacket;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginManager;

class Observer {

	protected Profile $profile;

	/**
	 * @var ICheck[]
	 */
	protected array $list;

	public function __construct(Profile $profile) {
		$this->profile = $profile;
		$this->list = [];
	}

	public function getProfile(): Profile {
		return $this->profile;
	}

	public function registerCheck(ICheck $check): void {
		if (isset($this->list[$check->getFullId()])) {
			throw new \Exception("check \"{$check->getFullId()}\" is already registered");
		}
		$this->list[$check->getFullId()] = $check;

		$check->onLoad();
	}

	public function getCheck(string $fullId): ?ICheck {
		return $this->list[$fullId] ?? null;
	}

	/**
	 * @return ICheck[]
	 */
	public function getAllChecks(): array {
		return $this->list;
	}

	public function fail(ICheck $cause, FailReason $reason): bool {
		$this->reportFail($cause, $reason);
		return true;
	}

	public function reportFail(ICheck $cause, FailReason $reason): void {
		$this->profile->getPlayer()->sendMessage("Fail: {$cause->getFullId()} verbose: {$reason->verbose}");

		// todo: temporary
	}
}
