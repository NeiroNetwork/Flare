<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use Closure;
use NeiroNetwork\Flare\profile\PlayerProfile;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\BroadcastLoggerForwarder;
use pocketmine\utils\Utils;

abstract class BaseCheck implements ICheck {
	use CheckViolationTrait;

	private string $debugPrefix = "§9> §7[§f%s §8/ §2%s§7] §7";

	/**
	 * @var bool
	 */
	protected bool $enabled;

	/**
	 * @var CommandSender[]
	 */
	protected array $debuggers;

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
		$this->debuggers = [];
	}

	public function getDebugPrefix(): string {
		return sprintf($this->debugPrefix, $this->profile->getPlayer()->getName(), $this->getFullId());
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

		if ($reason instanceof ViolationFailReason) {
			$this->violate();
		}

		$this->observer->doFail($this, $reason);

		if ($this->observer->requestPunish($this)) {
			$this->observer->doPunish();
		}
	}

	public function tryCheck(): bool {
		return
			$this->enabled &&
			!$this->observer->isClosed() &&
			$this->profile->getPlayer()->isConnected() &&
			!$this->profile->getPlayer()->isClosed() &&
			$this->profile->getPlayer()->hasBlockCollision() &&
			!$this->profile->getPlayer()->canClimbWalls() &&
			!$this->profile->getPlayer()->isCreative(); // literal: spectator
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

	public function onUnload(): void {
	}

	public function isExperimental(): bool {
		return false;
	}

	/**
	 * @return CommandSender[]
	 */
	public function getDebuggers(): array {
		return $this->debuggers;
	}

	public function broadcastDebugMessage(string $message): void {
		foreach ($this->debuggers as $player) {
			$player->sendMessage($this->getDebugPrefix() . $message);
		}
	}

	public function consumeDebuggers(Closure $closure): void {
		Utils::validateCallableSignature(function (CommandSender $debugger): void {
		}, $closure);

		foreach ($this->debuggers as $player) {
			($closure)($player);
		}
	}

	public function subscribeDebugger(CommandSender $debugger): void {
		if ($debugger instanceof ConsoleCommandSender) {
			// ConsoleCommandSender を登録すると Command Output | の prefix がついてしまう
			$debugger = new BroadcastLoggerForwarder(Server::getInstance(), Server::getInstance()->getLogger(), Server::getInstance()->getLanguage());
		}

		$this->debuggers[$debugger->getName()] = $debugger;
	}

	public function unsubscribeDebugger(CommandSender $debugger): void {
		if ($debugger instanceof ConsoleCommandSender) {
			$debugger = new BroadcastLoggerForwarder(Server::getInstance(), Server::getInstance()->getLogger(), Server::getInstance()->getLanguage());
		}

		unset($this->debuggers[$debugger->getName()]);
	}
}
