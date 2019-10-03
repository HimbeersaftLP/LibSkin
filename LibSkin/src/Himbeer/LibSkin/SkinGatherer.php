<?php

declare(strict_types=1);

namespace Himbeer\LibSkin;

use Exception;
use pocketmine\scheduler\BulkCurlTask;
use pocketmine\Server;
use pocketmine\utils\InternetException;

final class SkinGatherer {
	public const MCJE_STATE_SUCCESS = 0;
	public const MCJE_STATE_ERR_UNKNOWN = 1;
	public const MCJE_STATE_ERR_PLAYER_NOT_FOUND = 2;
	public const MCJE_STATE_ERR_TOO_MANY_REQUESTS = 3;

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

	/**
	 * @param string $url
	 * @param callable $callback
	 */
	private static function asyncHttpGetRequest(string $url, callable $callback) {
		$task = new class([[
			"page" => $url
		]], $callback) extends BulkCurlTask {
			public function __construct(array $operations, $callback) {
				parent::__construct($operations, $callback);
			}
			public function onCompletion(Server $server) {
				/** @var callable $callback */
				$callback = $this->fetchLocal();
				if (isset($this->getResult()[0]) && !$this->getResult()[0] instanceof InternetException) {
					$response = $this->getResult()[0];
					$callback($response);
				}
			}
		};
		Server::getInstance()->getAsyncPool()->submitTask($task);
	}

	/**
	 * @param string $userName Java Edition player name
	 * @param callable $callback A function which gets called when the request is finished, with the first argument being the URL (or null) and the second the success/error state
	 */
	public static function getJavaEditionSkinUrl(string $userName, callable $callback) {
		self::asyncHttpGetRequest("https://api.mojang.com/users/profiles/minecraft/{$userName}", function ($response) use ($callback) {
			var_dump($response);;
			$body = $response[0];
			if ($body === "") {
				if ($response[2] === 204) { // Status Code 204: No Content
					$callback(null, self::MCJE_STATE_ERR_PLAYER_NOT_FOUND);
				}  else {
					$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
				}
				return;
			}
			$data = json_decode($body, true);
			if ($data === null || !isset($data["id"])) {
				$callback(null, self::MCJE_STATE_ERR_UNKNOWN);
				return;
			}
			self::asyncHttpGetRequest("https://sessionserver.mojang.com/session/minecraft/profile/{$data["id"]}", function ($response) use ($callback) {
				var_dump($response);
				$body = $response[0];
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
	 * @param string $userName
	 * @param callable $callback A function which gets called when the request is finished, with the first argument being the skin data (or null) and the second the success/error state
	 * @throws Exception
	 */
	public static function getJavaEditionSkinData(string $userName, callable $callback) {
		self::getJavaEditionSkinUrl($userName, function ($skinUrl, $state) use ($callback) {
			$callback($skinUrl === null ? null : SkinConverter::imageToSkinDataFromPngPath($skinUrl), $state);
		});
	}
}