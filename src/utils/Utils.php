<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use NeiroNetwork\WaterdogPEAccepter\api\WdpePlayer;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;

class Utils{

	public static function mustStartedException() : RuntimeException{
		return new \RuntimeException("must not be called before started");
	}

	public static function getEnumName(string $enumClass, int $id) : ?string{
		$ref = new \ReflectionClass($enumClass);
		foreach($ref->getReflectionConstants() as $const){
			if($const->getValue() === $id){
				return $const->getName();
			}
		}

		return null;
	}

	public static function getNiceName(string $name) : string{
		return ucwords(strtolower(join(" ", explode("_", $name))));
	}

	public static function resolveOnOffInputFlags(int $inputFlags, int $startFlag, int $stopFlag) : ?bool{
		$enabled = ($inputFlags & (1 << $startFlag)) !== 0;
		$disabled = ($inputFlags & (1 << $stopFlag)) !== 0;
		if($enabled !== $disabled){
			return $enabled;
		}
		//neither flag was set, or both were set
		return null;
	}

	public static function getBestPing(Player $player) : int{
		if(Server::getInstance()->getPluginManager()->getPlugin("WaterdogPEAccepter") !== null){
			return (int) WdpePlayer::getRespondTime($player);
		}else{
			return $player->getNetworkSession()->getPing();
		}
	}

	public static function ms2tick(float $time) : int{
		return (int) floor($time / 50);
	}

	public static function getTime() : float{
		return hrtime(true) / 1e+9;
	}

	public static function getTimeMillis() : float{
		return hrtime(true) / 1e+6;
	}

	public static function getTimeNanos() : int{
		return (int) hrtime(true);
	}

	public static function equalsArrayValues(array $target, mixed $value){
		foreach($target as $targetValue){
			if($value != $targetValue){
				return false;
			}
		}

		return true;
	}

	public static function findAscending(array $arr, int $key) : mixed{
		$results = array_filter($arr, function($v) use ($key){
			return $v <= $key;
		});

		if(count($results) > 0){
			return max($results);
		}

		return null;
	}

	public static function findDescending(array $arr, int $key) : mixed{
		$results = array_filter($arr, function($v) use ($key){
			return $v >= $key;
		});

		if(count($results) > 0){
			return min($results);
		}

		return null;
	}

	public static function findArrayRange(array $arr, int $key, int $range) : array{
		$min = $key - $range;
		$max = $key + $range;

		return array_filter($arr, function($v) use ($min, $max){
			return $v >= $min && $v <= $max;
		});
	}

	public static function debugAxisAlignedBB(AxisAlignedBB $bb, Player $player) : void{
		self::debugPosition(new Vector3($bb->minX, $bb->minY, $bb->maxZ), $player);
		self::debugPosition(new Vector3($bb->maxX, $bb->minY, $bb->minZ), $player);
		self::debugPosition(new Vector3($bb->minX, $bb->minY, $bb->minZ), $player);
		self::debugPosition(new Vector3($bb->maxX, $bb->minY, $bb->maxZ), $player);

		self::debugPosition(new Vector3($bb->minX, $bb->maxY, $bb->maxZ), $player);
		self::debugPosition(new Vector3($bb->maxX, $bb->maxY, $bb->minZ), $player);
		self::debugPosition(new Vector3($bb->minX, $bb->maxY, $bb->minZ), $player);
		self::debugPosition(new Vector3($bb->maxX, $bb->maxY, $bb->maxZ), $player);
	}

	public static function debugPosition(Vector3 $pos, Player $player) : void{
		$pk = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $pos, "minecraft:balloon_gas_particle", null);

		$player->getNetworkSession()->sendDataPacket($pk);
	}
}
