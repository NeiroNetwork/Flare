<?php

namespace NeiroNetwork\Flare\profile\check\list\packet\pingspoof;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class PingSpoofA extends BaseCheck{

	use HandleEventCheckTrait;
	use ClassNameAsCheckIdTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function isExperimental() : bool{
		return true;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$netPing = $this->profile->getPlayer()->getNetworkSession()->getPing() ?? -1;

		if($netPing === -1){
			return;
		}

		if(!$this->profile->isTransactionPairingEnabled()){
			return;
		}

		if(!$this->profile->isServerStable()){
			return;
		}

		$estimatePacketPing = ($this->profile->getTransactionPairing()->getServerTick() - $this->profile->getTransactionPairing()->getLatestConfirmedTick()) * 50;

		$diff = abs($estimatePacketPing - $netPing);

		if($netPing > $estimatePacketPing && $diff > 100){
			$this->fail(new ViolationFailReason("Ping bigger than estimated ping ({$diff}ms)"));
		}

		if($netPing < $estimatePacketPing && $diff > 250){
			$this->fail(new ViolationFailReason("Estimated ping bigger than ping ({$diff}ms)"));
		}

		$this->broadcastDebugMessage("Estimated: {$estimatePacketPing}ms, Session: {$netPing}ms");
	}
}
