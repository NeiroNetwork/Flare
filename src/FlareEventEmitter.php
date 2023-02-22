<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use Closure;
use NeiroNetwork\Flare\utils\timings\EventEmitterTimings;
use NeiroNetwork\Flare\utils\timings\FlareTimings;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityEvent;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\RegisteredListener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\player\Player;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\Utils;

class FlareEventEmitter {

	/**
	 * @var Closure[][][]
	 */
	protected array $packetHandlers;

	protected array $sendPacketHandlers;

	/**
	 * @var Closure[][]
	 */
	protected array $playerEventHandlers;

	/**
	 * @var Closure[][][]
	 */
	protected array $eventHandlers;

	/**
	 * @var RegisteredListener[]
	 */
	protected array $eventListeners;

	protected EventEmitterTimings $timings;

	protected Flare $flare;

	public function __construct(Flare $flare) {
		$this->flare = $flare;

		$this->packetHandlers = [];
		$this->eventHandlers = [];
		$this->eventListeners = [];
		$this->sendPacketHandlers = [];

		$this->timings = FlareTimings::global()->eventEmitter;

		$this->registerEventHandler(DataPacketReceiveEvent::class, function (DataPacketReceiveEvent $event): void {
			$packet = $event->getPacket();
			$networkId = $packet->pid();
			$origin = $event->getOrigin();
			if ($origin->getPlayerInfo() !== null) {
				$id = $origin->getPlayerInfo()->getUuid()->toString();
				$sig = $event->isCancelled() ? 1 : 0;

				$t = $this->timings->summarizeHandler;
				$t->startTiming();
				$list = $this->packetHandlers;
				krsort($list, SORT_NUMERIC); // todo: 登録時にソートしたほうが絶対いい
				$t->stopTiming();


				foreach ($list as $priority => $data) {
					$t->startTiming();
					$all = $data[$id][$networkId][0] ?? [];
					if ($event instanceof Cancellable && $event->isCancelled()) {
						foreach ($data[$id][$networkId][1] ?? [] as $_handler) {
							$all[] = $_handler;
						}
					}

					$t->stopTiming();
					$this->timings->packetHandling->startTiming();
					foreach ($all as $handler) {
						$handler($packet);
					}
					$this->timings->packetHandling->stopTiming();
				}
			}
		}, true);

		$this->registerEventHandler(DataPacketSendEvent::class, function (DataPacketSendEvent $event): void {
			$t = $this->timings->summarizeHandler;
			$t->startTiming();
			$list = $this->sendPacketHandlers;
			krsort($list, SORT_NUMERIC);
			$t->stopTiming();

			foreach ($event->getTargets() as $origin) {
				if ($origin->getPlayerInfo() !== null) {
					$id = $origin->getPlayerInfo()->getUuid()->toString();
					foreach ($list as $priority => $data) {
						foreach ($event->getPackets() as $packet) {
							$networkId = $packet->pid();
							$t->startTiming();

							$all = $data[$id][$networkId][0] ?? [];
							if ($event instanceof Cancellable && $event->isCancelled()) {
								foreach ($data[$id][$networkId][1] ?? [] as $_handler) {
									$all[] = $_handler;
								}
							}

							$t->stopTiming();
							$this->timings->sendPacketHandling->startTiming();
							foreach ($all as $handler) {
								$handler($packet);
							}
							$this->timings->sendPacketHandling->stopTiming();
						}
					}
				}
			}
		}, true);
	}

