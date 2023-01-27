<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils as PMUtils;
use NeiroNetwork\WaterdogPEAccepter\api\WdpePlayer;

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

	public static function getPing(Player $player): int {
		if (Server::getInstance()->getPluginManager()->getPlugin("WaterdogPEAccepter") !== null) {
			return (int) WdpePlayer::getRespondTime($player);
		} else {
			return $player->getNetworkSession()->getPing();
		}
	}

	public static function getTime(): float {
		return hrtime(true) / 1e+9;
	}

	public static function getTimeMilis(): float {
		return hrtime(true) / 1e+6;
	}

	public static function equalsArrayValues(array $target, mixed $value) {
		foreach ($target as $targetValue) {
			if ($value != $targetValue) {
				return false;
			}
		}

		return true;
	}

	public static function findAscending(array $arr, int $key): mixed {
		$results = array_filter($arr, function ($v) use ($key) {
			return $v <= $key;
		});

		if (count($results) > 0) {
			return max($results);
		}

		return null;
	}

	public static function findDecending(array $arr, int $key): mixed {
		$results = array_filter($arr, function ($v) use ($key) {
			return $v >= $key;
		});

		if (count($results) > 0) {
			return min($results);
		}

		return null;
	}

	public static function findArrayRange(array $arr, int $key, int $range): array {
		$min = $key - $range;
		$max = $key + $range;

		$result = array_filter($arr, function ($v) use ($min, $max) {
			return $v >= $min && $v <= $max;
		});

		return $result;
	}
}
