<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class InvalidB extends BaseCheck implements HandleInputPacketCheck{

	use HandleInputPacketCheckTrait;
	use ClassNameAsCheckIdTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$ki = $this->profile->getKeyInputs();

		if($ki->getSprintRecord()->getLength() > 1 && $ki->getSneakRecord()->getLength() > 1){
			if($sd->getFlowRecord()->getTickSinceAction() >= 5){
				$this->fail(new ViolationFailReason("Sneak & Sprint"));
			}
		}

		if($player->getEffects()->has(VanillaEffects::BLINDNESS()) && $packet->hasFlag(PlayerAuthInputFlags::START_SPRINTING)){
			$this->fail(new ViolationFailReason("Sprinting in blind"));
		}

		if($player->getHungerManager()->getFood() <= (3.0 * 2) && $packet->hasFlag(PlayerAuthInputFlags::START_SPRINTING)){
			$this->fail(new ViolationFailReason("Sprinting on low food <=6.0"));
		}
	}
}
