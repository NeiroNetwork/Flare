<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use Closure;
use NeiroNetwork\Flare\Flare;
use pocketmine\command\CommandSender;

interface Profile {

	public function getCommandSender(): CommandSender;

	public function getClient(): ?Client;

	public function getFlare(): Flare;

	public function getLogStyle(): LogStyle;

	public function getName(): string;
}
