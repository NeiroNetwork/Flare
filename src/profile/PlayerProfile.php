<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use Closure;
use NeiroNetwork\Flare\event\player\PlayerPacketLossEvent;
use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\check\ICheck;
use NeiroNetwork\Flare\profile\check\list\combat\aim\AimA;
use NeiroNetwork\Flare\profile\check\list\combat\aim\AimC;
use NeiroNetwork\Flare\profile\check\list\combat\reach\ReachA;
use NeiroNetwork\Flare\profile\check\list\movement\jump\JumpA;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionB;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionC;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionD;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedA;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedB;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedC;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedD;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedE;
use NeiroNetwork\Flare\profile\check\list\packet\badpacket\BadPacketA;
use NeiroNetwork\Flare\profile\check\list\packet\badpacket\BadPacketB;
use NeiroNetwork\Flare\profile\check\list\packet\badpacket\BadPacketC;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidA;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidB;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidC;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidD;
use NeiroNetwork\Flare\profile\check\list\packet\timer\TimerA;
use NeiroNetwork\Flare\profile\check\list\packet\timer\TimerB;
use NeiroNetwork\Flare\profile\check\list\packet\timer\TimerC;
use NeiroNetwork\Flare\profile\check\Observer;
use NeiroNetwork\Flare\profile\data\CombatData;
use NeiroNetwork\Flare\profile\data\KeyInputs;
use NeiroNetwork\Flare\profile\data\MovementData;
use NeiroNetwork\Flare\profile\data\SurroundData;
use NeiroNetwork\Flare\profile\data\TransactionData;
use NeiroNetwork\Flare\profile\style\FlareStyle;
use NeiroNetwork\Flare\profile\style\PeekAntiCheatStyle;
use NeiroNetwork\Flare\reporter\LogReportContent;
use NeiroNetwork\Flare\utils\EventHandlerLink;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use RuntimeException;

class PlayerProfile implements Profile {
	use CooldownLoggingTrait;

	protected Flare $flare;

	protected Client $client;

	protected Player $player;

	protected LogStyle $logStyle;

	protected Observer $observer;

	protected Config $config;

	protected ?KeyInputs $keyInputs;
	protected ?MovementData $movementData;
	protected ?SurroundData $surroundData;
	protected ?CombatData $combatData;
	protected ?TransactionData $transactionData;

	protected EventHandlerLink $eventLink;

	protected int $inputMode;
	protected string $inputModeName;

	protected bool $dataReportEnabled;

	protected bool $started;

	public function __construct(Flare $flare, Player $player) {
		$this->flare = $flare;
		$this->player = $player;
		$this->config = $flare->getConfig()->getPlayerConfigStore()->get($player);
		$this->client = Client::create($player->getNetworkSession());
		$this->started = false;

		$this->eventLink = new EventHandlerLink($flare);

		$conf = $this->getConfig();

		$this->alertCooldown = $conf->get("alert_cooldown");
		$this->alertEnabled = $conf->get("alert");

		$this->logCooldown = $conf->get("log_cooldown");
		$this->logEnabled = $conf->get("log");

		$this->verboseEnabled = $conf->get("verbose");

		/**
		 * // fixme: start後は無理やり変更しても反映されない
		 * @see MovementData ::__construct
		 */
		$this->dataReportEnabled = $conf->get("collection");

		$this->movementData = null;
		$this->surroundData = null;
		$this->combatData = null;
		$this->transactionData = null;
		$this->keyInputs = null;

		$this->inputMode = -1;
		$this->inputModeName = "unknown";

		$this->logStyle = LogStyle::search($this->config->get("log_style")) ?? throw new RuntimeException("log style not found");

		$this->observer = new Observer($this);

		$this->eventLink->add($this->getFlare()->getEventEmitter()->registerPacketHandler(
			$this->player->getUniqueId()->toString(),
			PlayerAuthInputPacket::NETWORK_ID,
			Closure::fromCallable([$this, "handleInput"]),
			false,
			EventPriority::HIGH
		));
	}

	public function getEventHandlerLink(): EventHandlerLink {
		return $this->eventLink;
	}

	public function getLogStyle(): LogStyle {
		return $this->logStyle;
	}

