<?php

declare(strict_types=1);

namespace Himbeer\LibSkin;

use Exception;

final class SkinConverter {
	/**
	 * @param string $skinData
	 * @return resource GD image resource
	 * @throws Exception
	 */
	public static function skinDataToImage(string $skinData) {
		$size = strlen($skinData);
		LibSkin::validateSize($size);
		$width = LibSkin::SKIN_WIDTH_MAP[$size];
		$height = LibSkin::SKIN_HEIGHT_MAP[$size];
		$skinPos = 0;
		$image = imagecreatetruecolor($width, $height);
		if ($image === false) {
			throw new Exception("Couldn't create image");
		}
		// Make background transparent
		imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				$r = ord($skinData[$skinPos]);
				$skinPos++;
				$g = ord($skinData[$skinPos]);
				$skinPos++;
				$b = ord($skinData[$skinPos]);
				$skinPos++;
				$a = 127 - intdiv(ord($skinData[$skinPos]), 2);
				$skinPos++;
				$col = imagecolorallocatealpha($image, $r, $g, $b, $a);
				imagesetpixel($image, $x, $y, $col);
			}
		}
		imagesavealpha($image, true);
		return $image;
	}

	/**
	 * @param resource $image GD image resource
	 * @param bool $destroyImage Whether to call imagedestroy on the image resource after finishing
	 * @return string Minecraft Skin Data
	 * @throws Exception
	 */
	public static function imageToSkinData($image, bool $destroyImage): string {
		if (get_resource_type($image) !== "gd") {
			throw new Exception("1st parameter must be a GD image resource");
		}
		$size = imagesx($image) * imagesy($image) * 4;
		LibSkin::validateSize($size);

		$width = LibSkin::SKIN_WIDTH_MAP[$size];
		$height = LibSkin::SKIN_HEIGHT_MAP[$size];

		// TODO: non-true-color support

		$skinData = "";
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				// https://www.php.net/manual/en/function.imagecolorat.php
				$rgba = imagecolorat($image, $x, $y);
				$a = (127 - (($rgba >> 24) & 0x7F)) * 2;
				$r = ($rgba >> 16) & 0xff;
				$g = ($rgba >> 8) & 0xff;
				$b = $rgba & 0xff;
				$skinData .= chr($r) . chr($g) . chr($b) . chr($a);
			}
		}
		if ($destroyImage) imagedestroy($image);
		return $skinData;
	}

	/**
	 * @param string $skinData Minecraft Skin Data
	 * @param string $savePath Path where skin PNG is saved
	 * @throws Exception
	 */
	public static function skinDataToImageSave(string $skinData, string $savePath) {
		$image = self::skinDataToImage($skinData);
		imagepng($image, $savePath);
		imagedestroy($image);
	}

	/**
	 * @param string $imagePath Path to skin PNG
	 * @return string Minecraft Skin Data
	 * @throws Exception
	 */
	public static function imageToSkinDataFromPngPath(string $imagePath): string {
		$image = imagecreatefrompng($imagePath);
		if ($image === false) {
			throw new Exception("Couldn't load image");
		}
		return self::imageToSkinData($image, true);
	}
}