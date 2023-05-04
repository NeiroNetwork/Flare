<?php

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\profile\ProfileManager;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;

class TransactionPairingHost{

	protected ProfileManager $profileManager;

	protected bool $enabled;

	public function __construct(ProfileManager $profileManager){
		$this->profileManager = $profileManager;
		$this->enabled = true;
	}

	/**
	 * @return bool
	 */
	public function isEnabled() : bool{
		return $this->enabled;
	}

	/**
	 * @param bool $enabled
	 */
	public function setEnabled(bool $enabled) : void{
		$this->enabled = $enabled;
	}

	public function onStartOfTick(int $tick) : void{
		if(!$this->enabled){
			return;
		}
		foreach($this->profileManager->getAll() as $profile){
			$profile->getTransactionPairing()->onStartOfTick($tick);
		}
	}

	public function onEndOfTick(int $tick) : void{
		if(!$this->enabled){
			return;
		}

		foreach($this->profileManager->getAll() as $profile){
			$profile->getTransactionPairing()->onEndOfTick($tick);
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		foreach($event->getTargets() as $origin){
			$this->onDataPacketSendSpecify($origin, $event->getPackets());
		}
	}

	/**
	 * @param NetworkSession                   $origin
	 * @param (DataPacket&ClientboundPacket)[] $packets
	 *
	 * @return void
	 */
	public function onDataPacketSendSpecify(NetworkSession $origin, array $packets) : void{
		$player = $origin->getPlayer();
		if(is_null($player)){
			return;
		}

		if(!$this->enabled){
			return;
		}

		$profile = $this->profileManager->fetch($player->getUniqueId()->toString());

		if(is_null($profile)){
			return;
		}

		$profile->getTransactionPairing()->handlePacketSend($packets);
	}

}
