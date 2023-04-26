<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\config;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;

class PlayerConfigStore{

	/**
	 * @var Config[]
	 */
	protected array $list;

	public function __construct(
		protected FlareConfig $flareConfig,
		protected string $folder
	){
		@mkdir($folder, 0777, true);
		$this->list = [];
	}

	/**
	 * @param Player $player
	 *
	 * @return Config player config
	 *
	 * fixme: fetch or get?
	 */
	public function get(Player $player) : Config{
		$config = $this->getFromUuid($player->getUniqueId()->toString());

		$config->set("username", $player->getName());

		return $config;
	}

	/**
	 * @param string $uuid
	 *
	 * @return Config player config
	 *
	 * fixme: internal?
	 * fixme: fetch or get?
	 */
	public function getFromUuid(string $uuid) : Config{
		$config =
			$this->list[$uuid]
			??
			new Config(
				Path::join($this->folder, "$uuid.yml"),
				Config::YAML,
				$this->flareConfig->getProfileDefault()->getAll()
			);

		$this->list[$uuid] = $config;

		return $config;
	}

	/**
	 * @return Config[]
	 */
	public function getAll() : array{
		return $this->list;
	}
}
