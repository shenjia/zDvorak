<?php
error_reporting(E_ERROR);
ini_set("display_errors","Off");
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';
define('WORD_WEIGHT', 1);

// load chars dict
$chars = array();
foreach (array('', '.first', '.second') as $dict) {
	$scanner = new Scanner(__DIR__ . '/../build/zdvorak' . $dict . '.dict.yaml');
	$scanner->scan(function($line)use(&$chars){
		// skip header
		if (strpos($line, "\t") == 0) return;
		list($code, $char, $weight) = explode("\t", trim($line));
		$chars[$char] = $code;
	});	
}

// load words
$words = array();
$missed_chars = array();	
$scanner = new Scanner(__DIR__ . '/../data/spells_words.txt');
$scanner->scan(function($line)use(&$words, &$missed_chars, &$chars){
	list($word, $spell, $weight) = explode("\t", trim($line));
	$len = mb_strlen($word, 'utf-8');
	for ($i = 0; $i < $len; $i++) {
		$char = mb_substr($word, $i, 1, 'utf-8');
		if (!isset($chars[$char])) {
			$missed_chars[] = $char;
		}
	}
});

echo 'missed chars: ' . count($missed_chars) . PHP_EOL;

// save missed chars
$file = fopen(__DIR__ . '/../data/missed_chars.txt', 'w');
array_unique($missed_chars);
foreach ($missed_chars as $char) {
	fputs($file, $char . "\t" . PHP_EOL);
}
fclose($file);

// done
echo 'Done.' . PHP_EOL;
