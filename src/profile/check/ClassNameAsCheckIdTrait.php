<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\profile\check;

trait ClassNameAsCheckIdTrait {

	private ?string $name = null;
	private ?string $type = null;

	private function solve(): void {
		$ref = new \ReflectionClass($this);
		$words =  explode(",", wordwrap($ref->getShortName(), 75, ","));
		$this->name = $words[0] ?? throw new \Exception("cannot solve check name from class name: \"{$ref->getShortName()}\"");
		$this->type = $words[1] ?? "";
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
