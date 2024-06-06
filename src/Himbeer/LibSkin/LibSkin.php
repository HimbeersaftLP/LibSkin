<?php

declare(strict_types=1);

namespace Himbeer\LibSkin;

use Exception;

final class LibSkin {
	// https://github.com/pmmp/PocketMine-MP/blob/a19143cae76ad55f1bdc2f39ad007b1fc170980b/src/pocketmine/entity/Skin.php#L33-L37
	public const ACCEPTED_SKIN_SIZES = [
		64 * 32 * 4,
		64 * 64 * 4,
		128 * 128 * 4
	];

	public const SKIN_WIDTH_MAP = [
		64 * 32 * 4 => 64,
		64 * 64 * 4 => 64,
		128 * 128 * 4 => 128
	];

	public const SKIN_HEIGHT_MAP = [
		64 * 32 * 4 => 32,
		64 * 64 * 4 => 64,
		128 * 128 * 4 => 128
	];

	public static function validateSize(int $size) {
		if (!in_array($size, self::ACCEPTED_SKIN_SIZES)) {
			throw new Exception("Invalid skin size");
		}
	}
}