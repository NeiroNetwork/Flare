<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\event\EventPriority;

class InvalidC extends BaseCheck{

	use ClassNameAsCheckIdTrait;

	private string $hash;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->hash = $this->profile->getFlare()->getEventEmitter()->registerPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			function(PlayerAttackEvent $event) : void{
				assert($event->getPlayer() === $this->profile->getPlayer());
				if($this->tryCheck()){
					$this->handle($event);
				}
			},
			false,
			EventPriority::MONITOR
		);
	}

	public function handle(PlayerAttackEvent $event) : void{
		$this->reward();
		$entity = $event->getEntity();
		$player = $event->getPlayer();

		if($player === $entity){
			$this->fail(new ViolationFailReason("Attacking self"));
		}
	}

	public function onUnload() : void{
		$this->profile->getFlare()->getEventEmitter()->unregisterPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			$this->hash,
			EventPriority::MONITOR
		);
	}
}
