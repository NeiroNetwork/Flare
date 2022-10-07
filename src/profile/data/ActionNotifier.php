<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use Closure;
use pocketmine\utils\Utils;

class ActionNotifier {

	/**
	 * @var Closure[]
	 * 
	 * only InstantActionRecord
	 */
	protected array $onAction = [];

	/**
	 * @var Closure[]
	 */
	protected array $onEnd = [];

	/**
	 * @var Closure[]
	 */
	protected array $onStart = [];

	/**
	 * @var Closure[]
	 */
	protected array $onUpdate = [];

	public function __construct() {
	}

	public function notifyUpdate(Closure $closure): void {
		Utils::validateCallableSignature(function (ActionRecord $record, bool $flag): void {
		}, $closure);

		$this->onUpdate[] = $closure;
	}

	public function notifyEnd(Closure $closure): void {
		Utils::validateCallableSignature(function (ActionRecord $record): void {
		}, $closure);

		$this->onEnd[] = $closure;
	}

	public function notifyStart(Closure $closure): void {
		Utils::validateCallableSignature(function (ActionRecord $record): void {
		}, $closure);

		$this->onStart[] = $closure;
	}

	public function notifyAction(Closure $closure): void {
		Utils::validateCallableSignature(function (ActionRecord $record): void {
		}, $closure);

		$this->onAction[] = $closure;
	}

	public function onUpdate(ActionRecord $record, bool $flag): void {
		foreach ($this->onUpdate as $closure) {
			($closure)($record, $flag);
		}
	}

	public function onEnd(ActionRecord $record): void {
		foreach ($this->onEnd as $closure) {
			($closure)($record);
		}
	}

	public function onStart(ActionRecord $record): void {
		foreach ($this->onStart as $closure) {
			($closure)($record);
		}
	}

	public function onAction(ActionRecord $record): void {
		foreach ($this->onAction as $closure) {
			($closure)($record);
		}
	}
}
