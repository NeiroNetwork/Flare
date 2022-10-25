<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\utils\Utils as PMUtils;

class Utils {

	public static function mustStartedException(): void {
		throw new \Exception("must not be called before started");
	}

	public static function getEnumName(string $enumClass, int $id): ?string {
		$ref = new \ReflectionClass($enumClass);
		foreach ($ref->getReflectionConstants() as $const) {
			if ($const->getValue() === $id) {
				return $const->getName();
			}
		}

		return null;
	}

	public static function getNiceName(string $name): string {
		return ucwords(strtolower(join(" ", explode("_", $name))));
	}

	public static function resolveOnOffInputFlags(int $inputFlags, int $startFlag, int $stopFlag): ?bool {
		$enabled = ($inputFlags & (1 << $startFlag)) !== 0;
		$disabled = ($inputFlags & (1 << $stopFlag)) !== 0;
		if ($enabled !== $disabled) {
			return $enabled;
		}
		//neither flag was set, or both were set
		return null;
	}
}
