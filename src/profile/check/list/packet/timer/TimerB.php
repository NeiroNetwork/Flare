<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\timer;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\NumericalSampling;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class TimerB extends BaseCheck implements HandleInputPacketCheck{

	use HandleInputPacketCheckTrait;
	use ClassNameAsCheckIdTrait;

	protected NumericalSampling $diff;
	protected float $lastTime;
	protected float $packetsSinceMax;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->registerInputPacketHandler();

		$this->diff = new NumericalSampling(15);
		$this->lastTime = -1;
		$this->packetsSinceMax = 0;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();

		$curr = Utils::getTimeMilis();

		if(
			$md->getJoinRecord()->getTickSinceAction() < 120 ||
			!$this->profile->isServerStable()
		){
			return;
		}

		$diff = $curr - $this->lastTime;

		if($diff > 0 && $diff <= 500){
			$this->diff->add($diff);
			$this->preReward();

			if($this->diff->isMax() && $this->packetsSinceMax++ >= 3){
				$fast = true;
				foreach($this->diff->getAll() as $value){
					if($value > 40){
						$fast = false;
						break;
					}
				}

				if($fast){
					$this->fail(new ViolationFailReason("All diff is < 40"));
				}
			}
		}else{
			$this->diff->clear();
			$this->packetsSinceMax = 0;
		}


		$this->lastTime = $curr;
	}
}
