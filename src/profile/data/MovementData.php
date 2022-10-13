<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use Closure;
use NeiroNetwork\Flare\math\EntityRotation;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\utils\BlockUtil;
use NeiroNetwork\Flare\utils\PlayerUtil;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;

class MovementData {

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

	/**
	 * @var int
	 */
	protected int $clientTick;

	/**
	 * @var Vector3
	 */
	protected Vector3 $delta;

	/**
	 * @var float
	 */
	protected float $deltaXZ;

	/**
	 * @var Vector3
	 */
	protected Vector3 $lastDelta;

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
	protected ActionRecord $onGround;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $air;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $fly;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $ronGround;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $rair;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $immobile;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $void;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $join;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $glide;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $swim;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $motion;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $jump;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $teleport;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $sprint;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $respawn;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $death;

	public function __construct(protected PlayerProfile $profile) {
		$uuid = $profile->getPlayer()->getUniqueId()->toString();
		$emitter = $this->profile->getFlare()->getEventEmitter();
		$plugin = $this->profile->getFlare()->getPlugin();


		$links = $profile->getEventHandlerLink();

		$links->add($emitter->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handleInput"]),
			false,
			EventPriority::LOWEST
		));

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			EntityMotionEvent::class,
			Closure::fromCallable([$this, "handleMotion"]),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		//todo: EventEmitter を改良する。 (軽量化)
		// eventListenersにpriorityのキーを作って引数にそれも追加

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			EntityTeleportEvent::class,
			Closure::fromCallable([$this, "handleTeleport"]),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			PlayerDeathEvent::class,
			Closure::fromCallable([$this, "handleDeath"]),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		$links->add($plugin->getServer()->getPluginManager()->registerEvent(
			PlayerRespawnEvent::class,
			Closure::fromCallable([$this, "handleRespawn"]),
			EventPriority::MONITOR,
			$plugin,
			false
		));

		$this->clientTick = 0;

		$this->deltaXZ = 0.0;
		$this->lastDeltaXZ = 0.0;
		$this->realDeltaXZ = 0.0;
		$this->lastRealDeltaXZ = 0.0;

