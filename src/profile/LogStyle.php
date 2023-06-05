<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\profile\check\FailReason;
use NeiroNetwork\Flare\profile\check\ICheck;

abstract class LogStyle{

	/**
	 * @var LogStyle[]
	 */
	private static array $registered = [];

	/**
	 * @return LogStyle[]
	 */
	public static function getAllRegistered() : array{
		return array_values(self::$registered);
	}

	public static function register(LogStyle $style) : void{
		self::$registered[$style::class] = $style;
	}

	public static function search(string $needle) : ?LogStyle{
		$needle = strtolower($needle);
		foreach(self::$registered as $style){
			if(in_array($needle, array_map("strtolower", $style->getAliases()), true)){
				return $style;
			}
		}

		return null;
	}

	/**
	 * @return string[]
	 */
	abstract public function getAliases() : array;

	/**
	 * @param Profile    $profile
	 * @param Profile    $viewer
	 * @param ICheck     $cause
	 * @param FailReason $reason
	 *
	 * @return string
	 */
	abstract public function fail(Profile $profile, Profile $viewer, ICheck $cause, FailReason $reason) : string;
}
