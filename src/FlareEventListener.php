<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\event\network\NackReceiveEvent;
use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\event\player\PlayerAttackWatchBotEvent;
use NeiroNetwork\Flare\event\player\PlayerPacketLossEvent;
use NeiroNetwork\Flare\network\TransparentRakLibInterface;
use NeiroNetwork\Flare\player\FakePlayer;
use NeiroNetwork\Flare\profile\Client;
use NeiroNetwork\Flare\reporter\LogReportContent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\mcpe\raklib\RakLibServer;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use raklib\utils\InternetAddress;

class FlareEventListener implements Listener {

	protected Flare $flare;
	protected Server $server;

	/**
	 * @var Player[]
	 */
	private array $playerFromAddress;

	private bool $rakLibOverrideSuccess;

	public function __construct(Flare $flare) {
		$this->flare = $flare;
		$this->server = $flare->getPlugin()->getServer();
		$this->playerFromAddress = [];
		$this->rakLibOverrideSuccess = false;
	}

	public function getPlayerFromAddress(InternetAddress $address): ?Player {
		return $this->playerFromAddress[$address->toString()] ?? null;
	}

	public function onPreLogin(PlayerPreLoginEvent $event): void {
		$client = new Client($event->getPlayerInfo(), $event->getIp());

		if (!$client->isValid()) {
			$event->setKickReason(FlareKickReasons::PRE_KICK_REASON_INVALID_CLIENT, FlareKickReasons::invalid_client($event->getPlayerInfo()->getUsername()));
		}
	}

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$session = $player->getNetworkSession();

		$address = (new InternetAddress($session->getIp(), $session->getPort(), 4))->toString(); // todo: ipv6
		$this->playerFromAddress[$address] = $player;

		$this->flare->getProfileManager()->start($player);

		$this->flare->getReporter()->autoSubscribe($player);
	}

	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		$session = $player->getNetworkSession();

		$address = (new InternetAddress($session->getIp(), $session->getPort(), 4))->toString(); // todo: ipv6
		unset($this->playerFromAddress[$address]);

		$this->flare->getProfileManager()->remove($player->getUniqueId()->toString());

		$this->flare->getReporter()->autoUnsubscribe($player);

		$this->flare->getReporter()->report(new LogReportContent(Flare::PREFIX . "§b{$player->getName()} §fが退出しました: §c{$event->getQuitReason()}§f", $this->flare));
	}

	public function onNackReceive(NackReceiveEvent $event) {
		$address = $event->getAddress();

		$player = $this->getPlayerFromAddress($address);

		if ($player instanceof Player) {
			$ev = new PlayerPacketLossEvent($player);
			$ev->call();
		}

		print_r("NACK!!\n");
	}

	public function onDataPacketSend(DataPacketSendEvent $event): void {
		foreach ($event->getPackets() as $packet) {
			if ($packet instanceof MoveActorAbsolutePacket) {
				foreach ($event->getTargets() as $target) {
					if (($player = $target->getPlayer()) instanceof Player) {
						$ppos = clone $packet->position;
						if ($player->getWorld()->getEntity($packet->actorRuntimeId) instanceof Player) {
							$ppos->y -= 1.62;
						}
						$this->flare->getSupports()->getEntityMoveRecorder()->add(
							$player,
							$packet->actorRuntimeId,
							$ppos,
							$this->flare->getPlugin()->getServer()->getTick()
						);
					}
				}
			}

			if ($packet instanceof AddActorPacket) {
				foreach ($event->getTargets() as $target) {
					if (($player = $target->getPlayer()) instanceof Player) {
						$this->flare->getSupports()->getEntityMoveRecorder()->add(
							$player,
							$packet->actorRuntimeId,
							$packet->position,
							$this->flare->getPlugin()->getServer()->getTick()
						);
					}
				}
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 * 
	 * @return void
	 * 
	 * @priority LOWEST
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
		$packet = $event->getPacket();
		$origin = $event->getOrigin();

		if ($packet instanceof PlayerAuthInputPacket) {
			$position = $packet->getPosition()->subtract(0, 1.62, 0);
			$yaw = $packet->getYaw();
			$pitch = $packet->getPitch();

			foreach ([
				$position->x,
				$position->y,
				$position->z,
				$yaw,
				$pitch,
				$packet->getHeadYaw()
			] as $entry) {
				if (is_infinite($entry) || is_nan($entry)) {
					$event->cancel();
					break;
				}
			}
		} elseif ($packet instanceof InventoryTransactionPacket) {
			$data = $packet->trData;
			if (($player = $origin->getPlayer()) === null) {
				return;
			}

			if ($data instanceof UseItemOnEntityTransactionData) {
				if ($data->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK) {
					$runtimeId = $data->getActorRuntimeId();
					$fakePlayer = FakePlayer::getFakePlayer($runtimeId);
					if ($fakePlayer instanceof FakePlayer) {
						$ev = new PlayerAttackWatchBotEvent($player, $fakePlayer);
						$ev->call();
						$event->cancel();
					} else {
						$entity = $player->getWorld()->getEntity($runtimeId);
						if ($entity !== null) {
							$ev = new PlayerAttackEvent($player, $entity, $data->getPlayerPosition(), $data->getClickPosition());
							$ev->call();
						}
					}
				}
			}
		}
	}

	public function onNetworkInterfaceRegister(NetworkInterfaceRegisterEvent $event) {
		$interface = $event->getInterface();


		if ($interface instanceof RakLibInterface && !is_subclass_of($interface, RakLibInterface::class)) {
			$iref = new \ReflectionClass(RakLibInterface::class);
			$serverProp = $iref->getProperty("rakLib");
			$serverProp->setAccessible(true);

			$sref = new \ReflectionClass(RakLibServer::class);
			$addressProp = $sref->getProperty("address");
			$addressProp->setAccessible(true);


			$raklibServer = $serverProp->getValue($interface);
			if ($raklibServer instanceof RakLibServer) {
				$address = $addressProp->getValue($raklibServer);

				return; // todo: 

				if ($address instanceof InternetAddress) {
					$isIpv6 = $address->getVersion() === 4 ? false : true;
					$newInterface = new TransparentRakLibInterface($this->flare->getPlugin()->getServer(), $address->getIp(), $address->getPort(), $isIpv6);
					$this->flare->getPlugin()->getServer()->getNetwork()->registerInterface($newInterface);
					$event->cancel();
					$this->rakLibOverrideSuccess = true;
				}
			}
		}

		if ($interface instanceof DedicatedQueryNetworkInterface && $this->rakLibOverrideSuccess) {
			$event->cancel();
		}
	}
}
