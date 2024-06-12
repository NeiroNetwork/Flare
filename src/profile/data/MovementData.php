<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use Closure;
use NeiroNetwork\Flare\data\report\DataReport;
use NeiroNetwork\Flare\math\EntityRotation;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\utils\BlockUtil;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\data\bedrock\EffectIds;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class MovementData{

	/**
	 * @var Vector3
	 */
	protected Vector3 $rawPosition;

	/**
	 * @var Vector3
	 */
	protected Vector3 $roundedPosition;

	/**
	 * @var AxisAlignedBB
	 */
	protected AxisAlignedBB $boundingBox;

	protected float $jumpVelocity;

	/**
	 * @var int
	 */
	protected int $clientTick;

	/**
	 * @var float
	 */
	protected float $movementSpeed;

	/**
	 * @var float
	 */
	protected float $lastMovementSpeed;

	/**
	 * @var Vector3
	 */
	protected Vector3 $clientPredictedDelta;

	/**
	 * @var float
	 */
	protected float $deltaXZ;

	/**
	 * @var Vector3
	 */
	protected Vector3 $lastClientPredictedDelta;

	/**
	 * @var float
	 */
	protected float $lastDeltaXZ;

	/**
	 * @var Vector3
	 */
	protected Vector3 $realDelta;

	/**
	 * @var float
	 */
	protected float $realDeltaXZ;

	/**
	 * @var Vector3
	 */
	protected Vector3 $lastRealDelta;

	/**
	 * @var float
	 */
	protected float $lastRealDeltaXZ;

	/**
	 * @var Vector3
	 */
	protected Vector3 $lastFrom;

	/**
	 * @var Vector3
	 */
	protected Vector3 $from;

	/**
	 * @var Vector3
	 */
	protected Vector3 $to;

	protected Vector3 $eyePosition;

	protected float $eyeHeight;

	protected ActionRecord $onGround;

	/**
	 * @var EntityRotation
	 */
	protected EntityRotation $rotation;

	/**
	 * @var EntityRotation
	 */
	protected EntityRotation $lastRotation;

	/**
	 * @var EntityRotation
	 */
	protected EntityRotation $rotDelta;

	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $clientOnGround;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $air;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $fly;
	protected ActionRecord $levitation;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $immobile;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $move;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $void;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $join;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $motion;

	protected ?Vector3 $motionVector;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $teleport;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $respawn;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $death;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $speedChange;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $sneakChange;

	protected ?DataReport $rotationDataReport;

	protected Closure $motionHookClosure;

	protected int $inputCount;

	public function __construct(protected PlayerProfile $profile){
		$uuid = $profile->getPlayer()->getUniqueId()->toString();
		$emitter = $this->profile->getFlare()->getEventEmitter();
		$plugin = $this->profile->getFlare()->getPlugin();

		$this->rotationDataReport = null;
		if($profile->isDataReportEnabled()){
			$this->rotationDataReport = new DataReport();

			$profile->getFlare()->getDataReportManager()->setDefault($uuid, "rotation_delta", $this->rotationDataReport);
		}

		$links = $profile->getEventHandlerLink();

		$links->add($emitter->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			$this->handleInput(...),
			false,
			EventPriority::NORMAL
		));
		//todo: EventEmitter を改良する。 (軽量化)
		// eventListenersにpriorityのキーを作って引数にそれも追加

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			EntityTeleportEvent::class,
			$this->handleTeleport(...),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			PlayerDeathEvent::class,
			$this->handleDeath(...),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			PlayerRespawnEvent::class,
			$this->handleRespawn(...),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		$this->profile->getActorStateProvider()->getOnMotionHooks()->add($this->motionHookClosure = function(int $runtimeId, int $tick, Vector3 $motion) : void{
			$this->motion->onAction();
			$this->motionVector = $motion;
		});

		$this->clientTick = 0;

		$this->deltaXZ = 0.0;
		$this->lastDeltaXZ = 0.0;
		$this->realDeltaXZ = 0.0;
		$this->lastRealDeltaXZ = 0.0;

		ProfileData::autoPropertyValue($this);

		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->join->onAction();

		$this->motionVector = null;
	}

	/**
	 * @return float
	 */
	public function getLastMovementSpeed() : float{
		return $this->lastMovementSpeed;
	}

	/**
	 * @return Vector3
	 */
	public function getRawPosition() : Vector3{
		return $this->rawPosition;
	}

	/**
	 * @return Vector3
	 */
	public function getRoundedPosition() : Vector3{
		return $this->roundedPosition;
	}

	/**
	 * Get the value of clientTick
	 *
	 * @return int
	 */
	public function getClientTick() : int{
		return $this->clientTick;
	}

	/**
	 * Get the value of lastDelta
	 *
	 * @return Vector3
	 */
	public function getLastClientPredictedDelta() : Vector3{
		return $this->lastClientPredictedDelta;
	}

	/**
	 * Get the value of from
	 *
	 * @return Vector3
	 */
	public function getFrom() : Vector3{
		return $this->from;
	}

	/**
	 * Get the value of to
	 *
	 * @return Vector3
	 */
	public function getTo() : Vector3{
		return $this->to;
	}

	/**
	 * Get the value of air
	 *
	 * @return ActionRecord
	 */
	public function getAirRecord() : ActionRecord{
		return $this->air;
	}

	/**
	 * Get the value of fly
	 *
	 * @return ActionRecord
	 */
	public function getFlyRecord() : ActionRecord{
		return $this->fly;
	}

	/**
	 * Get the value of immobile
	 *
	 * @return ActionRecord
	 */
	public function getImmobileRecord() : ActionRecord{
		return $this->immobile;
	}

	/**
	 * Get the value of void
	 *
	 * @return ActionRecord
	 */
	public function getVoidRecord() : ActionRecord{
		return $this->void;
	}

	/**
	 * Get the value of join
	 *
	 * @return InstantActionRecord
	 */
	public function getJoinRecord() : InstantActionRecord{
		return $this->join;
	}

	/**
	 * Get the value of teleport
	 *
	 * @return InstantActionRecord
	 */
	public function getTeleportRecord() : InstantActionRecord{
		return $this->teleport;
	}

	/**
	 * Get the value of respawn
	 *
	 * @return InstantActionRecord
	 */
	public function getRespawnRecord() : InstantActionRecord{
		return $this->respawn;
	}

	/**
	 * Get the value of death
	 *
	 * @return InstantActionRecord
	 */
	public function getDeathRecord() : InstantActionRecord{
		return $this->death;
	}

	/**
	 * Get the value of realDelta
	 *
	 * @return Vector3
	 */
	public function getRealDelta() : Vector3{
		return $this->realDelta;
	}

	/**
	 * Get the value of lastRealDelta
	 *
	 * @return Vector3
	 */
	public function getLastRealDelta() : Vector3{
		return $this->lastRealDelta;
	}

	/**
	 * @return ActionRecord
	 */
	public function getLevitationRecord() : ActionRecord{
		return $this->levitation;
	}

	/**
	 * Get the value of rotation
	 *
	 * @return EntityRotation
	 */
	public function getRotation() : EntityRotation{
		return $this->rotation;
	}

	/**
	 * Get the value of lastRotation
	 *
	 * @return EntityRotation
	 */
	public function getLastRotation() : EntityRotation{
		return $this->lastRotation;
	}

	/**
	 * Get the value of rotDelta
	 *
	 * @return EntityRotation
	 */
	public function getRotationDelta() : EntityRotation{
		return $this->rotDelta;
	}

	/**
	 * Get the value of lastFrom
	 *
	 * @return Vector3
	 */
	public function getLastFrom() : Vector3{
		return $this->lastFrom;
	}

	/**
	 * Get the value of deltaXZ
	 *
	 * @return float
	 */
	public function getDeltaXZ() : float{
		return $this->deltaXZ;
	}

	/**
	 * Get the value of lastDeltaXZ
	 *
	 * @return float
	 */
	public function getLastDeltaXZ() : float{
		return $this->lastDeltaXZ;
	}

	/**
	 * Get the value of realDeltaXZ
	 *
	 * @return float
	 */
	public function getRealDeltaXZ() : float{
		return $this->realDeltaXZ;
	}

	/**
	 * Get the value of lastRealDeltaXZ
	 *
	 * @return float
	 */
	public function getLastRealDeltaXZ() : float{
		return $this->lastRealDeltaXZ;
	}

	/**
	 * Get the value of move
	 *
	 * @return ActionRecord
	 */
	public function getMoveRecord() : ActionRecord{
		return $this->move;
	}

	/**
	 * Get the value of speedChange
	 *
	 * @return InstantActionRecord
	 */
	public function getSpeedChangeRecord() : InstantActionRecord{
		return $this->speedChange;
	}

	/**
	 * Get the value of clientOnGround
	 *
	 * @return ActionRecord
	 */
	public function getClientOnGroundRecord() : ActionRecord{
		return $this->clientOnGround;
	}

	/**
	 * @return ActionRecord
	 */
	public function getOnGroundRecord() : ActionRecord{
		return $this->onGround;
	}

	/**
	 * @return int
	 */
	public function getInputCount() : int{
		return $this->inputCount;
	}

	/**
	 * @return Vector3
	 */
	public function getEyePosition() : Vector3{
		return $this->eyePosition;
	}

	/**
	 * @return float
	 */
	public function getEyeHeight() : float{
		return $this->eyeHeight;
	}

	/**
	 * @return float
	 */
	public function getMovementSpeed() : float{
		return $this->movementSpeed;
	}

	/**
	 * Get the value of delta
	 *
	 * @return Vector3
	 */
	public function getClientPredictedDelta() : Vector3{
		return $this->clientPredictedDelta;
	}

	/**
	 * Get the value of motion
	 *
	 * @return InstantActionRecord
	 */
	public function getMotionRecord() : InstantActionRecord{
		return $this->motion;
	}

	/**
	 * @return float
	 */
	public function getJumpVelocity() : float{
		return $this->jumpVelocity;
	}

	protected function handleRespawn(PlayerRespawnEvent $event) : void{
		if($event->getPlayer() === $this->profile->getPlayer()){
			$this->respawn->onAction();
		}
	}

	protected function handleDeath(PlayerDeathEvent $event) : void{
		if($event->getPlayer() === $this->profile->getPlayer()){
			$this->death->onAction();
		}
	}

	protected function handleTeleport(EntityTeleportEvent $event) : void{
		if($event->getEntity() === $this->profile->getPlayer()){
			$this->teleport->onAction();
		}
	}

	protected function handleInput(PlayerAuthInputPacket $packet) : void{
		$player = $this->profile->getPlayer();
		$ki = $this->profile->getKeyInputs();
		$position = $packet->getPosition()->subtract(0, MinecraftPhysics::PLAYER_EYE_HEIGHT, 0);
		$providerSupport = $this->profile->getSupport();

		$rawRot = EntityRotation::create($packet->getYaw(), $packet->getPitch(), $packet->getHeadYaw());
		$rot = EntityRotation::create(fmod($rawRot->yaw, 360), fmod($rawRot->pitch, 360), fmod($rawRot->headYaw, 360));

		EntityRotation::check($rot);

		$this->inputCount++;


		$this->lastFrom = clone $this->from;
		$this->from = clone $this->to;
		$this->to = clone $position;

		$this->rawPosition = clone $position;
		$this->roundedPosition = $position->round(4); // InGamePacketHandler #210
		$d = $this->to->subtractVector($this->from);
		$this->boundingBox = $providerSupport->getBoundingBox($player->getId(), $position) ?? $player->getBoundingBox()->offsetCopy($d->x, $d->y, $d->z);

		$this->clientTick = $packet->getTick();

		$this->lastClientPredictedDelta = clone $this->clientPredictedDelta;
		$this->clientPredictedDelta = clone $packet->getDelta();

		$this->lastDeltaXZ = $this->deltaXZ;
		$this->deltaXZ = $this->clientPredictedDelta->x ** 2 + $this->clientPredictedDelta->z ** 2;

		$this->lastRotation = clone $this->rotation;
		$this->rotation = clone $rot;
		$this->rotDelta = $this->rotation->diff($this->lastRotation);
		// fixes Aim(C)
		if(abs($this->rotDelta->pitch) < 1E-5){
			$this->rotDelta->pitch = 0;
		}
		if(abs($this->rotDelta->yaw) < 1E-5){
			$this->rotDelta->yaw = 0;
		}
		if(abs($this->rotDelta->headYaw) < 1E-5){
			$this->rotDelta->headYaw = 0;
		}

		$this->rotationDataReport?->add([$this->rotDelta->yaw, $this->rotDelta->pitch]); // data report

		$this->lastRealDelta = clone $this->realDelta;
		$this->realDelta = clone $d;

		$this->lastRealDeltaXZ = $this->realDeltaXZ;
		$this->realDeltaXZ = $this->realDelta->x ** 2 + $this->realDelta->z ** 2;

		$this->lastMovementSpeed = $this->movementSpeed;
		$speedAttr = $providerSupport->getMovementSpeedAttribute($player->getId());
		$defaultSpeed = $providerSupport->getActorBaseAbilitiesLayer($player->getId())?->getWalkSpeed();
		$pairedSprinting = $providerSupport->checkActorMetadataGenericFlag($player->getId(), EntityMetadataFlags::SPRINTING) ?? false;
		$speed = $speedAttr?->getCurrent();

		if($pairedSprinting && !is_null($speed)){
			$speed /= 1.3;
		}
		$this->movementSpeed = $speed ?? ($defaultSpeed ?? 0.1);

		if($ki->sprint() || $pairedSprinting){
			$this->movementSpeed *= 1.3;
		}
		// 走りでの加速はクライアント側なのに、サーバー側も速度を適用している

		$scale = $providerSupport->getActorNumericalMetadataProperty($player->getId(), EntityMetadataProperties::SCALE)?->getValue();
		$this->eyeHeight = (MinecraftPhysics::PLAYER_EYE_HEIGHT) + ($ki->sneak() ? -0.15 : 0.0);
		$this->eyePosition = $this->rawPosition->add(0, $this->eyeHeight, 0);

		$this->levitation->update($this->profile->getSupport()->hasEffect($player->getId(), EffectIds::LEVITATION) ?? $player->getEffects()->has(VanillaEffects::LEVITATION()));

		$this->jumpVelocity = MinecraftPhysics::JUMP_VELOCITY + ($providerSupport->getEffect($player->getId(), EffectIds::JUMP_BOOST)?->getEffectLevel() ?? 0) / 10;

		$this->clientOnGround->update(abs($this->clientPredictedDelta->y - MinecraftPhysics::nextFreefallVelocity(Vector3::zero())->y) < 1.0E-8);

		$this->immobile->update($providerSupport->checkActorMetadataGenericFlag(
			$player->getId(),
			EntityMetadataFlags::NO_AI // it's working!
		) ?? $player->hasNoClientPredictions());

		$this->void->update($position->y <= -39.75);

		//fixme: 名前は fly なのに実際は allow flight でやってる
		$this->fly->update($providerSupport->checkActorBaseAbility(
			$player->getId(),
			AbilitiesLayer::ABILITY_FLYING
		) ?? $player->getAllowFlight());

		$this->move->update($packet->getMoveVecX() > 0 || $packet->getMoveVecZ() > 0);

		$this->motion->update();

		/**
		 * @see Player::syncNetworkData()
		 */
		if(!$providerSupport->checkActorMetadataGenericFlag($player->getId(), EntityMetadataFlags::HAS_COLLISION)){
			$this->onGround->update(false);
		}else{
			$lastClientPredictedDelta = clone $this->lastClientPredictedDelta;

			if(!is_null($this->motionVector)){
				$lastClientPredictedDelta->y = $this->motionVector->y;
				$this->motionVector = null;
			}

			if($this->profile->getKeyInputs()->getStartJumpRecord()->getFlag()){
				$lastClientPredictedDelta->y = $this->jumpVelocity;
			}

			$collidedVertically = abs($lastClientPredictedDelta->y - $this->realDelta->y) > 0.001;
			$shouldBeStand = false;

			// 半ブロックはマジでゴミです。消えてください。
			$bb = $this->boundingBox->extendedCopy(Facing::DOWN, 2 / 64)->addCoord($d->x, $d->y, $d->z)->expand(1 / 3, 1 / 3, 1 / 3);
			foreach($this->profile->getSurroundData()->getStepBlocks() as $block){
				if(BlockUtil::collidesWithFixedBB($block, $bb) && floor($block->getPosition()->y) <= floor($position->y)){
					$shouldBeStand = true;
					break;
				}
			}

			$this->onGround->update(
				$collidedVertically &&
				($lastClientPredictedDelta->y < 0) &&
				$shouldBeStand
			);
		}


		$this->air->update(!$this->onGround->getFlag());

		if($this->movementSpeed !== $this->lastMovementSpeed){
			$this->speedChange->onAction();
		}

		$this->speedChange->update();

		$this->join->update();
		$this->teleport->update();
		$this->death->update();
		$this->respawn->update();
	}

	/**
	 * Get the value of boundingBox
	 *
	 * @return AxisAlignedBB
	 */
	public function getBoundingBox() : AxisAlignedBB{
		return $this->boundingBox;
	}
}
