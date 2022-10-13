<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\style\FlareStyle;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;

class ConsoleProfile implements Profile {

	protected LogStyle $logStyle;

	protected Flare $flare;

	protected ConsoleCommandSender $console;

	public function __construct(Flare $flare, ConsoleCommandSender $console) {
		$this->flare = $flare;
		$this->console = $console;
		$this->logStyle = new FlareStyle;
	}

	public function getLogStyle(): LogStyle {
		return $this->logStyle;
	}

	public function getClient(): ?Client {
		return null;
	}

	public function getFlare(): Flare {
		return $this->flare;
	}

	public function getCommandSender(): CommandSender {
		return $this->console;
	}

	public function getName(): string {
		return $this->getCommandSender()->getName();
	}
}
