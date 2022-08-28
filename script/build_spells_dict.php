<?php
error_reporting(E_ERROR);
ini_set("display_errors","Off");
require __DIR__ . '/helpers/Scanner.class.php';

define('MIN_CHAR_WEIGHT', 1);
//define('LETTERS_COUNT', true);
//define('COUNT_BY_WEIGHT', true);

// load spells maps
$maps = array();
$scanner = new Scanner(__DIR__ . '/../data/maps_spells.txt');
$scanner->scan(function($line)use(&$maps){
	list($spell, $code) = explode("\t", trim($line));
	if (empty($code)) return;
	$maps[$spell] = $code;
});

// save spells maps as array
$output = fopen(__DIR__ . '/../data/maps_spells.php', 'w');
fwrite($output, '<?php return ' . var_export($maps, true) . ';');
fclose($output);

// map spells and chars
$spells = array();
$skip_spells = array('m', 'fiao', 'den', 'dei', 'n', 'lia', 'kei', 'zhei', 'eng', 'hm', 'ng');
$chars = array();
$skip_chars = array();
$scanner = new Scanner(__DIR__ . '/../data/spells_chars.txt');
$scanner->scan(function($line)use($maps, &$spells, &$chars, $skip_spells, &$skip_chars){

	list($char, $spell, $weight) = explode("\t", trim($line));

	// skip rare char
	if ($weight < MIN_CHAR_WEIGHT) {
		$skip_chars[] = $char;
		return;
	}

	// skip rare spell
	if (!isset($maps[$spell])) {
		if (!isset($skip_spells)) {
			echo 'missed map for spell [' . $spell . '].' . PHP_EOL;
		}
		$skip_chars[] = $char;
		return;
	}

	// map spell
	if (!isset($spells[$spell])) $spells[$spell] = array();
	$spells[$spell][$char] = $weight; 
	if (!isset($chars[$char]) || $weight > $chars[$char]['weight']) {
		$chars[$char] = array(
			'spell' => $spell,
			'weight' => $weight
		);
	}
	//echo $maps[$spell] . "\t" . $char . "\t" . $weight . PHP_EOL;
});


// sort by weight
foreach ($spells as $spell => &$spell_chars) {
	arsort($spell_chars);
}

// build dict
$letters_count = array();
$dict = fopen(__DIR__ . '/../build/zdvorak.spells.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../template/zdvorak.spells.dict.yaml');
fwrite($dict, $header);
foreach($spells as $spell => $spellChars) {
	foreach ($spellChars as $char => $weight) {
		fputs($dict, $maps[$spell] . "\t" . $char . "\t" . $weight . PHP_EOL);
		// record count
		$letter = $maps[$spell][1];
		if (!isset($letters_count[$letter])) {
			$letters_count[$letter] = 0;
		}
		$letters_count[$letter] += defined('COUNT_BY_WEIGHT') ? $weight : 1;
	}
}
fclose($dict);

// print letters count
if (defined('LETTERS_COUNT')) {
	echo 'letters count: ' . PHP_EOL;
	arsort($letters_count);
	$col = 1;
	foreach ($letters_count as $letter => $count) {
		echo $letter . ':' . $count . ', ';
		if ($col % 8 == 0) {
			echo PHP_EOL;
		}
		$col++;
	}
	echo PHP_EOL;
}

// save spells as array
$output = fopen(__DIR__ . '/../data/spells_chars.php', 'w');
fwrite($output, '<?php return ' . var_export($spells, true) . ';');
fclose($output);

// save chars as array
$output = fopen(__DIR__ . '/../data/chars_spells.php', 'w');
fwrite($output, '<?php return ' . var_export($chars, true) . ';');
fclose($output);

// done
echo 'Done.' . PHP_EOL;
