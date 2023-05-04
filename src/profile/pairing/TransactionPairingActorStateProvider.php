<?php

namespace NeiroNetwork\Flare\profile\pairing;

use NeiroNetwork\Flare\profile\ActorStateProvider;
use NeiroNetwork\Flare\utils\IntegerSortSizeMap;
use NeiroNetwork\Flare\utils\Map;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

class TransactionPairingActorStateProvider implements ActorStateProvider{

	protected TransactionPairing $transactionPairing;

	/**
	 * @var Map<int, Vector3>
	 */
	protected Map $position;

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

	public function __construct(TransactionPairing $transactionPairing, int $tickMapSize){
		$this->transactionPairing = $transactionPairing;
		$this->position = new Map();
		$this->tickPosition = new Map();
		$this->networkProperties = new Map();
		$this->tickNetworkProperties = new Map();
		$this->motion = new Map();
		$this->tickMotion = new Map();
		$this->attributes = new Map();
		$this->tickAttributes = new Map();
		$this->effects = new Map();
		$this->tickEffects = new Map();
		$this->abilities = new Map();
		$this->tickAbilities = new Map();

		$this->tickMapSize = $tickMapSize;

		$transactionPairing->addConfirmHandler($this->handleConfirm(...));
	}

	public function __destruct(){
		$this->transactionPairing->removeConfirmHandler($this->handleConfirm(...));
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

	protected function handleConfirm(int $tick) : void{
		$packets = $this->transactionPairing->getConfirmedPackets($tick);

		foreach($packets as $packet){
			if($packet instanceof MoveActorAbsolutePacket){
				$pos = $packet->position;
				$this->position->put($packet->actorRuntimeId, clone $pos);
				$this->tickPosition->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
				$this->tickPosition->get($packet->actorRuntimeId)->put($tick, clone $pos);
			}

			if($packet instanceof SetActorDataPacket){
				$collection = $this->networkProperties->get($packet->actorRuntimeId) ?? new EntityMetadataCollection();
				$collection->setAtomicBatch($packet->metadata, true);
				$this->networkProperties->put($packet->actorRuntimeId, $collection);
				$this->tickNetworkProperties->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
				$this->tickNetworkProperties->get($packet->actorRuntimeId)->put($tick, $collection);
			}

			if($packet instanceof SetActorMotionPacket){
				$this->motion->put($packet->actorRuntimeId, $packet->motion);
				$this->tickMotion->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
				$this->tickMotion->get($packet->actorRuntimeId)->put($tick, $packet->motion);
			}

			if($packet instanceof UpdateAttributesPacket){
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

			if($packet instanceof MobEffectPacket){
				$effect = EffectIdMap::getInstance()->fromId($packet->effectId);
				if(!is_null($effect)){
					$effectInstance = new EffectInstance($effect, $packet->duration, $packet->amplifier, $packet->particles, false);

					$effectList = $this->effects->get($packet->actorRuntimeId);

					if($packet->eventId === MobEffectPacket::EVENT_ADD){
						$effectList[] = $effectInstance;
					}elseif($packet->eventId === MobEffectPacket::EVENT_MODIFY){
						if(isset($effectList[$packet->effectId])){
							$effectList[$packet->effectId] = $effectInstance;
						}
					}elseif($packet->eventId === MobEffectPacket::EVENT_REMOVE){
						if(isset($effectList[$packet->eventId])){
							unset($effectList[$packet->eventId]);
						}
					}

					$this->effects->put($packet->actorRuntimeId, $effectList);

					$this->tickEffects->putIfAbsent($packet->actorRuntimeId, new IntegerSortSizeMap($this->tickMapSize));
					$this->tickEffects->get($packet->actorRuntimeId)->put($tick, $effectList);
				}
			}

			if($packet instanceof UpdateAbilitiesPacket){
				$actorId = $packet->getData()->getTargetActorUniqueId();
				$this->abilities->put($actorId, $packet->getData());
				$this->tickAbilities->putIfAbsent($actorId, new IntegerSortSizeMap($this->tickMapSize));
				$this->tickAbilities->get($actorId)->put($tick, $packet->getData());
			}
		}
	}
}
