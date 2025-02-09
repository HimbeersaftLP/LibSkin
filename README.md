# LibSkin

A [Virion](https://github.com/poggit/support/blob/master/virion.md) for working with player skins.

[![Poggit Build status for the virion](https://poggit.pmmp.io/ci.shield/HimbeersaftLP/LibSkin/~)](https://poggit.pmmp.io/ci/HimbeersaftLP/LibSkin/LibSkin)
[![Join the Discord Server](https://img.shields.io/discord/252874887113342976?logo=discord)](https://www.himbeer.me/discord)
[![Packagist](https://img.shields.io/packagist/v/himbeer/libskin)](https://packagist.org/packages/himbeer/libskin)

See LibSkinExample for a usage example.

## Features:

- Convert skin data to PNG (and metadata, if allowed, to JSON)
- Convert PNG to skin data
- Asynchronously Download skins of Minecraft: Java Edition players

# SkinThief (LibSkinExample) <img alt="Plugin Logo/Icon" src="https://raw.githubusercontent.com/HimbeersaftLP/LibSkin/master/SkinPluginExample/icon.png" height="45">

[![Poggit Build status for the example plugin](https://poggit.pmmp.io/ci.shield/HimbeersaftLP/LibSkin/SkinThief)](https://poggit.pmmp.io/ci/HimbeersaftLP/LibSkin/SkinThief)
[![Poggit Release status for the example plugin](https://poggit.pmmp.io/shield.state/SkinThief)](https://poggit.pmmp.io/p/SkinThief)
[![Join the Discord Server](https://img.shields.io/discord/252874887113342976?logo=discord)](https://www.himbeer.me/discord)

## Description

- A [PocketMine](https://pmmp.io/) plugin for stealing other player's skins
- Can get skins from online players, offline players, files and Minecraft: Java Edition players

## Installation instruction

1. Put phar from Poggit into `plugins` folder
2. Start server

## Commands

The command of this plugin is `/skin`. It has the following subcommands:

- `/skin load [file name] [target player]`
    - Description: Load skin from a png file and metadata from a json file in `plugin_data/SkinThief/skins`
    - Note: If no file name is given, the player's name is used
- `/skin save [file name] [target player]`
    - Description: Save skin to a png file and metadata to a json file in `plugin_data/SkinThief/skins`
    - Note: If no file name is given, the player's name is used
- `/skin steal <player to steal from> [target player]`
    - Description: Steal the skin of another player (online or offline)
- `/skin mcje <player to steal from> [target player]`
    - Description: Steal the skin of a Minecraft: Java Edition player

## Permissions

- `skinthief.command`
    - Description: Allow access to the `/skin` base command (subcommands need seperate permissions!)
    - Default: op
- `skinthief.command.load`
    - Description: Allow access to `/skin load`
    - Default: op
- `skinthief.command.save`
    - Description: Allow access to `/skin save`
    - Default: op
- `skinthief.command.steal`
    - Description: Allow access to `/skin steal`
    - Default: op
- `skinthief.command.mcje`
    - Description: Allow access to `/skin mcje`
    - Default: op
- `skinthief.command.all`
    - Description: Allow access to all `/skin` subcommands
    - Default: op
- `skinthief.otherplayer`
    - Description: Allow changing another player's skin (using the `target player` argument)
    - Default: op
- `skinthief.anyfilename`
    - Description: Allow picking any file name when saving or loading a skin (otherwise only the player's name is
      allowed)
    - Default: op
- `skinthief.metadata`
    - Description: Allow loading and saving skin metadata (such as geometry) as well.
    - Default: op

## Additional Information

Icon credits: https://pixabay.com/vectors/moneybag-hand-coins-symbol-icon-400290/