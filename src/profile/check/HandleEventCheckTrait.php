<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use NeiroNetwork\Flare\utils\EventHandlerLink;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\DataPacket;

trait HandleEventCheckTrait{

	private EventHandlerLink $eventLink;

	public function onUnload() : void{
		parent::onUnload();
		$this->unregisterAllPacketHandler();
	}

	protected function unregisterAllPacketHandler() : void{
		$this->getEventLink()->unregisterAll();
	}

	protected function getEventLink() : EventHandlerLink{
		assert($this instanceof ICheck);
		return $this->eventLink ??= new EventHandlerLink($this->getObserver()->getProfile()->getFlare());
	}

	protected function registerSendPacketHandler(\Closure $handler) : void{
		assert($this instanceof ICheck);
		try{
			$packetClassName = $this->detectFirstParameterClassFromSignature($handler);
			$packetRef = new \ReflectionClass($packetClassName);
			$packetId = $packetRef->getConstant("NETWORK_ID");

			if($packetId === false){
				return;
			}
		}catch(\ReflectionException){
			return;
		}


		/**
		 * @var int $packetId
		 */

		$this->getEventLink()->add($this->profile->getFlare()->getEventEmitter()->registerSendPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			$packetId,
			function(mixed $packet) use ($handler) : void{
				if($this->tryCheck()){
					$handler($packet);
				}
			},
			false,
			EventPriority::MONITOR
		));
	}

	private function detectFirstParameterClassFromSignature(\Closure $signature) : string{
		try{
			$ref = new \ReflectionFunction($signature);
			$className = $ref->getParameters()[0]->getType()->getName();
		}catch(\ReflectionException $e){
			throw new \RuntimeException(previous: $e);
		}


		return $className;
	}

	/**
	 * @template T of DataPacket
	 *
	 * @param \Closure(T $packet): void $handler
	 *
	 * @return void
	 */
	protected function registerPacketHandler(\Closure $handler) : void{
		assert($this instanceof ICheck);
		try{
			$packetClassName = $this->detectFirstParameterClassFromSignature($handler);
			$packetRef = new \ReflectionClass($packetClassName);
			$packetId = $packetRef->getConstant("NETWORK_ID");

			if($packetId === false){
				return;
			}
		}catch(\ReflectionException){
			return;
		}


		/**
		 * @var int $packetId
		 */

		$this->getEventLink()->add($this->profile->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			$packetId,
			function(mixed $packet) use ($handler) : void{
				if($this->tryCheck()){
					$handler($packet);
				}
			},
			false,
			EventPriority::MONITOR
		));
	}

	/**
	 * @template T of Event
	 *
	 * @param \Closure(T $event): void $handler
	 *
	 * @return void
	 */
	protected function registerEventHandler(\Closure $handler) : void{
		assert($this instanceof ICheck);

		$eventName = $this->detectFirstParameterClassFromSignature($handler);

		$this->getEventLink()->add($this->profile->getFlare()->getEventEmitter()->registerPlayerEventHandler(
			$this->profile->getPlayer()->getUniqueId()->toString(),
			$eventName,
			function(mixed $event) use ($handler) : void{
				if($this->tryCheck()){
					$handler($event);
				}
			},
			false,
			EventPriority::MONITOR
		));
	}
}
