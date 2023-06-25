<?php

namespace NeiroNetwork\Flare\profile\pairing;

use NeiroNetwork\Flare\profile\PacketBaseActorStateProvider;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

class TransactionPairingActorStateProvider extends PacketBaseActorStateProvider{

	protected TransactionPairing $transactionPairing;


	public function __construct(TransactionPairing $transactionPairing, int $tickMapSize){
		parent::__construct($tickMapSize);
		$this->transactionPairing = $transactionPairing;

		$transactionPairing->addConfirmHandler($this->handleConfirm(...));
	}

	public function dispose() : void{
		$this->transactionPairing->removeConfirmHandler($this->handleConfirm(...));
	}

	protected function handleConfirm(int $tick) : void{
		$packets = $this->transactionPairing->getConfirmedPackets($tick);

		foreach($packets as $packet){
			if($packet instanceof MoveActorAbsolutePacket){
				$this->handleMoveActorAbsolute($packet, $tick);
			}

			if($packet instanceof SetActorDataPacket){
				$this->handleSetActorData($packet, $tick);
			}

			if($packet instanceof SetActorMotionPacket){
				$this->handleSetActorMotion($packet, $tick);
			}

			if($packet instanceof UpdateAttributesPacket){
				$this->handleUpdateAttributes($packet, $tick);
			}

			if($packet instanceof MobEffectPacket){
				$this->handleMobEffect($packet, $tick);
			}

			if($packet instanceof UpdateAbilitiesPacket){
				$this->handleUpdateAbilities($packet, $tick);
			}

			if($packet instanceof AddActorPacket){
				$this->handleAddActor($packet, $tick);
			}

			if($packet instanceof AddPlayerPacket){
				$this->handleAddPlayer($packet, $tick);
			}
		}
	}
}
