<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\utils\Utils;
use SplFixedArray;

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
		return array_sum($list) / count($list);
	}

	public static function duplicates(array $list): int {
		return count($list) - count(array_unique($list, SORT_NUMERIC));
	}

	/**
	 * @param float[] $sortedList
	 *
	 * @return float
	 */
	protected static function getMedian(array $sortedList): float {
		$size = count($sortedList);

		$list = array_values($sortedList); // キーは保証する

		if ($size % 2 === 0) {
			return ($list[$size / 2] + $list[$size / 2 - 1]) / 2;
		} else {
			return $list[(int) floor($size / 2)];
		}
	}

	public static function median(array $list): float {
		sort($list, SORT_NUMERIC);
		return self::getMedian($list);
	}

	/**
	 * @param float[] $list
	 *
	 * @return float
	 *
	 * @see https://en.wikipedia.org/wiki/Skewness
	 */
	public static function skewness(array $list): float {
		$sum = array_sum($list);
		$count = count($list);

		$average = $sum / $count;
		$median = self::median($list);
		$variance = self::variance($list);

		if (Math::equals($variance, 0.)) {
			return 0.;
		}

		return 3 * ($average - $median) / $variance;
	}

	/**
	 * @param float[] $list
	 *
	 * @return OutliersResult
	 *
	 * @see https://en.wikipedia.org/wiki/Outlier
	 */
	public static function outliers(array $list): OutliersResult {
		$result = new OutliersResult;

		$values = array_values($list);
		$size = count($values);

		$q1r = $values;
		array_splice($q1r, 0, (int) ($size / 2));

		$q3r = $values;
		array_splice($q3r, (int) ($size / 2), $size);

		$q1 = self::median($q1r);
		$q3 = self::median($q3r);

		$iqr = abs($q1 - $q3);

		$lowThreshold = $q1 - 1.5 * $iqr;
		$highThreshold = $q3 - 1.5 * $iqr;

		foreach ($values as $n) {
			if ($n < $lowThreshold) {
				$result->low[] = $n;
			} elseif ($n > $highThreshold) {
				$result->high[] = $n;
			}
		}

		return $result;
	}

	/**
	 * @param float[] $list
	 *
	 * @return float
	 *
	 * @see https://en.wikipedia.org/wiki/Kurtosis
	 */
	public static function kurtosis(array $list): float {
		$sum = array_sum($list);
		$count = count($list);

		if ($count < 3) {
			return 0.;
		}

		if ($sum <= 0) {
			return 0.;
		}

		$efficiencyFirst = $count * ($count + 1) / (($count - 1) * ($count - 2) * ($count - 3));
		$efficiencySecond = 3.0 * (($count - 1) ** 2) / (($count - 2) * ($count - 3));

		$average = $sum / $count;

		$advanceVariance = 0.0;
		$advanceVarianceSquared = 0.0;

		foreach ($list as $n) {
			$advanceVariance += ($average - $n) ** 2;
			$advanceVarianceSquared += ($average - $n) ** 4;
		}

		if (Math::equals($advanceVariance, 0.)) {
			return -$efficiencySecond;
		}

		return $efficiencyFirst * ($advanceVarianceSquared / (($advanceVariance / $sum) ** 2)) - $efficiencySecond;
	}
}
