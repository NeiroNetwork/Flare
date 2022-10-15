<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;
use pocketmine\utils\Utils;

abstract class LogStyle {

	/**
	 * @var LogStyle[]
	 */
	private static array $registered = [];

	public static function register(LogStyle $style): void {
		self::$registered[$style::class] = $style;
	}

	public static function search(string $needle): ?LogStyle {
		$needle = strtolower($needle);
		foreach (self::$registered as $style) {
			if (in_array($needle, array_map("strtolower", $style->getAliases()), true)) {
				return $style;
			}
		}

		return null;
	}

	/**
	 * @param Profile $profile
	 * @param ICheck $cause
	 * @param FailReason $reason
	 * 
	 * @return string
	 */
	abstract public function fail(Profile $profile, ICheck $cause, FailReason $reason): string;

	/**
	 * @return string[]
	 */
	abstract public function getAliases(): array;
}