	public function unregisterAll(string $hash): void {
		// 魔境
		$runPlayerPacketHandler = function (array $handlers) use ($hash): array {
			foreach ($handlers as $priority => $_d1) {
				foreach ($_d1 as $playerUuid => $_d2) {
					foreach ($_d2 as $networkId => $_d3) {
						foreach ($_d3 as $sig => $_d4) {
							foreach ($_d4 as $targetHash => $handler) {
								if ($hash === $targetHash) {
									unset($handlers[$priority][$playerUuid][$networkId][$sig][$targetHash]);
								}
							}
						}
					}
				}
			}

			return $handlers;
		};

		$runEventHandler = function (array $handlers) use ($hash): array {
			foreach ($handlers as $priority => $_d1) {
				foreach ($_d1 as $event => $_d2) {
					foreach ($_d2 as $sig => $_d3) {
						foreach ($_d3 as $targetHash => $handler) {
							if ($hash === $targetHash) {
								unset($handlers[$priority][$event][$sig][$targetHash]);
							}
						}
					}
				}
			}

			return $handlers;
		};

		$runPlayerEventHandler = function (array $handlers) use ($hash): array {
			foreach ($handlers as $priority => $_d1) {
				foreach ($_d1 as $playerUuid => $_d2) {
					foreach ($_d2 as $event => $_d3) {
						foreach ($_d3 as $sig => $_d4) {
							foreach ($_d4 as $targetHash => $handler) {
								if ($hash === $targetHash) {
									unset($handlers[$priority][$playerUuid][$event][$sig][$targetHash]);
								}
							}
						}
					}
				}
			}

			return $handlers;
		};

		$this->sendPacketHandlers = $runPlayerPacketHandler($this->sendPacketHandlers);
		$this->packetHandlers = $runPlayerPacketHandler($this->packetHandlers);

		$this->eventHandlers = $runEventHandler($this->eventHandlers);
		$this->playerEventHandlers = $runPlayerEventHandler($this->playerEventHandlers);
	}

	public function registerEventHandler(string $event, ?Closure $handler = null, ?bool $handleCancelled = null, int $priority = EventPriority::NORMAL): ?string {
		$rt = $this->timings->register;
		$rt->startTiming();
		// handleCancelled ではなく、 handleCancelled, handleUncancelled, handleAll のほうがいいのでは？
		// 上のほうに cancelled なら foreach で追加していくコードがある

		$listener = null;
		if (!isset($this->eventListeners[$event])) {
			// 自分でも何かいてるかわからないのでメモをおいておきます

			// eventHandler を呼び出すためのやつ
			// 最初から Event を glob して全て登録しようかと思ったけど
			// 意味ないEventをリッスンさせて重くなりそうだしプラグインのEventをリッスンできない問題が発生する
			$listener = $this->flare->getPlugin()->getServer()->getPluginManager()->registerEvent($event, function (Event $event): void {
				// fixme: 重複した処理があるのでまとめたい 1
				$t = $this->timings->summarizeHandler;
				$t->startTiming();
				$list = $this->eventHandlers;
				krsort($list, SORT_NUMERIC);
				$t->stopTiming();

				foreach ($list as $priority => $data) {
					$t->startTiming();
					$all = $data[$event::class][0] ?? [];
					if ($event instanceof Cancellable && $event->isCancelled()) {
						foreach ($data[$event::class][1] ?? [] as $_handler) {
							$all[] = $_handler;
						}
					}
					$t->stopTiming();

					$this->timings->handling->startTiming();
					foreach ($all as $handler) {
						$handler($event);
					}
					$this->timings->handling->stopTiming();
				}

				if ($event instanceof PlayerEvent || $event instanceof EntityEvent) {
					if ($event instanceof EntityEvent) {
						$player = $event->getEntity();
					} else {
						$player = $event->getPlayer();
					}

					if ($player instanceof Player) {
						$uuid = $player->getPlayerInfo()->getUuid()->toString();

						// fixme: 重複した処理があるのでまとめたい 2

						$t->startTiming();
						$list = $this->playerEventHandlers;
						krsort($list, SORT_NUMERIC);
						$t->stopTiming();

						foreach ($list as $priority => $data) {
							$t->startTiming();
							$playerAll = $data[$uuid][$event::class][0] ?? [];
							if ($event instanceof Cancellable && $event->isCancelled()) {
								foreach ($data[$uuid][$event::class][1] ?? [] as $_handler) {
									$playerAll[] = $_handler;
								}
							}
							$t->stopTiming();

							$this->timings->handling->startTiming();
							foreach ($playerAll as $handler) {
								$handler($event);
							}
							$this->timings->handling->stopTiming();
						}
					}
				}
			}, EventPriority::LOW, $this->flare->getPlugin(), true);

			$this->eventListeners[$event] = $listener;
		}

		if ($handler !== null && $handleCancelled !== null) {
			$sig = $handleCancelled ? 1 : 0;

			$this->eventHandlers[$priority][$event][$sig][spl_object_hash($handler)] = $handler;

			if ($handleCancelled) {
				$this->eventHandlers[$priority][$event][0][spl_object_hash($handler)] = $handler;
			}

			// ここもなかなかエグい
			return spl_object_hash($handler);
		}

		$rt->stopTiming();

		return null;
	}

