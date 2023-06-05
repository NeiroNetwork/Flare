<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aim;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\NumericalSampling;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\InputMode;

class AimA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	protected NumericalSampling $deltaPitch;

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));

		$this->deltaPitch = new NumericalSampling(24);
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		if($this->profile->getInputMode() !== InputMode::MOUSE_KEYBOARD){
			return;
		}

		$this->reward();

		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$rotDelta = $md->getRotationDelta()->abs();

		if(
			abs($md->getRotation()->pitch) < 80 &&
			$rotDelta->yaw >= 1 &&
			$rotDelta->yaw <= 6
		){
			$this->deltaPitch->add($rotDelta->pitch);

			if(
				$this->deltaPitch->isMax() &&
				Utils::equalsArrayValues($this->deltaPitch->getAll(), 0.0)
			){
				$this->fail(new ViolationFailReason(""));
			}
		}
	}
}
