<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\network;

use NeiroNetwork\Flare\event\network\NackReceiveEvent;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\network\mcpe\raklib\RakLibServer;
use pocketmine\network\mcpe\raklib\RakLibThreadCrashInfo;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperNotifier;
use raklib\generic\Socket;
use raklib\generic\SocketException;
use raklib\protocol\NACK;
use raklib\protocol\Packet;
use raklib\server\ipc\RakLibToUserThreadMessageSender;
use raklib\server\ipc\UserToRakLibThreadMessageReceiver;
use raklib\server\Server;
use raklib\server\SimpleProtocolAcceptor;
use raklib\utils\ExceptionTraceCleaner;
use raklib\utils\InternetAddress;

class LowRakLibServer extends RakLibServer {

	protected \Threaded $nackThreadToMainBuffer;

	public function __construct(
		\ThreadedLogger $logger,
		\Threaded $mainToThreadBuffer,
		\Threaded $threadToMainBuffer,
		\Threaded $nackThreadToMainBuffer,
		InternetAddress $address,
		int $serverId,
		int $maxMtuSize,
		int $protocolVersion,
		SleeperNotifier $sleeper
	) {
		$this->address = $address;

		$this->serverId = $serverId;
		$this->maxMtuSize = $maxMtuSize;

		$this->logger = $logger;

		$this->mainToThreadBuffer = $mainToThreadBuffer;
		$this->threadToMainBuffer = $threadToMainBuffer;
		$this->nackThreadToMainBuffer = $nackThreadToMainBuffer;

		$this->mainPath = \pocketmine\PATH;

		$this->protocolVersion = $protocolVersion;

		$this->mainThreadNotifier = $sleeper;
	}
	private function setCrashInfo(RakLibThreadCrashInfo $info): void {
		$this->synchronized(function (RakLibThreadCrashInfo $info): void {
			$this->crashInfo = $info;
			$this->notify();
		}, $info);
	}

	protected function onRun(): void {
		try {
			gc_enable();
			ini_set("display_errors", '1');
			ini_set("display_startup_errors", '1');

			register_shutdown_function([$this, "shutdownHandler"]);

			try {
				$socket = new Socket($this->address);
			} catch (SocketException $e) {
				$this->setCrashInfo(RakLibThreadCrashInfo::fromThrowable($e));
				return;
			}
			$manager = new TransparentServer(
				$this->serverId,
				$this->logger,
				$socket,
				$this->maxMtuSize,
				new SimpleProtocolAcceptor($this->protocolVersion),
				new UserToRakLibThreadMessageReceiver(new PthreadsChannelReader($this->mainToThreadBuffer)),
				new RakLibToUserThreadMessageSender(new SnoozeAwarePthreadsChannelWriter($this->threadToMainBuffer, $this->mainThreadNotifier)),
				new SnoozeAwarePthreadsChannelWriter($this->nackThreadToMainBuffer, $this->mainThreadNotifier),
				new ExceptionTraceCleaner($this->mainPath)
			);

			// fixme: これはよろしくないのでは
			// 名前はtransparentなのに結局外部から変更できない。
			$manager->registerRawDatagramHandler(function (Packet $packet, InternetAddress $address): bool {
				if ($packet instanceof NACK) {
					$this->nackThreadToMainBuffer->wri
				}

				return true;
			});
			$this->synchronized(function (): void {
				$this->ready = true;
				$this->notify();
			});
			while (!$this->isKilled) {
				$manager->tickProcessor();
			}
			$manager->waitShutdown();
			$this->cleanShutdown = true;
		} catch (\Throwable $e) {
			$this->setCrashInfo(RakLibThreadCrashInfo::fromThrowable($e));
			$this->logger->logException($e);
		}
	}
}
