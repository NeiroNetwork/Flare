<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\speed;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedE extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	protected float $lastRealDeltaXZ;

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));

	}

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$ki = $this->profile->getKeyInputs();

		$tick = $this->profile->getServerTick();


		$diffYaw = abs($md->getRotationDelta()->yaw);
		if(
			$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
			$md->getJumpRecord()->getTickSinceAction() >= 6 &&
			$sd->getSlipRecord()->getTickSinceAction() >= 6 &&
			$sd->getCobwebRecord()->getTickSinceAction() >= 10 &&
			$sd->getCollideUpdateRecord()->getTickSinceAction() >= 20 &&
			$md->getMotionRecord()->getTickSinceAction() >= 15 &&
			$sd->getFlowRecord()->getTickSinceAction() >= 15 &&
			(($md->getSpeedChangeRecord()->getEndTick() - 1) < $md->getMoveRecord()->getStartTick()) &&
			(($ki->getMoveVecChangeRecord()->getEndTick() - 1) < $md->getMoveRecord()->getStartTick()) &&
			$ki->getSneakChangeRecord()->getTickSinceAction() >= 5 &&
			count($sd->getComplexBlocks()) <= 0 &&
			count($sd->getTouchingBlocks()) <= 0 &&
			$diffYaw < 20 &&
			$md->getOnGroundRecord()->getLength() >= 5
		){

			if($md->getMoveRecord()->getLength() < 8 && $md->getMoveRecord()->getLength() > 2){
				$diff = $md->getRealDeltaXZ() - $md->getLastRealDeltaXZ();
				if($diff < 1.0e-6){
					if($this->preFail()){
						$this->fail(new ViolationFailReason("Diff: $diff"));
					}
				}
			}
		}
	}
}
