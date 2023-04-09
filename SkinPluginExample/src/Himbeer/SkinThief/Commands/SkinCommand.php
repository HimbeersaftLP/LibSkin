<?php

declare(strict_types=1);

namespace Himbeer\SkinThief\Commands;

use Exception;
use Himbeer\LibSkin\SkinConverter;
use Himbeer\LibSkin\SkinGatherer;
use Himbeer\SkinThief\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class SkinCommand extends Command implements PluginOwned {
	private Main $owner;

	public function __construct(Main $owner) {
		parent::__construct("skin", "Skin Utilities", "/skin <load|save|steal|mcje> <skin source player> [skin destination player]");
		$this->setPermission("skinthief.command");
		$this->owner = $owner;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		// Main command permission checking
		if (!$this->testPermission($sender)) {
			return true;
		}

		// Subcommand checking
		if (count($args) < 1) {
			throw new InvalidCommandSyntaxException();
		}
		$subCommand = $args[0];
		$subCommands = ["load", "save", "steal", "mcje"];
		if (!in_array($subCommand, $subCommands)) {
			$sender->sendMessage(TextFormat::RED . "Unknown subcommand!");
			return false;
		}

		// Subcommand permission checking
		if (!($sender->hasPermission("skinthief.command.all") || $sender->hasPermission("skinthief.command." . $subCommand))) {
			$sender->sendMessage(TextFormat::RED . "You don't have the permission to use this subcommand!");
			return true;
		}

		// Getting target player
		if (isset($args[2])) {
			if (!$sender->hasPermission("skinthief.otherplayer")) {
				$sender->sendMessage(TextFormat::RED . "You don't have the permission to change another player's skin!");
				return true;
			}
			$targetPlayer = $this->getOwningPlugin()->getServer()->getPlayerByPrefix($args[2]);
			if ($targetPlayer === null) {
				$sender->sendMessage(TextFormat::RED . "Target player not found!");
				return true;
			}
		}
		if (!isset($targetPlayer) && !$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "You must provide a player name if you run this command from the console!");
			return true;
		}

		/**
		 * @var Player
		 */
		$player = $targetPlayer ?? $sender;

		// Getting and escaping file name for load and save subcommands
		if ($subCommand === "load" || $subCommand === "save") {
			if (isset($args[1]) && !$sender->hasPermission("skinthief.anyfilename")) {
				$sender->sendMessage(TextFormat::RED . "You don't have the permission to choose a file name!");
				return true;
			}
			$baseFileName = $args[1] ?? strtolower($player->getName());
			$baseFileName = $this->getOwningPlugin()->escapeFileName($baseFileName);
			$imageFileName = $baseFileName . ".png";
			$geoFileName = $baseFileName . ".json";
			$fullImagePath = $this->getOwningPlugin()->getSkinStorageLocation($imageFileName);
			$fullGeoPath = $this->getOwningPlugin()->getSkinStorageLocation($geoFileName);
		}

		// Ensure player name is provided for steal and mcje subcommands
		if ($subCommand === "steal" || $subCommand === "mcje") {
			if (!isset($args[1])) {
				$sender->sendMessage(TextFormat::RED . "You must provide a player name!");
				return true;
			}
			$stealFromPlayerName = $args[1];
		}

		// Handle subcommand
		switch ($subCommand) {
			case "load":
				try {
					if (is_file($fullImagePath)) {
						$skinData = SkinConverter::imageToSkinDataFromPngPath($fullImagePath);
						if ($sender->hasPermission("skinthief.metadata") && is_file($fullGeoPath)) {
							self::changeSkinAndGeo($player, $skinData, $fullGeoPath);
							$sender->sendMessage("Skin and geometry loaded from file successfully!");
						} else {
							self::changeSkin($player, $skinData);
							$sender->sendMessage("Skin loaded from file successfully!");
						}
					} else {
						$sender->sendMessage(TextFormat::RED . "File \"$imageFileName\" not found!");
					}
				} catch (Exception $exception) {
					$sender->sendMessage(TextFormat::RED . "An unknown error occurred!");
					$this->getOwningPlugin()->getLogger()->notice("An exception occurred while trying to load a skin: " . $exception->getMessage());
				}
				break;
			case "save":
				$skinData = $player->getSkin()->getSkinData();
				try {
					SkinConverter::skinDataToImageSave($skinData, $fullImagePath);
					if ($sender->hasPermission("skinthief.metadata")) {
						self::skinMetaDataToJsonSave($player->getSkin()->getSkinId(), $player->getSkin()->getGeometryName(), $player->getSkin()->getGeometryData(), $fullGeoPath);
						$sender->sendMessage("Skin and geometry saved successfully as \"$imageFileName\" and \"$geoFileName\"!");
					} else {
						$sender->sendMessage("Skin saved successfully as \"$imageFileName\"!");
					}
				} catch (Exception $exception) {
					$sender->sendMessage(TextFormat::RED . "An unknown error occurred!");
					$this->getOwningPlugin()->getLogger()->notice("An exception occurred while trying to save a skin: " . $exception->getMessage());
				}
				break;
			case "steal":
				$onlinePlayer = $this->getOwningPlugin()->getServer()->getPlayerByPrefix($stealFromPlayerName);
				if ($onlinePlayer !== null) {
					$skinData = $onlinePlayer->getSkin()->getSkinData();
				} else {
					$skinData = SkinGatherer::getSkinDataFromOfflinePlayer($stealFromPlayerName);
				}
				if ($skinData === null) {
					$sender->sendMessage(TextFormat::RED . "Player not found or doesn't have a skin saved.");
				} else {
					self::changeSkin($player, $skinData);
					$sender->sendMessage("Skin stolen successfully!");
				}
				break;
			case "mcje":
				try {
					SkinGatherer::getJavaEditionSkinData($stealFromPlayerName, function($skinData, $state) use ($sender, $player) {
						if ($skinData === null) {
							switch ($state) {
								case SkinGatherer::MCJE_STATE_ERR_UNKNOWN:
									$sender->sendMessage(TextFormat::RED . "An unknown error occurred!");
									break;
								case SkinGatherer::MCJE_STATE_ERR_PLAYER_NOT_FOUND:
									$sender->sendMessage(TextFormat::RED . "Player not found!");
									break;
								case SkinGatherer::MCJE_STATE_ERR_TOO_MANY_REQUESTS:
									$sender->sendMessage(TextFormat::RED . "Mojang API rate limit reached!");
									break;
							}
						} else {
							self::changeSkin($player, $skinData);
							$sender->sendMessage("Skin downloaded successfully!");
						}
					});
				} catch (Exception $exception) {
					$sender->sendMessage(TextFormat::RED . "An unknown error occurred!");
					$this->getOwningPlugin()->getLogger()->notice("An exception occurred while trying to download a Java Edition skin: " . $exception->getMessage());
				}
				break;
		}
		return true;
	}

	public function getOwningPlugin() : Main {
		return $this->owner;
	}

	private static function changeSkin(Player $player, string $skinData) : void {
		$player->setSkin(new Skin($player->getSkin()->getSkinId(), $skinData));
		$player->sendSkin();
	}

	private static function changeSkinAndGeo(Player $player, string $skinData, string $fullGeoPath) : void {
		$player->setSkin(self::skinMetaDataFromJsonFile($fullGeoPath, $skinData));
		$player->sendSkin();
	}

	/**
	 * @param string $skinId Skin ID (e.g. CustomSlim<UUID>)
	 * @param string $geometryName Skin geometry name (e.g. geometry.humanoid.customSlim)
	 * @param string $geometryData Skin geometry data as JSON string
	 * @param string $savePath Path where skin PNG is saved
	 *
	 * @returns void
	 * @throws Exception
	 */
	public static function skinMetaDataToJsonSave(string $skinId, string $geometryName, string $geometryData, string $savePath) : void {
		$jsonData = json_encode([
			"skinId" => $skinId,
			"geometryName" => $geometryName,
			"geometryData" => $geometryData
		]);
		if ($jsonData === false) {
			throw new Exception("JSON encoding failed!");
		}
		if (file_put_contents($savePath, $jsonData) === false) {
			throw new Exception("Saving JSON file failed!");
		}
	}

	/**
	 * @param string $savePath Path where skin PNG is saved
	 * @param string $skinData Data of the skin that will be returned
	 *
	 * @return Skin Skin with the given skin data and the loaded metadata
	 * @throws Exception
	 */
	public static function skinMetaDataFromJsonFile(string $savePath, string $skinData) : Skin {
		$jsonData = file_get_contents($savePath);
		if ($jsonData === false) {
			throw new Exception("Reading file failed!");
		}
		$parsedData = json_decode($jsonData, true);
		if ($parsedData === null) {
			throw new Exception("JSON decoding failed!");
		}
		$skinId = $parsedData["skinId"];
		$geometryName = $parsedData["geometryName"];
		$geometryData = $parsedData["geometryData"];
		return new Skin($skinId, $skinData, "", $geometryName, $geometryData);
	}
}