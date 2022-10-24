<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\utils\Utils;

class Statistics {

	/**
	 * @param float[] $list
	 * 
	 * @return float
	 */
	public static function standardDeviation(array $list): float {
		return sqrt(self::variance($list));
	}

	/**
	 * @param float[] $list
	 * 
	 * @return float
	 */
	public static function variance(array $list): float {
		$avg = self::average($list);
		$result = 0;
		foreach ($list as $num) {
			$deviation = $num - $avg;
			$result += $deviation ** 2;
		}

		$result /= count($list);
		return $result;
	}


	/**
	 * @param float[] $list
	 * 
	 * @return float
	 */
	public static function average(array $list): float {
		// validate?
		return array_sum($list) / count($list);
	}
}
