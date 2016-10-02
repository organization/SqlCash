<?php
namespace delos\SqlCash;

use pocketmine\utils\Config;
class MySqlDB {
	private $conf;
	private $db;
	private $plugin;
	public function __construct(Config $config, SqlCash $plugin){
		$this->conf = $config;
		$this->plugin = $plugin;
		$this->db = new \mysqli($this->conf->get("host"), $this->conf->get("username"), $this->conf->get("password"), $this->conf->get("database"), $this->conf->get("port"));
		if ($this->db->errno) {
			$this->plugin->getServer ()->getLogger ()->error ( "SQL서버 연결 실패 : " . $this->db->error );
			$this->plugin->getServer ()->getPluginManager ()->disablePlugin ( $this->plugin );
			return true;
		}
		$this->plugin->getServer()->getLogger()->notice("SQL서버 연결 성공");
	}
	public function createTable($tableName) {
		$this->db->query ( "CREATE TABLE IF NOT EXISTS $tableName (k VARCHAR(255) PRIMARY KEY,v FLOAT)" );
	}
	public function keyExists($tableName, $key) {
		$result = $this->db->query ( "SELECT * FROM $tableName WHERE k = '" . $key . "'" );
		$num = 0;
		if ($result instanceof \mysqli_result) {
			$num = $result->num_rows;
		}
		return $num > 0 ? true : false;
	}
	public function getValueByKey($tableName, $key) {
		$result = $this->db->query ( "SELECT * FROM $tableName WHERE k = '" . $key . "'" );
		$arr = [ ];
		if ($result instanceof \mysqli_result) {
			$arr = $result->fetch_assoc ();
		} else {
			$arr ["v"] = 0;
		}
		return $arr ["v"];
	}
	public function setKeyValue($tableName, $key, $value) {
		$database = $this->conf->get ( "database" );
		$query = "UPDATE $tableName SET v = '$value' WHERE k = '$key'";
		$this->db->query ( $query );
	}
	public function deleteKey($tableName, $key) {
		$this->db->query ( "DELETE FROM $tableName WHERE k='" . $key . "''" );
		$this->getServer ()->getLogger ()->info ( $key . " 가 삭제되었습니다." );
	}
	public function putKey($tableName, $key, float $value) {
		$this->db->query ( "INSERT INTO $tableName(k, v)VALUES('" . $key . "', '" . $value . "')" );
	}
	public function disable(){
		$this->db->close();
	}
}