<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile;

use NeiroNetwork\Flare\event\player\PlayerPacketLossEvent;
use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\check\list\combat\aim\AimA;
use NeiroNetwork\Flare\profile\check\list\combat\aim\AimC;
use NeiroNetwork\Flare\profile\check\list\combat\aura\AuraA;
use NeiroNetwork\Flare\profile\check\list\combat\aura\AuraD;
use NeiroNetwork\Flare\profile\check\list\combat\autoclicker\AutoClickerA;
use NeiroNetwork\Flare\profile\check\list\combat\autoclicker\AutoClickerB;
use NeiroNetwork\Flare\profile\check\list\combat\autoclicker\AutoClickerC;
use NeiroNetwork\Flare\profile\check\list\combat\autoclicker\AutoClickerD;
use NeiroNetwork\Flare\profile\check\list\combat\reach\ReachA;
use NeiroNetwork\Flare\profile\check\list\combat\reach\ReachB;
use NeiroNetwork\Flare\profile\check\list\combat\reach\ReachC;
use NeiroNetwork\Flare\profile\check\list\movement\jump\JumpA;
use NeiroNetwork\Flare\profile\check\list\movement\jump\JumpB;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionA;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionB;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionC;
use NeiroNetwork\Flare\profile\check\list\movement\motion\MotionD;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedA;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedB;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedC;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedD;
use NeiroNetwork\Flare\profile\check\list\movement\speed\SpeedE;
use NeiroNetwork\Flare\profile\check\list\movement\velocity\VelocityA;
use NeiroNetwork\Flare\profile\check\list\packet\badpacket\BadPacketA;
use NeiroNetwork\Flare\profile\check\list\packet\badpacket\BadPacketB;
use NeiroNetwork\Flare\profile\check\list\packet\badpacket\BadPacketC;
use NeiroNetwork\Flare\profile\check\list\packet\interact\InteractA;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidA;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidB;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidC;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidD;
use NeiroNetwork\Flare\profile\check\list\packet\invalid\InvalidE;
use NeiroNetwork\Flare\profile\check\list\packet\timer\TimerA;
use NeiroNetwork\Flare\profile\check\list\packet\timer\TimerB;
use NeiroNetwork\Flare\profile\check\list\packet\timer\TimerC;
use NeiroNetwork\Flare\profile\check\Observer;
use NeiroNetwork\Flare\profile\data\CombatData;
use NeiroNetwork\Flare\profile\data\KeyInputs;
use NeiroNetwork\Flare\profile\data\MovementData;
use NeiroNetwork\Flare\profile\data\SurroundData;
use NeiroNetwork\Flare\profile\data\TransactionData;
use NeiroNetwork\Flare\profile\latency\LatencyHandler;
use NeiroNetwork\Flare\profile\pairing\TransactionPairing;
use NeiroNetwork\Flare\profile\pairing\TransactionPairingActorStateProvider;
use NeiroNetwork\Flare\reporter\DebugReportContent;
use NeiroNetwork\Flare\reporter\LogReportContent;
use NeiroNetwork\Flare\utils\EventHandlerLink;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use RuntimeException;

class PlayerProfile implements Profile{

	use CoolDownLoggingTrait;

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

	protected int $lastFpsAlertTick;

	protected bool $started;

	protected LatencyHandler $latencyHandler;

	protected ?TransactionPairing $transactionPairing;

	protected bool $transactionPairingEnabled;

	protected ActorStateProvider $actorStateProvider;

	protected ProfileSupport $support;

