<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use NeiroNetwork\Flare\FlareKickReasons;
use NeiroNetwork\Flare\player\WatchBot;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\reporter\FailReportContent;
use pocketmine\event\EventPriority;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\EventPacket;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginManager;
use pocketmine\utils\Utils;

class Observer {

	protected PlayerProfile $profile;

	protected ?WatchBot $watchBot;

	/**
	 * @var ICheck[]
	 */
	protected array $list;

	/**
	 * @var bool
	 */
	protected bool $closed;

	/**
	 * @var bool
	 */
	protected bool $checkEnabled;

	/**
	 * @var bool
	 */
	protected bool $punishEnabled;

	public function __construct(PlayerProfile $profile) {
		$this->profile = $profile;
		$this->list = [];
		$this->closed = false;
		$this->watchBot = null;

		$conf = $profile->getConfig();

		$this->checkEnabled = $conf->get("check");
		$this->punishEnabled = $conf->get("punish");
	}

	public function spawnWatchBot(int $duration): bool {
		if ($this->watchBot?->isSpawned() ?? true) {
			return false;
		}

		$this->watchBot = new WatchBot(WatchBot::createFakePlayer($this->profile->getPlayer()->getEyePos()->add(0, 0.3, 0)), $this->profile->getPlayer());

		$this->profile->getFlare()->getWatchBotTask()->addBot($this->watchBot, $duration);

		return true;
	}

	public function setEnabled(bool $enabled): void {
		foreach ($this->list as $check) {
			$check->setEnabled($enabled);
		}

		$this->checkEnabled = $enabled;
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

		$check->setEnabled($this->checkEnabled);
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

	public function doPunish(): void {
		// requestPunish に移動するべき？
		if (!$this->punishEnabled) {
			return;
		}
		$this->profile->getPlayer()->disconnect(FlareKickReasons::unfair_advantage($this->profile->getPlayer()->getName()));
		$this->profile->close();
	}

	public function requestFail(ICheck $cause, FailReason $reason): bool {
		return true;
	}

	public function doFail(ICheck $cause, FailReason $reason): void {
		$this->profile->getFlare()->getReporter()->report(new FailReportContent($cause, $reason));

		if ($cause->getCheckGroup() === CheckGroup::COMBAT) {
			$this->spawnWatchBot(120);
		}
	}

	/**
	 * Get the value of checkEnabled
	 *
	 * @return bool
	 */
	public function isCheckEnabled(): bool {
		return $this->checkEnabled;
	}

	/**
	 * Get the value of punishEnabled
	 *
	 * @return bool
	 */
	public function isPunishEnabled(): bool {
		return $this->punishEnabled;
	}
}
