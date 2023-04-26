<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\movement\speed;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\NumericalSampling;
use NeiroNetwork\Flare\utils\Statistics;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedC extends BaseCheck implements HandleInputPacketCheck{

	use ClassNameAsCheckIdTrait;
	use HandleInputPacketCheckTrait;

	protected NumericalSampling $samples;
	protected int $speedTicks;

	public function onLoad() : void{
		$this->registerInputPacketHandler();
		$this->samples = new NumericalSampling(24);
		$this->speedTicks = 0;
	}

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}

	public function onEnable() : void{
		$this->samples->clear();
	}


	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		//$this->debug((string) $this->profile->getMovementData()->getDistance());
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$ki = $this->profile->getKeyInputs();

		if(
			$md->getJoinRecord()->getTickSinceAction() >= 200 &&
			$md->getTeleportRecord()->getTickSinceAction() >= 10 &&
			$md->getMotionRecord()->getTickSinceAction() >= 30 &&
			$md->getFlyRecord()->getTickSinceAction() >= 10 &&
			$ki->getGlideRecord()->getTickSinceAction() >= 30
		){

			if($player->getMovementSpeed() > 0.25){
				return;
			}

			$this->samples->add($md->getRealDeltaXZ());

			$speed = Statistics::average($this->samples->getAll());

			$ice = (($sd->getSlipRecord()->getTickSinceAction() <= 20) ? 0.675 : 0);
			$maxSpeed = (0.2 + 0.2 + $ice) * max(1.0, ($player->getMovementSpeed() * 10));

			if($player->getMovementSpeed() != 0.1){
				$this->speedTicks++;
				if($this->speedTicks === 1){
					$this->samples->clear();
				}
			}else{
				if($this->speedTicks >= 1){
					$this->samples->clear();
				}
				$this->speedTicks = 0;
			}

			if($speed > $maxSpeed){
				$this->fail(new ViolationFailReason("AvgSpeed: $speed"));
			}
		}else{
			$this->samples->clear();
		}
	}
}
