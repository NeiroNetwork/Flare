<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use NeiroNetwork\Flare\math\EntityRotation;
use pocketmine\math\Vector3;

final class ProfileData {

	public static function autoPropertyValue(object $obj): void {
		$ref = new \ReflectionClass($obj);
		foreach ($ref->getProperties() as $prop) {
			$prop->setAccessible(true);
			$name = $prop->getType()->getName();
			if ($name === Vector3::class) {
				$prop->setValue($obj, Vector3::zero());
			} elseif ($name === ActionRecord::class) {
				$prop->setValue($obj, new ActionRecord);
			} elseif ($name === InstantActionRecord::class) {
				$prop->setValue($obj, new InstantActionRecord);
			} elseif ($name === EntityRotation::class) {
				$prop->setValue($obj, EntityRotation::create(0, 0));
			} elseif ($name === "bool") {
				$prop->setValue($obj, false);
			} elseif ($name === "array") {
				$prop->setValue($obj, []);
			} elseif ($name === "float" || $name === "int") {
				$prop->setValue($obj, 0);
			}
		}
	}
}
