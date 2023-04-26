<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\form;

use NeiroNetwork\Flare\Flare;
use NeiroNetwork\Flare\profile\LogStyle;
use NeiroNetwork\Flare\profile\PlayerProfile;
use pjz9n\advancedform\custom\CustomForm;
use pjz9n\advancedform\custom\element\Dropdown;
use pjz9n\advancedform\custom\element\SelectorOption;
use pjz9n\advancedform\custom\element\Slider;
use pjz9n\advancedform\custom\element\Toggle;
use pjz9n\advancedform\custom\response\CustomFormResponse;
use pjz9n\advancedform\custom\result\exception\InvalidResponseException;
use pocketmine\player\Player;

class PlayerSettingsForm extends CustomForm{

	public function __construct(
		string $title,
		protected PlayerProfile $profile
	){
		parent::__construct($title, [
			new Toggle("Alert", $profile->isAlertEnabled(), "alert_enabled"),
			new Toggle("Log", $profile->isLogEnabled(), "log_enabled"),
			new Toggle("Verbose", $profile->isVerboseEnabled(), "verbose_enabled"),
			new Toggle("Debug", $profile->isDebugEnabled(), "debug_enabled"),
			new Toggle("Observer: Check", $profile->getObserver()->isEnabled(), "check_enabled"),
			new Toggle("Observer: Punishment", $profile->getObserver()->isPunishEnabled(), "punishment_enabled"),
			new Slider("Alert Cooldown", 0, 50, 1, $profile->getAlertCooldown(), "alert_cooldown"),
			new Slider("Log Cooldown", 0, 50, 1, $profile->getLogCooldown(), "log_cooldown"),
			new Dropdown(
				"Style",
				array_map(
					function(LogStyle $style){
						$name = count($style->getAliases()) > 0 ?
							($style->getAliases()[array_key_first($style->getAliases())]) : null;
						if($name !== null){
							return new SelectorOption($name, null, $name);
						}
					},
					LogStyle::getAllRegistered()
				),
				null,
				"style"
			),
		]);
	}

	protected function handleSubmit(Player $player, CustomFormResponse $response) : void{
		try{
			$alertEnabled = $response->getToggleResult("alert_enabled")->getValue();
			$logEnabled = $response->getToggleResult("log_enabled")->getValue();
			$verboseEnabled = $response->getToggleResult("verbose_enabled")->getValue();
			$debugEnabled = $response->getToggleResult("debug_enabled")->getValue();
			$checkEnabled = $response->getToggleResult("check_enabled")->getValue();
			$punishEnabled = $response->getToggleResult("punishment_enabled")->getValue();
			$alertCooldown = $response->getSliderResult("alert_cooldown")->getInt();
			$logCooldown = $response->getSliderResult("log_cooldown")->getInt();
			$styleName = $response->getSelectorResult("style")->getOptionName() ?? throw new InvalidResponseException("Option name: null");
		}catch(InvalidResponseException $e){
			return;
		}

		$this->profile->setAlertEnabled($alertEnabled);
		$this->profile->setLogEnabled($logEnabled);
		$this->profile->setVerboseEnabled($verboseEnabled);
		$this->profile->setDebugEnabled($debugEnabled);

		$this->profile->getObserver()->setEnabled($checkEnabled);
		$this->profile->getObserver()->setPunishEnabled($punishEnabled);

		$this->profile->setAlertCooldown($alertCooldown);
		$this->profile->setLogCooldown($logCooldown);

		$style = LogStyle::search($styleName);
		if($style !== null){
			$this->profile->setLogStyle($style);
		}else{
			$player->sendMessage(Flare::PREFIX . "§cスタイル {$styleName} が見つかりませんでした");
		}
	}
}
