<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\combat\aim;


use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\Math;
use NeiroNetwork\Flare\utils\NumericalSampling;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimC extends BaseCheck{

	use HandleEventCheckTrait;
	use ClassNameAsCheckIdTrait;

	protected NumericalSampling $deltaPitch;
	protected float $gcd;

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));

		$this->deltaPitch = new NumericalSampling(14);
		$this->gcd = 0;

	}

	public function isExperimental() : bool{
		return true;
	}

	public function getCheckGroup() : int{
		return CheckGroup::COMBAT;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();

		$rotDelta = $md->getRotationDelta()->abs();
		$rot = $md->getRotation();

		if($rotDelta->pitch <= 0.96 && $rotDelta->pitch > 0.1 && abs($rot->pitch) <= 70){
			$this->deltaPitch->add($rotDelta->pitch);
		}

		if(!$this->deltaPitch->isMax()){
			return;
		}

		$this->reward();
		$this->preReward(3);

		$getGCD = function() : float{
			$list = $this->deltaPitch->getAll();
			$base = array_shift($list);
			return Math::getArrayGCD($base, $list);
		};

		$gcd = $getGCD();
		$gcdDiff = abs($this->gcd - $gcd);

		if($gcdDiff > 0.001){
			if($this->gcd > 0.001){
				$this->deltaPitch->add($this->gcd);
				$gcd = $getGCD();
				$gcdDiff = abs($this->gcd - $gcd);
			}
		}

		if($gcd < 0.00001){
			if($this->preFail()){
				$this->fail(new ViolationFailReason("GCD: {$gcd}"));
			}
		}

		$this->gcd = $gcd;
		$this->deltaPitch->clear();
	}
}
