<?php

declare(strict_types=1);

namespace Himbeer\LibSkinExample;

use Himbeer\LibSkinExample\Commands\PngSkinCommand;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

	public function onEnable(): void {
		$this->getServer()->getCommandMap()->register("LibSkinExample", new PngSkinCommand($this));
	}
}
