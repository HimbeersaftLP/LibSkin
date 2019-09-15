<?php

declare(strict_types=1);

namespace Himbeer\LibSkin;

use pocketmine\Server;

final class SkinGatherer {
	/**
	 * @param string $playerName
	 * @return string|null Minecraft Skin Data or null if the player doesn't exist or doesn't have saved skin data
	 */
	public static function getSkinDataFromOfflinePlayer(string $playerName): ?string {
		$namedTag = Server::getInstance()->getOfflinePlayerData($playerName);
		$skinTag = $namedTag->getCompoundTag("Skin");
		if ($skinTag === null) {
			return null;
		}
		$skinData = $skinTag->getByteArray("Data");
		return $skinData;
	}

	public static function downloadJavaEditionSkin(string $userName) {

	}
}