<?php

declare(strict_types=1);

namespace Himbeer\LibSkinExample\Commands;

use Himbeer\LibSkin\LibSkin;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Skin;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class PngSkinCommand extends PluginCommand implements PluginIdentifiableCommand {


	public function __construct(Plugin $owner) {
		parent::__construct("pngskin", $owner);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if (count($args) < 1) {
			return false;
		}
		if (isset($args[2])) {
			$player = $this->getPlugin()->getServer()->getPlayer($args[2]);
			if ($player === null) {
				$sender->sendMessage("Player not found!");
				return true;
			}
		}
		if (!isset($player) && !$sender instanceof Player) {
			$sender->sendMessage("You must provide a player name if you run this command from the console!");
			return true;
		}
		$player = $sender;
		$fileName = $args[1] ?? $player->getLowerCaseName() . ".png";
		switch ($args[0]) {
			case "load":
				try {
					$skinData = LibSkin::imageToSkinDataFromPngPath($this->getPlugin()->getDataFolder() . $fileName);
					$player->setSkin(new Skin("steve", $skinData));
					$player->sendSkin();
					$sender->sendMessage("Skin changed successfully");
				} catch (\Exception $exception) {
					$sender->sendMessage("Error while loading skin: " . $exception->getMessage());
				}
				break;
			case "save":
				$skinData = $player->getSkin()->getSkinData();
				$savePath = $this->getPlugin()->getDataFolder() . $fileName;
				try {
					LibSkin::skinDataToImageSave($skinData, $savePath);
					$sender->sendMessage("Saved as " . $savePath);
				} catch (\Exception $exception) {
					$sender->sendMessage("Error while saving skin: " . $exception->getMessage());
				}

				break;
			default:
				return false;
		}
		return true;
	}
}