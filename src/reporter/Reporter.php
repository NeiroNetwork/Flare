<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\reporter;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\FlarePermissionNames;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class Reporter {

	const BROADCAST_CHANNEL = "flare.broadcast"; // other broadcast
	const BROADCAST_CHANNEL_INSPECTOR = "flare.broadcast.inspector"; // check broadcast

	public function __construct(protected PluginBase $plugin, protected ConsoleCommandSender $console) {
		$this->autoSubscribe($console);
		// todo: reporter / broadcaster(singleton)
	}

	public function subscribeInspector(CommandSender $commandSender): void {
		$this->plugin->getServer()->subscribeToBroadcastChannel(self::BROADCAST_CHANNEL_INSPECTOR, $commandSender);
	}

	public function subscribeTeam(CommandSender $commandSender): void {
		$this->plugin->getServer()->subscribeToBroadcastChannel(self::BROADCAST_CHANNEL, $commandSender);
	}


	public function refreshSubscriber(): void {
		$server = $this->plugin->getServer();
		foreach ([
			self::BROADCAST_CHANNEL,
			self::BROADCAST_CHANNEL_INSPECTOR
		] as $channelId) {
			foreach ($server->getBroadcastChannelSubscribers($channelId) as $subscriber) {
				$server->unsubscribeFromBroadcastChannel($channelId, $subscriber);
			}
		}

		foreach ($server->getOnlinePlayers() as $player) {
			$this->autoSubscribe($player);
		}

		$this->autoSubscribe($this->console);
	}

	public function autoSubscribe(CommandSender $commandSender): void {
		if ($commandSender->hasPermission(FlarePermissionNames::INSPECTOR)) {
			$this->subscribeInspector($commandSender);
		}

		if ($commandSender->hasPermission(FlarePermissionNames::TEAM)) {
			$this->subscribeTeam($commandSender);
		}
	}

	public function autoUnsubscribe(CommandSender $commandSender): void {
		$this->plugin->getServer()->unsubscribeFromAllBroadcastChannels($commandSender);
	}

	public function report(ReportContent $content): void {
		$channelId = match (true) {
			$content instanceof FailReportContent => self::BROADCAST_CHANNEL_INSPECTOR,
			$content instanceof LogReportContent => self::BROADCAST_CHANNEL, // いらない
			default => self::BROADCAST_CHANNEL
		};

		foreach ($this->plugin->getServer()->getBroadcastChannelSubscribers($channelId) as $subscriber) {
			if (($text = $content->getText($subscriber)) !== null) {
				$subscriber->sendMessage($text);
			}
		}
	}
}
