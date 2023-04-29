<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\motion;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MotionB extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$cd = $this->profile->getCombatData();
		$ki = $this->profile->getKeyInputs();
		$from = $md->getFrom();
		$to = $md->getTo();
		$lastFrom = $md->getLastFrom();

		$step = $sd->getStep();
		$climb = $step->canClimb();
		$rair = $md->getRairRecord();
		$air = $md->getAirRecord();
		$ronGround = $md->getRonGroundRecord();
		$onGround = $md->getOnGroundRecord();

		// $player->sendMessage("air: {$air->getLength()}, rair: {$rair->getLength()}");

		if(!$climb){
			if(
				(
					($air->getLength() >= 6 && $ronGround->getLength() >= 5) || # RonGround を bypass した場合は Air が対応する
					($rair->getLength() >= 16 && $onGround->getLength() >= 5) || # OnGround を bypass した場合は Rair が対応する
					($rair->getLength() >= 12 && $air->getLength() >= 12)
				) &&
				$md->getImmobileRecord()->getTickSinceAction() >= 2 &&
				$md->getFlyRecord()->getTickSinceAction() >= 4 &&
				$md->getVoidRecord()->getTickSinceAction() >= 2 &&
				$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
				$sd->getFlowRecord()->getTickSinceAction() >= 4 &&
				$sd->getClimbRecord()->getTickSinceAction() >= 12 &&
				$md->getMotionRecord()->getTickSinceAction() >= 12 &&
				$sd->getCobwebRecord()->getTickSinceAction() >= 5 &&
				$sd->getHitHeadRecord()->getTickSinceAction() >= 4 &&
				$ki->getGlideRecord()->getTickSinceAction() >= 7 &&
				$cd->getKnockbackRecord()->getTickSinceAction() >= 20
			){
				$this->preReward();
				$deltaY = ($to->y - $from->y);
				$lastDeltaY = ($from->y - $lastFrom->y);

				$expectedDeltaY = MinecraftPhysics::nextFreefallVelocity(new Vector3(0, $lastDeltaY, 0))->y;

				$diff = abs($expectedDeltaY - $deltaY);
				$squaredDiff = $diff * 100;
				// $player->sendMessage("diff: $squaredDiff");
				if($squaredDiff > 0.6){
					if($this->preFail()){
						$this->fail(new ViolationFailReason("Diff: $diff"));
					}
				}
			}
		}
	}
}
