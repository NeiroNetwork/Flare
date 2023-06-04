<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\badpacket;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAction;

class BadPacketC extends BaseCheck{

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
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerActionPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();

		if($packet->action === PlayerAction::JUMP){
			$this->fail(new ViolationFailReason(""));
		}
	}
}
