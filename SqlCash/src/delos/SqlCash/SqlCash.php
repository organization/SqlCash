<?php

namespace delos\SqlCash;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SqlCash extends PluginBase implements Listener {
	private $db;
	private $table;
	private static $instance;
	public function onLoad() {
		self::$instance = $this;
	}
	public static function getInstance() {
		return self::$instance;
	}
	public function onEnable() {
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		$this->db = new MySqlDB ( new Config ( $this->getDataFolder () . "sqlinfo.yml", Config::YAML, [ 
				"host" => "yourhost.kr",
				"username" => "root",
				"password" => "yourpassword",
				"database" => "yourdatabase",
				"port" => 3306 
		] ), $this );
		$this->table = "cash";
		$this->db->createTable ( $this->table ); // 데이터 테이블 생성
	}
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer ();
		$name = $player->getName ();
		if (! $this->db->keyExists ( $this->table, $name )) {
			$this->db->putKey ( $this->table, $name, 0 );
			$this->getServer ()->getLogger ()->debug ( $name . "님의 데이터를 찾지 못했습니다. 데이터를 생성합니다." );
		}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$command = $command->getName ();
		if ($command === "지불") {
			if (! $sender instanceof Player) {
				$sender->sendMessage ( TextFormat::RED . "콘솔에서는 사용하실 수 없습니다." );
				return true;
			}
			if (! isset ( $args [0] ) || ! isset ( $args [1] ) || ! is_numeric ( $args [1] )) {
				$sender->sendMessage(TextFormat::BLUE . "사용법: /지불 <플레이어명> <숫자>");
				return true;
			}
			if(! $this->db->keyExists($this->table, $args[0])){
				$sender->sendMessage(TextFormat::RED . "$args[0]님의 데이터가 없습니다.");
				return true;
			}
			if($args[1] > $this->getCash($sender->getName())){
				$sender->sendMessage(TextFormat::RED . "캐쉬가 부족합니다.");
				return true;
			}
			$this->increaseCash($args[0], $args[1]);
			$sender->sendMessage(TextFormat::GREEN . "$args[0]님께 $args[1]캐쉬를 지불하셨습니다.");
			$this->decreaseCash($sender->getName(), $args[1]);
			if(($target = $this->getServer()->getPlayer($args[0])) instanceof Player){
				$target->sendMessage(TextFormat::GREEN . $sender->getName() . "님이 $args[1]캐쉬를 지불하셨습니다.");
			}
			return true;
		}
		if ($command === "내캐쉬") {
			if (! $sender instanceof Player) {
				$sender->sendMessage ( TextFormat::RED . "콘솔에서는 사용하실 수 없습니다." );
				return true;
			}
			$sender->sendMessage ( TextFormat::YELLOW . "당신의 캐쉬 : " . $this->getCash ( $sender->getName () ) );
			return true;
		}
		if ($command === "캐쉬") {
			if (! isset ( $args [0] )) {
				$sender->sendMessage ( TextFormat::BLUE . "사용법: /캐쉬 <주기|뺏기|설정|보기> <닉네임> [숫자]" );
				return true;
			}
			switch ($args [0]) {
				case "주기" :
					if (! isset ( $args [1] ) || ! isset ( $args [2] ) || ! is_numeric ( $args [2] )) {
						$sender->sendMessage ( TextFormat::BLUE . "사용법: /캐쉬 <주기|뺏기|설정|보기> <닉네임> [숫자]" );
						return true;
					}
					if (! $this->db->keyExists ( $this->table, $args [1] )) {
						$sender->sendMessage ( TextFormat::RED . "$args[1]님의 데이터가 존재하지 않습니다." );
						return true;
					}
					$this->increaseCash ( $args [1], $args [2] );
					$sender->sendMessage(TextFormat::GREEN . "$args[1]님께 $args[2]만큼의 캐쉬를 주셨습니다.");
					if(($target = $this->getServer()->getPlayer($args[1])) instanceof Player){
						$target->sendMessage(TextFormat::GREEN . $sender->getName() . "님이 $args[2]만큼의 캐쉬를 주셨습니다.");
					}
					break;
				case "뺏기" :
					if (! isset ( $args [1] ) || ! isset ( $args [2] ) || ! is_numeric ( $args [2] )) {
						$sender->sendMessage ( TextFormat::BLUE . "사용법: /캐쉬 <주기|뺏기|설정|보기> <닉네임> [숫자]" );
						return true;
					}
					if (! $this->db->keyExists ( $this->table, $args [1] )) {
						$sender->sendMessage ( TextFormat::RED . "$args[1]님의 데이터가 존재하지 않습니다." );
						return true;
					}
					$this->decreaseCash ( $args [1], $args [2] );
					$sender->sendMessage(TextFormat::GREEN . "$args[1]님께 $args[2]만큼의 캐쉬를 빼앗으셨습니다.");
					if(($target = $this->getServer()->getPlayer($args[1])) instanceof Player){
						$target->sendMessage(TextFormat::GREEN . $sender->getName() . "님이 $args[2]만큼의 캐쉬를 빼앗으셨습니다.");
					}
					break;
				case "설정" :
					if (! isset ( $args [1] ) || ! isset ( $args [2] ) || ! is_numeric ( $args [2] )) {
						$sender->sendMessage ( TextFormat::BLUE . "사용법: /캐쉬 <주기|뺏기|설정|보기> <닉네임> [숫자]" );
						return true;
					}
					if (! $this->db->keyExists ( $this->table, $args [1] )) {
						$sender->sendMessage ( TextFormat::RED . "$args[1]님의 데이터가 존재하지 않습니다." );
						return true;
					}
					$this->setCash ( $args [1], $args [2] );
					$sender->sendMessage(TextFormat::GREEN . "$args[1]님의 캐쉬를 $args[2]로 설정하셨습니다.");
					if(($target = $this->getServer()->getPlayer($args[1])) instanceof Player){
						$target->sendMessage(TextFormat::GREEN . $sender->getName() . "님이 캐쉬를 $args[2]로 설정하셨습니다.");
					}
					break;
				case "보기" :
					if (! isset ( $args [1] )) {
						$sender->sendMessage ( TextFormat::RED . "사용법: /캐쉬 <보기> <닉네임>" );
						return true;
					}
					if (! $this->db->keyExists ( $this->table, $args [1] )) {
						$sender->sendMessage ( TextFormat::RED . "$args[1]님의 데이터가 존재하지 않습니다." );
						return true;
					}
					$sender->sendMessage ( TextFormat::YELLOW . "$args[1]님의 캐쉬: " . $this->getCash ( $args [1] ) );
					break;
				default :
					$sender->sendMessage ( TextFormat::BLUE . "사용법: /캐쉬 <주기|뺏기|설정|보기> <닉네임> [숫자]" );
					break;
			}
		}
	}
	public function onDisable() {
		$this->db->disable ();
	}
	public function getCash(string $name) {
		return $this->db->getValueByKey ( $this->table, $name );
	}
	public function setCash(string $name, float $value) {
		if($value < 0){
			$value = 0;
		}
		$this->db->setKeyValue ( $this->table, $name, $value );
	}
	public function decreaseCash(string $name, float $value) {
		$cash = $this->getCash ( $name );
		$cash = $cash - $value;
		$this->setCash ( $name, $cash );
	}
	public function increaseCash(string $name, float $value) {
		$cash = $this->getCash ( $name );
		$cash = $cash + $value;
		$this->setCash ( $name, $cash );
	}
}