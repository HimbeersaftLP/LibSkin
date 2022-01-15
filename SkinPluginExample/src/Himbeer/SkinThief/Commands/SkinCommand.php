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
			$fileName = $args[1] ?? strtolower($player->getName());
			$fileName = $this->getOwningPlugin()->escapeFileName($fileName) . ".png";
			$fullPath = $this->getOwningPlugin()->getSkinStorageLocation($fileName);
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
					if (is_file($fullPath)) {
						$skinData = SkinConverter::imageToSkinDataFromPngPath($fullPath);
						self::changeSkin($player, $skinData);
						$sender->sendMessage("Skin loaded from file successfully!");
					} else {
						$sender->sendMessage(TextFormat::RED . "File \"$fileName\" not found!");
					}
				} catch (Exception $exception) {
					$sender->sendMessage(TextFormat::RED . "An unknown error occurred!");
					$this->getOwningPlugin()->getLogger()->notice("An exception occurred while trying to load a skin: " . $exception->getMessage());
				}
				break;
			case "save":
				$skinData = $player->getSkin()->getSkinData();
				try {
					SkinConverter::skinDataToImageSave($skinData, $fullPath);
					$sender->sendMessage("Skin saved successfully as \"$fileName\"!");
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
}