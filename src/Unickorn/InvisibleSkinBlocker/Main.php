<?php
declare(strict_types=1);

namespace Unickorn\InvisibleSkinBlocker;

use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use Ramsey\Uuid\Uuid;
use function chr;
use function ord;
use function strlen;

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
	private string $message;
	private string $behaviour;
	private ?Skin $defaultSkin = null;

	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$c = $this->getConfig();
		$this->percentage = $c->get("percentage", 75);
		$this->message = $c->get("message", "Invisible skins are not allowed.");
		$this->behaviour = $c->get("behaviour", "kick");
		if ($defaultSkin = $c->get("default-skin")) {
			$this->defaultSkin = $this->loadSkin($defaultSkin["geometry"], $defaultSkin["texture"]);
		}
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
						return false;
					}
				}
			}
		}
		return true;
	}

	private function loadSkin(string $geometry, string $texture) : ?Skin {
		$geometryData = "";
		if ($geometry !== "") {
			try {
				$customGeometry = json_decode(file_get_contents($this->getDataFolder() . $geometry), true, 512, JSON_THROW_ON_ERROR);
			} catch (\Exception $e) {
				$this->getLogger()->error("Could not load custom geometry file, reverting back to random bytes: " . $e->getMessage());
				return null;
			}
			$geometry = $customGeometry["minecraft:geometry"][0]["description"]["identifier"];
		}

		if ($texture !== "") {
			$f = $this->getDataFolder() . $texture;
			$image = imagecreatefrompng($f);
			[$width, $height] = getimagesize($f);
			$bytes = '';
			for ($y = 0; $y < $height; ++$y) {
				for ($x = 0; $x < $width; ++$x) {
					$color = @imagecolorsforindex($image, @imagecolorat($image, $x, $y));
					$bytes .= chr($color['red']) . chr($color['green']) . chr($color['blue']) . chr((($color['alpha'] << 1) ^ 0xff) - 1);
				}
			}
			imagedestroy($image);
			return new Skin(Uuid::uuid4()->toString(), $bytes, "", $geometry, $geometryData);
		}

		return null;
	}

	public function randomSkin() : ?Skin {
		$bytes = '';
		for ($i = 0; $i < 2048; $i++) {
			$bytes .= chr(mt_rand(0, 255)) . chr(mt_rand(0, 255)) . chr(mt_rand(0, 255)) . chr(255);
		}
		return new Skin(Uuid::uuid4()->toString(), $bytes, "", "geometry.humanoid.custom", "");
	}

	public function onLogin(PlayerLoginEvent $event) : void {
		$player = $event->getPlayer();
		if ($this->checkSkin($player)) {
			if ($this->behaviour === "kick") {
				$event->cancel();
				$player->kick($this->message);
			} else {
				$player->setSkin($this->defaultSkin ?? $this->randomSkin());
			}
		}
	}

	public function onChangeSkin(PlayerChangeSkinEvent $event) : void {
		$player = $event->getPlayer();
		if ($this->checkSkin($player, $event->getNewSkin()->getSkinData())) {
			if ($this->behaviour === "kick") {
				$event->cancel();
				$player->kick($this->message);
			} else {
				$player->setSkin($this->defaultSkin ?? $this->randomSkin());
				$player->sendMessage($this->message);
			}
		}
	}
}