<?php

namespace NeiroNetwork\Flare\profile\pairing;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\FlareKickReasons;
use NeiroNetwork\Flare\profile\latency\PendingLatencyInfo;
use NeiroNetwork\Flare\profile\PlayerProfile;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

class TransactionPairing{

	protected PlayerProfile $profile;

	protected int $latestConfirmedTick;
	protected int $serverTick;

	protected array $pendingTicks;

	/**
	 * @var \Closure(DataPacket, int): void[]
	 */
	protected array $confirmHandlers;

	public function __construct(
		PlayerProfile $profile,
	){
		$this->profile = $profile;

		$this->latestConfirmedTick = $profile->getServerTick();
		$this->serverTick = $profile->getServerTick();
		$this->confirmHandlers = [];
		$this->pendingTicks = [];
	}

	/**
	 * @return int
	 */
	public function getServerTick() : int{
		return $this->serverTick;
	}

	/**
	 * @return int
	 */
	public function getLatestConfirmedTick() : int{
		return $this->latestConfirmedTick;
	}

	/**
	 * @return PlayerProfile
	 */
	public function getProfile() : PlayerProfile{
		return $this->profile;
	}

	/**
	 * @param \Closure(int $tick): void $handler
	 *
	 * @return void
	 */
	public function addConfirmHandler(\Closure $handler) : void{
		$this->confirmHandlers[spl_object_hash($handler)] = $handler;
	}

	/**
	 * @param \Closure(int $tick): void $handler
	 *
	 * @return void
	 */
	public function removeConfirmHandler(\Closure $handler) : void{
		unset($this->confirmHandlers[spl_object_hash($handler)]);
	}

	public function onStartOfTick(int $tick) : void{
		$this->serverTick = $tick;

		if(isset($this->pendingTicks[$tick - 140])){
			$this->profile->disconnectPlayerAndClose(FlareKickReasons::pairing_not_responded($this->profile->getPlayer()->getName(), $this->latestConfirmedTick));
		}
	}

	public function onEndOfTick(int $tick) : void{}

	/**
	 * @param (DataPacket&ClientboundPacket)[] $packets
	 *
	 * @return void
	 */
	public function handlePacketSend(array $packets) : void{
		foreach($packets as $packet){
			$this->handleSinglePacketSend($packet);
		}
	}

	public function handleSinglePacketSend(DataPacket $packet) : void{
		if(!$this->filterPacket($packet)){
			return;
		}

		if($packet instanceof SetActorDataPacket && empty($packet->metadata)){
			return; // fixme: PATCH FOR COMBATDATA
		}

		$tick = $this->serverTick;

		$this->pendingTicks[$tick] = null;
		$this->profile->getLatencyHandler()->request(function(PendingLatencyInfo $latencyInfo) use ($packet, $tick) : void{
			if($this->profile->isDebugEnabled()){
				$diff = $this->serverTick - $tick;
				$this->profile->getPlayer()->sendMessage(Flare::DEBUG_PREFIX . "§7{$packet->getName()} のサンドイッチに成功しました ($diff ticks)");
			}
			foreach($this->confirmHandlers as $handler){
				$handler($packet, $tick);
			}

			$this->latestConfirmedTick = $tick;
			unset($this->pendingTicks[$tick]);
		});
	}

	public function filterPacket(DataPacket $packet) : bool{
		return
			($packet instanceof MoveActorAbsolutePacket) ||
			($packet instanceof SetActorDataPacket) ||
			($packet instanceof SetActorMotionPacket) ||
			($packet instanceof UpdateAttributesPacket) ||
			($packet instanceof UpdateAbilitiesPacket) ||
			($packet instanceof MobEffectPacket) ||
			($packet instanceof AddActorPacket) ||
			($packet instanceof AddPlayerPacket);
		//todo: VirtualWorld でのブロックのペアリングも考える
		// (コストが高いし、ブロックのずれによる誤検知はそこまで致命的ではないので今のところは必要ない)
	}
}
