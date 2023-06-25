<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\utils;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\SnowLayer;
use pocketmine\block\Trapdoor;
use pocketmine\block\WaterLily;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\World;

class BlockUtil{

	public static function canChangeCollisionBoxByInteract(Block $block) : bool{
		return ($block instanceof Trapdoor ||
			$block instanceof Door ||
			$block instanceof FenceGate
		);
	}

	public static function getMaxCollisionY(Block $block) : float{
		return max(PHP_INT_MIN, $block->getPosition()->y, ...array_map(fn(AxisAlignedBB $bb) => $bb->maxY, $block->getCollisionBoxes()));
	}

	public static function isAbleToStep(Block $block) : bool{
		if(count($block->getCollisionBoxes()) <= 0){
			return false;
		}

		// heavy?
		return min(array_map(fn(AxisAlignedBB $bb) => $bb->maxY - $block->getPosition()->y, $block->getCollisionBoxes())) <= 0.6;
	}

	public static function calculateBreakBlockTick(Item $item, Block $block, int $hasteLevel = 0, int $fatigueLevel = 0) : int{
		$time = ceil($block->getBreakInfo()->getBreakTime($item) * 20); #完全なコピペ

		$time *= 1 - (0.25 * $hasteLevel);

		$time *= 1 + (0.3 * $fatigueLevel);

		$time -= 1.0;

		return (int) $time;
	}

	/**
	 * @param AxisAlignedBB $bb
	 * @param World         $world
	 * @param float         $inset
	 *
	 * @return Block[]
	 */
	public static function getEntityBlocksAround(AxisAlignedBB $bb, World $world, float $inset = 0.001, float $yInset = 0.0) : array{
		$minX = (int) floor($bb->minX + $inset);
		$minY = (int) floor($bb->minY + $inset + $yInset);
		$minZ = (int) floor($bb->minZ + $inset);
		$maxX = (int) floor($bb->maxX - $inset);
		$maxY = (int) floor($bb->maxY - $inset - $yInset);
		$maxZ = (int) floor($bb->maxZ - $inset);

		$blocksAround = [];
		for($z = $minZ; $z <= $maxZ; ++$z){
			for($x = $minX; $x <= $maxX; ++$x){
				for($y = $minY; $y <= $maxY; ++$y){
					$block = $world->getBlockAt($x, $y, $z);
					if(!$block instanceof Air){
						$blocksAround[] = $block;
					}
				}
			}
		}
		return $blocksAround;
	}

	/**
	 * @return Block[]
	 */
	public static function getFixedCollisionBlocks(World $world, AxisAlignedBB $bb, bool $targetFirst = false) : array{
		$minX = (int) floor($bb->minX - 1);
		$minY = (int) floor($bb->minY - 1);
		$minZ = (int) floor($bb->minZ - 1);
		$maxX = (int) floor($bb->maxX + 1);
		$maxY = (int) floor($bb->maxY + 1);
		$maxZ = (int) floor($bb->maxZ + 1);

		$collides = [];

		if($targetFirst){
			for($z = $minZ; $z <= $maxZ; ++$z){
				for($x = $minX; $x <= $maxX; ++$x){
					for($y = $minY; $y <= $maxY; ++$y){
						$block = $world->getBlockAt($x, $y, $z);
						if(self::collidesWithFixedBB($block, $bb)){
							return [$block];
						}
					}
				}
			}
		}else{
			for($z = $minZ; $z <= $maxZ; ++$z){
				for($x = $minX; $x <= $maxX; ++$x){
					for($y = $minY; $y <= $maxY; ++$y){
						$block = $world->getBlockAt($x, $y, $z);
						if(self::collidesWithFixedBB($block, $bb)){
							$collides[] = $block;
						}
					}
				}
			}
		}

		return $collides;
	}

	public static function collidesWithFixedBB(Block $block, AxisAlignedBB $bb) : bool{
		foreach(self::getFixedCollisionBoxes($block) as $box){
			if($bb->intersectsWith($box)){
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Block $block
	 *
	 * @return AxisAlignedBB[]
	 */
	public static function getFixedCollisionBoxes(Block $block) : array{
		$boxes = $block->getCollisionBoxes();

		if($block instanceof SnowLayer){
			$height = match (true) {
				$block->getLayers() == SnowLayer::MAX_LAYERS => 1,
				$block->getLayers() >= 4 => 0.5,
				default => 0
			};

			# SnowLayer の recalculateCollisionBoxes おかしくないですか

			foreach($boxes as $bb){
				$bb->maxY = $bb->minY + $height;
			}
		}
		if($block instanceof WaterLily){
			foreach($boxes as $bb){
				$bb->expand(1 / 16, 0, 1 / 16);
			}
		}

		return $boxes;
	}
}
