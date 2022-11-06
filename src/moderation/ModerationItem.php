<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\moderation;

use Closure;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Utils;

class ModerationItem {

	public static function is(Item $item): bool {
		return (bool) max(0, $item->getNamedTag()->getByte("is_moderation_item", 0));
	}

	protected string $id;
	protected Item $item;
	protected Closure $onInteract;
	protected Closure $onClickAir;

	// todo: permission

	/**
	 * Undocumented function
	 *
	 * @param string $id
	 * @param Item $item
	 * @param Closure $onInteract Interaction: block
	 * @param Closure $onClickAir
	 */
	public function __construct(
		string $id,
		Item $item,
		Closure $onInteract,
		Closure $onClickAir
	) {

		$this->id = $id;
		$this->item = clone $item;

		$tag = $this->item->getNamedTag();
		$tag->setByte("is_moderation_item", 1);
		$tag->setString("moderation_item_id", $id);

		$this->item->setLore(["§r§6Moderation Item"]);

		$this->setOnInteract($onInteract);
		$this->setOnClickAir($onClickAir);
	}

	/**
	 * Get the value of id
	 *
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * Get the value of item
	 *
	 * @return Item
	 */
	public function getItem(): Item {
		return $this->item;
	}

	/**
	 * Get the value of onInteract
	 *
	 * @return Closure
	 */
	public function getOnInteract(): Closure {
		return $this->onInteract;
	}

	/**
	 * Set the value of onInteract
	 *
	 * @param Closure $onInteract
	 *
	 * @return self
	 */
	public function setOnInteract(Closure $onInteract): self {
		Utils::validateCallableSignature(function (PlayerInteractEvent $event): void {
		}, $onInteract);
		$this->onInteract = $onInteract;

		return $this;
	}

	/**
	 * Get the value of onClickAir
	 *
	 * @return Closure
	 */
	public function getOnClickAir(): Closure {
		return $this->onClickAir;
	}

	/**
	 * Set the value of onClickAir
	 *
	 * @param Closure $onClickAir
	 *
	 * @return self
	 */
	public function setOnClickAir(Closure $onClickAir): self {
		Utils::validateCallableSignature(function (PlayerItemUseEvent $event): void {
		}, $onClickAir);

		$this->onClickAir = $onClickAir;

		return $this;
	}
}
