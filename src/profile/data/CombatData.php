<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\utils\NumericalSampling;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

class CombatData{

	protected ?Entity $clientAiming;
	protected Vector3 $clientAimingAt;
	protected ?Entity $hitEntity;
	protected ?Entity $lastHitEntity;
	protected int $lastHitEntityTime;

	protected ?Entity $hurtBy;
	protected ?Entity $lastHurtBy;

	/**
	 * @var Vector3[]
	 */
	protected array $targetPos;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $hurt;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $attack;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $aim;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $triggerAim;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $playerSpawn;

	/**
	 * @var NumericalSampling
	 */
	protected NumericalSampling $clickDelta;

	/**
	 * @var NumericalSampling
	 */
	protected NumericalSampling $clickTickDelta;

	protected float $lastClickTimeDelta;

	protected int $lastClickInputTick;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $click;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $swing;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $knockback;

	protected float $lastClickTime = 0;

	protected float $clickTime;

	public function __construct(protected PlayerProfile $profile){
		$emitter = $this->profile->getFlare()->getEventEmitter();
		$player = $profile->getPlayer();
		$uuid = $player->getUniqueId()->toString();
		$links = $this->profile->getEventHandlerLink();

		$links->add($emitter->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			$this->handleInput(...),
			false,
			EventPriority::NORMAL
		));

		$links->add($emitter->registerSendPacketHandler(
			$uuid,
			AddPlayerPacket::NETWORK_ID,
			$this->handleSendAddPlayer(...),
			false,
			EventPriority::LOWEST
		));

		$links->add($emitter->registerPacketHandler(
			$uuid,
			InteractPacket::NETWORK_ID,
			$this->handleInteract(...),
			false,
			EventPriority::LOWEST
		));

		$links->add($emitter->registerPacketHandler(
			$uuid,
			LevelSoundEventPacket::NETWORK_ID,
			$this->handleLevelSound(...),
			false,
			EventPriority::LOWEST
		));

		$links->add($emitter->registerPlayerEventHandler(
			$uuid,
			EntityDamageEvent::class,
			$this->handleDamage(...),
			false,
			EventPriority::LOWEST
		));

		$links->add($emitter->registerPlayerEventHandler(
			$uuid,
			EntityDamageByEntityEvent::class,
			$this->handleDamageByEntity(...),
			false,
			EventPriority::LOWEST
		));

		$links->add($emitter->registerPlayerEventHandler(
			$uuid,
			PlayerAttackEvent::class,
			$this->handleAttack(...),
			false,
			EventPriority::LOWEST
		));

		$this->clickDelta = new NumericalSampling(20);

		ProfileData::autoPropertyValue($this);

		$this->clientAiming = null;
		$this->clientAimingAt = new Vector3(0, 0, 0);

		$this->lastHitEntity = null;
		$this->hitEntity = null;