		ProfileData::autoPropertyValue($this);

		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->join->onAction();
	}

	protected function handleRespawn(PlayerRespawnEvent $event): void {
		if ($event->getPlayer() === $this->profile->getPlayer()) {
			$this->respawn->onAction();
		}
	}

	protected function handleDeath(PlayerDeathEvent $event): void {
		if ($event->getPlayer() === $this->profile->getPlayer()) {
			$this->death->onAction();
		}
	}

	protected function handleTeleport(EntityTeleportEvent $event): void {
		if ($event->getEntity() === $this->profile->getPlayer()) {
			$this->teleport->onAction();
		}
	}

	protected function handleMotion(EntityMotionEvent $event): void {
		if ($event->getEntity() === $this->profile->getPlayer()) {
			$this->motion->onAction();
		}
	}

	protected function handleInput(PlayerAuthInputPacket $packet): void {
		$player = $this->profile->getPlayer();
		$position = $packet->getPosition()->subtract(0, 1.62, 0);

		$rawRot = EntityRotation::create($packet->getYaw(), $packet->getPitch(), $packet->getHeadYaw());
		$rot = EntityRotation::create(fmod($rawRot->yaw, 360), fmod($rawRot->pitch, 360), fmod($rawRot->headYaw, 360));
		EntityRotation::check($rot);

		$this->lastFrom = clone $this->from;
		$this->from = clone $this->to;
		$this->to = clone $position;

		$this->rawPosition = clone $position;
		$this->roundedPosition = $position->round(4); // InGamePacketHandler #210
		$d = $this->to->subtractVector($this->from);
		$this->boundingBox = $player->getBoundingBox()->offsetCopy($d->x, $d->y, $d->z);

		$this->clientTick = $packet->getTick();

		$this->lastDelta = clone $this->delta;
		$this->delta = clone $packet->getDelta();

		$this->lastDeltaXZ = $this->deltaXZ;
		$this->deltaXZ = $this->delta->x ** 2 + $this->delta->z ** 2;

		$this->lastRotation = clone $this->rotation;
		$this->rotation = clone $rot;
		$this->rotDelta = $this->rotation->subtract($this->lastRotation);

		$this->lastRealDelta = clone $this->realDelta;
		$this->realDelta = clone $d;

		$this->lastRealDeltaXZ = $this->realDeltaXZ;
		$this->realDeltaXZ = $this->realDelta->x ** 2 + $this->realDelta->z ** 2;

		if (!$player->hasBlockCollision()) {
			$this->onGround->update(false);
		} else {
			$bb = clone $this->boundingBox;
			$bb->minY = $bb->minY - 0.2;
			$bb->maxY = $bb->maxY + 0.1;

			// $bb = $bb->addCoord(-$d->x, -$d->y, -$d->z);

			$this->onGround->update(
				count(BlockUtil::getFixedCollisionBlocks($player->getWorld(), $bb, true)) > 0
			);
		}

		$this->air->update(!$this->onGround->getFlag());

		$this->immobile->update($player->isImmobile());

		$this->void->update($position->y <= -39.75);

		$this->fly->update($player->getAllowFlight());

		$roundedY = $this->roundedPosition->y;
		$m = fmod($roundedY, 1 / 64);
		$step = round($roundedY - floor($roundedY), 4);
		if (
			$step == 0.1825 || #忘れた
			$step == 0.9999 || #スポーンブロック
			$step == 0.95 || #チェスト
			$step == 0.0156
		) { #蓮の葉
			$m = 0;
		} #todo: これを元にbypassされる可能性があるので解決策を探す

		$this->ronGround->update(!$m);
		$this->rair->update(!$this->ronGround->getFlag());

		$this->glide->update(PlayerUtil::isGenericFlag($player, EntityMetadataFlags::GLIDING));

		$this->swim->update(PlayerUtil::isGenericFlag($player, EntityMetadataFlags::SWIMMING));

		$this->sprint->update($player->isSprinting());
		$this->join->update();
		$this->motion->update();
		$this->teleport->update();
		$this->death->update();
		$this->respawn->update();
		$this->jump->update();
	}

	/**
	 * @return Vector3
	 */
	public function getRawPosition(): Vector3 {
		return $this->rawPosition;
	}

	/**
	 * @return Vector3
	 */
	public function getRoundedPosition(): Vector3 {
		return $this->roundedPosition;
	}

	/**
	 * Get the value of delta
	 *
	 * @return Vector3
	 */
	public function getDelta(): Vector3 {
		return $this->delta;
	}

	/**
	 * Get the value of clientTick
	 *
	 * @return int
	 */
	public function getClientTick(): int {
		return $this->clientTick;
	}

	/**
	 * Get the value of lastDelta
	 *
	 * @return Vector3
	 */
	public function getLastDelta(): Vector3 {
		return $this->lastDelta;
	}

	/**
	 * Get the value of from
	 *
	 * @return Vector3
	 */
	public function getFrom(): Vector3 {
		return $this->from;
	}

	/**
	 * Get the value of to
	 *
	 * @return Vector3
	 */
	public function getTo(): Vector3 {
		return $this->to;
	}

	/**
	 * Get the value of onGround
	 *
	 * @return ActionRecord
	 */
	public function getOnGroundRecord(): ActionRecord {
		return $this->onGround;
	}

	/**
	 * Get the value of air
	 *
	 * @return ActionRecord
	 */
	public function getAirRecord(): ActionRecord {
		return $this->air;
	}

	/**
	 * Get the value of fly
	 *
	 * @return ActionRecord
	 */
	public function getFlyRecord(): ActionRecord {
		return $this->fly;
	}

	/**
	 * Get the value of ronGround
	 *
	 * @return ActionRecord
	 */
	public function getRonGroundRecord(): ActionRecord {
		return $this->ronGround;
	}

	/**
	 * Get the value of rair
	 *
	 * @return ActionRecord
	 */
	public function getRairRecord(): ActionRecord {
		return $this->rair;
	}

	/**
	 * Get the value of immobile
	 *
	 * @return ActionRecord
	 */
	public function getImmobileRecord(): ActionRecord {
		return $this->immobile;
	}

	/**
	 * Get the value of void
	 *
	 * @return ActionRecord
	 */
	public function getVoidRecord(): ActionRecord {
		return $this->void;
	}

	/**
	 * Get the value of join
	 *
	 * @return InstantActionRecord
	 */
	public function getJoinRecord(): InstantActionRecord {
		return $this->join;
	}

	/**
	 * Get the value of glide
	 *
	 * @return ActionRecord
	 */
	public function getGlideRecord(): ActionRecord {
		return $this->glide;
	}

	/**
	 * Get the value of swim
	 *
	 * @return ActionRecord
	 */
	public function getSwimRecord(): ActionRecord {
		return $this->swim;
	}

	/**
	 * Get the value of motion
	 *
	 * @return InstantActionRecord
	 */
	public function getMotionRecord(): InstantActionRecord {
		return $this->motion;
	}

	/**
	 * Get the value of jump
	 *
	 * @return InstantActionRecord
	 */
	public function getJumpRecord(): InstantActionRecord {
		return $this->jump;
	}

	/**
	 * Get the value of teleport
	 *
	 * @return InstantActionRecord
	 */
	public function getTeleportRecord(): InstantActionRecord {
		return $this->teleport;
	}

	/**
	 * Get the value of sprint
	 *
	 * @return ActionRecord
	 */
	public function getSprintRecord(): ActionRecord {
		return $this->sprint;
	}

	/**
	 * Get the value of respawn
	 *
	 * @return InstantActionRecord
	 */
	public function getRespawnRecord(): InstantActionRecord {
		return $this->respawn;
	}

	/**
	 * Get the value of death
	 *
	 * @return InstantActionRecord
	 */
	public function getDeathRecord(): InstantActionRecord {
		return $this->death;
	}

	/**
	 * Get the value of boundingBox
	 *
	 * @return AxisAlignedBB
	 */
	public function getBoundingBox(): AxisAlignedBB {
		return $this->boundingBox;
	}

	/**
	 * Get the value of realDelta
	 *
	 * @return Vector3
	 */
	public function getRealDelta(): Vector3 {
		return $this->realDelta;
	}

	/**
	 * Get the value of lastRealDelta
	 *
	 * @return Vector3
	 */
	public function getLastRealDelta(): Vector3 {
		return $this->lastRealDelta;
	}

	/**
	 * Get the value of rotation
	 *
	 * @return EntityRotation
	 */
	public function getRotation(): EntityRotation {
		return $this->rotation;
	}

	/**
	 * Get the value of lastRotation
	 *
	 * @return EntityRotation
	 */
	public function getLastRotation(): EntityRotation {
		return $this->lastRotation;
	}

	/**
	 * Get the value of rotDelta
	 *
	 * @return EntityRotation
	 */
	public function getRotationDelta(): EntityRotation {
		return $this->rotDelta;
	}

	/**
	 * Get the value of lastFrom
	 *
	 * @return Vector3
	 */
	public function getLastFrom(): Vector3 {
		return $this->lastFrom;
	}

	/**
	 * Get the value of deltaXZ
	 *
	 * @return float
	 */
	public function getDeltaXZ(): float {
		return $this->deltaXZ;
	}

	/**
	 * Get the value of lastDeltaXZ
	 *
	 * @return float
	 */
	public function getLastDeltaXZ(): float {
		return $this->lastDeltaXZ;
	}

	/**
	 * Get the value of realDeltaXZ
	 *
	 * @return float
	 */
	public function getRealDeltaXZ(): float {
		return $this->realDeltaXZ;
	}

	/**
	 * Get the value of lastRealDeltaXZ
	 *
	 * @return float
	 */
	public function getLastRealDeltaXZ(): float {
		return $this->lastRealDeltaXZ;
	}
}
