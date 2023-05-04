<?php

namespace NeiroNetwork\Flare\profile\pairing;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\FlareKickReasons;
use NeiroNetwork\Flare\profile\latency\PendingLatencyInfo;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\reporter\LogReportContent;
use NeiroNetwork\Flare\utils\IntegerSortSizeMap;
use NeiroNetwork\Flare\utils\Map;
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

	/**
	 * @var Map<int, (DataPacket&ClientboundPacket)[]>
	 */
	protected Map $unconfirmedPackets;

	/**
	 * @var IntegerSortSizeMap<int, (DataPacket&ClientboundPacket)[]>
	 */
	protected IntegerSortSizeMap $confirmedPackets;

	protected int $latestConfirmedTick;
	protected int $serverTick;
	protected int $lastTick;

	protected bool $inTick;

	protected ?PendingLatencyInfo $pairingLatencyInfo;

	/**
	 * @var \Closure(int $tick): void[]
	 */
	protected array $confirmHandlers;

	public function __construct(
		PlayerProfile $profile,
		int $confirmedPacketsMapSize
	){
		$this->profile = $profile;

		$this->unconfirmedPackets = new Map();
		$this->confirmedPackets = new IntegerSortSizeMap($confirmedPacketsMapSize);
		$this->latestConfirmedTick = -1;
		$this->serverTick = $profile->getServerTick();
		$this->lastTick = $profile->getServerTick();
		$this->inTick = false;
		$this->pairingLatencyInfo = null;
		$this->confirmHandlers = [];
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

	/**
	 * @param int $tick
	 *
	 * @return (ClientboundPacket&DataPacket[])|null
	 */
	public function getConfirmedPackets(int $tick) : ?array{
		return $this->confirmedPackets->get($tick);
	}

	/**
	 * @param int $tick
	 *
	 * @return (ClientboundPacket&DataPacket[])|null
	 */
	public function getUnconfirmedPackets(int $tick) : ?array{
		return $this->unconfirmedPackets->get($tick);
	}

	public function onStartOfTick(int $tick) : void{
		$this->inTick = true;
		$this->serverTick = $tick;

		if($this->serverTick - $this->lastTick > 100){
			$this->profile->disconnectPlayerAndClose(FlareKickReasons::pairing_not_responded($this->profile->getPlayer()->getName(), $this->latestConfirmedTick));
			return;
		}

		$this->profile->getLatencyHandler()->request(function(PendingLatencyInfo $latencyInfo) use ($tick) : void{
			$this->pairingStartOfTick($latencyInfo, $tick);
		}, true);
	}

	protected function pairingStartOfTick(PendingLatencyInfo $latencyInfo, int $tick) : void{
		if(!is_null($this->pairingLatencyInfo)){
			$this->profile->getFlare()->getReporter()->report(new LogReportContent(
				Flare::PREFIX . "§b{$this->profile->getName()}: §cペアリング: チックスタートを2回連続で受け取りました(チックエンドが無視されました)",
				$this->profile->getFlare()
			));
			return;
		}
		$this->pairingLatencyInfo = $latencyInfo;
	}

	public function onEndOfTick(int $tick) : void{
		$this->inTick = false;

		$this->profile->getLatencyHandler()->request(function(PendingLatencyInfo $latencyInfo) use ($tick) : void{
			$this->pairingEndOfTick($latencyInfo, $tick);
		}, true);
	}

	protected function pairingEndOfTick(PendingLatencyInfo $latencyInfo, int $tick) : void{
		$this->confirm($tick);
		if(is_null($this->pairingLatencyInfo)){
			$this->profile->getFlare()->getReporter()->report(new LogReportContent(
				Flare::PREFIX . "§b{$this->profile->getName()}: §cペアリング: チックエンドを受け取りましたが、チックスタートを受け取っていません。",
				$this->profile->getFlare()
			));
			return;
		}

		$this->pairingLatencyInfo = null;
	}

	protected function confirm(int $tick) : void{
		$packets = $this->unconfirmedPackets->get($tick) ?? [];
		$this->confirmedPackets->put($tick, $packets);
		$this->unconfirmedPackets->remove($tick);
		$this->latestConfirmedTick = $tick;
		$this->lastTick = $tick;

		// $this->profile->getPlayer()->sendMessage("confirmed tick {$tick} : {$this->serverTick}");

		foreach($this->confirmHandlers as $handler){
			$handler($tick);
		}
	}

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


		$arr = $this->unconfirmedPackets->get($this->serverTick) ?? [];
		$arr[] = $packet;
		$this->unconfirmedPackets->put($this->serverTick, $arr);
	}

	public function filterPacket(DataPacket $packet) : bool{
		return
			($packet instanceof MoveActorAbsolutePacket) ||
			($packet instanceof SetActorDataPacket) ||
			($packet instanceof SetActorMotionPacket) ||
			($packet instanceof UpdateAttributesPacket) ||
			($packet instanceof UpdateAbilitiesPacket) ||
			($packet instanceof MobEffectPacket);
		//todo: VirtualWorld でのブロックのペアリングも考える
		// (コストが高いし、ブロックのずれによる誤検知はそこまで致命的ではないので今のところは必要ない)
	}
}
