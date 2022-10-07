<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use Closure;
use NeiroNetwork\Flare\event\player\PlayerPacketLossEvent;
use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\check\Observer;
use NeiroNetwork\Flare\profile\data\KeyInputs;
use NeiroNetwork\Flare\profile\data\MovementData;
use NeiroNetwork\Flare\profile\data\SurroundData;
use pocketmine\player\Player;

class Profile {

	protected Flare $flare;

	protected Client $client;

	protected Player $player;

	protected Observer $observer;

	protected ?KeyInputs $keyInputs;
	protected ?MovementData $movementData;
	protected ?SurroundData $surroundData;

	protected bool $started;

	public function __construct(Flare $flare, Player $player) {
		$this->flare = $flare;
		$this->player = $player;
		$this->client = Client::create($player->getNetworkSession());
		$this->started = false;

		$this->movementData = null;
		$this->surroundData = null;
		$this->keyInputs = null;

		$this->observer = new Observer($this);
	}

	protected function registerChecks(Observer $o): void {
		$o->registerCheck(new MotionA($o));
	}

	public function start(): void {
		if (!$this->started) {
			$this->started = true;


			$this->flare->getEventEmitter()->registerPlayerEventHandler(
				$this->player->getUniqueId()->toString(),
				PlayerPacketLossEvent::class,
				Closure::fromCallable([$this, "handlePacketLoss"])
			);

			$this->movementData = new MovementData($this);
			$this->surroundData = new SurroundData($this);
			$this->keyInputs = new KeyInputs($this);

			$this->registerChecks($this->observer);
		}
	}

	public function getMovementData(): MovementData {
		return $this->movementData ?? throw new \Exception("must not be called before start");
	}

	public function getKeyInputs(): KeyInputs {
		return $this->keyInputs ?? throw new \Exception("must not be called before start");
	}

	public function getClient(): Client {
		return $this->client;
	}

	public function getFlare(): Flare {
		return $this->flare;
	}

	protected function handlePacketLoss(PlayerPacketLossEvent $event): void {
		$this->player->sendMessage("NACK: Packet Loss");
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function getServerTick(): int {
		return $this->flare->getPlugin()->getServer()->getTick();
	}

	/**
	 * Get the value of surroundData
	 *
	 * @return ?SurroundData
	 */
	public function getSurroundData(): ?SurroundData {
		return $this->surroundData;
	}
}
