<?php

namespace NeiroNetwork\Flare;

use Closure;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketRateLimiter;

class PacketRateLimitModifier{

	public static function modifySession(NetworkSession $session, int $averagePerTick, int $budget) : void{
		$ref = new \ReflectionClass($session);
		self::modify($ref->getProperty("gamePacketLimiter")->getValue($session), $averagePerTick, $budget);
		self::modify($ref->getProperty("packetBatchLimiter")->getValue($session), $averagePerTick, $budget);
	}

	public static function modify(PacketRateLimiter $rateLimiter, int $averagePerTick, int $budget) : void{
		Closure::bind(function() use ($averagePerTick, $budget) : void{
			$this->maxBudget = $averagePerTick * $budget;
			$this->lastUpdateTimeNs = 0;
		}, $rateLimiter, $rateLimiter)();
	}
}
