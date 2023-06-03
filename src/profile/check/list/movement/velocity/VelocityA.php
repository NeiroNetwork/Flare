<?php

namespace NeiroNetwork\Flare\profile\check\list\movement\velocity;

use NeiroNetwork\Flare\profile\check\BaseCheck;
use NeiroNetwork\Flare\profile\check\CheckGroup;
use NeiroNetwork\Flare\profile\check\ClassNameAsCheckIdTrait;
use NeiroNetwork\Flare\profile\check\HandleEventCheckTrait;
use NeiroNetwork\Flare\profile\check\ViolationFailReason;
use NeiroNetwork\Flare\utils\MinecraftPhysics;
use NeiroNetwork\Flare\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;

class VelocityA extends BaseCheck{

	use HandleEventCheckTrait;
	use ClassNameAsCheckIdTrait;

	protected int $lastTick;

	protected int $predictStartTick;

	protected Vector3 $predict;
	protected Vector3 $original;

	/**
	 * @var Vector3[]
	 */
	protected array $predictList;

	/**
	 * @var Vector3[]
	 */
	protected array $deltaList;

	protected bool $predicting;

	public function onLoad() : void{
		$this->registerPacketHandler($this->handle(...));
		$this->registerSendPacketHandler($this->handleMotion(...));

		$this->lastTick = 0;
		$this->predict = Vector3::zero();
		$this->original = Vector3::zero();
		$this->predictList = [];
		$this->deltaList = [];
		$this->predicting = false;
		$this->predictStartTick = 0;
	}

	public function isExperimental() : bool{
		return true;
	}

	public function handleMotion(SetActorMotionPacket $packet) : void{
		if(!$this->profile->getPlayer()->isAlive()){
			return;
		}

		if($packet->actorRuntimeId !== $this->profile->getPlayer()->getId()){
			return;
		}

		if($packet->motion->y >= 7){
			return;
		}

		if($packet->motion->x >= 5){
			return;
		}

		if($packet->motion->z >= 5){
			return;
		}

		$this->broadcastDebugMessage($packet->motion);

		if($this->predicting){
			$this->resetAndCheck();
		}

		$this->predictStartTick = $this->profile->getServerTick();
		$this->original = clone $packet->motion;
		$this->predict = clone $packet->motion;
		$this->predictList = [];
		$this->deltaList = [];
		$this->predicting = true;
	}

	/**
	 * @return void null
	 *
	 * max <font color ="#FF866C">O(10n)</font>
	 */
	protected function resetAndCheck() : void{
		$this->reset();

		$trying = array_values($this->deltaList);
		$count = count($this->predictList);
		$predictList = array_values($this->predictList);
		$allow = 5;
		$allow += $this->profile->getFlare()->getSupports()->getLagCompensator()->getPingValue(Utils::getBestPing($this->profile->getPlayer()));
		$allow = min(10, $allow);

		if($count <= 7){
			return;
		}

		$maxCompleteSection = [
			"completes" => 0,
			"need" => 0,
			"count" => 0,
			"tries" => 0
		];

		for($_ = 0; $_ < $allow; $_++){
			if(count($trying) <= 1){
				break;
			}

			$completes = 0;

			for($i = 0; $i < $count; $i++){
				$expect = $trying[$i] ?? null;
				$predict = $predictList[$i];

				if(!is_null($expect)){
					$diff = $expect->subtractVector($predict)->abs();

					if($diff->y < 0.0001){
						$completes++;
					}
				}
			}

			unset($trying[array_key_first($trying)]);
			$trying = array_values($trying);
			$need = ($count - $_ - 1) - (int) round($count / 12);
			$this->broadcastDebugMessage("completes: {$completes}/{$need} count: {$count} tries: {$_}");

			if($maxCompleteSection["completes"] < $completes){
				$maxCompleteSection["completes"] = $completes;
				$maxCompleteSection["need"] = $need;
				$maxCompleteSection["count"] = $count;
				$maxCompleteSection["tries"] = $_;
			}

			if($completes >= $need){
				return;
			}
		}

		$this->fail(new ViolationFailReason(
			"Best result at {$maxCompleteSection['tries']} try (completes: {$maxCompleteSection['completes']}/{$maxCompleteSection['need']} predict count: {$maxCompleteSection['count']})"
		));
	}

	protected function reset() : void{
		$this->predict = Vector3::zero();
		$this->predicting = false;
	}

	public function handle(PlayerAuthInputPacket $packet) : void{
		$player = $this->profile->getPlayer();
		$md = $this->profile->getMovementData();
		$sd = $this->profile->getSurroundData();
		$deltaTick = $packet->getTick() - $this->lastTick;


		if($this->predicting){
			if(
				$sd->getClimbRecord()->getTickSinceAction() <= 5 ||
				$sd->getBounceRecord()->getTickSinceAction() <= 5 ||
				$sd->getCobwebRecord()->getTickSinceAction() <= 5 ||
				($md->getJumpRecord()->getEndTick() > $this->predictStartTick && $md->getJumpRecord()->getEndTick() < $this->predictStartTick + 5) || // ジャンプリセットによる誤検知無効化
				$sd->getHitHeadRecord()->getTickSinceAction() <= 2 ||
				$sd->getFlowRecord()->getTickSinceAction() <= 4 ||
				$md->getFlyRecord()->getTickSinceAction() <= 4
			){
				$this->reset();
			}else{
				// ジャンプリセットについて詳しく検証したら、
				// モーション(kb) 1tick後くらいにジャンプ: 飛距離が伸びる
				// モーション3tick後くらいにジャンプ(kb): ジャンプリセット
				// このチェックはモーション受けた時にジャンプすれば回避できる。
				for($i = 0; $i < $deltaTick; $i++){
					$this->predict = MinecraftPhysics::nextFreefallVelocity($this->predict);
				}

				$delta = $packet->getDelta();

				$this->broadcastDebugMessage("predict: " . $this->predict->y . " delta: {$delta->y}");

				$this->predictList[] = clone $this->predict;
				$this->deltaList[] = clone $delta;

				if(($delta->y < 0 && $this->predict->y < 0 && ($md->getOnGroundRecord()->getFlag() || $md->getRonGroundRecord()->getFlag())) || count($this->predictList) > 75){
					$this->resetAndCheck();
				}
			}

		}

		$this->lastTick = $packet->getTick();
	}

	public function getCheckGroup() : int{
		return CheckGroup::MOVEMENT;
	}
}