	public function __construct(Flare $flare, Player $player){
		$this->flare = $flare;
		$this->player = $player;
		$this->config = $flare->getConfig()->getPlayerConfigStore()->get($player);
		$this->client = Client::create($player->getNetworkSession());
		$this->started = false;
		$this->lastFpsAlertTick = 0;

		$this->eventLink = new EventHandlerLink($flare);

		$conf = $this->getConfig();

		$this->alertCoolDown = $conf->get("alert_cooldown");
		$this->alertEnabled = $conf->get("alert");

		$this->logCoolDown = $conf->get("log_cooldown");
		$this->logEnabled = $conf->get("log");

		$this->debugEnabled = $conf->get("debug");

		$this->verboseEnabled = $conf->get("verbose");

		/**
		 * // fixme: start後は無理やり変更しても反映されない
		 *
		 * @see MovementData ::__construct
		 */
		$this->dataReportEnabled = $conf->get("collection");

		$this->latencyHandler = new LatencyHandler($this);
		$this->transactionPairing = null;
		$this->setTransactionPairingEnabled($conf->get("transaction_pairing"));
		$this->support = new ProfileSupport($this);

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
			$this->handleInput(...),
			false,
			EventPriority::HIGH
		));
	}

	/**
	 * Get the value of config
	 *
	 * @return Config config
	 *
	 * fixme: delete? internal? protected?
	 */
	public function getConfig() : Config{
		return $this->config;
	}

	public function getFlare() : Flare{
		return $this->flare;
	}

	/**
	 * @return ProfileSupport
	 */
	public function getSupport() : ProfileSupport{
		return $this->support;
	}

	/**
	 * @return ActorStateProvider
	 */
	public function getActorStateProvider() : ActorStateProvider{
		return $this->actorStateProvider;
	}

	/**
	 * @return LatencyHandler
	 */
	public function getLatencyHandler() : LatencyHandler{
		return $this->latencyHandler;
	}

	public function getEventHandlerLink() : EventHandlerLink{
		return $this->eventLink;
	}

	public function getLogStyle() : LogStyle{
		return $this->logStyle;
	}

	/**
	 * Set the value of logStyle
	 *
	 * @param LogStyle $logStyle
	 *
	 * @return self
	 */
	public function setLogStyle(LogStyle $logStyle) : self{
		$this->logStyle = $logStyle;

		return $this;
	}

	public function isTransactionPairingEnabled() : bool{
		return $this->transactionPairingEnabled;
	}

	public function setTransactionPairingEnabled(bool $enabled) : void{
		$changed = $this->transactionPairingEnabled !== $enabled;
		$this->transactionPairingEnabled = $enabled;

		if(!$changed){
			return;
		}

		if($enabled){
			$this->transactionPairing = new TransactionPairing($this, 20);
			$this->actorStateProvider = new TransactionPairingActorStateProvider($this->transactionPairing, 40);
		}else{
			unset($this->transactionPairing);
			$this->transactionPairing = null;
			$this->actorStateProvider = new SimpleActorStateProvider($this, 40);
		}
	}

	public function reload() : void{
		if($this->started){
			$this->shutdown();
			$this->start();
		}
	}

	public function shutdown() : void{
		if($this->started){
			$this->eventLink->unregisterAll();

			if(!$this->observer->isClosed()){
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

	public function close() : void{
		if($this->started){
			$this->shutdown();
		}
	}

	public function start() : void{
		if(!$this->started){
			$this->started = true;

			$this->flare->getReporter()->report(new DebugReportContent(Flare::DEBUG_PREFIX . "Profile セッションを開始しています", $this->flare));
			$startTime = microtime(true);

			$this->eventLink->add($this->flare->getEventEmitter()->registerPlayerEventHandler(
				$this->player->getUniqueId()->toString(),
				PlayerPacketLossEvent::class,
				$this->handlePacketLoss(...)
			));

			$this->eventLink->add($this->flare->getEventEmitter()->registerPacketHandler(
				$this->player->getUniqueId()->toString(),
				NetworkStackLatencyPacket::NETWORK_ID,
				$this->latencyHandler->handleResponse(...),
				true,
				EventPriority::LOWEST
			));

			$this->movementData = new MovementData($this);
			$this->surroundData = new SurroundData($this);
			$this->combatData = new CombatData($this);
			$this->transactionData = new TransactionData($this);
			$this->keyInputs = new KeyInputs($this);

			$t = microtime(true) - $startTime;
			$rt = round($t * 1000);
			$this->flare->getReporter()->report(new DebugReportContent(Flare::DEBUG_PREFIX . "Profile セッションの開始が終了しました: {$rt}ms", $this->flare));


			$this->registerChecks($this->observer);
		}
	}

	protected function registerChecks(Observer $o) : void{
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
			$o->registerCheck(new JumpB($o));
		} {
			$o->registerCheck(new VelocityA($o));
		} {
			$o->registerCheck(new BadPacketA($o));
			$o->registerCheck(new BadPacketB($o));
			$o->registerCheck(new BadPacketC($o));
		} {
			$o->registerCheck(new InvalidA($o));
			$o->registerCheck(new InvalidB($o));
			$o->registerCheck(new InvalidC($o));
			$o->registerCheck(new InvalidD($o));
			$o->registerCheck(new InvalidE($o));
		} {
			$o->registerCheck(new TimerA($o));
			$o->registerCheck(new TimerB($o));
			$o->registerCheck(new TimerC($o));
		} {
			$o->registerCheck(new AimA($o));
			$o->registerCheck(new AimC($o));
		} {
			$o->registerCheck(new ReachA($o));
			$o->registerCheck(new ReachB($o));
			$o->registerCheck(new ReachC($o));
		} {
			$o->registerCheck(new AuraA($o));
			$o->registerCheck(new AuraD($o));
		} {
			$o->registerCheck(new AutoClickerA($o));
			$o->registerCheck(new AutoClickerB($o));
			$o->registerCheck(new AutoClickerC($o));
			$o->registerCheck(new AutoClickerD($o));
		} {
			$o->registerCheck(new InteractA($o));
		}

		// グループ分けみたいなことをしてみたけど

		// todo: Aim(C) の 1.0e-4以下のpitch diffを削除 (たまにある誤検知が直るかな？)
		// finished: Speed(E) で移動速度の加速度検証 (move length 16 tick以内の時前回と同じ速度だったら検知？)
	}

	public function getMovementData() : MovementData{
		return $this->movementData ?? throw new RuntimeException("must not be called before start");
	}

	public function getKeyInputs() : KeyInputs{
		return $this->keyInputs ?? throw new RuntimeException("must not be called before start");
	}

	public function getClient() : Client{
		return $this->client;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function isServerStable() : bool{
		$s = $this->flare->getPlugin()->getServer();

		if($s->getTicksPerSecond() < 19.975){
			return false;
		}

		if($s->getTicksPerSecondAverage() < 19.975){
			return false;
		}

		if($this->getFlare()->getTickProcessor()->getTimeSinceLastTick() > 200){
			return false;
		}

		if($this->flare->getTickProcessor()->getOverloadRecord()->getTickSinceAction() < 200){
			return false;
		}

		return true;
	}

	/**
	 * Get the value of surroundData
	 *
	 * @return SurroundData
	 */
	public function getSurroundData() : SurroundData{
		return $this->surroundData ?? throw new RuntimeException("must not be called before start");
	}

	/**
	 * Get the value of transactionData
	 *
	 * @return TransactionData
	 */
	public function getTransactionData() : TransactionData{
		return $this->transactionData ?? throw new RuntimeException("must not be called before start");
	}

	/**
	 * Get the value of dataReportEnabled
	 *
	 * @return bool
	 */
	public function isDataReportEnabled() : bool{
		return $this->dataReportEnabled;
	}

	/**
	 * Get the value of verboseEnabled
	 *
	 * @return bool
	 */
	public function isVerboseEnabled() : bool{
		return $this->verboseEnabled;
	}

	/**
	 * Set the value of verboseEnabled
	 *
	 * @param bool $verboseEnabled
	 *
	 * @return self
	 */
	public function setVerboseEnabled(bool $verboseEnabled) : self{
		$this->verboseEnabled = $verboseEnabled;

		return $this;
	}

	/**
	 * Get the value of inputModeName
	 *
	 * @return string
	 */
	public function getInputModeName() : string{
		return $this->inputModeName;
	}

	/**
	 * Get the value of observer
	 *
	 * @return Observer
	 */
	public function getObserver() : Observer{
		return $this->observer;
	}

	public function disconnectPlayerAndClose(string $message) : void{
		$this->player->disconnect($message);
		$this->close();
	}

	public function getPing() : int{
		return Utils::getBestPing($this->player);
	}

	protected function handleInput(PlayerAuthInputPacket $packet) : void{
		$player = $this->player;
		$inputMode = $packet->getInputMode();

		if($inputMode !== $this->inputMode){
			$toName = Utils::getNiceName(Utils::getEnumName(InputMode::class, $inputMode) ?? "unknown<{$inputMode}>"); // 重い？
			$fromName = $this->inputModeName;

			$this->flare->getReporter()->report(new LogReportContent(Flare::PREFIX . "§b{$player->getName()} §fが入力方法を変更しました §d($fromName -> $toName)", $this->flare));

			$this->inputMode = $inputMode;
			$this->inputModeName = $toName;
		}
		$fps = $this->getCombatData()->getAimTriggerPerSecond();
		if($this->debugEnabled){
			$ping = Utils::getBestPing($player);
			$tick = $this->getServerTick();
			$confirmedTick = $this->getTransactionPairing()->getLatestConfirmedTick();
			$deltaTick = $tick - $confirmedTick;
			$player->sendActionBarMessage("§7---------- Debug Mode ----------\n§7Ping: §b{$ping}ms§7 Tick: §b{$tick}§7 | §e{$confirmedTick}§7, §c({$deltaTick})§7\n§7APD: §b{$fps}/20");
		}

		if(
			$this->getCombatData()->getAimRecord()->getLength() >= 50 &&
			$fps <= 13 &&
			$this->getServerTick() - $this->lastFpsAlertTick > 140
		){
			$this->lastFpsAlertTick = $this->getServerTick();
			$this->flare->getReporter()->report(new LogReportContent(Flare::PREFIX . "§b{$player->getName()} §fのエイム位置検知回数 §6($fps/s)§f が §c13/s§f を下回っています", $this->flare));
		}

		$this->support->update($this->getServerTick());
	}

	/**
	 * Get the value of inputMode
	 *
	 * @return int
	 */
	public function getInputMode() : int{
		return $this->inputMode;
	}

	public function getName() : string{
		return $this->getCommandSender()->getName();
	}

	public function getCommandSender() : CommandSender{
		return $this->player;
	}

	/**
	 * Get the value of combatData
	 *
	 * @return CombatData
	 */
	public function getCombatData() : CombatData{
		return $this->combatData ?? throw new RuntimeException("must not be called before start");
	}

	public function getServerTick() : int{
		return $this->flare->getPlugin()->getServer()->getTick();
	}

	/**
	 * @return TransactionPairing
	 */
	public function getTransactionPairing() : TransactionPairing{
		return $this->transactionPairing ?? throw new RuntimeException("Transaction pairing not enabled");
	}

	protected function handlePacketLoss(PlayerPacketLossEvent $event) : void{
		$this->player->sendMessage("NACK: Packet Loss");
	}
}
