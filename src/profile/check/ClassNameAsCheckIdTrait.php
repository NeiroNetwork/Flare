<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

use RuntimeException;

trait ClassNameAsCheckIdTrait{

	private ?string $name = null;
	private ?string $type = null;

	public function getName() : string{
			$this->name ?? $this->solve();
		return $this->name;
	}

	private function solve() : void{
		$ref = new \ReflectionClass($this);
		$name = $ref->getShortName();
		$index = false;

		// todo: もっと賢い方法があるはず

		// testcase: AutoClickerCA

		$length = strlen($name);

		$type = "";

		// 最後から小文字のindexを探す
		for($i = $length - 1; $i > 1; $i--){
			$char = $name[$i];

			if(ctype_lower($char)){
				$index = $i + 1;
				break;
			}
		}
		// index: 10 (r)


		if($index === false){
			throw new RuntimeException("unexpected");
		}


		// 最後の文字が小文字の場合
		if($index === $length){
			$this->name = $name;
			$this->type = "";

			return;
		}

		// 小文字の場所から最後の文字までを type に追加する
		for($i = $index; $i < $length; $i++){
			$char = $name[$i];

			$type .= $char;
		}


		// type: 11, 12 (C, A)
		// name: 0 ~ 10 (AutoClicker)
		$this->name = substr($name, 0, $index);
		$this->type = $type;
	}

	public function getType() : string{
			$this->type ?? $this->solve();
		return $this->type;
	}
}