	protected function registerChecks(Observer $o): void {
		// todo: glob all checks?
		{
			$o->registerCheck(new MotionA($o));
			$o->registerCheck(new MotionB($o));
			$o->registerCheck(new MotionC($o));
			$o->registerCheck(new MotionD($o));
		} {
			$o->registerCheck(new SpeedA($o));
			$o->registerCheck(new SpeedB($o));
			$o->registerCheck(new SpeedC($o));
			$o->registerCheck(new SpeedD($o));
			$o->registerCheck(new SpeedE($o));
		} {
			$o->registerCheck(new JumpA($o));
		} {
			$o->registerCheck(new BadPacketA($o));
			$o->registerCheck(new BadPacketB($o));
			$o->registerCheck(new BadPacketC($o));
		} {
			$o->registerCheck(new InvalidA($o));
			$o->registerCheck(new InvalidB($o));
			$o->registerCheck(new InvalidC($o));
			$o->registerCheck(new InvalidD($o));
		} {
			$o->registerCheck(new TimerA($o));
			$o->registerCheck(new TimerB($o));
			$o->registerCheck(new TimerC($o));
		} {
			$o->registerCheck(new AimA($o));
			$o->registerCheck(new AimC($o));
		} {
			$o->registerCheck(new ReachA($o));
		}

		// グループ分けみたいなことをしてみたけど

		// todo: Aim(C) の 1.0e-4以下のpitch diffを削除 (たまにある誤検知が直るかな？)
		// finished: Speed(E) で移動速度の加速度検証 (move length 16 tick以内の時前回と同じ速度だったら検知？)
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
			$this->shutdown();
		}
	}

	public function shutdown(): void {
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

			$this->observer = new Observer($this);

			$this->started = false;
		}
	}

	public function reload(): void {
		if ($this->started) {
			$this->shutdown();
			$this->start();
		}
	}

	protected function handleInput(PlayerAuthInputPacket $packet): void {
		$player = $this->player;
		$inputMode = $packet->getInputMode();

		if ($inputMode !== $this->inputMode) {
			$toName = Utils::getNiceName(Utils::getEnumName(InputMode::class, $inputMode) ?? "unknown<{$inputMode}>"); // 重い？
			$fromName = $this->inputModeName;

			$this->flare->getReporter()->report(new LogReportContent(Flare::PREFIX . "§b{$player->getName()} §fが入力方法を変更しました §d($fromName -> $toName)", $this->flare));

			$this->inputMode = $inputMode;
			$this->inputModeName = $toName;
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

	public function isServerStable(): bool {
		$s = $this->flare->getPlugin()->getServer();

		if ($s->getTicksPerSecond() < 19.975) {
			return false;
		}

		if ($s->getTicksPerSecondAverage() < 19.975) {
			return false;
		}

		if ($this->getFlare()->getTickProcessor()->getTimeSinceLastTick() > 200) {
			return false;
		}

		if ($this->flare->getTickProcessor()->getOverloadRecord()->getTickSinceAction() < 200) {
			return false;
		}

		return true;
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
		return Utils::getPing($this->player);
	}

	/**
	 * Get the value of config
	 *
	 * @return Config config
	 * 
	 * fixme: delete? internal? protected?
	 */
	public function getConfig(): Config {
		return $this->config;
	}

	/**
	 * Get the value of dataReportEnabled
	 *
	 * @return bool
	 */
	public function isDataReportEnabled(): bool {
		return $this->dataReportEnabled;
	}


	/**
	 * Set the value of logStyle
	 *
	 * @param LogStyle $logStyle
	 *
	 * @return self
	 */
	public function setLogStyle(LogStyle $logStyle): self {
		$this->logStyle = $logStyle;

		return $this;
	}

	/**
	 * Get the value of verboseEnabled
	 *
	 * @return bool
	 */
	public function isVerboseEnabled(): bool {
		return $this->verboseEnabled;
	}

	/**
	 * Set the value of verboseEnabled
	 *
	 * @param bool $verboseEnabled
	 *
	 * @return self
	 */
	public function setVerboseEnabled(bool $verboseEnabled): self {
		$this->verboseEnabled = $verboseEnabled;

		return $this;
	}

	/**
	 * Get the value of inputMode
	 *
	 * @return int
	 */
	public function getInputMode(): int {
		return $this->inputMode;
	}

	/**
	 * Get the value of inputModeName
	 *
	 * @return string
	 */
	public function getInputModeName(): string {
		return $this->inputModeName;
	}

	/**
	 * Get the value of observer
	 *
	 * @return Observer
	 */
	public function getObserver(): Observer {
		return $this->observer;
	}
}
