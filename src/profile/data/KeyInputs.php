<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class KeyInputs{

	protected bool $w;
	protected bool $a;
	protected bool $s;
	protected bool $d;
	protected bool $jump;

	protected bool $sneaking;
	protected bool $sprinting;
	protected bool $swimming;
	protected bool $gliding;

	protected float $forwardValue;
	protected float $strafeValue;

	protected float $lastForwardValue;
	protected float $lastStrafeValue;

	protected int $lastInputFlags;

	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $sprint;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $sneak;
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
	protected InstantActionRecord $sneakChange;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $moveKeyChange;

	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $moveVecChange;

	public function __construct(protected PlayerProfile $profile){
		$uuid = $profile->getPlayer()->getUniqueId()->toString();
		$links = $this->profile->getEventHandlerLink();
		$links->add($this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			$this->handleInput(...),
			false,
			EventPriority::LOWEST
		));

		ProfileData::autoPropertyValue($this);
	}

	public function w() : bool{
		return $this->w;
	}

	public function a() : bool{
		return $this->a;
	}

	public function s() : bool{
		return $this->s;
	}

	public function d() : bool{
		return $this->d;
	}

	public function forwardValue() : float{
		return $this->forwardValue;
	}

	public function strafeValue() : float{
		return $this->strafeValue;
	}

	public function jump() : bool{
		return $this->jump;
	}

	/**
	 * @return bool
	 */
	public function sneak() : bool{
		return $this->sneaking;
	}

	/**
	 * @return bool
	 */
	public function sprint() : bool{
		return $this->sprinting;
	}

	/**
	 * @return bool
	 */
	public function swim() : bool{
		return $this->swimming;
	}

	/**
	 * @return bool
	 */
	public function glide() : bool{
		return $this->gliding;
	}

	/**
	 * @return bool
	 */
	public function anyMoveKey() : bool{
		return $this->w || $this->a || $this->s || $this->d;
	}

	/**
	 * Get the value of sprint
	 *
	 * @return ActionRecord
	 */
	public function getSprintRecord() : ActionRecord{
		return $this->sprint;
	}

	/**
	 * Get the value of sneak
	 *
	 * @return ActionRecord
	 */
	public function getSneakRecord() : ActionRecord{
		return $this->sneak;
	}

	/**
	 * Get the value of glide
	 *
	 * @return ActionRecord
	 */
	public function getGlideRecord() : ActionRecord{
		return $this->glide;
	}

	/**
	 * Get the value of swim
	 *
	 * @return ActionRecord
	 */
	public function getSwimRecord() : ActionRecord{
		return $this->swim;
	}

	/**
	 * Get the value of sneakChange
	 *
	 * @return InstantActionRecord
	 */
	public function getSneakChangeRecord() : InstantActionRecord{
		return $this->sneakChange;
	}

	/**
	 * Get the value of moveVecChange
	 *
	 * @return InstantActionRecord
	 */
	public function getMoveVecChangeRecord() : InstantActionRecord{
		return $this->moveVecChange;
	}

	protected function handleInput(PlayerAuthInputPacket $packet) : void{
		$vz = $packet->getMoveVecZ();
		$vx = $packet->getMoveVecX();
		$this->w = $packet->hasFlag(PlayerAuthInputFlags::UP);
		$this->s = $packet->hasFlag(PlayerAuthInputFlags::DOWN);
		$this->a = $packet->hasFlag(PlayerAuthInputFlags::LEFT);
		$this->d = $packet->hasFlag(PlayerAuthInputFlags::RIGHT);

		$inputFlags = $packet->getInputFlags();

		//todo: サーバー権威のフラグをサポートする
		// metadata にある
		/**
		 * @see EntityMetadataFlags::SWIMMING
		 */
		if($this->lastInputFlags !== $inputFlags){
			$sneaking = Utils::resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_SNEAKING, PlayerAuthInputFlags::STOP_SNEAKING);
			$sprinting = Utils::resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_SPRINTING, PlayerAuthInputFlags::STOP_SPRINTING);
			$swimming = Utils::resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_SWIMMING, PlayerAuthInputFlags::STOP_SWIMMING);
			$gliding = Utils::resolveOnOffInputFlags($inputFlags, PlayerAuthInputFlags::START_GLIDING, PlayerAuthInputFlags::STOP_GLIDING);

			if($sneaking !== null){
				$this->sneaking = $sneaking;
			}
			if($swimming !== null){
				$this->swimming = $swimming;
			}
			if($sprinting !== null){
				$this->sprinting = $sprinting;
			}
			if($gliding !== null){
				$this->gliding = $gliding;
			}

			$this->lastInputFlags = $inputFlags;
		}


		$this->glide->update($this->gliding);
		$this->swim->update($this->swimming);
		$this->sprint->update($this->sprinting);
		$this->sneak->update($this->sneaking);

		$this->sneakChange->update();

		if($this->sneak->getFlag() !== $this->sneak->getLastFlag()){
			$this->sneakChange->onAction();
		}

		$this->lastForwardValue = $this->forwardValue;
		$this->forwardValue = $vz * 0.98;

		$this->lastStrafeValue = $this->strafeValue;
		$this->strafeValue = $vx * 0.98;

		$this->moveVecChange->update();

		if($this->forwardValue !== $this->lastForwardValue || $this->strafeValue !== $this->lastStrafeValue){
			$this->moveVecChange->onAction();
		}

		$this->jump = ($packet->hasFlag(PlayerAuthInputFlags::JUMPING));
	}
}
