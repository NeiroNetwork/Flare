<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\config;

use NeiroNetwork\Flare\profile\LogStyle;
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;

class FlareConfig{

	protected Config $generic;

	protected Config $profileDefault;

	protected Config $console;

	protected PlayerConfigStore $playerConfig;

	public function __construct(string $folder){
		$this->generic = new Config(Path::join($folder, "generic.yml"), Config::YAML, [
			"inspectors" => [],
			"test_server_mode" => false
		]);

		$this->profileDefault = new Config(Path::join($folder, "profile_default.yml"), Config::YAML, [
			"alert" => true,
			"alert_experimental_checks" => true,
			"log" => true,
			"check" => true,
			"punish" => true,
			"watch_bot" => true,
			"debug" => false,
			"log_cooldown" => 0,
			"alert_cooldown" => 4,
			"log_style" => "flare",
			"verbose" => false,
			"setback" => true,
			"transaction_pairing" => true,
			"collection" => false,
			"bot" => true
		]);

		$this->console = new Config(Path::join($folder, "console.yml"), Config::YAML, [
			"alert" => true,
			"log" => true,
			"debug" => true,
			"log_style" => "flare",
			"verbose" => false,
			"log_cooldown" => 0,
			"alert_cooldown" => 4,
			"alert_experimental_checks" => true
		]);

		$this->playerConfig = new PlayerConfigStore($this, Path::join($folder, "player"));

		$this->validate();
	}

	public function validate() : void{
		if(
			LogStyle::search(
				($logStyle = $this->profileDefault->get("log_style", null) ?? throw new \RuntimeException("ProfileDefault: log_style key not found"))
			)
			=== null
		){
			throw new \RuntimeException("ProfileDefault: log style \"$logStyle\" not found");
		}

		if(
			LogStyle::search(
				($logStyle = $this->console->get("log_style", null) ?? throw new \RuntimeException("Console: log_style key not found"))
			)
			=== null
		){
			throw new \RuntimeException("Console: log style \"$logStyle\" not found");
		}
	}

	public function close(bool $save = true) : void{
		if($save){
			foreach($this->getAll() as $config){
				$config->save();
			}
		}
	}

	/**
	 * @return Config[]
	 */
	public function getAll() : array{
		return array_merge(
			[
				$this->generic,
				$this->console,
				$this->profileDefault
			],
			$this->playerConfig->getAll()
		);
	}

	public function reloadAll() : void{
		foreach($this->getAll() as $config){
			$config->reload();
		}
	}

	/**
	 * Get the value of profileDefault
	 *
	 * @return Config
	 */
	public function getProfileDefault() : Config{
		return $this->profileDefault;
	}

	/**
	 * Get the value of console
	 *
	 * @return Config
	 */
	public function getConsole() : Config{
		return $this->console;
	}

	/**
	 * Get the value of generic
	 *
	 * @return Config
	 */
	public function getGeneric() : Config{
		return $this->generic;
	}

	/**
	 * Get the value of playerConfig
	 *
	 * @return PlayerConfigStore
	 */
	public function getPlayerConfigStore() : PlayerConfigStore{
		return $this->playerConfig;
	}
}