	public function registerPacketHandler(string $playerUuid, int $networkId, Closure $handler, bool $handleCancelled, int $priority = EventPriority::NORMAL): string {
		$rt = $t = $this->timings->register;
		$rt->startTiming();
		$sig = $handleCancelled ? 1 : 0;

		$this->packetHandlers[$priority][$playerUuid][$networkId][$sig][spl_object_hash($handler)] = $handler;

		if ($handleCancelled) {
			$this->packetHandlers[$priority][$playerUuid][$networkId][0][spl_object_hash($handler)] = $handler;
		}

		$t->stopTiming();

		return spl_object_hash($handler);

		// エグいて
	}

	public function unregisterPacketHandler(string $playerUuid, int $networkId, string $hash, ?int $priority = null): void {
		$search = $priority !== null ? [$priority] : EventPriority::ALL;

		foreach ($search as $priority) {
			unset($this->packetHandlers[$priority][$playerUuid][$networkId][0][$hash]);
			unset($this->packetHandlers[$priority][$playerUuid][$networkId][1][$hash]);
		}
	}

	public function unregisterSendPacketHandler(string $playerUuid, int $networkId, string $hash, ?int $priority = null): void {
		$search = $priority !== null ? [$priority] : EventPriority::ALL;

		foreach ($search as $priority) {
			unset($this->sendPacketHandlers[$priority][$playerUuid][$networkId][0][$hash]);
			unset($this->sendPacketHandlers[$priority][$playerUuid][$networkId][1][$hash]);
		}
	}

	public function unregisterPlayerEventHandler(string $playerUuid, string $event, string $hash, ?int $priority = null): void {
		$search = $priority !== null ? [$priority] : EventPriority::ALL;

		foreach ($search as $priority) {
			unset($this->playerEventHandlers[$priority][$playerUuid][$event][0][$hash]);
			unset($this->playerEventHandlers[$priority][$playerUuid][$event][1][$hash]);
		}
	}


	public function registerSendPacketHandler(string $playerUuid, int $networkId, Closure $handler, bool $handleCancelled, int $priority = EventPriority::NORMAL): string {
		$rt = $t = $this->timings->register;
		$rt->startTiming();
		$sig = $handleCancelled ? 1 : 0;

		$this->sendPacketHandlers[$priority][$playerUuid][$networkId][$sig][spl_object_hash($handler)] = $handler;

		if ($handleCancelled) {
			$this->sendPacketHandlers[$priority][$playerUuid][$networkId][0][spl_object_hash($handler)] = $handler;
		}

		$t->stopTiming();

		return spl_object_hash($handler);

		// エグいて
	}

	public function registerPlayerEventHandler(string $playerUuid, string $event, Closure $handler, bool $handleCancelled = false, int $priority = EventPriority::NORMAL): string {
		$this->registerEventHandler($event);
		$rt = $t = $this->timings->register;
		$rt->startTiming();

		$sig = $handleCancelled ? 1 : 0;

		$this->playerEventHandlers[$priority][$playerUuid][$event][$sig][spl_object_hash($handler)] = $handler;

		if ($handleCancelled) {
			$this->playerEventHandlers[$priority][$playerUuid][$event][0][spl_object_hash($handler)] = $handler;
		}
		$rt->stopTiming();

		return spl_object_hash($handler);
	}

	/**
	 * Get the value of packetHandlers
	 *
	 * @return array
	 */
	public function getPacketHandlers(): array {
		return $this->packetHandlers;
	}
}
