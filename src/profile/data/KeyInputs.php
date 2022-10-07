<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use Closure;
use NeiroNetwork\Flare\profile\Profile;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class KeyInputs {

	protected bool $w;
	protected bool $a;
	protected bool $s;
	protected bool $d;
	protected bool $jump;

	public function __construct(protected Profile $profile) {
		$uuid = $profile->getPlayer()->getUniqueId()->toString();
		$this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handleInput"]),
			false,
			EventPriority::LOWEST
		);

		ProfileData::autoPropertyValue($this);
	}

	public function w(): bool {
		return $this->w;
	}

	public function a(): bool {
		return $this->a;
	}

	public function s(): bool {
		return $this->s;
	}

	public function d(): bool {
		return $this->d;
	}

	public function jump(): bool {
		return $this->jump;
	}

	public function anyMove(): bool {
		return $this->w || $this->a || $this->s || $this->d;
	}

	protected function handleInput(PlayerAuthInputPacket $packet): void {
		$vz = $packet->getMoveVecZ();
		$vx = $packet->getMoveVecX();
		$this->w = $vz > 0;
		$this->s = $vz < 0;
		$this->a = $vx > 0;
		$this->d = $vx < 0;

		$this->jump = ($packet->hasFlag(PlayerAuthInputFlags::JUMPING));
	}
}
