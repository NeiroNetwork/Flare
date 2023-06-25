<?php

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\utils\Map;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\utils\ObjectSet;

interface ActorStateProvider{

	public function getPosition(int $runtimeId) : ?Vector3;

	public function getType(int $runtimeId) : ?string;

	/**
	 * @return Map<int, string>
	 */
	public function getTypeMap() : Map;

	/**
	 * @return Map<int, Vector3>
	 */
	public function getPositionMap() : Map;

	/**
	 * @return Map<int, Map<int, Vector3>>
	 */
	public function getPositionTickMap() : Map;

	public function getNetworkProperties(int $runtimeId) : ?EntityMetadataCollection;

	/**
	 * @return Map<int, EntityMetadataCollection>
	 */
	public function getNetworkPropertiesMap() : Map;

	/**
	 * @return Map<int, Map<int, EntityMetadataCollection>>
	 */
	public function getNetworkPropertiesTickMap() : Map;


	public function getMotion(int $runtimeId) : ?Vector3;

	/**
	 * @return Map<int, Vector3>
	 */
	public function getMotionMap() : Map;

	/**
	 * @return Map<int, Map<int, Vector3>>
	 */
	public function getMotionTickMap() : Map;

	/**
	 * @param int $runtimeId
	 *
	 * @return array<string, NetworkAttribute>|null
	 */
	public function getAttributes(int $runtimeId) : ?array;

	/**
	 * @return Map<int, array<string, NetworkAttribute>>
	 */
	public function getAttributesMap() : Map;

	/**
	 * @return Map<int, Map<int, array<string, NetworkAttribute>>>
	 */
	public function getAttributesTickMap() : Map;

	/**
	 * @param int $runtimeId
	 *
	 * @return array<int, EffectInstance>|null
	 */
	public function getEffects(int $runtimeId) : ?array;

	/**
	 * @return Map<int, array<int, EffectInstance>>
	 */
	public function getEffectsMap() : Map;

	/**
	 * @return Map<int, Map<int, array<int, EffectInstance>>>
	 */
	public function getEffectsTickMap() : Map;

	/**
	 * @param int $playerRuntimeId
	 *
	 * @return AbilitiesData|null
	 */
	public function getAbilities(int $playerRuntimeId) : ?AbilitiesData;

	/**
	 * @return Map<int, AbilitiesData>
	 */
	public function getAbilitiesMap() : Map;

	/**
	 * @return Map<int, Map<int, AbilitiesData>>
	 */
	public function getAbilitiesTickMap() : Map;

	public function dispose() : void;

	public function getOnMotionHooks() : ObjectSet;
}
