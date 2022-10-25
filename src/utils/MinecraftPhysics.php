<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\math\Vector3;
use pocketmine\player\Player;

class MinecraftPhysics {

	const FRICTION_AIR = 0.02;
	const FRICTION_GROUND = 0.2;

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

	public static function previousFreefallVelocity(Vector3 $velocity): Vector3 {
		$v = clone $velocity;
		$v = self::revertAirFriction($v);
		$v->y -= 0.08;
		return $v;
	}

	public static function nextAirXZ(float $xz): float {
		return $xz * 0.91;
	}

	public static function previousAirXZ(float $xz): float {
		return $xz / 0.91;
	}

	public static function applyAirFriction(Vector3 $velocity) {
		$v = clone $velocity;
		$v->y *= 0.9800000116229;
		$v->x *= 0.91;
		$v->z *= 0.91;
		return $v;
	}

	public static function revertAirFriction(Vector3 $velocity): Vector3 {
		$v = clone $velocity;
		$v->y /= 0.9800000116229;
		$v->x /= 0.91;
		$v->z /= 0.91;
		return $v;
	}

	public static function moveDistancePerTick(Player $player): float {
		$base = $player->getMovementSpeed() * ($player->isSneaking() ? 0.3 : 1.0) * 10;
		return $base * 4.317 / 20;
	}

	public static function moveFlying(float $forward, float $strafe, float $yaw, float $friction) {
		$f = $strafe ** 2 + $forward ** 2;
		if ($f >= 1.0E-4) { # Entity.java EnttyLivingBase.java
			$f = sqrt($f);

			if ($f < 1.0) {
				$f = 1.0;
			}

			$f = $friction / $f;

			$strafe = $strafe * $f;
			$forward = $forward * $f;

			$f1 = sin($yaw * (M_PI / 180));
			$f2 = cos($yaw * (M_PI / 180));

			return new Vector3($strafe * $f2 - $forward * $f1, 0, $forward * $f2 + $strafe * $f1);
		}
		return new Vector3(0, 0, 0);
	}
}
