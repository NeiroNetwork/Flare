<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check\list\packet\invalid;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class InvalidE extends BaseCheck{

	use ClassNameAsCheckIdTrait;
	use HandleEventCheckTrait;

	protected ?int $lastClientTick = null;

	public function getCheckGroup() : int{
		return CheckGroup::PACKET;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$this->reward();
		$player = $this->profile->getPlayer();

		if(is_null($this->lastClientTick)){
			$this->lastClientTick = $packet->getTick();
			return;
		}

		// todo: ActionRecord のcurrentTick引数をサーバーのtickじゃなくする
		// -> ラグによる誤検知がさらに減る
		// -> パケットのtickをリスポーン時にずっと固定されたままにされたら予期しない誤検知/検知回避がされる可能性がある(ここでパケットのtick検証をしているが、リスポーン時には一時ストップしているため)
		if($packet->getTick() - $this->lastClientTick !== 1 && $this->profile->getMovementData()->getRespawnRecord()->getTickSinceAction() >= 10){
			$this->fail(new ViolationFailReason("Invalid auth tick"));
		}

		$this->lastClientTick = $packet->getTick();
	}
}
