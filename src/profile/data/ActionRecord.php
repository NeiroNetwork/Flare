<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use pocketmine\Server;

class ActionRecord{

	/**
	 * @var int
	 */
	protected int $tickSinceAction;

	/**
	 * @var int
	 */
	protected int $length;

	/**
	 * @var int
	 */
	protected int $endTick;

	/**
	 * @var int
	 */
	protected int $startTick;

	/**
	 * @var bool
	 */
	protected bool $lastFlag;

	/**
	 * @var bool
	 */
	protected bool $flag;

	/**
	 * @var ActionNotifier[]
	 */
	protected array $notifiers;

	protected ?self $last;

	public function __construct(){
		$this->tickSinceAction = 0;
		$this->length = 0;
		$this->endTick = 0;
		$this->startTick = 0;
		$this->last = null;

		$this->lastFlag = false;
		$this->flag = false;

		$this->notifiers = [];
	}

	public function notify(ActionNotifier $notifier) : void{
		$this->notifiers[spl_object_hash($notifier)] = $notifier;
	}

	public function stopNotify(ActionNotifier $notifier) : void{
		unset($this->notifiers[spl_object_hash($notifier)]);
	}

	public function getTickSinceAction() : int{ #tips: これは >= 0(0以上の値ならok) と検証することで同時にFlagが有効でないことを確かめることができる
		return $this->tickSinceAction;
	}

	public function getLength() : int{
		return $this->length;
	}

	public function getEndTick() : int{
		return $this->endTick;
	}

	public function getStartTick() : int{
		return $this->startTick;
	}

	public function getLastFlag() : bool{
		return $this->lastFlag;
	}

	public function getFlag() : bool{
		return $this->flag;
	}

	public function getLastOrSelf() : ActionRecord{
		return $this->getLast() ?? $this;
	}

	/**
	 * @return ActionRecord
	 */
	public function getLast() : ?ActionRecord{
		return $this->last;
	}

	public function update(bool $flag = false, ?int $currentTick = null) : void{
		$this->notifyUpdate($flag);
		$currentTick ??= Server::getInstance()->getTick();
		$this->last = clone $this;
		$this->last->last = null;

		#ここにおいて $this->flag は 一つまえのflag
		if($this->flag && !$flag){ #off
			$this->endTick = $currentTick;
			$this->notifyEnd();
		}elseif(!$this->flag && $flag){
			$this->startTick = $currentTick;
			$this->notifyStart();
		}

		if($flag){
			$this->length = 1 + ($currentTick - ($this->startTick));
			$this->tickSinceAction = -1;
		}else{
			$this->length = -1;
			$this->tickSinceAction = $currentTick - ($this->endTick);
		}

		$this->lastFlag = $this->flag;
		$this->flag = $flag;

		#関数外において $this->flag は update() に渡した flag と同じ
	}

	protected function notifyUpdate(bool $flag) : void{
		foreach($this->notifiers as $notifier){
			$notifier->onUpdate($this, $flag);
		}
	}

	protected function notifyEnd() : void{
		foreach($this->notifiers as $notifier){
			$notifier->onEnd($this);
		}
	}

	protected function notifyStart() : void{
		foreach($this->notifiers as $notifier){
			$notifier->onStart($this);
		}
	}

	protected function notifyAction() : void{
		foreach($this->notifiers as $notifier){
			$notifier->onAction($this);
		}
	}
}
