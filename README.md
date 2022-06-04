# InvisibleSkinBlocker

This is a PocketMine-MP plugin kicking players with invisible/transparent skins.

## Configuration

The plugin has two behaviour modes: `kick` and `block`.
If set to kick, the plugin will kick players with invisible/transparent skins.
If set to block, the plugin will block players from changing to or joining with invisible/transparent skins.
The config.yml file has pretty descriptive comments for each setting and the default values. Below is a table of
the settings and their descriptions:

| Setting        | Default Value                      | Description                                                                                         |
|----------------|------------------------------------|-----------------------------------------------------------------------------------------------------|
| `percentage`   | 75                                 | If the transparent pixel percentage in a skin is higher than this, player will be kicked.           |
| `behaviour`    |                                    | (`block` or `kick`) Whether to kick the players or just block changing to invisible skins.          |
| `default-skin` | `geometry: ""`<br/>`texture:""`    | File names to custom geometry / texture to set as the fallback skin if behaviour is set to `block`. |
| `message`      | `Invisible skins are not allowed.` | The message to send to the player if they are caught.                                               |

## How it works

It checks for empty pixel percentage in a player's skin texture and kicks them if it is above the configurable value,
with a configurable message. Also supports changing the skin in-game.

## A little warning

Keep in mind, skin textures normally already contain empty pixels. This plugin does not bother with calculating which
pixels of the skin are actually rendered, but runs the percentage checks on the entire texture file. For this reason you
most likely do not want to change the percentage value too low, I found 75 to work pretty well.