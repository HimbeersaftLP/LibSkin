<?php

declare(strict_types=1);

namespace Himbeer\SkinPluginExample\Commands;

use Exception;
use Himbeer\LibSkin\SkinConverter;
use Himbeer\LibSkin\SkinGatherer;
use Himbeer\SkinPluginExample\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

class SkinCommand extends Command implements PluginOwned {
	private Main $owner;

	public function __construct(Main $owner) {
		parent::__construct("skin", "Skin Utilities", "/skin <load|save|steal|mcje> <skin source player> [skin destination player]");
		$this->owner = $owner;
	}

	private static function changeSkin(Player $player, string $skinData) {
		$player->setSkin(new Skin($player->getSkin()->getSkinId(), $skinData));
		$player->sendSkin();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if (count($args) < 1) {
			throw new InvalidCommandSyntaxException();
		}
		if (isset($args[2])) {
			$player = $this->getOwningPlugin()->getServer()->getPlayerByPrefix($args[2]);
			if ($player === null) {
				$sender->sendMessage("Player not found!");
				return true;
			}
		}
		if (!isset($player) && !$sender instanceof Player) {
			$sender->sendMessage("You must provide a player name if you run this command from the console!");
			return true;
		}
		/**
		 * @var Player
		 */
		$player = $player ?? $sender;
		$fileName = $args[1] ?? strtolower($player->getName()) . ".png";
		switch ($args[0]) {
			case "load":
				try {
					$skinData = SkinConverter::imageToSkinDataFromPngPath($this->getOwningPlugin()->getDataFolder() . $fileName);
					self::changeSkin($player, $skinData);
					$sender->sendMessage("Skin changed successfully");
				} catch (Exception $exception) {
					$sender->sendMessage("Error while loading skin: " . $exception->getMessage());
				}
				break;
			case "save":
				$skinData = $player->getSkin()->getSkinData();
				$savePath = $this->getOwningPlugin()->getDataFolder() . $fileName;
				try {
					SkinConverter::skinDataToImageSave($skinData, $savePath);
					$sender->sendMessage("Saved as " . $savePath);
				} catch (Exception $exception) {
					$sender->sendMessage("Error while saving skin: " . $exception->getMessage());
				}
				break;
			case "steal":
				$skinData = SkinGatherer::getSkinDataFromOfflinePlayer($fileName);
				if ($skinData === null) {
					$sender->sendMessage("Player not found or doesn't have a skin saved.");
				} else {
					self::changeSkin($player, $skinData);
					$sender->sendMessage("Skin changed successfully");
				}
				break;
			case "mcje":
				if (!isset($args[1])) {
					$sender->sendMessage("You must provide a player name!");
					return true;
				}
				try {
					SkinGatherer::getJavaEditionSkinData($fileName, function ($skinData, $state) use ($sender, $player) {
						if ($skinData === null) {
							switch ($state) {
								case SkinGatherer::MCJE_STATE_ERR_UNKNOWN:
									$sender->sendMessage("An unknown error occurred");
									break;
								case SkinGatherer::MCJE_STATE_ERR_PLAYER_NOT_FOUND:
									$sender->sendMessage("Player not found");
									break;
								case SkinGatherer::MCJE_STATE_ERR_TOO_MANY_REQUESTS:
									$sender->sendMessage("Error: Mojang API rate limit reached!");
									break;
							}
						} else {
							self::changeSkin($player, $skinData);
							$sender->sendMessage("Skin changed successfully");
						}
					});
				} catch (Exception $exception) {
					$sender->sendMessage("Error while loading skin: " . $exception->getMessage());
				}
				break;
			default:
				throw new InvalidCommandSyntaxException();
		}
		return true;
	}

	public function getOwningPlugin() : Main{
		return $this->owner;
	}
}