<?php
declare(strict_types=1);

namespace Unickorn\InvisibleSkinBlocker;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use function ord;
use function strlen;
use pocketmine\entity\Skin;

class Main extends PluginBase implements Listener
{
	public const SKIN_WIDTH_MAP = [
		64 * 32 * 4   => 64,
		64 * 64 * 4   => 64,
		128 * 128 * 4 => 128,
	];
	public const SKIN_HEIGHT_MAP = [
		64 * 32 * 4   => 32,
		64 * 64 * 4   => 64,
		128 * 128 * 4 => 128,
	];

	private int $percentage;

	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->percentage = $this->getConfig()->get("percentage", 75);
	}

	public function checkSkin(Player $player, ?string $skinData = null) : bool {
		$skinData ??= $player->getSkin()->getSkinData();
		$size = strlen($skinData);
		$width = self::SKIN_WIDTH_MAP[$size];
		$height = self::SKIN_HEIGHT_MAP[$size];
		$pos = -1;
		$pixelsNeeded = (int)((100 - $this->percentage) / 100 * ($width * $height)); // visible pixels needed
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				if (ord($skinData[$pos += 4]) === 255) {
					if (--$pixelsNeeded === 0) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		if($this->checkSkin($event->getPlayer())){
			$player->setSkin(new Skin("Standard_Custom", str_repeat(random_bytes(3) . "\xff", 4096)));
		}
	}

	public function onChangeSkin(PlayerChangeSkinEvent $event) : void {
		if($this->checkSkin($event->getPlayer(), $event->getNewSkin()->getSkinData())){
			$event->cancel();
		}
	}
}