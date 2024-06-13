<?php

namespace NeiroNetwork\Flare\profile;

use Closure;
use NeiroNetwork\Flare\utils\IntegerSortSizeMap;
use NeiroNetwork\Flare\utils\Map;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\utils\ObjectSet;

abstract class PacketBaseActorStateProvider implements ActorStateProvider{

	/**
	 * @var Map<int, Vector3>
	 */
	protected Map $position;

	protected Map $type;

	/**
	 * @var Map<int, IntegerSortSizeMap<Vector3>>
	 */
	protected Map $tickPosition;

	/**
	 * @var Map<int, EntityMetadataCollection>
	 */
	protected Map $networkProperties;

	/**
	 * @var Map<int, IntegerSortSizeMap<EntityMetadataCollection>>
	 */
	protected Map $tickNetworkProperties;

	/**
	 * @var Map<int, Vector3>
	 */
	protected Map $motion;

	/**
	 * @var Map<int, IntegerSortSizeMap<Vector3>>
	 */
	protected Map $tickMotion;

	/**
	 * @var Map<int, array<string, NetworkAttribute>>
	 */
	protected Map $attributes;

	/**
	 * @var Map<int, IntegerSortSizeMap<array<string, NetworkAttribute>>>
	 */
	protected Map $tickAttributes;

	/**
	 * @var Map<int, array<int, EffectInstance>>
	 */
	protected Map $effects;

	/**
	 * @var Map<int, IntegerSortSizeMap<array<int, EffectInstance>>>
	 */
	protected Map $tickEffects;

	/**
	 * @var Map<int, AbilitiesData>
	 */
	protected Map $abilities;

	/**
	 * @var Map<int, IntegerSortSizeMap<AbilitiesData>>
	 */
	protected Map $tickAbilities;

	protected int $tickMapSize;

	/**
	 * @var ObjectSet<Closure(int $runtimeId, int $tick, Vector3 $motion): void>
	 */
	protected ObjectSet $onMotionHooks;

	public function __construct(int $tickMapSize){
		$this->position = new Map();
		$this->tickPosition = new Map();
		$this->networkProperties = new Map();
		$this->type = new Map();
		$this->tickNetworkProperties = new Map();
		$this->motion = new Map();
		$this->tickMotion = new Map();
		$this->attributes = new Map();
		$this->tickAttributes = new Map();
		$this->effects = new Map();
		$this->tickEffects = new Map();
		$this->abilities = new Map();
		$this->tickAbilities = new Map();
		$this->onMotionHooks = new ObjectSet();

		$this->tickMapSize = $tickMapSize;
	}

	/**
	 * @return ObjectSet<Closure(int $runtimeId, int $tick, Vector3 $motion): void>
	 */
	public function getOnMotionHooks() : ObjectSet{
		return $this->onMotionHooks;
	}

	public function getMotion(int $runtimeId) : ?Vector3{
		return $this->motion->get($runtimeId);
	}

	public function getPosition(int $runtimeId) : ?Vector3{
		return $this->position->get($runtimeId);
	}

	public function getNetworkProperties(int $runtimeId) : ?EntityMetadataCollection{
		return $this->networkProperties->get($runtimeId);
	}

