<?php

namespace NeiroNetwork\Flare\profile\latency;

use NeiroNetwork\Flare\profile\PlayerProfile;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

class LatencyHandler{

	const TIMESTAMP_SIZE = 1000;

	protected PlayerProfile $profile;

	protected int $nextTimestamp;

	/**
	 * @var array<int, PendingLatencyInfo>
	 */
	protected array $pending;

	public function __construct(PlayerProfile $profile){
		$this->profile = $profile;
		$this->pending = [];
		$this->nextTimestamp = 0;
	}

	/**
	 * @param \Closure(PendingLatencyInfo $latencyInfo): void $onResponse
	 *
	 * @return PendingLatencyInfo
	 */
	public function request(\Closure $onResponse, bool $immediate = false) : PendingLatencyInfo{
		$packet = NetworkStackLatencyPacket::request($this->nextTimestamp);
		$latencyInfo = new PendingLatencyInfo($packet, $onResponse);

		$this->profile->getPlayer()->getNetworkSession()->sendDataPacket($packet, $immediate);

		$this->pending[$latencyInfo->getExceptResponseTimestamp()] = $latencyInfo;

		$this->changeTimestamp();

		return $latencyInfo;
	}

	protected function changeTimestamp() : void{
		$this->nextTimestamp -= self::TIMESTAMP_SIZE;
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
