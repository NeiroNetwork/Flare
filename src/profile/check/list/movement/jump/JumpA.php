<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\jump;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\profile\data\ActionNotifier;
use NeiroNetwork\Flare\profile\data\ActionRecord;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class JumpA extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	protected ?Vector3 $motion;
	protected bool $jumpSprinting;

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function onLoad() : void{

		$this->registerPacketHandler($this->handle(...));


		$notifier = new ActionNotifier();
		$notifier->notifyAction(function(ActionRecord $record) : void{
			$md = $this->profile->getMovementData();
			$this->motion = $md->getClientPredictedDelta();
			$this->motion->y = $this->profile->getMovementData()->getJumpVelocity();

			$this->jumpSprinting = $this->profile->getPlayer()->isSprinting();
		});
		$this->profile->getKeyInputs()->getStartJumpRecord()->notify($notifier);

		$this->motion = null;
		$this->jumpSprinting = false;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$cd = $this->profile->getCombatData();
		$ki = $this->profile->getKeyInputs();

		if($this->motion !== null){
			if(
				abs($md->getRotation()->yaw - $md->getLastRotation()->yaw) > 3 ||
				$sd->getHitHeadRecord()->getLength() >= 1 ||
				$md->getOnGroundRecord()->getLength() >= 1 ||
				$md->getMotionRecord()->getTickSinceAction() <= 20 ||
				$md->getTeleportRecord()->getTickSinceAction() <= 6 ||
				$ki->getGlideRecord()->getTickSinceAction() <= 8 ||
				$sd->getBounceRecord()->getTickSinceAction() <= 20 ||
				$sd->getFlowRecord()->getTickSinceAction() <= 10 ||
				$sd->getSlipRecord()->getTickSinceAction() <= 10 ||
				$sd->getCobwebRecord()->getTickSinceAction() <= 5 ||
				$md->getFlyRecord()->getTickSinceAction() <= 5 ||
				$md->getLevitationRecord()->getTickSinceAction() <= 4 ||
				$md->getImmobileRecord()->getTickSinceAction() <= 2 ||
				count($sd->getTouchingBlocks()) > 0 ||
				$this->motion->y < 0 ||
				$player->isSprinting() !== $this->jumpSprinting
			){
				$this->motion = null;
				$this->jumpSprinting = false;
				return;
			}


			$rot = $md->getRotation();
			$motion = $md->getClientPredictedDelta();

			$sprintMotion = MinecraftPhysics::moveFlying(
				$ki->forwardValue() * ($player->getMovementSpeed() * 10),
				$ki->strafeValue() * ($player->getMovementSpeed() * 10),
				$rot->yaw,
				MinecraftPhysics::FRICTION_AIR
			);

			$this->motion = MinecraftPhysics::nextFreefallVelocity($this->motion->addVector($sprintMotion));

			$motionLength = $motion->lengthSquared();
			$predictionLength = $this->motion->lengthSquared();

			$diff = 0;
			if($motionLength <= $predictionLength){
				$diff = $this->motion->subtractVector($motion)->length();
			}

			if($diff > 0.07 && $md->getAirRecord()->getLength() >= 5){
				$this->fail(new ViolationFailReason("Diff: $diff"));
			}

			$this->broadcastDebugMessage("m: {$motionLength} pred: {$predictionLength} diff: {$diff}");
		}
	}
}
