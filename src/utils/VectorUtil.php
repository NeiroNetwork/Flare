<?php

namespace NeiroNetwork\Flare\utils;

use pocketmine\math\Vector3;

class VectorUtil{

	public static function getDirection3D(float $yaw, float $pitch) : Vector3{
		$y = -sin(deg2rad($pitch));
		$xz = cos(deg2rad($pitch));
		$x = -$xz * sin(deg2rad($yaw));
		$z = $xz * cos(deg2rad($yaw));

		return (new Vector3($x, $y, $z))->normalize();
	}

}
