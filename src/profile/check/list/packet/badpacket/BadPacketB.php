<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\badpacket;

use NeiroNetwork\Flare\event\player\PlayerAttackEvent;
use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheck;
use NeiroNetwork\Flare\profile\check\HandleInputPacketCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class BadPacketB extends BaseCheck {
	use ClassNameAsCheckIdTrait;

	private string $hash;

	/**
	 * @var int[]
	 */
	protected array $attackTick;

	public function getCheckGroup(): int {
		return CheckGroup::PACKET;
	}

	public function onLoad(): void {
		$this->hash = $this->profile->getFlare()->getEventEmitter()->registerPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			function (PlayerAttackEvent $event): void {
				assert($event->getPlayer() === $this->profile->getPlayer());
				if ($this->tryCheck()) $this->handle($event);
			},
			false,
			EventPriority::MONITOR
		);
	}

	public function onUnload(): void {
		$this->profile->getFlare()->getEventEmitter()->unregisterPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerAttackEvent::class,
			$this->hash,
			EventPriority::MONITOR
		);
	}

	public function handle(PlayerAttackEvent $event): void {
		$this->reward();
		$entity = $event->getEntity();
		$player = $event->getPlayer();

		if (!$entity->isAlive()) {
			$hash = spl_object_hash($entity);
			if (!isset($this->attackTick[$hash])) {
				$tick = $this->profile->getServerTick();
				$this->attackTick[$hash] = isset($this->attackTick[$hash]) ? ($this->attackTick[$hash]) : $tick;
				$elapsed = $tick - $this->attackTick[$hash];
				if ($elapsed >= 4) {
					$this->fail(new ViolationFailReason("Attacking a dead entity"));
					unset($this->attackTick[$hash]);
				}
			}
		}
	}
}
