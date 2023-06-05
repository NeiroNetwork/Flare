<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use NeiroNetwork\Flare\Flare;
use pocketmine\event\HandlerListManager;
use pocketmine\event\RegisteredListener;

class EventHandlerLink{

	/**
	 * @var string[]
	 */
	protected array $links;

	/**
	 * @var RegisteredListener[]
	 */
	protected array $listeners;

	public function __construct(protected Flare $flare){
		$this->links = [];
		$this->listeners = [];
	}

	public function add(string|RegisteredListener $link) : void{
		if(is_string($link)){
			$this->links[$link] = true;
		}elseif($link instanceof RegisteredListener){
			$this->listeners[spl_object_hash($link)] = $link;
		}
	}


	public function remove(string|RegisteredListener $link) : void{
		if(is_string($link)){
			unset($this->links[$link]);
		}elseif($link instanceof RegisteredListener){
			unset($this->listeners[spl_object_hash($link)]);
		}
	}

	public function unregisterAll() : void{
		foreach($this->links as $hash => $_){
			$this->flare->getEventEmitter()->unregisterAll($hash);
		}

		foreach($this->listeners as $listener){
			HandlerListManager::global()->unregisterAll($listener);
		}
	}
}
