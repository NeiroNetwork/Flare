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
		return $r;
	}

	public function add(EntityRotation $r2): self {
		$r = clone $this;
		return $r->rotate($r2->yaw, $r2->pitch, $r2->headYaw);
	}

	public function subtract(EntityRotation $r2): self {
		$r = clone $this;
		return $r->rotate(-$r2->yaw, -$r2->pitch, -$r2->headYaw);
	}

	public function abs(): self {
		return new EntityRotation(abs($this->yaw), abs($this->pitch), abs($this->headYaw));
	}

	public function diff(EntityRotation $b, float $maxDiff = 180): self {
		$a = clone $this;
		$b = clone $b;
		$yawDiff = self::rotationDiff($a->yaw, $b->yaw);
		$pitchDiff = $a->pitch - $b->pitch;
		$headYawDiff = self::rotationDiff($a->headYaw, $b->headYaw);

		return new EntityRotation($yawDiff, $pitchDiff, $headYawDiff);
	}

	public static function rotationDiff(float $a, float $b, float $maxDiff = 180) {
		$diff = $a - $b;
		if ($diff > $maxDiff) {
			$b += 360;
		} elseif ($diff < -$maxDiff) {
			$a += 360;
		}

		$diff = $a - $b;

		return $diff;
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
