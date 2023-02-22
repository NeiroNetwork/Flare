<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;

interface ICheck {

	/**
	 * @return string
	 * 
	 * check name
	 */
	public function getName(): string;

	/**
	 * @return int
	 * 
	 * todo: return CheckGroup instance
	 */
	public function getCheckGroup(): int;

	public function fail(FailReason $reason): void;

	public function getFullId(): string;

	public function onEnable(): void;

	public function onDisable(): void;

	public function onLoad(): void;

	public function onUnload(): void;

	public function setEnabled(bool $enabled = true): void;

	public function isEnabled(): bool;

	public function isExperimental(): bool;

	public function tryCheck(): bool;

	/**
	 * @return CommandSender[]
	 */
	public function getDebuggers(): array;

	public function getDebugPrefix(): string;

	public function subscribeDebugger(CommandSender $debugger): void;

	public function unsubscribeDebugger(CommandSender $debugger): void;

	/**
	 * @return Observer
	 * 
	 * todo: remove this
	 */
	public function getObserver(): Observer;
}
