<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

trait ClassNameAsCheckIdTrait {

	private ?string $name = null;
	private ?string $type = null;

	private function solve(): void {
		$ref = new \ReflectionClass($this);
		$camelcase = lcfirst($ref->getShortName());
		$index = false;

		// fixme: preg_match?
		foreach (range("A", "Z") as $needle) { // stupid
			$index = strpos($camelcase, $needle);
			if ($index !== false) {
				break;
			}
		}
		if ($index !== false) {
			$chunks = substr_replace($camelcase, ",", $index, 0);
			$words = explode(",", $chunks);
			$this->name = ucfirst($words[0] ?? throw new \Exception("cannot solve check name from class name: \"{$ref->getShortName()}\""));
			$this->type = $words[1] ?? "";
		} else {
			$this->name = $ref->getShortName();
			$this->type = "";
		}
	}

	public function getName(): string {
		$this->name ?? $this->solve();
		return $this->name;
	}

	public function getType(): string {
		$this->type ?? $this->solve();
		return $this->type;
	}
}
