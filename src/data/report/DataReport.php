<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\data\report;

use JsonSerializable;
use pocketmine\utils\Utils;

class DataReport implements JsonSerializable {

	/**
	 * @var mixed[]
	 */
	protected array $values;

	final public function __construct() {
		$this->values = [];
	}

	public function add(mixed $data): void {
		$this->values[] = $data;
	}

	public function jsonSerialize(): mixed {
		return [
			"values" => $this->values
		];
	}

	public function load(array $assoc_json): void {
		Utils::validateArrayValueType($assoc_json["values"] ?? [], function (mixed $data): void {
		});

		foreach ($assoc_json["values"] ?? [] as $data) {
			$this->add($data);
		}
	}
}
