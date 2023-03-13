<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use Ramsey\Uuid\Rfc4122\Validator;
use Ramsey\Uuid\Validator\GenericValidator;

final class Client {

	private const ID_PATTERNS = [ # :person_bowing:
		DeviceOS::ANDROID => '/^[0-9a-f]{12}4[0-9a-f]{19}$/',    // UUIDv4 (no hyphen)    // Android
		DeviceOS::IOS => '/^[0-9A-F]{12}4[0-9A-F]{19}$/',    // UUIDv4 (no hyphen, upper case)    // iOS
		DeviceOS::AMAZON => '/^[0-9a-f]{12}4[0-9a-f]{19}$/',    // UUIDv4 (no hyphen)    // FireOS (Android)
		DeviceOS::WINDOWS_10 => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',    // UUIDv3    // Windows
		DeviceOS::PLAYSTATION => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',    // UUIDv3    // PlayStation 4
		DeviceOS::NINTENDO => '/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',    // UUIDv5    // Nintendo Switch
		DeviceOS::XBOX => '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',    // UUIDv3    // Xbox One
	];

	private string $name;
	private string $locale;
	private string $clientUuid;
	private int $device;
	private string $model;
	private string $gameVersion;
	private string $deviceId;
	private int $clientId;
	private int $guiScale;
	private string $langCode;
	private string $playfabId;
	private string $serverAddress;
	private string $address;
	private int $uiProfile;
	private string $xuid;

	private bool $xboxLive;

	/**
	 * @param NetworkSession $session
	 * 
	 * @return self
	 */
	public static function create(NetworkSession $session): self {
		return new self($session->getPlayerInfo(), $session->getIp());
	}

	public function isValid(): bool {
		if ($this->isUnknownDevice()) return false;

		$didPattern = self::ID_PATTERNS[$this->device] ?? null;
		if ($didPattern !== null) {
			if (!preg_match($didPattern, $this->deviceId)) {
				return false;
			}
		}

		return true;
	}

	public function __construct(PlayerInfo $info, string $address) {
		$this->name = $info->getUsername();
		$e = $info->getExtraData();
		$this->locale = $info->getLocale();
		$this->clientUuid = (string) $info->getUuid(); #UuidInterface;
		$this->device = $e["DeviceOS"];
		$this->model = $e["DeviceModel"];
		$this->gameVersion = $e["GameVersion"];
		$this->deviceId = $e["DeviceId"];
		$this->clientId = $e["ClientRandomId"];
		$this->guiScale = $e["GuiScale"];
		$this->langCode = $e["LanguageCode"];
		$this->playfabId = $e["PlayFabId"];
		$this->serverAddress = $e["ServerAddress"];
		$this->address = $address;
		$this->uiProfile = $e["UIProfile"];
		$this->xuid = "unknown";
		if ($info instanceof XboxLivePlayerInfo) {
			$this->xuid = $info->getXuid();
			$this->xboxLive = true;
		} else {
			$this->xboxLive = false;
		}
	}

	public function isXboxLive(): bool {
		return $this->xboxLive;
	}

	public static function convertDeviceIdToString(int $deviceId) {
		$string = match ($deviceId) {
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
		return $string;
	}

	public function intersects(Client $client): bool {
		return
			$this->name === $client->getName() ||
			$this->deviceId === $client->getDeviceId() ||
			$this->clientId === $client->getClientId() ||
			$this->clientUuid === $client->getClientUuid() ||
			$this->playfabId === $client->getPlayfabId() ||
			#$this->address === $client->getAddress() ||
			($this->xuid === $client->getXuid() && ($client->isXboxLive() || $this->isXboxLive()));
	}

	public function equal(Client $client): bool {
		return
			$this->name === $client->getName() &&
			$this->deviceId === $client->getDeviceId() &&
			$this->clientId === $client->getClientId() &&
			$this->clientUuid === $client->getClientUuid() &&
			$this->playfabId === $client->getPlayfabId() &&
			#$this->address === $client->getAddress() &&
			($this->xuid === $client->getXuid() && ($client->isXboxLive() || $this->isXboxLive()));
	}

	public function getName(): string {
		return $this->name;
	}

	public function getLocale(): string {
		return $this->locale;
	}

	public function getClientUuid(): string {
		return $this->clientUuid;
	}

	public function getDevice(): int {
		return $this->device;
	}

	public function getModel(): string {
		return $this->model;
	}

	public function getGameVersion(): string {
		return $this->gameVersion;
	}

	public function getDeviceId(): string {
		return $this->deviceId;
	}

	public function getClientId(): int {
		return $this->clientId;
	}

	public function getGuiScale(): int {
		return $this->guiScale;
	}

	public function getLangCode(): string {
		return $this->langCode;
	}

	public function getPlayfabId(): string {
		return $this->playfabId;
	}

	public function getServerAddress(): string {
		return $this->serverAddress;
	}

	public function getAddress(): string {
		return $this->address;
	}

	public function getUiProfile(): int {
		return $this->uiProfile;
	}

	public function getXuid(): string {
		return $this->xuid;
	}

	public function isTap() {
		return
			$this->device === DeviceOS::ANDROID ||
			$this->device === DeviceOS::WINDOWS_PHONE ||
			$this->device === DeviceOS::AMAZON ||
			$this->device === DeviceOS::IOS ||
			$this->device === DeviceOS::TVOS;
	}

	public function isDesktop() {
		return
			$this->device === DeviceOS::WIN32 ||
			$this->device === DeviceOS::WINDOWS_10 ||
			$this->device === DeviceOS::OSX;
	}

	public function isController() {
		return
			$this->device === DeviceOS::NINTENDO ||
			$this->device === DeviceOS::PLAYSTATION ||
			$this->device === DeviceOS::XBOX;
	}

	public function isVR() {
		return
			$this->device === DeviceOS::HOLOLENS ||
			$this->device === DeviceOS::GEAR_VR;
	}

	public function isDedicatedDevice() {
		return $this->device === DeviceOS::DEDICATED;
	}

	public function isUnknownDevice() {
		return
			!$this->isTap() &&
			!$this->isDesktop() &&
			!$this->isController() &&
			!$this->isVR() &&
			!$this->isDedicatedDevice();
	}
}
