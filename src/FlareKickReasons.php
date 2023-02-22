<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\ICheck;
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
			"§7Too many inputs.\nID: " . self::$TOO_MANY_INPUTS . " v: $violations";
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
			Flare::PREFIX . "§cKicked from server: §lUnfair Advantage";
	}
}
