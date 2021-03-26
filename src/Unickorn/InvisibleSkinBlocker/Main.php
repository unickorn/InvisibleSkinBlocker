<?php
declare(strict_types=1);

namespace Unickorn\InvisibleSkinBlocker;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use function ord;
use function strlen;

class Main extends PluginBase implements Listener{

	public const SKIN_WIDTH_MAP = [
		64 * 32 * 4   => 64,
		64 * 64 * 4   => 64,
		128 * 128 * 4 => 128
	];
	public const SKIN_HEIGHT_MAP = [
		64 * 32 * 4   => 32,
		64 * 64 * 4   => 64,
		128 * 128 * 4 => 128
	];
	/** @var int */
	private $percentage;
	/** @var string */
	private $message;

	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->percentage = $this->getConfig()->get("percentage", 75);
		$this->message = $this->getConfig()->get("message", "Invisible skins are not allowed.");
	}

	public function checkSkin(Player $player, ?string $skinData = null): void{
		if($skinData === null){
			$skinData = $player->getSkin()->getSkinData();
		}
		$size = strlen($skinData);
		$width = self::SKIN_WIDTH_MAP[$size];
		$height = self::SKIN_HEIGHT_MAP[$size];
		$pos = -1;
		$pixelsNeeded = (int) ((100 - $this->percentage) / 100 * ($width * $height)); // visible pixels needed
		for($y = 0; $y < $height; $y++){
			for($x = 0; $x < $width; $x++){
				if(ord($skinData[$pos += 4]) === 255){
					if(--$pixelsNeeded === 0){
						return;
					}
				}
			}
		}
		$player->kick($this->message, false);
	}

	public function onJoin(PlayerJoinEvent $event): void{
		$this->checkSkin($event->getPlayer());
	}

	public function onChangeSkin(PlayerChangeSkinEvent $event): void{
		$this->checkSkin($event->getPlayer(), $event->getNewSkin()->getSkinData());
	}
}