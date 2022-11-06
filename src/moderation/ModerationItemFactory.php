<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\moderation;

use Closure;
use NeiroNetwork\Flare\Flare;
use pocketmine\block\Air;
use pocketmine\block\Wheat;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Explosion;
use pocketmine\world\particle\BlockForceFieldParticle;
use pocketmine\world\particle\HappyVillagerParticle;

class ModerationItemFactory {
	use SingletonTrait {
		getInstance as Singleton__getInstance;
	}

	public static function getInstance(): self {
		return self::Singleton__getInstance();
	}

	/**
	 * @var ModerationItem[]
	 */
	protected array $list;

	public function __construct() {
		$this->list = [];

		$this->register(new ModerationItem(
			"magic",
			VanillaItems::STICK()->setCustomName("§r§d§lマジックステッキ"),
			function (PlayerInteractEvent $event): void {
			},
			function (PlayerItemUseEvent $event): void {
				$player = $event->getPlayer();
				$directionVector = $player->getDirectionVector();

				if ($player->isSneaking()) {
					$found = false;
					foreach (VoxelRayTrace::inDirection($player->getEyePos(), $directionVector, 100) as $pos) {
						$block = $player->getWorld()->getBlockAt($pos->x, $pos->y, $pos->z);

						if ($block->isSolid()) {
							$found = true;
							continue;
						}

						if ($found) {
							$player->teleport($pos);
							break;
						}
					}

					if (!$found) {
						$player->sendMessage(Flare::PREFIX . "視線の先にブロックがありません");
					}
				} else {
					$player->setMotion($directionVector->multiply(1.25));
				}
			}
		));

		$this->register(new ModerationItem(
			"block_modifier",
			VanillaItems::STONE_AXE()->setCustomName("§r§c§lブロックモディファイア"),
			function (PlayerInteractEvent $event): void {
				$player = $event->getPlayer();
				$block = $event->getBlock();

				if ($player->isSneaking()) {
				} else {
					if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
						if ($block->ticksRandomly()) {
							for ($i = 0; $i < 10; $i++) {
								$block->onRandomTick();
							}

							$block->getPosition()->getWorld()->addParticle($block->getPosition()->add(0.5, 0.5, 0.5)->getSide($event->getFace()), new HappyVillagerParticle());

							$player->sendMessage(Flare::PREFIX . "このブロックを 10回 チックさせました");
						} else {
							$player->sendMessage(Flare::PREFIX . "このブロックはチックできません... §7成長させられる種などに対して試してみてください");
						}
					}
				}
			},
			function (PlayerItemUseEvent $event): void {
			}
		));
	}

	public function register(ModerationItem $moderationItem): void {
		$id = $moderationItem->getId();
		if (isset($this->list[$id])) {
			throw new \Exception("moderation item id \"$id\" already registered");
		}

		$this->list[$id] = $moderationItem;
	}

	public function get(string $id): ?ModerationItem {
		return $this->list[$id] ?? null;
	}

	/**
	 * @return ModerationItem[]
	 */
	public function getAll(): array {
		return $this->list;
	}

	public function search(Item $item, bool $allowLiteral = false): ?ModerationItem {
		$tag = $item->getNamedTag();

		$id = ($data = $tag->getString("moderation_item_id", "")) === "" ? null : $data;

		$result = null;

		if ($id !== null) {
			$result = $this->get($id);
		}

		if ($allowLiteral && is_null($result)) {
			$result = $this->searchLiteral($item);
		}

		return $result;
	}

	protected function searchLiteral(Item $item): ?ModerationItem {
		foreach ($this->list as $moderationItem) {
			if ($moderationItem->getItem()->equals($item, true, false)) {
				return $moderationItem;
			}
		}

		return null;
	}
}
