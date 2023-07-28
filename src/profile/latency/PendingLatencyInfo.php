<?php

namespace NeiroNetwork\Flare\profile\latency;

use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

class PendingLatencyInfo{

	protected NetworkStackLatencyPacket $request;

	/**
	 * @var \Closure(PendingLatencyInfo $latencyInfo): void
	 */
	protected \Closure $onResponse;

	public function __construct(NetworkStackLatencyPacket $packet, \Closure $onResponse){
		$this->request = $packet;
		$this->onResponse = $onResponse;
	}

	public function success() : void{
		($this->onResponse)($this);
	}

	/**
	 * @return NetworkStackLatencyPacket
	 */
	public function getRequest() : NetworkStackLatencyPacket{
		return $this->request;
	}

	public function getExceptResponseTimestamp() : int{
		// understandable
		return $this->request->timestamp * (10 ** 6);
	}
}
