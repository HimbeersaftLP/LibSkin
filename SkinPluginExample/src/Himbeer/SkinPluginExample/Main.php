<?php

declare(strict_types=1);

namespace Himbeer\SkinPluginExample;

use Himbeer\SkinPluginExample\Commands\PngSkinCommand;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

	public function onEnable(): void {
		$this->getServer()->getCommandMap()->register("SkinPluginExample", new PngSkinCommand($this));
	}
}