		$this->hurtBy = null;
		$this->lastHurtBy = null;
	}

	/**
	 * Get the value of clientAiming
	 *
	 * @return ?Entity
	 */
	public function getClientAiming() : ?Entity{
		return $this->clientAiming;
	}

	/**
	 * Get the value of hitEntity
	 *
	 * @return ?Entity
	 */
	public function getHitEntity() : ?Entity{
		return $this->hitEntity;
	}

	/**
	 * Get the value of lastHitEntity
	 *
	 * @return ?Entity
	 */
	public function getLastHitEntity() : ?Entity{
		return $this->lastHitEntity;
	}

	/**
	 * Get the value of lastHitEntityTime
	 *
	 * @return int
	 */
	public function getLastHitEntityTime() : int{
		return $this->lastHitEntityTime;
	}

	/**
	 * Get the value of hurtBy
	 *
	 * @return ?Entity
	 */
	public function getHurtBy() : ?Entity{
		return $this->hurtBy;
	}

	/**
	 * Get the value of lastHurtBy
	 *
	 * @return ?Entity
	 */
	public function getLastHurtBy() : ?Entity{
		return $this->lastHurtBy;
	}

	/**
	 * Get the value of targetPos
	 *
	 * @return Vector3[]
	 */
	public function getTargetPos() : array{
		return $this->targetPos;
	}

	public function getHurtRecord() : InstantActionRecord{
		return $this->hurt;
	}

	public function getAttackRecord() : InstantActionRecord{
		return $this->attack;
	}

	public function getAimRecord() : ActionRecord{
		return $this->aim;
	}

	public function getTriggerAimRecord() : InstantActionRecord{
		return $this->triggerAim;
	}

	public function getPlayerSpawnRecord() : InstantActionRecord{
		return $this->playerSpawn;
	}

	public function getClickRecord() : InstantActionRecord{
		return $this->click;
	}

	public function getSwingRecord() : InstantActionRecord{
		return $this->swing;
	}

	public function getClickDelta() : NumericalSampling{
		return $this->clickDelta;
	}

	public function getClickTimeDelta() : float{
		return $this->lastClickTimeDelta;
	}

	public function getClickTime() : float{
		return $this->clickTime;
	}

	public function getLastClickTime() : float{
		return $this->lastClickTime;
	}

	public function getLastClickInputTick() : int{
		return $this->lastClickInputTick;
	}

	/**
	 * Get the value of knockback
	 *
	 * @return InstantActionRecord
	 */
	public function getKnockbackRecord() : InstantActionRecord{
		return $this->knockback;
	}

	/**
	 * Get the value of clientAimingAt
	 *
	 * @return Vector3
	 */
	public function getClientAimingAt() : Vector3{
		return $this->clientAimingAt;
	}

	protected function handleSendAddPlayer(AddPlayerPacket $packet) : void{
		$this->playerSpawn->onAction();
	}

	protected function handleAttack(PlayerAttackEvent $event) : void{
		$entity = $event->getEntity();

		$this->handleMouseClick();

		$this->lastHitEntity = $this->hitEntity;

		$this->attack->onAction();
		$this->hitEntity = $entity;

		if($this->hitEntity !== $this->lastHitEntity){
			$this->targetPos = [];
		}
	}

	protected function handleMouseClick() : void{
		$time = hrtime(true);
		$this->lastClickTimeDelta = $time - $this->lastClickTime;
		$this->clickDelta->add($time - $this->lastClickTime);

		$this->clickTime = $time;

		$this->click->onAction();

		$this->lastClickInputTick = $this->profile->getMovementData()->getInputCount();
		$this->lastClickTime = $time;
	}

	protected function handleLevelSound(LevelSoundEventPacket $packet) : void{
		if($packet->sound === LevelSoundEvent::ATTACK_NODAMAGE){
			$this->handleMouseClick();
			$this->swing->onAction();
		}
	}

	protected function handleInteract(InteractPacket $packet) : void{
		$player = $this->profile->getPlayer();

		if($packet->action === InteractPacket::ACTION_MOUSEOVER){
			$target = $packet->targetActorRuntimeId;
			if($target !== 0){ #target is entity
				$this->clientAiming = $player->getWorld()->getEntity($target);
				$this->clientAimingAt = new Vector3($packet->x, $packet->y, $packet->z);
				$this->triggerAim->onAction();
			}else{
				if($this->playerSpawn->getTickSinceAction() >= (7 + 0)){ // todo: LagCompensator
					if($packet->x !== 0 && $packet->y !== 0 && $packet->z !== 0){
						$this->clientAiming = null;
					}
				}
			}
		}
	}

	protected function handleInput(PlayerAuthInputPacket $packet) : void{
		$player = $this->profile->getPlayer();

		if($this->clientAiming instanceof Entity){
			$pk = SetActorDataPacket::create(
				$this->clientAiming->getId(),
				[],
				new PropertySyncData([], []),
				0
			);
			$player->getNetworkSession()->sendDataPacket($pk);
			// todo: 代替パケットを探す

			// SetActorDataPacket を送信することにより、
			// InteractPacket::ACTION_MOUSEOVER を再送させることができる。
		}

		$this->triggerAim->update();
		$this->playerSpawn->update();
		$this->click->update();
		$this->attack->update();
		$this->swing->update();
		$this->aim->update($this->clientAiming !== null);
		$this->knockback->update();

		$this->hurt->update();

		if($this->hitEntity !== null){
			if(!$this->hitEntity->isClosed() && $this->attack->getTickSinceAction() <= 60){
				$this->targetPos[] = $this->hitEntity->getLocation();
			}else{
				$this->targetPos = [];
			}
		}
	}

	protected function handleDamage(EntityDamageEvent $event) : void{
		$this->hurt->onAction();
	}

	protected function handleDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$entity = $event->getDamager();
		$this->lastHurtBy = $this->hurtBy;
		$this->hurtBy = $entity;

		if($event->getKnockBack() > 0.0){
			$this->knockback->onAction();
		}
	}
}
