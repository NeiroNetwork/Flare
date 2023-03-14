<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\reach;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\profile\data\MovementData;
use NeiroNetwork\Flare\utils\Math;
use pocketmine\entity\Entity;
use pocketmine\event\EventPriority;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use SplFixedArray;

class ReachB extends BaseCheck {
	use ClassNameAsCheckIdTrait;


	public function getCheckGroup(): int {
		return CheckGroup::PACKET;
	}

	public function onLoad(): void {

		$notifier = new ActionNotifier();
		$notifier->notifyEnd(function (ActionRecord $record): void {
			$this->handleTriggerAim();
		});

		$this->profile->getCombatData()->getTriggerAimRecord()->notify($notifier);
	}

	public function onUnload(): void {
	}

	public function handleTriggerAim(): void {
		$this->reward();
		$cd = $this->profile->getCombatData();
		$aimingAt = $cd->getClientAimingAt();
		$aiming = $cd->getClientAiming();
		$player = $this->profile->getPlayer();

		if ($aimingAt instanceof Vector3 && $aiming instanceof Entity) {
			$pos = $player->getEyePos();

			$reach = $pos->distanceSquared($aimingAt);

			if ($reach > 9.0 + 4.0) {
				$this->fail(new ViolationFailReason("Reach: {$reach}"));
			}

			$this->broadcastDebugMessage((string) $reach);
		}
	}
}
