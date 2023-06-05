<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\player;

use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use ramsey\Uuid\Uuid;
use ramsey\Uuid\UuidInterface;

class FakePlayer{

	/**
	 * @var FakePlayer[] $entityRuntimeIds
	 */
	private static $entityRuntimeIds = [];
	protected bool $spawned;
	private string $username;
	private string $skinBytes;
	private UuidInterface $uuid;
	private Vector3 $position;
	private int $eid;

	/**
	 * @param string  $username
	 * @param string  $skinBytes
	 * @param Vector3 $position
	 */
	public function __construct(string $username, string $skinBytes, Vector3 $position){
		$this->username = $username;
		$this->skinBytes = $skinBytes;
		$this->spawned = false;
		$this->uuid = Uuid::uuid4();
		$this->position = $position;
		$this->eid = Entity::nextRuntimeId();
		self::$entityRuntimeIds[$this->eid] = $this;
	}

	public static function simple(string $username, Vector3 $position) : FakePlayer{
		return new self($username, str_repeat("\x00", 8192), $position);
	}

	/**
	 * @return FakePlayer[]
	 */
	public static function getEntityRuntimeIds() : array{
		return self::$entityRuntimeIds;
	}

	public static function getFakePlayer(int $runtimeId) : ?FakePlayer{
		return (self::isFakePlayer($runtimeId) ? self::$entityRuntimeIds[$runtimeId] : null);
	}

	public static function isFakePlayer(int $runtimeId) : bool{
		return isset(self::$entityRuntimeIds[$runtimeId]);
	}

	public function __destruct(){
		if(isset(self::$entityRuntimeIds[$this->eid])){
			unset(self::$entityRuntimeIds[$this->eid]);
		}
	}

	public function getBaseHeight(Player $player) : float{
		return $player->size->getEyeHeight();
	}

	public function sendMoveTo(Player $player, Vector3 $to, float $yaw, float $headYaw, float $pitch) : void{
		$packet = $this->getMovePacket($to->add(0, 1.62, 0), $yaw, $headYaw, $pitch);
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	protected function getMovePacket(Vector3 $to, float $yaw, float $headYaw, float $pitch) : MoveActorAbsolutePacket{
		return MoveActorAbsolutePacket::create(
			$this->eid,
			$to,
			$pitch,
			$yaw,
			$headYaw,
			0 # onGround 検知?
		);
	}

	public function spawnTo(Player $player) : void{
		$this->spawned = true;
		$packets = [$this->getPlayerListPacket(), $this->getAddPlayerPacket()];
		foreach($packets as $packet){
			if($player->isOnline()){
				$player->getNetworkSession()->sendDataPacket($packet);
			}
		}
	}

	protected function getPlayerListPacket() : PlayerListPacket{
		$packet = new PlayerListPacket;
		$packet->type = PlayerListPacket::TYPE_ADD;
		$adapter = TypeConverter::getInstance()->getSkinAdapter();

		$packet->entries = [
			PlayerListEntry::createAdditionEntry(
				$this->uuid,
				$this->eid,
				$this->username,
				$adapter->toSkinData(new Skin(
					"Standard_Custom",
					$this->skinBytes,
					"",
					"geometry.humanoid.custom",
				))
			)
		];
		return $packet;
	}

	protected function getAddPlayerPacket() : AddPlayerPacket{
		$item = ItemStackWrapper::legacy(ItemStack::null());
		$abilities = UpdateAbilitiesPacket::create(new AbilitiesData(CommandPermissions::NORMAL, PlayerPermissions::VISITOR, $this->eid, []));
		$packet = AddPlayerPacket::create(
			$this->uuid,
			$this->username,
			$this->eid,
			"",
			$this->position,
			null,
			0,
			0,
			0,
			$item,
			GameMode::ADVENTURE,
			[
				new LongMetadataProperty(
					$this->getCorrectFlag(EntityMetadataFlags::CAN_SHOW_NAMETAG) |
					$this->getCorrectFlag(EntityMetadataFlags::ALWAYS_SHOW_NAMETAG) #rip antibot
				)
			], #metadata
			new PropertySyncData([], []),
			$abilities,
			[],
			"",
			DeviceOS::UNKNOWN
		);
		return $packet;
	}

	protected function getCorrectFlag(int $flag) : int{
		return 1 << $flag;
	}

	public function despawnFromAll() : void{
		$this->spawned = false;
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			$this->despawnFrom($player);
		}
	}

	public function despawnFrom(Player $player) : void{
		$this->spawned = false;
		$packet = new PlayerListPacket;
		$packet->type = PlayerListPacket::TYPE_REMOVE;
		$packet->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$packets = [$packet, $this->getRemoveActorPacket()];

		foreach($packets as $packet){
			if($player->isOnline()){
				$player->getNetworkSession()->sendDataPacket($packet);
			}
		}
	}

	protected function getRemoveActorPacket() : RemoveActorPacket{
		return RemoveActorPacket::create($this->eid);
	}

	public function isSpawned(){
		return $this->spawned;
	}
}
