<?php
error_reporting(E_ALL^E_NOTICE);

define('STRUCTURE', __DIR__ . '/../../data/structure.txt');
define('MAPS_SPELLS', __DIR__ . '/../../data/maps_spells.php');
define('MAPS_CHARS', __DIR__ . '/../../data/maps_chars.txt');
define('MAPS_SYMBOLS', __DIR__ . '/../../data/maps_symbols.txt');

class Encoder {

	private $_missed = array();
	private $_duplicated = array();
	private $_structure = array();
	private $_chars_map = array();
	private $_spells_map = array();
	private static $_INSTANCE;

	public static function ins() {
		if (!isset(self::$_INSTANCE)) {
			self::$_INSTANCE = new Encoder();
		}
		return self::$_INSTANCE;
	}

	public function __construct() {
		$this->_loadSpellsMap();
		$this->_loadSymbolsMap();
		$this->_loadCharsMap();
		$this->_loadStructure();
	}

	public function encodeChar($char) {

		if (!isset($this->_structure[$char])) {
			return false;
		}
		$code = '';
		$parts = $this->_structure[$char];
		foreach ($parts as $part) {
			if (isset($this->_chars_map[$part])) {
				$code .= $this->_chars_map[$part];
			} else {
				echo 'miss map for symbol [' . $part . '] in char [' . $char . '].' . PHP_EOL;
				$this->_missed[$part] = 1;
				return false;				
			}
		}
		return trim($code);
	}

	public function encodeSpell($spell) {
		if (isset($this->_spells_map[$spell])) {
			return $this->_spells_map[$spell];
		} else {
			echo 'miss map for spell [' . $spell . '].' . PHP_EOL;
			return false;				
		}
	}

	public function encodeSpells($spells) {
		$code = '';
		$parts = explode(' ', $spells);
		$length = count($parts);
		switch ($length) {
			case 2:  return $this->_encode2spells($parts);
			case 3:  return $this->_encode3spells($parts);
			case 4:  return $this->_encode4spells($parts);
			default: return $this->_encodeNspells($parts);
		}
	}

	public function missed() {
		return array_keys($this->_missed);
	}

	public function duplicated() {
		return array_keys($this->_duplicated);
	}

	private function _encode2spells($spells) {
		return $this->encodeSpell($spells[0])
			 . $this->encodeSpell($spells[1]);
	}

	private function _encode3spells($spells) {
		return $this->encodeSpell($spells[0])
			 . $this->_encodeShortSpell($spells[1])
			 . $this->_encodeShortSpell($spells[2]);
	}

	private function _encode4spells($spells) {
		return $this->_encodeShortSpell($spells[0])
			 . $this->_encodeShortSpell($spells[1])
			 . $this->_encodeShortSpell($spells[2])
			 . $this->_encodeShortSpell($spells[3]);
	}

	private function _encodeNspells($spells) {
		return $this->_encodeShortSpell($spells[0])
			 . $this->_encodeShortSpell($spells[1])
			 . $this->_encodeShortSpell($spells[2])
			 . $this->_encodeShortSpell(end($spells));
	}

	private function _encodeShortSpell($spell) {
		$code = $this->encodeSpell($spell);
		return $code[0] == ';' ? $code[1] : $code[0];
	}

	public function getCharCode($char) {
		if (isset($this->_chars_map[$char])) {
			return $this->_chars_map[$char];
		} else {
			echo 'miss map for char [' . $char . '].' . PHP_EOL;
			$this->_missed[$char] = 1;
			return false;				
		}
	}

	public function setCharCode($char, $code) {
		if (isset($this->_chars_map[$char])) {
			echo 'duplicate code [' . $code . '] for char [' . $part . '].' . PHP_EOL;
			$this->_duplicated[$char] = 1;
		} else {
			$this->_chars_map[$char] = $code;	
		}
	}

	private function _loadSpellsMap() {
		$this->_spells_map = require MAPS_SPELLS;
	}

	private function _loadSymbolsMap() {
		$encoder = $this;
		$scanner = new Scanner(MAPS_SYMBOLS);
		$scanner->scan(function($line)use($encoder){
			list($code, $parts) = explode("\t", trim($line));
			$count = mb_strlen($parts, 'utf-8');
			for ($i = 0; $i < $count; $i++) {
				$symbol = mb_substr($parts, $i, 1, 'utf-8');
				$encoder->setCharCode($symbol, $code);
			}
		});
	}

	private function _loadCharsMap() {
		$encoder = $this;
		$scanner = new Scanner(MAPS_CHARS);
		$scanner->scan(function($line)use(&$symbols, $encoder){
			list($symbol, $chars) = explode("\t", trim($line));
			if (empty($chars)) return;
			if ($code = $encoder->getCharCode($symbol)) {
				$count = mb_strlen($chars, 'utf-8');
				for ($i = 0; $i < $count; $i++) {
					$char = mb_substr($chars, $i, 1, 'utf-8');
					$encoder->setCharCode($char, $code);
				}
			}
		});
	}

	private function _loadStructure() {
		$_structure = array();
		$scanner = new Scanner(STRUCTURE);
		$scanner->scan(function($line)use(&$_structure){
			list($char, $parts) = explode("\t", trim($line));
			$count = mb_strlen($parts, 'utf-8');
			$_structure[$char] = array();
			for ($i = 0; $i < $count; $i++) {
				$part = mb_substr($parts, $i, 1, 'utf-8');
				$_structure[$char][] = $part;
			}
		});
		$this->_structure = $_structure;
	}
}