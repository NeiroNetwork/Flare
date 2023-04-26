<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

interface HandleInputPacketCheck{

	public function handle(PlayerAuthInputPacket $packet) : void;
}
