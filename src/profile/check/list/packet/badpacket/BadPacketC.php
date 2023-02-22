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
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\PlayerAction;

class BadPacketC extends BaseCheck {
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
		$this->hash = $this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerActionPacket::NETWORK_ID,
			function (PlayerActionPacket $packet): void {
				if ($this->tryCheck()) $this->handle($packet);
			},
			false,
			EventPriority::MONITOR
		);
	}

	public function onUnload(): void {
		$this->profile->getFlare()->getEventEmitter()->unregisterPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			PlayerActionPacket::NETWORK_ID,
			$this->hash,
			EventPriority::MONITOR
		);
	}

	public function handle(PlayerActionPacket $packet): void {
		$this->reward();
		$player = $this->profile->getPlayer();

		if ($packet->action === PlayerAction::JUMP) {
			$this->fail(new ViolationFailReason(""));
		}
	}
}
