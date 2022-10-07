<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\math\Vector3;

class MinecraftPhysics {

	/**
	 * Entity.java
	 * EntityLivingBase.java
	 */

	public function __construct(
		protected Vector3 $pos,
	) {
	}

	public static function nextFreefallVelocity(Vector3 $velocity): Vector3 {
		$v = clone $velocity;
		$v->y -= 0.08; // どうやら、-0.08を先にやっているみたい
		// EntityLivingBase.java #1677
		return self::applyAirFriction($v);
	}

	public static function nextAirXZ(float $xz): float {
		return $xz * 0.91;
	}

	public static function previousAirXZ(float $xz): float {
		return $xz / 0.91;
	}

	public static function applyAirFriction(Vector3 $velocity) {
		$v = clone $velocity;
		$v->y *= 0.980000012;
		$v->x *= 0.91;
		$v->z *= 0.91;
		return $v;
	}
}
