<?php

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\support\MoveDelaySupport;
use NeiroNetwork\Flare\utils\Map;
use NeiroNetwork\Flare\utils\PlayerUtil;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;

class ProfileSupport{

	protected PlayerProfile $profile;

	/**
	 * @var Map<int, array>
	 */
	protected Map $predictedMoveDelayCache;

	public function __construct(
		PlayerProfile $profile
	){
		$this->profile = $profile;
		$this->predictedMoveDelayCache = new Map();
	}

	public function update(int $currentTick) : void{
		if(is_null($this->profile->getActorStateProvider()->getAbilities($this->profile->getPlayer()->getId()))){
			$this->profile->getPlayer()->getNetworkSession()->syncAbilities($this->profile->getPlayer());
		}
	}

	public function getMoveDelayPredictedPosition(int $runtimeId) : ?Vector3{
		$currentTick = $this->profile->getServerTick();

		if($this->predictedMoveDelayCache->exists($runtimeId)){
			[$tick, $position] = $this->predictedMoveDelayCache->get($runtimeId);
			/**
			 * @var int     $tick
			 * @var Vector3 $position
			 */

			if($currentTick === $tick){
				return $position;
			}
		}

		$histories = $this->getActorPositionHistory($runtimeId)->getAll();
		$pos = MoveDelaySupport::getInstance()->predict($histories, $currentTick, $this->isPlayer($runtimeId) ? -3 : 0);

		if(is_null($pos)){
			return null;
		}

		$this->predictedMoveDelayCache->put($runtimeId, [$currentTick, $pos]);

		return $pos;
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return Map<int, Vector3>
	 */
	public function getActorPositionHistory(int $runtimeId) : Map{
		$map = $this->profile->getActorStateProvider()->getPositionTickMap()->get($runtimeId);
		return new Map($map?->getAll() ?? []);
	}

	public function isPlayer(int $runtimeId) : ?bool{
		return $this->profile->getActorStateProvider()->getType($runtimeId) === EntityIds::PLAYER;
	}

	public function getLatestTick() : int{
		return $this->profile->isTransactionPairingEnabled() ?
			$this->profile->getTransactionPairing()->getLatestConfirmedTick() : $this->profile->getServerTick();
	}

	public function createVirtualActor(int $runtimeId) : ?VirtualActor{
		$provider = $this->profile->getActorStateProvider();
		$position = $provider->getPosition($runtimeId);
		$motion = $provider->getMotion($runtimeId);
		$properties = $provider->getNetworkProperties($runtimeId);
		$effects = $provider->getEffects($runtimeId);
		$attributes = $provider->getAttributes($runtimeId);
		$abilities = $provider->getAbilities($runtimeId);

		if(
			!is_null($position) &&
			!is_null($motion) &&
			!is_null($properties) &&
			!is_null($effects) &&
			!is_null($attributes)
		){
			return new VirtualActor(
				$position,
				$motion,
				$properties,
				$attributes,
				$effects,
				$abilities
			);
		}

		return null;
	}

	public function getActorMotionHistory(int $runtimeId) : Map{
		$map = $this->profile->getActorStateProvider()->getMotionTickMap()->get($runtimeId);
		return new Map($map?->getAll() ?? []);
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return Map<int, EntityMetadataCollection>
	 */
	public function getActorNetworkPropertiesHistory(int $runtimeId) : Map{
		$map = $this->profile->getActorStateProvider()->getNetworkPropertiesTickMap()->get($runtimeId);
		return new Map($map?->getAll() ?? []);
	}

	public function getActorAttributesHistory(int $runtimeId) : Map{
		$map = $this->profile->getActorStateProvider()->getAttributesTickMap()->get($runtimeId);
		return new Map($map?->getAll() ?? []);
	}

	public function getActorEffectsHistory(int $runtimeId) : Map{
		$map = $this->profile->getActorStateProvider()->getEffectsTickMap()->get($runtimeId);
		return new Map($map?->getAll() ?? []);
	}

	public function checkActorBaseAbility(int $runtimeId, int $boolAbilityId) : ?bool{
		$abilitiesLayer = $this->getActorBaseAbilitiesLayer($runtimeId);

		if(is_null($abilitiesLayer)){
			return null;
		}

		return $abilitiesLayer->getBoolAbilities()[$boolAbilityId];
	}

	public function getActorBaseAbilitiesLayer(int $runtimeId) : ?AbilitiesLayer{
		$abilities = $this->profile->getActorStateProvider()->getAbilities($runtimeId);

		foreach($abilities?->getAbilityLayers() ?? [] as $layer){
			if($layer->getLayerId() === AbilitiesLayer::LAYER_BASE){
				return $layer;
			}
		}

		return null;
	}

	public function checkActorMetadataGenericFlag(int $runtimeId, int $metadataFlag) : ?bool{
		$collection = $this->profile->getActorStateProvider()->getNetworkProperties($runtimeId);
		if(is_null($collection)){
			return null;
		}
		return PlayerUtil::hasGenericFlag($collection, $metadataFlag);
	}

	public function getActorNumericalMetadataProperty(int $runtimeId, int $propertyId) : null|FloatMetadataProperty|IntMetadataProperty{
		$property = $this->getActorMetadataProperty($runtimeId, $propertyId);

		if($property instanceof FloatMetadataProperty || $property instanceof IntMetadataProperty){
			return $property;
		}

		return null;
	}

	public function getActorMetadataProperty(int $runtimeId, int $propertyId) : ?MetadataProperty{
		$collection = $this->profile->getActorStateProvider()->getNetworkProperties($runtimeId);
		if(is_null($collection)){
			return null;
		}

		$metadata = $collection->getAll();
		return $metadata[$propertyId] ?? null;
	}

	public function hasEffect(int $runtimeId, int $effectId) : ?bool{
		$effects = $this->profile->getActorStateProvider()->getEffects($runtimeId) ?? [];
		return isset($effects[$effectId]);
	}

	public function getEffect(int $runtimeId, int $effectId) : ?EffectInstance{
		$effects = $this->profile->getActorStateProvider()->getEffects($runtimeId) ?? [];
		return $effects[$effectId] ?? null;
	}

	public function getMovementSpeedAttribute(int $runtimeId) : ?NetworkAttribute{
		$attributes = $this->profile->getActorStateProvider()->getAttributes($runtimeId) ?? [];
		return $attributes[Attribute::MOVEMENT_SPEED] ?? null;
	}

	public function getBoundingBox(int $runtimeId, ?Vector3 $rpos = null, ?Vector3 $overridePos = null) : ?AxisAlignedBB{
		$size = $this->getSize($runtimeId);
		if(!is_null($size)){
			$pos = $this->getActorPosition($runtimeId);
			$pos ??= $rpos;
			if(!is_null($overridePos)){
				$pos = $overridePos;
			}
			if(is_null($pos)){
				return null;
			}
			return new AxisAlignedBB(
				$pos->x - $size->getWidth() / 2,
				$pos->y,
				$pos->z - $size->getWidth() / 2,
				$pos->x + $size->getWidth() / 2,
				$pos->y + $size->getHeight(),
				$pos->z + $size->getWidth() / 2
			);
		}

		return null;
	}

	public function getSize(int $runtimeId) : ?EntitySizeInfo{
		$collection = $this->profile->getActorStateProvider()->getNetworkProperties($runtimeId);
		$metadata = $collection?->getAll() ?? [];
		$widthProperty = $metadata[EntityMetadataProperties::BOUNDING_BOX_WIDTH] ?? null;
		$heightProperty = $metadata[EntityMetadataProperties::BOUNDING_BOX_HEIGHT] ?? null;
		$scaleProperty = $metadata[EntityMetadataProperties::SCALE] ?? new FloatMetadataProperty(1.0);

		if($widthProperty instanceof FloatMetadataProperty && $heightProperty instanceof FloatMetadataProperty && $scaleProperty instanceof FloatMetadataProperty){
			$scale = $scaleProperty->getValue();
			$width = $widthProperty->getValue();
			$height = $heightProperty->getValue();

			return (new EntitySizeInfo(
				$height,
				$width
			))->scale($scale);
		}

		return null;
	}

	public function getActorPosition(int $runtimeId) : ?Vector3{
		return $this->profile->getActorStateProvider()->getPosition($runtimeId);
	}
}
