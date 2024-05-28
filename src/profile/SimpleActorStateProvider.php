<?php

namespace NeiroNetwork\Flare\profile;

use Closure;
use NeiroNetwork\Flare\utils\EventHandlerLink;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

class SimpleActorStateProvider extends PacketBaseActorStateProvider{

	protected EventHandlerLink $eventLink;

	public function __construct(protected PlayerProfile $profile, int $tickMapSize){
		parent::__construct($tickMapSize);

		$this->eventLink = new EventHandlerLink($this->profile->getFlare());

		$emitter = $this->profile->getFlare()->getEventEmitter();
		$uuid = $this->profile->getPlayer()->getUniqueId()->toString();

		$register = function(int $networkId, Closure $handler) use ($emitter, $uuid) : void{
			$this->eventLink->add($emitter->registerSendPacketHandler(
				$uuid,
				$networkId,
				function($packet) use ($handler) : void{
					$handler($packet, $this->profile->getServerTick());
				},
				false,
				EventPriority::LOWEST
			));
		};

		$register(SetActorDataPacket::NETWORK_ID, $this->handleSetActorData(...));
		$register(SetActorMotionPacket::NETWORK_ID, $this->handleSetActorMotion(...));
		$register(MoveActorAbsolutePacket::NETWORK_ID, $this->handleMoveActorAbsolute(...));
		$register(UpdateAttributesPacket::NETWORK_ID, $this->handleUpdateAttributes(...));
		$register(MobEffectPacket::NETWORK_ID, $this->handleMobEffect(...));
		$register(UpdateAbilitiesPacket::NETWORK_ID, $this->handleUpdateAbilities(...));
		$register(AddActorPacket::NETWORK_ID, $this->handleAddActor(...));
		$register(AddPlayerPacket::NETWORK_ID, $this->handleAddPlayer(...));
		$register(RemoveActorPacket::NETWORK_ID, $this->handleRemoveActor(...));
	}

	public function dispose() : void{
		$this->eventLink->unregisterAll();
	}

}
