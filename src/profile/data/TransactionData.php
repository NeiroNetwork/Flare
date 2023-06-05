<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use Closure;
use NeiroNetwork\Flare\profile\PlayerProfile;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class TransactionData{

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $inventoryOpen;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $inventoryClose;

	public function __construct(protected PlayerProfile $profile){
		$player = $profile->getPlayer();
		$emitter = $profile->getFlare()->getEventEmitter();
		$uuid = $player->getUniqueId()->toString();
		$emitter->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handleInput"]),
			false,
			EventPriority::NORMAL
		);

		$emitter->registerSendPacketHandler(
			$uuid,
			ContainerOpenPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handleContainerOpen"]),
			false,
			EventPriority::LOW
		);

		$emitter->registerSendPacketHandler(
			$uuid,
			ContainerClosePacket::NETWORK_ID,
			Closure::fromCallable([$this, "handleContainerClose"]),
			false,
			EventPriority::LOW
		);

		ProfileData::autoPropertyValue($this);
	}

	/**
	 * Get the value of inventoryOpen
	 *
	 * @return InstantActionRecord
	 */
	public function getInventoryOpenRecord() : InstantActionRecord{
		return $this->inventoryOpen;
	}

	/**
	 * Get the value of inventoryClose
	 *
	 * @return InstantActionRecord
	 */
	public function getInventoryCloseRecord() : InstantActionRecord{
		return $this->inventoryClose;
	}

	protected function handleContainerOpen(ContainerOpenPacket $packet) : void{
		$this->inventoryOpen->onAction();
	}

	protected function handleContainerClose(ContainerClosePacket $packet) : void{
		$this->inventoryClose->onAction();
	}

	protected function handleInput(PlayerAuthInputPacket $packet) : void{
		$this->inventoryClose->update();
		$this->inventoryOpen->update();
	}
}
