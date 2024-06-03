<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\form;

use NeiroNetwork\Flare\profile\Client;
use NeiroNetwork\Flare\profile\PlayerProfile;
use pjz9n\advancedform\menu\MenuForm;
use pjz9n\advancedform\menu\response\MenuFormResponse;
use pocketmine\player\Player;

class PlayerClientInfoForm extends MenuForm{


	public function __construct(string $title, protected PlayerProfile $profile){
		$text = "";

		$append = function(string $key, string $content) use (&$text) : void{
			$text .= "§r§7{$key}: " . "§r§u$content" . "\n";
		};
		$c = $this->profile->getClient();
		$append("Name", $c->getName());
		$append("Address", $c->getAddress());
		$append("Server Address", $c->getServerAddress());
		$append("Client UUID", $c->getClientUuid());
		$append("§c[DO NOT TRUST]§7 Client Random ID", (string) $c->getClientRandomId());
		$append("Device ID", $c->getDeviceId());
		$append("Device", Client::convertDeviceIdToString($c->getDevice()));
		$append("Game Version", $c->getGameVersion());
		$append("Language Code", $c->getLangCode());
		$append("Locale", $c->getLocale());
		$append("Device Model", $c->getModel());
		$append("Playfab ID", $c->getPlayfabId());
		$append("[JWT] Self Signed ID", $c->getSelfSignedId());
		$append("[Minecraft] GUI Scale", (string) $c->getGuiScale());
		$append("[Minecraft] UI Profile", (string) $c->getUiProfile());

		parent::__construct($title, $text, []);
	}

	protected function handleClose(Player $player) : void{}

	protected function handleSelect(Player $player, MenuFormResponse $response) : void{}
}
