<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\ICheck;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;

class FlareKickReasons {

	/**
	 * @var bool
	 * 
	 * キック理由を base64 エンコードします。デコードすることで、理由ID、詳細などが確認できます。
	 */
	public static bool $obfuscation = false;

	/**
	 * @var int
	 */
	private static int $TOO_MANY_INPUTS = 0x01;

	/**
	 * @var int
	 */
	private static int $UNFAIR_ADVANTAGE = 0x02;

	private static int $INVALID_CLIENT = 0x03;

	public const PRE_KICK_REASON_INVALID_CLIENT = PlayerPreLoginEvent::KICK_REASON_PLUGIN;

	public static function binaryWriteUTF8(string $utf8): string {
		return $utf8;
	}

	public static function too_many_inputs(int $violations, string $username): string {
		return
			self::$obfuscation
			?
			"§7Reason: §d" . base64_encode(
				Binary::writeInt(self::$TOO_MANY_INPUTS) .
					Binary::writeInt($violations) .
					self::binaryWriteUTF8($username)
			)
			:
			"§7Too many inputs.\n§dID: " . self::$TOO_MANY_INPUTS . " v: $violations";
	}

	public static function unfair_advantage(string $username, ICheck $cause = null): string {
		return
			self::$obfuscation
			?
			"§7Reason: §d" . base64_encode(
				Binary::writeInt(self::$UNFAIR_ADVANTAGE) .
					self::binaryWriteUTF8($username) .
					($cause !== null ? self::binaryWriteUTF8($cause->getFullId()) : "") .
					($cause instanceof BaseCheck ? Binary::writeInt($cause->getVL()) : "") .
					($cause instanceof BaseCheck ? Binary::writeInt($cause->getPunishVL()) : "")
			)
			:
			"§cKicked from server: §lUnfair Advantage";
	}

	public static function invalid_client(string $username): string {
		return
			self::$obfuscation
			?
			"§7Reason: §d" . base64_encode(
				Binary::writeInt(self::$INVALID_CLIENT) .
					self::binaryWriteUTF8($username)
			)
			:
			"§cError.\n§dID: " . self::$INVALID_CLIENT;
	}
}
