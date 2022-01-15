<?php

declare(strict_types=1);

namespace Himbeer\SkinThief;

use Himbeer\SkinThief\Commands\SkinCommand;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {
	private string $skinDir;

	public function onEnable() : void {
		$this->getServer()->getCommandMap()->register("SkinThief", new SkinCommand($this));
		$this->skinDir = $this->getDataFolder() . "skins/";
		$this->createSkinStorageFolderIfNotExists();
	}

	public function createSkinStorageFolderIfNotExists() : void {
		if (!is_dir($this->skinDir)) {
			mkdir($this->skinDir);
		}
	}

	public function getSkinStorageLocation(string $fileName) : string {
		return $this->skinDir . $fileName;
	}

	public function escapeFileName(string $fileName) : string {
		return preg_replace('/[^A-Za-z0-9_\-]/', '_', $fileName);
	}
}
