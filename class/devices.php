<?php

class Devices {
	private $db;
	private $ssid;
	private $site;
	private $u;
	private $username;
	
	public function __construct(&$db, $ssid, $site, &$u, $username){
		$this->db =& $db;
		$this->ssid = $ssid;
		$this->site = $site;
		$this->u =& $u;
		$this->username = $username;
	}
	
	public function getDevices($uid){
		if ($this->site){
			if ($this->site != $this->u->siteByID($uid)){
				return false;
			}
		}

		$q = $this->db->query("	SELECT
									id as did,
									macaddress as mac,
									SSID as ssid,
									Description as des
								FROM
									ssidmacauth
								WHERE
									UserID = '" . $this->db->real_escape_string($uid) . "'");
		
		$ret = array();
		$row = $q->fetch_assoc();
		while ($row !== null){
			//$row['manu'] = $this->_getMAC($row['macaddress']);
			$ret[] = $row;
			$row = $q->fetch_assoc();
		}
		
		return $ret;
	}
	
	public function _getMAC($mac){
		$file = file_get_contents(dirname(__FILE__) . '/../files/manuf.txt');
		
		$matches = array();
		
		preg_match_all('/^([0-9A-F]{2})\:([0-9A-F]{2})\:([0-9A-F]{2}).*([a-zA-Z0-9]+).*/', $file, $matches);
		
		print_r($matches);
	}

	public function _notifyNew($uid, $mac, $desc){
		if ($this->_countManu($mac) < 5){
			// Send email notification of device addition for new manufacturer.
			$file = file_get_contents(dirname(__FILE__) . '/../files/oui.txt');

			$matches = array();
			preg_match('/([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})/', $mac, $matches);

			$m2 = array();
			preg_match('/(' . strtoupper($matches[1]) . '-' . strtoupper($matches[2]) . '-' . strtoupper($matches[3]) . ')\s+\(hex\)\s+(.*)/', $file, $m2);
			
			$msg = '<b>Device from Unknown Manufacturer Whitelisted</b><br /><br />';
			
			$msg .= '<b>MAC: </b>&nbsp;' . $mac . '<br />';
			$msg .= '<b>Manufacturer: </b>&nbsp;' . $m2[2] . ' ( ' . $m2[1] . ' )<br />';
			$msg .= '<b>Description: </b>&nbsp;' . $desc . '<br />';
			$msg .= '<b>Added By: </b>&nbsp;' . $this->username . '<br />';
			$msg .= '<b>Added For: </b>&nbsp; ' . $this->u->nameByUID($uid) . '<br />'; 

			$this->u->adminNotify('Unknown Device Whitelisted', $msg);
		}
	}

	public function _countManu($mac){
		$matches = array();
		preg_match('/([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})-([a-f0-9]{2})/', $mac, $matches);
		
		$sql = "SELECT COUNT(ID) as num FROM ssidmacauth WHERE macaddress REGEXP '^" . $matches[1] . "-" . $matches[2] . "-" . $matches[3] . "-[a-zA-Z0-9]{2}-[a-zA-Z0-9]{2}-[a-zA-Z0-9]{2}$'";

		$q = $this->db->query($sql);
		if ($q->num_rows > 0){
			$row = $q->fetch_assoc();
			return $row['num'];
		} else {
			return 0;
		}
	}
	
	public function add($uid, $mac, $desc){

		if ($this->site){
                        if ($this->site != $this->u->siteByID($uid)){
                                return false;
                        }
                }

		if ( preg_match( "/^[a-f0-9]{2}\-[a-f0-9]{2}\-[a-f0-9]{2}\-[a-f0-9]{2}\-[a-f0-9]{2}\-[a-f0-9]{2}$/", $mac ) ){
			$this->_notifyNew($uid, $mac, $desc);

			$q = $this->db->query("	INSERT INTO
										ssidmacauth (
											macaddress,
											SSID,
											UserID,
											Description
										)
									VALUES
										(
											'" . $this->db->real_escape_string($mac) . "',
											'" . $this->db->real_escape_string($this->ssid) . "',
											'" . $this->db->real_escape_string($uid) . "',
											'" . $this->db->real_escape_string($desc) . "'
										)");
			if ($this->db->affected_rows > 0){
				return true;
			} else {
				return false;
			}
		} else {
			die('Invalid MAC Address');
		}
	}

	public function uidByID($did){
                $q = $this->db->query(" SELECT UserID as uid FROM ssidmacauth WHERE ID = '" . $this->db->real_escape_string($did) . "'");

                if ($q->num_rows > 0){
                        $row = $q->fetch_assoc();
                        return $row['uid'];
                } else {
                        return false;
                }
	}
	
	public function del($did){

		if ($this->site){
                        if ($this->site != $this->u->siteByID($this->uidByID($did))){
                                return false;
                        }
                }

		$q = $this->db->query("DELETE FROM ssidmacauth WHERE ID = '" . $this->db->real_escape_string($did) . "'");
		
		if ($this->db->affected_rows > 0){
			return true;
		} else {
			return false;
		}
	}
}
