<?php

declare(strict_types=1);

class Math {

	public static function getGCD(float $a, float $b, int $depth = 0): float {
		if ($a == 0 || $depth > 300) {
			return $b;
		}

		$quotient = self::getIntQuotient($b, $a);
		$remainder = (($b / $a) - $quotient) * $a;
		if (abs($remainder) < max($a, $b) * 1E-3) {
			$remainder = 0;
		}

		return self::getGCD($remainder, $a, $depth + 1);
	}

	public static function getArrayGCD(float $base, array $arr): float {
		$result = $base;

		foreach ($arr as $a) {
			$result = self::getGCD($a, $result);
			if ($result < 1E-7) {
				return 0;
			}
		}

		return $result;
	}

	public static function getIntQuotient(float $dividend, float $divisor) {
		$ans = $dividend / $divisor;
		$error = max($dividend, $divisor) * 1E-3;
		return (int) ($ans + $error);
	}
}
