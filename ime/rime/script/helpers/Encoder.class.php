<?php
error_reporting(E_ALL^E_NOTICE);

define('STRUCTURE_TABLE', __DIR__ . '/../../code/structure.txt');
define('SYMBOLS_TABLE', __DIR__ . '/../../code/symbols.txt');
define('MAPS_TABLE', __DIR__ . '/../../code/maps.txt');

class Encoder {

	private $_missed = array();
	private $_duplicated = array();
	private $_structure = array();
	private $_maps = array();
	private static $_INSTANCE;

	public static function ins() {
		if (!isset(self::$_INSTANCE)) {
			self::$_INSTANCE = new Encoder();
		}
		return self::$_INSTANCE;
	}

	public function __construct() {
		$this->_loadMaps();
		$this->_loadSymbols();
		$this->_loadStructure();
	}

	public function encode($char, $spell) {

		if (!isset($this->_structure[$char])) {
			return false;
		}
			
		$code = '';
		$parts = $this->_structure[$char];
		foreach ($parts as $part) {
			if (isset($this->_maps[$part])) {
				$code .= $this->_maps[$part];
			} else {
				echo 'miss map for symbol [' . $part . '] in char [' . $char . '].' . PHP_EOL;
				$this->_missed[$part] = 1;
				return false;				
			}
		}
		return $spell . trim($code);
	}

	public function maps() {
		return $this->_maps;
	}

	public function missed() {
		return array_keys($this->_missed);
	}

	public function duplicated() {
		return array_keys($this->_duplicated);
	}

	private function _loadMaps() {
		$scanner = new Scanner(MAPS_TABLE);
		$scanner->scan(function($line){
			list($code, $parts) = explode("\t", trim($line));
			$count = mb_strlen($parts, 'utf-8');
			for ($i = 0; $i < $count; $i++) {
				$symbol = mb_substr($parts, $i, 1, 'utf-8');
				$this->_setCode($symbol, $code);
			}
		});
	}

	private function _getCode($symbol) {
		if (isset($this->_maps[$symbol])) {
			return $this->_maps[$symbol];
		} else {
			echo 'miss map for symbol [' . $symbol . '].' . PHP_EOL;
			$this->_missed[$symbol] = 1;
			return false;				
		}
	}

	private function _setCode($symbol, $code) {
		if (isset($this->_maps[$symbol])) {
			echo 'duplicate code [' . $code . '] for symbol [' . $part . '].' . PHP_EOL;
			$this->_duplicated[$symbol] = 1;
		} else {
			$this->_maps[$symbol] = $code;	
		}
	}

	private function _loadSymbols() {
		$scanner = new Scanner(SYMBOLS_TABLE);
		$scanner->scan(function($line)use(&$symbols){
			list($symbol, $chars) = explode("\t", trim($line));
			if (empty($chars)) return;
			if ($code = $this->_getCode($symbol)) {
				$count = mb_strlen($chars, 'utf-8');
				for ($i = 0; $i < $count; $i++) {
					$char = mb_substr($chars, $i, 1, 'utf-8');
					$this->_setCode($char, $code);
				}
			}
		});
	}

	private function _loadStructure() {
		$_structure = array();
		$scanner = new Scanner(STRUCTURE_TABLE);
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