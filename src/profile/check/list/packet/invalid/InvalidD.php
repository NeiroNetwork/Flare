<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class InvalidD extends BaseCheck{

	use HandleEventCheckTrait;
	use ClassNameAsCheckIdTrait;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$ki = $this->profile->getKeyInputs();
		$delta = $md->getClientPredictedDelta();
		$realDelta = $md->getRealDelta();

		if($delta->y == 0 && $realDelta->y == 0){
			if(
				$md->getTeleportRecord()->getTickSinceAction() >= 6 &&
				$md->getFlyRecord()->getTickSinceAction() >= 20 &&
				$ki->getSwimRecord()->getTickSinceAction() >= 20 &&
				$md->getImmobileRecord()->getTickSinceAction() >= 5 &&
				$md->getLevitationRecord()->getTickSinceAction() >= 4
			){
				$this->fail(new ViolationFailReason("A"));
			}
		}

		// todo: check
		// $grd = ($md->getAirRecord()->getLength() >= 5 || $md->getRairRecord()->getLength() >= 4);
		// if($grd){
		// 	$grd = ($md->getRonGroundRecord()->getLength() >= 3);
		// }
		// if($md->getClientOnGroundRecord()->getLength() > 1 && $grd){
		// 	$this->fail(new ViolationFailReason("B"));
		// }
	}
}
