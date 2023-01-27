<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\command;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\Main;
use NeiroNetwork\VanillaCommands\parameter\BasicParameters;
use NeiroNetwork\VanillaCommands\parameter\Parameter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class DebugCommand extends Command implements ParameterCommand {

	public function registerParameters(): void {
		Parameter::getInstance()->add($this->getName(), [
			BasicParameters::enum("action", "Action", [
				"subscribe",
				"sub",
				"unsubscribe",
				"unsub"
			]),
			BasicParameters::targets("target"),
			BasicParameters::string("checkFullId")
		]);

		Parameter::getInstance()->add($this->getName(), [
			BasicParameters::enum("clear", "clear"),
			BasicParameters::targets("target"),
		]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if (count($args) <= 1) {
			return;
		}

		if (Main::getInstance() === null) {
			$sender->sendMessage(Flare::PREFIX . "Flare プラグインが有効ではありません");
			return;
		}

		$action = $args[0];
		$name = $args[1];



		$target = $sender->getServer()->getPlayerExact($name);

		if (is_null($target)) {
			$sender->sendMessage(Flare::PREFIX . "ターゲットが見つかりませんでした");
			return;
		}

		$profile = Main::getInstance()->getMainFlare()->getProfileManager()->fetch($target->getUniqueId()->toString());

		if (is_null($profile)) {
			$sender->sendMessage(Flare::PREFIX . "ターゲットは見つかりましたが、プロフィールが見つかりませんでした。");
			return;
		}

		if ($action === "clear") {
			foreach ($profile->getObserver()->getAllChecks() as $check) {
				$check->unsubscribeDebugger($sender);
			}

			$sender->sendMessage(Flare::PREFIX . "§a全てのチェックのデバッグを停止しました");
			return;
		}

		if (count($args) <= 2) {
			return;
		}

		$checkFullId = $args[2];
		$check = $profile->getObserver()->getCheck($checkFullId);

		if (is_null($check)) {
			$sender->sendMessage(Flare::PREFIX . "ID \"$checkFullId\" に一致するチェックが見つかりませんでした");
			return;
		}

		switch ($action) {
			case "subsrcibe":
			case "sub":
				$check->subscribeDebugger($sender);

				$sender->sendMessage($check->getDebugPrefix() . " §aデバッグを開始しました");
				break;

			case "unsubscribe":
			case "unsub":
				$check->unsubscribeDebugger($sender);
				$sender->sendMessage($check->getDebugPrefix() . " §aデバッグを停止しました");
				break;
		}
	}
}
