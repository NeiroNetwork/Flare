<?php

namespace NeiroNetwork\Flare\profile;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;

class VirtualActor{

	/**
	 * @param Vector3                         $position
	 * @param Vector3                         $motion
	 * @param EntityMetadataCollection        $networkProperties
	 * @param array<string, NetworkAttribute> $attributes
	 * @param array<int, EffectInstance>      $effects
	 * @param AbilitiesData|null              $abilities
	 */
	public function __construct(
		protected Vector3 $position,
		protected Vector3 $motion,
		protected EntityMetadataCollection $networkProperties,
		protected array $attributes,
		protected array $effects,
		protected ?AbilitiesData $abilities
	){}

	/**
	 * @return Vector3
	 */
	public function getPosition() : Vector3{
		return $this->position;
	}

	/**
	 * @return Vector3
	 */
	public function getMotion() : Vector3{
		return $this->motion;
	}

	/**
	 * @return EntityMetadataCollection
	 */
	public function getNetworkProperties() : EntityMetadataCollection{
		return $this->networkProperties;
	}

	/**
	 * @return array<string, NetworkAttribute>
	 */
	public function getAttributes() : array{
		return $this->attributes;
	}

	/**
	 * @return array<int, EffectInstance>
	 */
	public function getEffects() : array{
		return $this->effects;
	}

	/**
	 * @return AbilitiesData|null
	 */
	public function getAbilities() : ?AbilitiesData{
		return $this->abilities;
	}

}
