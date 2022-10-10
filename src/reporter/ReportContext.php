<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use pocketmine\command\CommandSender;

interface ReportContext {

	public function getText(Reporter $reporter, CommandSender $target): string;
}
