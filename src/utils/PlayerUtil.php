<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\player\Player;

class PlayerUtil {

	public static function getMetadata(Player $player) {
		return $player->getNetworkProperties();
	}

	public static function isGenericFlag(Player $player, int $flagId): ?bool {
		$metadata = self::getMetadata($player);
		$propertyId = self::getPropertyIdFromGenericFlag($flagId);
		$list = $metadata->getAll();
		$flagSetProp = $list[$propertyId] ?? null;
		if ($flagSetProp instanceof LongMetadataProperty) {
			$flags = $flagSetProp->getValue();
			$contained = ($flags & (1 << $flagId)) !== 0;
			return $contained;
		} else {
			return null;
		}
	}

	public static function getPropertyIdFromGenericFlag(int $flagId) {
		$propertyId = $flagId >= 64 ? EntityMetadataProperties::FLAGS2 : EntityMetadataProperties::FLAGS;
		return $propertyId;
	}

	public static function getEyeHeight(Player $player): float {
		return $player->getEyeHeight() + ($player->isSneaking() ? -0.15 : 0.0);
	}

	public static function getRealEyePos(Player $player): Vector3 {
		return $player->getPosition()->add(0, self::getEyeHeight($player), 0);
	}
}
