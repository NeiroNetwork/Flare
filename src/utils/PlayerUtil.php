<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;

class PlayerUtil{

	public static function hasGenericFlag(EntityMetadataCollection $collection, int $flagId) : ?bool{
		$propertyId = self::getPropertyIdFromGenericFlag($flagId);
		$list = $collection->getAll();
		$flagSetProp = $list[$propertyId] ?? null;
		if($flagSetProp instanceof LongMetadataProperty){
			$flags = $flagSetProp->getValue();
			$contained = ($flags & (1 << $flagId)) !== 0;
			return $contained;
		}else{
			return null;
		}
	}

	public static function getPropertyIdFromGenericFlag(int $flagId){
		$propertyId = $flagId >= 64 ? EntityMetadataProperties::FLAGS2 : EntityMetadataProperties::FLAGS;
		return $propertyId;
	}
}
