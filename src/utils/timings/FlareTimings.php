<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils\timings;

use pocketmine\utils\SingletonTrait;

class FlareTimings{

	use SingletonTrait {
		getInstance as Singleton__getInstance;
	}

	public EventEmitterTimings $eventEmitter;

	public function __construct(){
		$n = "Flare";
		$this->eventEmitter = new EventEmitterTimings($this, self::categoryPrefix($n . "(EventEmitter)", ""));
	}

	public static function categoryPrefix(string $category, string $main) : string{
		return "Plugin: " . $category . " Event: " . $main;
	}

	public static function global() : self{
		return self::Singleton__getInstance();
	}
}
