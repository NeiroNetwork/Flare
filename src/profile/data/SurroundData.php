<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\data;

use NeiroNetwork\Flare\profile\PlayerProfile;
use NeiroNetwork\Flare\utils\BlockUtil;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use pocketmine\block\Block;
use pocketmine\block\Cobweb;
use pocketmine\block\Liquid;
use pocketmine\block\Slime;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\EventPriority;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SurroundData{

	/**
	 * @var Block[]
	 */
	protected array $nearbyBlocks;

	/**
	 * @var Block[]
	 */
	protected array $stepBlocks;

	/**
	 * @var Block[]
	 */
	protected array $overheadBlocks;

	/**
	 * @var Block[]
	 */
	protected array $touchingBlocks;

	/**
	 * @var Block[]
	 */
	protected array $complexBlocks;

	/**
	 * @var Block[]
	 */
	protected array $ableToStepBlocks;

	/**
	 * @var Block
	 */
	protected Block $step;

	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $slip;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $bounce;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $nearbyUpdate;
	/**
	 * @var InstantActionRecord
	 */
	protected InstantActionRecord $collideUpdate;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $hithead;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $cobweb;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $flow;
	/**
	 * @var ActionRecord
	 */
	protected ActionRecord $climb;

	public function __construct(protected PlayerProfile $profile){
		$uuid = $profile->getPlayer()->getUniqueId()->toString();
		$emitter = $this->profile->getFlare()->getEventEmitter();
		$plugin = $this->profile->getFlare()->getPlugin();
		$links = $this->profile->getEventHandlerLink();
		$links->add($emitter->registerPacketHandler(
			$uuid,
			PlayerAuthInputPacket::NETWORK_ID,
			$this->handleInput(...),
			false,
			EventPriority::NORMAL
		));

		($hash = $emitter->registerEventHandler(
			BlockUpdateEvent::class,
			$this->handleBlockUpdate(...),
			false,
			EventPriority::MONITOR
		)) !== null ? $links->add($hash) : null;
		// fixme: E?

		($hash = $emitter->registerEventHandler(
			BlockFormEvent::class,
			$this->handleBlockForm(...),
			false,
			EventPriority::MONITOR
		)) !== null ? $links->add($hash) : null;

		ProfileData::autoPropertyValue($this);

		$this->step = VanillaBlocks::AIR();
	}

	public function getSlipRecord() : ActionRecord{
		return $this->slip;
	}

	public function getBounceRecord() : ActionRecord{
		return $this->bounce;
	}

	public function getHitHeadRecord() : ActionRecord{
		return $this->hithead;
	}

	public function getCobwebRecord() : ActionRecord{
		return $this->cobweb;
	}

	public function getFlowRecord() : ActionRecord{
		return $this->flow;
	}

	public function getClimbRecord() : ActionRecord{
		return $this->climb;
	}

	public function getNearbyUpdateRecord() : InstantActionRecord{
		return $this->nearbyUpdate;
	}

	public function getCollideUpdateRecord() : InstantActionRecord{
		return $this->collideUpdate;
	}

	/**
	 * Get the value of nearbyBlocks
	 *
	 * @return Block[]
	 */
	public function getNearbyBlocks() : array{
		return $this->nearbyBlocks;
	}

	/**
	 * Get the value of stepBlocks
	 *
	 * @return Block[]
	 */
	public function getStepBlocks() : array{
		return $this->stepBlocks;
	}

	/**
	 * Get the value of overheadBlocks
	 *
	 * @return Block[]
	 */
	public function getOverheadBlocks() : array{
		return $this->overheadBlocks;
	}

	/**
	 * Get the value of step
	 *
	 * @return Block
	 */
	public function getStep() : Block{
		return $this->step;
	}

	/**
	 * Get the value of touchingBlocks
	 *
	 * @return Block[]
	 */
	public function getTouchingBlocks() : array{
		return $this->touchingBlocks;
	}

	/**
	 * @return Block[]
	 */
	public function getAbleToStepBlocks() : array{
		return $this->ableToStepBlocks;
	}

	/**
	 * Get the value of complexBlocks
	 *
	 * @return Block[]
	 */
	public function getComplexBlocks() : array{
		return $this->complexBlocks;
	}

	protected function handleBlockUpdate(BlockUpdateEvent $event) : void{
		$this->handleBlockChanges($event);
	}

	protected function handleBlockChanges(BlockEvent $event) : void{
		$block = $event->getBlock();
		$player = $this->profile->getPlayer();
		$distSquared = $player->getPosition()->distanceSquared($block->getPosition());
		if($distSquared <= 49){ // 7
			$this->nearbyUpdate->onAction();
		}

		if($block->collidesWithBB($this->profile->getMovementData()->getBoundingBox()->expandedCopy(0.25, 0.2, 0.25))){
			$this->collideUpdate->onAction();
		}
	}

	protected function handleBlockForm(BlockFormEvent $event) : void{
		$this->handleBlockChanges($event);
	}

	protected function handleInput(PlayerAuthInputPacket $packet) : void{
		$position = $packet->getPosition()->subtract(0, MinecraftPhysics::PLAYER_EYE_HEIGHT, 0);
		$d = $this->profile->getMovementData()->getTo()->subtractVector($this->profile->getMovementData()->getFrom());
		$player = $this->profile->getPlayer();

		$block = $player->getWorld()->getBlock($position);

		$playerBB = $this->profile->getMovementData()->getBoundingBox();
		$this->nearbyBlocks = BlockUtil::getEntityBlocksAround($playerBB, $player->getWorld(), -0.2);

		$stepPos = $position->subtract(0, 0.74, 0);
		$stepBlock = $player->getWorld()->getBlock($stepPos);

		$this->step = $stepBlock;

		$this->slip->update($stepBlock->getFrictionFactor() > 0.6);

		$this->stepBlocks = [];
		$this->touchingBlocks = [];
		$this->overheadBlocks = [];
		$this->complexBlocks = [];
		$this->ableToStepBlocks = [];

		$cobweb = false;
		$flow = false;
		$hittingHead = false;
		$climb = false;

		$checkHittingHead = $d->lengthSquared() <= 25;

		$headBB = clone $playerBB;
		$headBB->minY = $headBB->maxY; #bbのminYとmaxYを同じにする (意味はあまりないけど軽量化)
		$headBB->maxY += 0.12; #maxYを上にずらす
		$headBB->expand(0.1, 0.0, 0.1); #少し横をかくちょうする
		// $headBB = $headBB->addCoord($d->x, max($d->y, 0), $d->z); #早い移動に対応、minYを伸ばさないようにする

		$touchBB = clone $playerBB;
		$touchBB->expand(0.14, 0.0, 0.14);

		foreach($this->nearbyBlocks as $block){
			if($block instanceof Cobweb){
				$cobweb = true;
			}

			if($block instanceof Liquid){
				$flow = true;
			}

			if($block->canClimb() && $player->canClimb()){
				$climb = true;
			}

			if(count($block->getCollisionBoxes()) > 1){
				$this->complexBlocks[] = $block;
			}

			if($block->getPosition()->y < $position->y){
				$this->stepBlocks[] = $block;
			}elseif($block->getPosition()->y > ($position->y + $this->profile->getMovementData()->getEyeHeight())){
				$this->overheadBlocks[] = $block;
			}

			if($checkHittingHead){
				if($block->collidesWithBB($headBB)){
					$hittingHead = true;
					$checkHittingHead = false;
				}
			}

			if($block->collidesWithBB($touchBB)){
				$this->touchingBlocks[] = $block;
			}

			if(BlockUtil::isAbleToStep($block)){
				$this->ableToStepBlocks[] = $block;
			}
		}

		$this->hithead->update($hittingHead);
		$this->bounce->update($stepBlock instanceof Slime);
		$this->cobweb->update($cobweb);
		$this->flow->update($flow);
		$this->climb->update($climb);

		$this->nearbyUpdate->update();
		$this->collideUpdate->update();
	}
}
