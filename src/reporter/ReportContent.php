<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;

interface ReportContent{

	public function getText(CommandSender $target) : null|string|Translatable;
}
