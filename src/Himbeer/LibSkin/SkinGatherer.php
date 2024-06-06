<?php

declare(strict_types=1);

namespace Himbeer\LibSkin;

use Exception;
use pocketmine\scheduler\BulkCurlTask;
use pocketmine\scheduler\BulkCurlTaskOperation;
use pocketmine\Server;
use pocketmine\utils\InternetException;
use pocketmine\utils\InternetRequestResult;

final class SkinGatherer {
	public const MCJE_STATE_SUCCESS = 0;
	public const MCJE_STATE_ERR_UNKNOWN = 1;
	public const MCJE_STATE_ERR_PLAYER_NOT_FOUND = 2;
	public const MCJE_STATE_ERR_TOO_MANY_REQUESTS = 3;

	/**
	 * @param string $playerName
	 *
	 * @return string|null Minecraft Skin Data or null if the player doesn't exist or doesn't have saved skin data
	 */
	public static function getSkinDataFromOfflinePlayer(string $playerName) : ?string {
		$namedTag = Server::getInstance()->getOfflinePlayerData($playerName);
		if ($namedTag === null) {
			return null;
		}
		$skinTag = $namedTag->getCompoundTag("Skin");
		if ($skinTag === null) {
			return null;
		}
		$skinData = $skinTag->getByteArray("Data");
		return $skinData;
	}

	/**
	 * @param string   $userName
	 * @param callable $callback A function which gets called when the request is finished, with the first argument being the skin data (or null) and the second the success/error state
	 *
	 * @throws Exception
	 */
	public static function getJavaEditionSkinData(string $userName, callable $callback) {
		self::getJavaEditionSkinUrl($userName, function($skinUrl, $state) use ($callback) {
			$callback($skinUrl === null ? null : SkinConverter::imageToSkinDataFromPngPath($skinUrl), $state);
		});
	}

	/**
	 * @param string   $userName Java Edition player name
	 * @param callable $callback A function which gets called when the request is finished, with the first argument being the URL (or null) and the second the success/error state
	 */
	public static function getJavaEditionSkinUrl(string $userName, callable $callback) {
		self::asyncHttpGetRequest("https://api.mojang.com/users/profiles/minecraft/{$userName}", function(InternetRequestResult|null $response) use ($callback) {
			if ($response === null) {
				$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
				return;
			}
			$body = $response->getBody();
			if ($body === "") {
				if ($response->getCode() === 204) { // Status Code 204: No Content
					$callback(null, self::MCJE_STATE_ERR_PLAYER_NOT_FOUND);
				} else {
					$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
				}
				return;
			}
			$data = json_decode($body, true);
			if ($data === null || !isset($data["id"])) {
				$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
				return;
			}
			self::asyncHttpGetRequest("https://sessionserver.mojang.com/session/minecraft/profile/{$data["id"]}", function(InternetRequestResult|null $response) use ($callback) {
				if ($response === null) {
					$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
					return;
				}
				$body = $response->getBody();
				if ($body === "") {
					$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
					return;
				}
				$data = json_decode($body, true);
				if ($data === null || !isset($data["properties"][0]["name"]) || $data["properties"][0]["name"] !== "textures") {
					if (isset($data["error"]) && $data["error"] === "TooManyRequestsException") {
						$callback(null, self::MCJE_STATE_ERR_TOO_MANY_REQUESTS);
					} else {
						$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
					}
					return;
				}
				if (isset($data["properties"][0]["value"]) && ($b64dec = base64_decode($data["properties"][0]["value"]))) {
					$textureInfo = json_decode($b64dec, true);
					if ($textureInfo !== null && isset($textureInfo["textures"]["SKIN"]["url"])) {
						$skinUrl = $textureInfo["textures"]["SKIN"]["url"];
						$callback($skinUrl, self::MCJE_STATE_SUCCESS);
						return;
					}
				}
				$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
			});
		});
	}

	/**
	 * @param string   $url
	 * @param callable $callback
	 */
	private static function asyncHttpGetRequest(string $url, callable $callback) {
		/**
		 * @param InternetRequestResult[] $results
		 *
		 * @return void
		 */
		$bulkCurlTaskCallback = function(array $results) use ($callback) {
			if (isset($results[0]) && !$results[0] instanceof InternetException) {
				$callback($results[0]);
			} else {
				$callback(null);
			}
		};
		$task = new BulkCurlTask([
			new BulkCurlTaskOperation($url)
		], $bulkCurlTaskCallback);
		Server::getInstance()->getAsyncPool()->submitTask($task);
	}
}