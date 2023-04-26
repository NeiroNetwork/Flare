<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class Math{

	public static function getArrayGCD(float $base, array $arr) : float{
		$result = $base;

		foreach($arr as $a){
			$result = self::getGCD($a, $result);
			if($result < 1E-7){
				return 0;
			}
		}

		return $result;
	}

	public static function getGCD(float $a, float $b, int $depth = 0) : float{
		if($a == 0 || $depth > 300){
			return $b;
		}

		$quotient = self::getIntQuotient($b, $a);
		$remainder = (($b / $a) - $quotient) * $a;
		if(abs($remainder) < max($a, $b) * 1E-3){
			$remainder = 0;
		}

		return self::getGCD($remainder, $a, $depth + 1);
	}

	public static function getIntQuotient(float $dividend, float $divisor){
		$ans = $dividend / $divisor;
		$error = max($dividend, $divisor) * 1E-3;
		return (int) ($ans + $error);
	}

	public static function distanceBoundingBox(AxisAlignedBB $bb, Vector3 $point) : float{
		return sqrt(self::distanceSquaredBoundingBox($bb, $point));
	}

	public static function distanceSquaredBoundingBox(AxisAlignedBB $bb, Vector3 $point) : float{
		$distX = max($bb->minX - $point->x, max(0, $point->x - $bb->maxX));
		$distY = max($bb->minY - $point->y, max(0, $point->y - $bb->maxY));
		$distZ = max($bb->minZ - $point->z, max(0, $point->z - $bb->maxZ));
		return ($distX ** 2 + $distY ** 2 + $distZ ** 2);
	}

	public static function getPracticalDistanceCalcError(AxisAlignedBB $bb) : float{
		return $bb->getAverageEdgeLength() / 2;
	}

	public static function equals(float $a, float $b, float $epsilon = 0.00000001) : bool{
		return abs($a - $b) <= $epsilon;
	}
}
