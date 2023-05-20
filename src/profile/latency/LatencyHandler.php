<?php

namespace NeiroNetwork\Flare\profile\latency;

use Closure;
use NeiroNetwork\Flare\profile\PlayerProfile;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

class LatencyHandler{

	const TIMESTAMP_SIZE = 1000;

	private static int $nextTimestamp = 0;
	protected PlayerProfile $profile;
	/**
	 * @var array<int, PendingLatencyInfo>
	 */
	protected array $pending;

	public function __construct(PlayerProfile $profile){
		$this->profile = $profile;
		$this->pending = [];
	}

	/**
	 * @param Closure(PendingLatencyInfo $latencyInfo): void $onResponse
	 * @param bool                                           $immediate
	 *
	 * @return PendingLatencyInfo
	 */
	public function request(Closure $onResponse, bool $immediate = false) : PendingLatencyInfo{
		$packet = NetworkStackLatencyPacket::request(self::nextTimestamp());
		$latencyInfo = new PendingLatencyInfo($packet, $onResponse);

		$this->profile->getPlayer()->getNetworkSession()->sendDataPacket($packet, $immediate);

		$this->pending[$latencyInfo->getExceptResponseTimestamp()] = $latencyInfo;

		return $latencyInfo;
	}

	public static function nextTimestamp() : int{
		return self::$nextTimestamp -= self::TIMESTAMP_SIZE;
	}

	public function handleResponse(NetworkStackLatencyPacket $packet) : void{
		$info = $this->get($packet->timestamp);

		if(!is_null($info)){
			$info->success();

			unset($this->pending[$packet->timestamp]);
		}
	}

	public function get(int $timestamp) : ?PendingLatencyInfo{
		return $this->pending[$timestamp] ?? null;
	}
}
