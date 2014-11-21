<?php

class Users {
	private $db;
	private $ssid;
	private $site;
	private $notifyList;
	
	public function __construct(&$db, &$ssid, $site, $notifyList){
		$this->db =& $db;
		$this->ssid =& $ssid;
		$this->site = $site;
		$this->notifyList = $notifyList;
	}
	
	public function getUsers(){
		$sql = "					SELECT
									u.id as uid,
									u.username as uname,
									chg.EmailAddress as email,
									UNIX_TIMESTAMP(chg.LastChanged) as lchg,
									u.value as passwd
								FROM
									radcheck u
								JOIN
									userchg chg
										ON chg.UserID = u.id
								WHERE
									u.attribute = 'Cleartext-Password'";
		if ($this->site){
			$sql .= " AND chg.SiteID = " . $this->db->real_escape_string($this->site);
		}

		$sql .= "					ORDER BY
									u.username ASC";
		
		$q = $this->db->query($sql);

		$ret = array();
		$row = $q->fetch_assoc();
		while ($row !== null){
			$ret[] = $row;
			$row = $q->fetch_assoc();
		}
		
		return $ret;
	}
	
	public function uidByName($name){
		$q = $this->db->query(" SELECT id as uid FROM radcheck WHERE username = '" . $this->db->real_escape_string($name) . "' AND attribute = 'Cleartext-Password' ");
		
		if ($q->num_rows > 0){
			$row = $q->fetch_assoc();
			return $row['uid'];
		} else {
			return false;
		}
	}

	public function nameByUID($uid){
                $q = $this->db->query(" SELECT username FROM radcheck WHERE id = '" . $this->db->real_escape_string($uid) . "' AND attribute = 'Cleartext-Password' ");

                if ($q->num_rows > 0){
                        $row = $q->fetch_assoc();
                        return $row['username'];
                } else {
                        return false;
                }
        }

	public function siteByID($id){
		$q = $this->db->query(" SELECT u.id as uid, chg.SiteID as site FROM radcheck u JOIN userchg chg ON chg.UserID = u.id WHERE u.id = '" . $this->db->real_escape_string($id) . "' AND u.attribute = 'Cleartext-Password' ");

                if ($q->num_rows > 0){
                        $row = $q->fetch_assoc();
                        return $row['site'];
                } else {
                        return false;
                }
	}
	
	public function del($uid){
		if ($this->site){
			if ($this->site != $this->siteByID($uid)){
				return false;
			}
		}

		$q = $this->db->query("DELETE FROM radcheck WHERE id = '" . $this->db->real_escape_string($uid) . "'");
		
		if ($this->db->affected_rows > 0){
			return true;
		} else {
			return false;
		}
	}
	
	public function add($uname, $email){
		if ($this->uidByName($uname) > 0){
			return false;
		} else {
			$pw = $this->_genpasswd();
		
			$q = $this->db->query("INSERT INTO radcheck (username, attribute, op, value) VALUES ( '" . $this->db->real_escape_string($uname) . "', 'Cleartext-Password', ':=', '" . $this->db->real_escape_string($pw) . "')");
		
			$uid = $this->db->insert_id;
			
			if ($this->site){
				$sql = "INSERT INTO userchg (UserID, SiteID, EmailAddress, LastChanged) VALUES ( '" . $this->db->real_escape_string($uid) . "', '" . $this->db->real_escape_string($this->site) . "', '" . $this->db->real_escape_string($email) . "', NOW())";
			} else {
				$sql = "INSERT INTO userchg (UserID, EmailAddress, LastChanged) VALUES ( '" . $this->db->real_escape_string($uid) . "', '" . $this->db->real_escape_string($email) . "', NOW())";
			}

			$q2 = $this->db->query($sql);
		
			$message = "<html><head></head><body><b>Your WiFi account has been created!</b><br /><br />Your username is: " . $uname . "<br />Your new password is: " . $pw . "<br /><br />Thanks,<br /><i>Your friendly WiFi bot.</i></body></html>";
		
			$this->_sendEmail($this->_getEmail($uid), $this->ssid . ' - Your Account Has Been Created', $message);
		
			return $uid;
		}
	}
	
	public function passwd($uid){

		if ($this->site){
                        if ($this->site != $this->siteByID($uid)){
                                return false;
                        }
                }

		$pw = $this->_genpasswd();
		$q = $this->db->query("UPDATE radcheck u, userchg chg SET u.value = '" . $this->db->real_escape_string($pw) . "', chg.LastChanged = NOW() WHERE u.id = '" . $this->db->real_escape_string($uid) . "' AND chg.UserID = '" . $this->db->real_escape_string($uid) . "'");
		
		if ($this->db->affected_rows > 0){
			$message = "<html><head></head><body><b>Your WiFi Password has been changed!</b><br /><br />Your new password is: " . $pw . "<br /><br />Thanks,<br /><i>Your friendly WiFi bot.</i></body></html>";
		
			$this->_sendEmail($this->_getEmail($uid), $this->ssid . ' - Your Password Has Been Changed', $message);
			
			return true;
		} else {
			return false;
		}
	}

	public function adminNotify($subject, $msg){
		$message = "<html><head></head><body>" . $msg . "</body></html>";

		foreach ($this->notifyList as $notify){
			$this->_sendEmail($notify, $subject, $message);
		}
	}
	
	private function _getEmail($uid){
		$q = $this->db->query("SELECT EmailAddress FROM userchg WHERE UserID = '" . $this->db->real_escape_string($uid) . "'");
		
		if ($q->num_rows > 0){
			$row = $q->fetch_assoc();
			return $row['EmailAddress'];
		} else {
			return false;
		}
	}
	
	private function _sendEmail($email, $subject, $message){
		$to = $email;

		$subject = 'WiFi Bot - ' . $subject;
		
		$headers = "From: Infitialis WiFi Bot <wifi-bot@infitialis.com>\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		
		mail($to, $subject, $message, $headers);
	}
	
	private function _genpasswd(){
		return rString(10);
	}
}

function rString($numc = 25){
	$salt_chars		=	array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'Z', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
		
	$code = "";
	for ($i = 0; $i < $numc; $i++){
		$code .= $salt_chars[array_rand($salt_chars)];
	}
		
	return $code;
}
