<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\badpacket;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;

class BadPacketB extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	/**
	 * @var int[]
	 */
	protected array $attackTick;
	private string $hash;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function onLoad() : void{
		$this->registerEventHandler($this->handle(...));
	}

	public function handle(PlayerAttackEvent $event) : void{
		$this->reward();
		$entity = $event->getEntity();
		$player = $event->getPlayer();

		if(!$entity->isAlive()){
			$hash = spl_object_hash($entity);
			if(!isset($this->attackTick[$hash])){
				$tick = $this->profile->getServerTick();
				$this->attackTick[$hash] = isset($this->attackTick[$hash]) ? ($this->attackTick[$hash]) : $tick;
				$elapsed = $tick - $this->attackTick[$hash];
				if($elapsed >= 4){
					$this->fail(new ViolationFailReason("Attacking a dead entity"));
					unset($this->attackTick[$hash]);
				}
			}
		}
	}
}
