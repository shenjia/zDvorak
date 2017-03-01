<?php
error_reporting(E_ALL^E_NOTICE);

define('CHARS_SPELLS', __DIR__ . '/../../data/chars_spells.php');
define('SPELL_SPLITTER', ' ');

class Speller
{
	private $_showMissed = false;
	private $_missedChars = array();
	private $_missedWords = array();
	private $_chars_spells_map = array();
	private static $_INSTANCE;

	public static function ins() {
		if (!isset(self::$_INSTANCE)) {
			self::$_INSTANCE = new Speller();
		}
		return self::$_INSTANCE;
	}

	public function __construct() {
		$this->_loadCharsSpellsMap();
	}

	public function spellWord($word) {
        $count = mb_strlen($word, 'utf-8');
        $chars = array();
        $spells = array();
        for ($i = 0; $i < $count; $i++) {
            $char = mb_substr($word, $i, 1, 'utf-8');
            if ($spell = $this->spellChar($char)) {
                $spells[]= $spell;
            } else {
                if ($this->_showMissed) {
                    echo 'miss spell for char [' . $char . '] in word [' . $word . '].' . PHP_EOL;
                }
                $this->_missedWords[$word] = 1;
                return false;
            }
        }
        return implode(SPELL_SPLITTER, $spells);
	}

	public function spellChar($char) {
		if (isset($this->_chars_spells_map[$char])) {
			return $this->_chars_spells_map[$char]['spell'];
		} else {
            if ($this->_showMissed) {
			    echo 'miss spell for char [' . $char . '].' . PHP_EOL;
			}
			$this->_missedChars[$char] = 1;
			return false;				
		}
	}

	private function _loadCharsSpellsMap() {
		$this->_chars_spells_map = require CHARS_SPELLS;
	}

	public function missedChars() {
		return array_keys($this->_missedChars);
	}

	public function missedWords() {
		return array_keys($this->_missedWords);
	}
}