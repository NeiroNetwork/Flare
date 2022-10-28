<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare\data\report;

use Exception;
use pocketmine\plugin\PluginLogger;
use Webmozart\PathUtil\Path;

class DataReportManager {

	/**
	 * @var array<string, array<string, DataReport>>
	 */
	protected array $reports;

	public function __construct(protected string $folder) {
		$this->reports = [];
		$this->load();
		@mkdir($folder, recursive: true);
	}

	public function set(string $uuid, string $name, DataReport $report, bool $override = false): void {
		if (isset($this->reports[$uuid][$name]) && !$override) {
			throw new \Exception("\"$uuid:$name\" is already set");
		}

		$this->reports[$uuid][$name] = $report;
	}

	public function setDefault(string $uuid, string $name, DataReport $report): bool {
		try {
			$this->set($uuid, $name, $report, false);
		} catch (Exception $e) {
			return false;
		} finally {
			return true;
		}
		// catchしないほうがいい？
		// fixme: 別に実装したほうがいい？
	}

	public function get(string $uuid, string $name): ?DataReport {
		return $this->reports[$uuid][$name] ?? null;
	}

	/**
	 * @param string $uuid
	 * 
	 * @return DataReport[]
	 */
	public function getAll(string $uuid): array {
		return $this->reports[$uuid] ?? [];
	}

	/**
	 * @return DataReport[][]
	 */
	public function getAllRaw(): array {
		return $this->reports;
	}

	public function getFile(string $uuid, string $name): string {
		return Path::join([$this->folder, $uuid, $name . ".json"]);
	}

	public function save(): void {
		foreach ($this->reports as $uuid => $all) {
			foreach ($all as $name => $report) {
				@mkdir(Path::join([$this->folder, $uuid]), recursive: true);
				$file = $this->getFile($uuid, $name);
				$assoc = $report->jsonSerialize();
				if (is_array($assoc)) {
					// fixme: 
					$assoc["name"] = $name;
					$assoc["uuid"] = $uuid;
					$assoc["class"] = $report::class;
				} else {
					continue;
				}

				file_put_contents($file, json_encode($assoc, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
			}
		}
	}

	protected function load(): void {
		foreach (glob(Path::join([$this->folder, "*.json"])) as $file) {
			$assoc_json = json_decode(file_get_contents($file), true, 512); // fixme: JSON_BIGINT_AS_STRING ?

			if (!isset($assoc_json["name"], $assoc_json["uuid"], $assoc_json["class"])) {
				continue;
			}

			$class = $assoc_json["class"];

			if (!is_a($class, DataReport::class) && !is_subclass_of($class, DataReport::class)) {
				continue;
			}

			$report = new $class();
			/**
			 * @var DataReport $report
			 */

			$report->load($assoc_json);
			$this->reports[$assoc_json["uuid"]][$assoc_json["name"]] = $report;
		}
	}
}
