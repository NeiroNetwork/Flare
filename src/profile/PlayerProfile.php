<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use Closure;
use NeiroNetwork\Flare\event\player\PlayerPacketLossEvent;
use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionB;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionC;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedA;
use NeiroNetwork\Flare\profile\check\Observer;
use NeiroNetwork\Flare\profile\data\CombatData;
use NeiroNetwork\Flare\profile\data\KeyInputs;
use NeiroNetwork\Flare\profile\data\MovementData;
use NeiroNetwork\Flare\profile\data\SurroundData;
use NeiroNetwork\Flare\profile\data\TransactionData;
use NeiroNetwork\Flare\profile\style\FlareStyle;
use NeiroNetwork\Flare\profile\style\PeekAntiCheatStyle;
use NeiroNetwork\Flare\utils\EventHandlerLink;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\player\Player;

class PlayerProfile implements Profile {

	protected Flare $flare;

	protected Client $client;

	protected Player $player;

	protected LogStyle $logStyle;

	protected Observer $observer;

	protected ?KeyInputs $keyInputs;
	protected ?MovementData $movementData;
	protected ?SurroundData $surroundData;
	protected ?CombatData $combatData;
	protected ?TransactionData $transactionData;

	protected EventHandlerLink $eventLink;

	protected bool $started;

	public function __construct(Flare $flare, Player $player) {
		$this->flare = $flare;
		$this->player = $player;
		$this->client = Client::create($player->getNetworkSession());
		$this->started = false;

		$this->eventLink = new EventHandlerLink($flare);

		$this->movementData = null;
		$this->surroundData = null;
		$this->combatData = null;
		$this->transactionData = null;
		$this->keyInputs = null;

		$this->logStyle = new FlareStyle;

		$this->observer = new Observer($this);
	}

	public function getEventHandlerLink(): EventHandlerLink {
		return $this->eventLink;
	}

	public function getLogStyle(): LogStyle {
		return $this->logStyle;
	}

	protected function registerChecks(Observer $o): void {
		$o->registerCheck(new MotionA($o));
		$o->registerCheck(new MotionB($o));
		$o->registerCheck(new MotionC($o));
		$o->registerCheck(new SpeedA($o));
	}

	public function start(): void {
		if (!$this->started) {
			$this->started = true;


			$this->eventLink->add($this->flare->getEventEmitter()->registerPlayerEventHandler(
				$this->player->getUniqueId()->toString(),
				PlayerPacketLossEvent::class,
				Closure::fromCallable([$this, "handlePacketLoss"])
			));

			$this->movementData = new MovementData($this);
			$this->surroundData = new SurroundData($this);
			$this->combatData = new CombatData($this);
			$this->transactionData = new TransactionData($this);
			$this->keyInputs = new KeyInputs($this);


			$this->registerChecks($this->observer);
		}
	}

	public function close(): void {
		if ($this->started) {
			$this->eventLink->unregisterAll();

			if (!$this->observer->isClosed()) {
				$this->observer->close();
			}

			$this->movementData = null;
			$this->surroundData = null;
			$this->combatData = null;
			$this->transactionData = null;
			$this->keyInputs = null;
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
	 * @return SurroundData
	 */
	public function getSurroundData(): SurroundData {
		return $this->surroundData ?? throw new \Exception("must not be called before start");
	}

	/**
	 * Get the value of combatData
	 *
	 * @return CombatData
	 */
	public function getCombatData(): CombatData {
		return $this->combatData ?? throw new \Exception("must not be called before start");
	}

	/**
	 * Get the value of transactionData
	 *
	 * @return TransactionData
	 */
	public function getTransactionData(): TransactionData {
		return $this->transactionData ?? throw new \Exception("must not be called before start");
	}

	public function getCommandSender(): CommandSender {
		return $this->player;
	}

	public function getName(): string {
		return $this->getCommandSender()->getName();
	}

	public function getPing(): int {
		return $this->player->getNetworkSession()->getPing() ?? -1; // return null?
	}
}
