<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\motion;

use Closure;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\EventPriority;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class MotionA extends BaseCheck {
	use ClassNameAsCheckIdTrait;

	protected Vector3 $lastFrom;

	public function getCheckGroup(): int {
		return CheckGroup::MOVEMENT;
	}

	public function onLoad(): void {
		$this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handle"]),
			false,
			EventPriority::HIGH
		);

		$this->lastFrom = Vector3::zero();
	}

	protected function handle(PlayerAuthInputPacket $packet): void {
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$from = $md->getFrom();
		$to = $md->getTo();
		$step = $sd->getStep();

		if (
			$md->getAirRecord()->getLength() >= 2 &&
			$md->getImmobileRecord()->getTickSinceAction() >= 2 &&
			$md->getFlyRecord()->getTickSinceAction() >= 4 &&
			$md->getVoidRecord()->getTickSinceAction() >= 2 &&
			($md->getRairRecord()->getLength() >= 2 || $md->getAirRecord()->getLength() >= 6) &&
			$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
			$md->getAirRecord()->getLength() <= 300 && # 空中にいる時間が長くなるにつれて $accel は 0 に近づいてくるため
			$sd->getFlowRecord()->getTickSinceAction() >= 5 &&
			$md->getGlideRecord()->getTickSinceAction() >= 7 &&
			$sd->getCobwebRecord()->getTickSinceAction() >= 5 &&
			$sd->getClimbRecord()->getTickSinceAction() >= 5 &&
			$md->getMotionRecord()->getTickSinceAction() >= 3
		) {
			if (!$player->getEffects()->has(VanillaEffects::LEVITATION())) {
				$distY = ($to->y - $from->y);
				$lastDistY = ($from->y - $this->lastFrom->y);

				$accel = abs($distY - $lastDistY);
				$player->sendMessage("Accel: $accel");
				if ($accel < 0.0001) {
					$this->fail(new ViolationFailReason("Accel: $accel"));
				}
			}
		}

		$this->lastFrom = clone $from;
	}
}
