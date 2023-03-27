<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\check\ICheck;
use NeiroNetwork\Flare\profile\style\FlareStyle;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;
use pocketmine\utils\BroadcastLoggerForwarder;

/**
 * fixme: クラス名変更するべき
 */
class ConsoleProfile implements Profile {
	use CooldownLoggingTrait;

	protected LogStyle $logStyle;

	protected Flare $flare;

	protected BroadcastLoggerForwarder $console;

	public function __construct(Flare $flare, \AttachableThreadedLogger $logger, Language $language) {
		$this->flare = $flare;
		$server = $flare->getPlugin()->getServer();
		$this->console = new BroadcastLoggerForwarder($server, $logger, $language);
		$this->console->setBasePermission(DefaultPermissions::ROOT_CONSOLE, true);
		$this->logStyle = new FlareStyle;

		$conf = $flare->getConfig()->getConsole();

		$this->alertCooldown = $conf->get("alert_cooldown");
		$this->alertEnabled = $conf->get("alert");

		$this->logCooldown = $conf->get("log_cooldown");
		$this->logEnabled = $conf->get("log");

		$this->debugEnabled = $conf->get("debug");

		$this->verboseEnabled = $conf->get("verbose");
	}

	public function getServerTick(): int {
		return $this->flare->getPlugin()->getServer()->getTick();
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
