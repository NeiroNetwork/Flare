<?php

declare(strict_types=1);

namespace NeiroNetwork\Flare;

use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

class FlarePermissionNames{

	public const TEAM = "flare.group.team";

	public const INSPECTOR = "flare.group.inspector";

	public const OPERATOR = "flare.group.operator";

	public static function init() : void{
		$operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);

		DefaultPermissions::registerPermission(
			new Permission(self::OPERATOR, "Flare: Operator", [
				self::INSPECTOR => true,
				self::TEAM => true
			]),
			[
				$operator
			]
		);

		DefaultPermissions::registerPermission(new Permission(self::INSPECTOR, "Flare: Check Inspector"));

		DefaultPermissions::registerPermission(new Permission(self::TEAM, "Flare: Operation Team"));
	}
}
