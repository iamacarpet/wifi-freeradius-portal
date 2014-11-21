<?php

class Site {
	private $susers;
	private $digestdata;
	
	public function __construct($susers, $ddata){
		$this->susers = $susers;
		$this->digestdata = $this->http_digest_parse($ddata);
	}

	public function http_digest_parse($txt){
		// protect against missing data
		$needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
		$data = array();
		$keys = implode('|', array_keys($needed_parts));

		preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

		foreach ($matches as $m) {
			$data[$m[1]] = $m[3] ? $m[3] : $m[4];
			unset($needed_parts[$m[1]]);
		}

		return $needed_parts ? false : $data;
	}

	public function getSite(){
		if (isset($this->digestdata['username'])){
			if (isset($this->susers[$this->digestdata['username']])){
				return $this->susers[$this->digestdata['username']];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function getUsername(){
		return $this->digestdata['username'];
	}
}
