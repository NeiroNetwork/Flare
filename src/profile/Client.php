<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;

final class Client{

	private const ID_PATTERNS = [ # :person_bowing:
		DeviceOS::ANDROID => '/^[0-9a-f]{12}4[0-9a-f]{19}$/',
		// UUIDv4 (no hyphen)    // Android
		DeviceOS::IOS => '/^[0-9A-F]{12}4[0-9A-F]{19}$/',
		// UUIDv4 (no hyphen, upper case)    // iOS
		DeviceOS::AMAZON => '/^[0-9a-f]{12}4[0-9a-f]{19}$/',
		// UUIDv4 (no hyphen)    // FireOS (Android)
		DeviceOS::WINDOWS_10 => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
		// UUIDv3    // Windows
		DeviceOS::PLAYSTATION => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
		// UUIDv3    // PlayStation 4
		DeviceOS::NINTENDO => '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
		// UUIDv5    // Nintendo Switch
		DeviceOS::XBOX => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
		// UUIDv3    // Xbox One
	];
	public readonly string $locale;
	public readonly string $clientUuid;
	public readonly int $device;
	public readonly string $model;
	public readonly string $gameVersion;
	public readonly string $deviceId;
	public readonly int $clientRandomId;
	public readonly string $selfSignedId;
	public readonly int $guiScale;
	public readonly string $langCode;
	public readonly string $playfabId;
	public readonly string $serverAddress;
	public readonly string $address;
	public readonly int $uiProfile;
	public readonly string $xuid;
	public readonly bool $xboxLive;
	public readonly string $name;

	public function __construct(PlayerInfo $info, string $address){
		$this->name = $info->getUsername();
		$e = $info->getExtraData();
		$this->locale = $info->getLocale();
		$this->clientUuid = (string) $info->getUuid(); #UuidInterface;
		$this->device = $e["DeviceOS"];
		$this->model = $e["DeviceModel"];
		$this->gameVersion = $e["GameVersion"];
		$this->deviceId = $e["DeviceId"];
		$this->clientRandomId = $e["ClientRandomId"];
		$this->guiScale = $e["GuiScale"];
		$this->langCode = $e["LanguageCode"];
		$this->playfabId = $e["PlayFabId"];
		$this->selfSignedId = $e["SelfSignedId"];
		$this->serverAddress = $e["ServerAddress"];
		$this->address = $address;
		$this->uiProfile = $e["UIProfile"];
		if($info instanceof XboxLivePlayerInfo){
			$this->xuid = $info->getXuid();
			$this->xboxLive = true;
		}else{
			$this->xuid = "unknown";
			$this->xboxLive = false;
		}
	}

	public function getLocale() : string{
		return $this->locale;
	}

	public function getXuid() : string{
		return $this->xuid;
	}

	/**
	 * @param NetworkSession $session
	 *
	 * @return self
	 */
	public static function create(NetworkSession $session) : self{
		return new self($session->getPlayerInfo(), $session->getIp());
	}

	public static function convertDeviceIdToString(int $deviceId) : string{
		return match ($deviceId) {
			DeviceOS::ANDROID => "Android",
			DeviceOS::IOS => "iOS",
			DeviceOS::OSX => "OSX",
			DeviceOS::WINDOWS_10 => "Windows 10 x64",
			DeviceOS::WIN32 => "Windows x86",
			DeviceOS::NINTENDO => "Nintendo Switch",
			DeviceOS::PLAYSTATION => "PlayStation",
			DeviceOS::XBOX => "Xbox",
			DeviceOS::HOLOLENS => "VR: Hololens",
			DeviceOS::GEAR_VR => "VR: Gear VR",
			DeviceOS::WINDOWS_PHONE => "Windows Phone",
			DeviceOS::DEDICATED => "Dedicated",
			DeviceOS::TVOS => "TVOS",
			DeviceOS::AMAZON => "Amazon",
			default => "unknown"
		};
	}

	public function isValid() : bool{
		if($this->isUnknownDevice()){
			return false;
		}

		$didPattern = self::ID_PATTERNS[$this->device] ?? null;
		if($didPattern !== null){
			if(!preg_match($didPattern, $this->deviceId)){
				return false;
			}
		}

		return true;
	}

	public function isUnknownDevice() : bool{
		return
			!$this->isTap() &&
			!$this->isDesktop() &&
			!$this->isController() &&
			!$this->isVR() &&
			!$this->isDedicatedDevice();
	}

	public function isTap() : bool{
		return
			$this->device === DeviceOS::ANDROID ||
			$this->device === DeviceOS::WINDOWS_PHONE ||
			$this->device === DeviceOS::AMAZON ||
			$this->device === DeviceOS::IOS ||
			$this->device === DeviceOS::TVOS;
	}

	public function isDesktop() : bool{
		return
			$this->device === DeviceOS::WIN32 ||
			$this->device === DeviceOS::WINDOWS_10 ||
			$this->device === DeviceOS::OSX;
	}

	public function isController() : bool{
		return
			$this->device === DeviceOS::NINTENDO ||
			$this->device === DeviceOS::PLAYSTATION ||
			$this->device === DeviceOS::XBOX;
	}

	public function isVR() : bool{
		return
			$this->device === DeviceOS::HOLOLENS ||
			$this->device === DeviceOS::GEAR_VR;
	}

	public function isDedicatedDevice() : bool{
		return $this->device === DeviceOS::DEDICATED;
	}

	public function intersects(Client $client) : bool{
		return
			$this->name === $client->getName() ||
			$this->deviceId === $client->getDeviceId() ||
			$this->clientUuid === $client->getClientUuid() ||
			$this->playfabId === $client->getPlayfabId() ||
			#$this->address === $client->getAddress() ||
			($this->xuid === $client->getXuid() && ($client->isXboxLive() || $this->isXboxLive()));
	}

	public function getName() : string{
		return $this->name;
	}

	public function getDeviceId() : string{
		return $this->deviceId;
	}

	public function getClientUuid() : string{
		return $this->clientUuid;
	}

	public function getPlayfabId() : string{
		return $this->playfabId;
	}

	public function isXboxLive() : bool{
		return $this->xboxLive;
	}

	public function equal(Client $client) : bool{
		return
			$this->name === $client->getName() &&
			$this->deviceId === $client->getDeviceId() &&
			$this->clientUuid === $client->getClientUuid() &&
			$this->playfabId === $client->getPlayfabId() &&
			#$this->address === $client->getAddress() &&
			($this->xuid === $client->getXuid() && ($client->isXboxLive() || $this->isXboxLive()));
	}

	public function getDevice() : int{
		return $this->device;
	}

	public function getModel() : string{
		return $this->model;
	}

	public function getGameVersion() : string{
		return $this->gameVersion;
	}

	/**
	 * @return int
	 *
	 * warning: この値は信用できません！BANデータなどに使用しないでください
	 */
	public function getClientRandomId() : int{
		return $this->clientRandomId;
	}

	public function getGuiScale() : int{
		return $this->guiScale;
	}

	public function getLangCode() : string{
		return $this->langCode;
	}

	public function getServerAddress() : string{
		return $this->serverAddress;
	}

	public function getAddress() : string{
		return $this->address;
	}

	public function getUiProfile() : int{
		return $this->uiProfile;
	}

	/**
	 * @return string
	 *
	 * note: 一定時間ごとに変化する
	 */
	public function getSelfSignedId() : string{
		return $this->selfSignedId;
	}
}
