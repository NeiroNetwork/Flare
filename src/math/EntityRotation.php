<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\math;

use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\math\Vector2;

class EntityRotation {

	public static function create(float $yaw, float $pitch, ?float $headYaw = null): self {
		return new self($yaw, $pitch, $headYaw ?? $yaw);
	}

	public static function from(Vector2|Location $rotVector): self {
		if ($rotVector instanceof Location) {
			return self::create($rotVector->yaw, $rotVector->pitch);
		} else {
			return self::create($rotVector->x, $rotVector->y);
		}
	}

	public static function fromEntity(Entity $entity): self {
		return self::from($entity->getLocation());
	}

	public function __construct(
		public float $yaw,
		public float $pitch,
		public float $headYaw
	) {
	}

	public function rotate(float $yaw, float $pitch, ?float $headYaw = null): self {
		$r = clone $this;
		$r->yaw += $yaw;
		$r->pitch = $pitch;
		$r->headYaw += $headYaw ?? $yaw;
		self::check($r);
		return $r;
	}

	public function add(EntityRotation $r2): self {
		$r = clone $this;
		$r->rotate($r2->yaw, $r2->pitch, $r2->headYaw);
		self::check($r);
		return $r;
	}

	public function subtract(EntityRotation $r2): self {
		$r = clone $this;
		$r->rotate(-$r2->yaw, -$r2->pitch, -$r2->headYaw);
		self::check($r);
		return $r;
	}

	public static function check(EntityRotation $rot): void {
		if ($rot->yaw < 0) {
			$rot->yaw += 360;
		}

		if ($rot->yaw > 360) {
			$rot->yaw -= 360;
		}

		if ($rot->headYaw < 0) {
			$rot->headYaw += 360;
		}

		if ($rot->headYaw > 360) {
			$rot->headYaw -= 360;
		}

		if ($rot->pitch > 90) {
			$rot->pitch -= 180;
		}

		if ($rot->pitch < -90) {
			$rot->pitch += 180;
		}
	}
}
