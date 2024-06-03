<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\motion;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MotionA extends BaseCheck{

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
		$ki = $this->profile->getKeyInputs();
		$from = $md->getFrom();
		$to = $md->getTo();

		if(
			$md->getImmobileRecord()->getTickSinceAction() >= 2 &&
			$md->getFlyRecord()->getTickSinceAction() >= 4 &&
			$md->getVoidRecord()->getTickSinceAction() >= 2 &&
			$md->getAirRecord()->getLength() >= 5 &&
			$md->getTeleportRecord()->getTickSinceAction() >= 3 &&
			$md->getAirRecord()->getLength() <= 300 && # 空中にいる時間が長くなるにつれて $accel は 0 に近づいてくるため
			$sd->getFlowRecord()->getTickSinceAction() >= 5 &&
			$ki->getGlideRecord()->getTickSinceAction() >= 7 &&
			$sd->getCobwebRecord()->getTickSinceAction() >= 5 &&
			$sd->getClimbRecord()->getTickSinceAction() >= 5 &&
			$md->getMotionRecord()->getTickSinceAction() >= 3 &&
			$md->getLevitationRecord()->getTickSinceAction() >= 4
		){
			$distY = ($to->y - $from->y);
			$lastDistY = ($from->y - $md->getLastFrom()->y);

			$accel = abs($distY - $lastDistY);
			$this->broadcastDebugMessage("accel: $accel");
			if($accel < 0.0001){
				$this->fail(new ViolationFailReason("Accel: $accel"));
			}

		}
	}
}