	public function handleRemoveActor(RemoveActorPacket $packet, int $tick) : void{
		$this->motion->remove($packet->actorUniqueId);
		$this->position->remove($packet->actorUniqueId);
		$this->type->remove($packet->actorUniqueId);
		$this->networkProperties->remove($packet->actorUniqueId);
		$this->attributes->remove($packet->actorUniqueId);
		$this->effects->remove($packet->actorUniqueId);
		$this->abilities->remove($packet->actorUniqueId);
		$this->tickPosition->get($packet->actorUniqueId)?->clear();
		$this->tickMotion->get($packet->actorUniqueId)?->clear();
		$this->tickAbilities->get($packet->actorUniqueId)?->clear();
		$this->tickAttributes->get($packet->actorUniqueId)?->clear();
		$this->tickEffects->get($packet->actorUniqueId)?->clear();
		$this->tickNetworkProperties->get($packet->actorUniqueId)?->clear();
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return array<string, NetworkAttribute>|null
	 */
	public function getAttributes(int $runtimeId) : ?array{
		return $this->attributes->get($runtimeId);
	}

	public function getPositionMap() : Map{
		return new Map($this->position->getAll());
	}

	public function getTypeMap() : Map{
		return new Map($this->type->getAll());
	}

	public function getNetworkPropertiesMap() : Map{
		return new Map($this->networkProperties->getAll());
	}

	public function getMotionMap() : Map{
		return new Map($this->motion->getAll());
	}

	public function getPositionTickMap() : Map{
		return new Map($this->tickPosition->getAll());
	}

	public function getNetworkPropertiesTickMap() : Map{
		return new Map($this->tickNetworkProperties->getAll());
	}

	public function getMotionTickMap() : Map{
		return new Map($this->tickMotion->getAll());
	}

	public function getAttributesMap() : Map{
		return new Map($this->attributes->getAll());
	}

	public function getAttributesTickMap() : Map{
		return new Map($this->tickAttributes->getAll());
	}

	public function getEffects(int $runtimeId) : ?array{
		return $this->effects->get($runtimeId);
	}

	public function getEffectsMap() : Map{
		return new Map($this->effects->getAll());
	}

	public function getEffectsTickMap() : Map{
		return new Map($this->tickEffects->getAll());
	}

	public function getAbilities(int $playerRuntimeId) : ?AbilitiesData{
		return $this->abilities->get($playerRuntimeId);
	}

	public function getAbilitiesMap() : Map{
		return new Map($this->abilities->getAll());
	}

	public function getAbilitiesTickMap() : Map{
		return new Map($this->tickAbilities->getAll());
	}

	public function handleAddActor(AddActorPacket $packet, int $tick) : void{
		$this->type->putIfAbsent($packet->actorRuntimeId, $packet->type);

		$this->internalSetActorPosition($packet->actorRuntimeId, $packet->position, $tick);
		if($packet->motion !== null){
			$this->internalSetActorMotion($packet->actorRuntimeId, $packet->motion, $tick);
		}
	}

	protected function internalSetActorPosition(int $actorId, Vector3 $position, int $tick, bool $adjustPosition = false) : void{
		$pos = clone $position;

		if($this->getType($actorId) === EntityIds::PLAYER && $adjustPosition){
			$pos->y -= MinecraftPhysics::PLAYER_EYE_HEIGHT; // should I put here?
		}

		$this->position->put($actorId, $pos);
		$this->tickPosition->putIfAbsent($actorId, new IntegerSortSizeMap($this->tickMapSize));
		$this->tickPosition->get($actorId)->put($tick, $pos);
	}

	public function getType(int $runtimeId) : ?string{
		return $this->type->get($runtimeId);
	}

	protected function internalSetActorMotion(int $actorId, Vector3 $motion, int $tick) : void{
		$mot = clone $motion;
		$this->motion->put($actorId, $mot);
		$this->tickMotion->putIfAbsent($actorId, new IntegerSortSizeMap($this->tickMapSize));
		$this->tickMotion->get($actorId)->put($tick, $mot);

		foreach($this->onMotionHooks as $hook){
			$hook($actorId, $tick, $mot);
		}
	}

	public function handleAddPlayer(AddPlayerPacket $packet, int $tick) : void{
		$this->type->putIfAbsent($packet->actorRuntimeId, EntityIds::PLAYER);

		$this->internalSetActorPosition($packet->actorRuntimeId, $packet->position, $tick);
		if($packet->motion !== null){
			$this->internalSetActorMotion($packet->actorRuntimeId, $packet->motion, $tick);
		}
	}

	public function copy(PacketBaseActorStateProvider $provider) : void{
		$provider->position = clone $this->position;
		$provider->type = clone $this->type;
		$provider->motion = clone $this->motion;
		$provider->networkProperties = clone $this->networkProperties;
		$provider->abilities = clone $this->abilities;
		$provider->attributes = clone $this->attributes;
		$provider->effects = clone $this->effects;

		$provider->tickPosition = clone $this->tickPosition;
		$provider->tickMotion = clone $this->tickMotion;
		$provider->tickNetworkProperties = clone $this->tickNetworkProperties;
		$provider->tickAbilities = clone $this->tickAbilities;
		$provider->tickAttributes = clone $this->tickAttributes;
		$provider->tickEffects = clone $this->tickEffects;
		$provider->onMotionHooks = clone $this->onMotionHooks;
	}

	public function handleMoveActorAbsolute(MoveActorAbsolutePacket $packet, int $tick) : void{
		$this->internalSetActorPosition($packet->actorRuntimeId, $packet->position, $tick, true);
	}

	public function handleSetActorData(SetActorDataPacket $packet, int $tick) : void{
		$collection = $this->networkProperties->get($packet->actorRuntimeId) ?? new EntityMetadataCollection();
		$collection->setAtomicBatch($packet->metadata, true);
		$this->networkProperties->put($packet->actorRuntimeId, $collection);
		$this->tickNetworkProperties->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
		$this->tickNetworkProperties->get($packet->actorRuntimeId)->put($tick, $collection);
	}

	public function handleSetActorMotion(SetActorMotionPacket $packet, int $tick) : void{
		$this->internalSetActorMotion($packet->actorRuntimeId, $packet->motion, $tick);
	}

	public function handlePlayerList(PlayerListPacket $packet, int $tick) : void{
		foreach($packet->entries as $entry){
			$this->type->putIfAbsent($entry->actorUniqueId, EntityIds::PLAYER);
		}
	}

	public function handleUpdateAttributes(UpdateAttributesPacket $packet, int $tick) : void{
		$attributes = [];
		foreach($packet->entries as $entry){
			$attributes[$entry->getId()] = $entry;
		}

		$oldAttributes = $this->attributes->get($packet->actorRuntimeId) ?? [];

		$attributes = array_merge($oldAttributes, $attributes);
		$this->attributes->put($packet->actorRuntimeId, $attributes);
		$this->tickAttributes->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
		$this->tickAttributes->get($packet->actorRuntimeId)->put($tick, $attributes);
	}

	public function handleMobEffect(MobEffectPacket $packet, int $tick) : void{
		$effect = EffectIdMap::getInstance()->fromId($packet->effectId);
		if(!is_null($effect)){
			$effectInstance = new EffectInstance($effect, $packet->duration, $packet->amplifier, $packet->particles, false);

			$effectList = $this->effects->get($packet->actorRuntimeId);

			if($packet->eventId === MobEffectPacket::EVENT_ADD){
				$effectList[$packet->effectId] = $effectInstance;
			}elseif($packet->eventId === MobEffectPacket::EVENT_MODIFY){
				if(isset($effectList[$packet->effectId])){
					$effectList[$packet->effectId] = $effectInstance;
				}
			}elseif($packet->eventId === MobEffectPacket::EVENT_REMOVE){
				if(isset($effectList[$packet->effectId])){
					unset($effectList[$packet->effectId]);
				}
			}

			$this->effects->put($packet->actorRuntimeId, $effectList);

			$this->tickEffects->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
			$this->tickEffects->get($packet->actorRuntimeId)->put($tick, $effectList);
		}
	}

	public function handleUpdateAbilities(UpdateAbilitiesPacket $packet, int $tick) : void{
		$actorId = $packet->getData()->getTargetActorUniqueId();
		$this->abilities->put($actorId, $packet->getData());
		$this->tickAbilities->putIfAbsent($actorId, new IntegerSortSizeMap($this->tickMapSize));
		$this->tickAbilities->get($actorId)->put($tick, $packet->getData());
	}
}
