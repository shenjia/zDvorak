<?php
error_reporting(E_ALL^E_NOTICE);
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';

define('FIRST_WEIGHT', 99999999);
define('SECOND_WEIGHT', 99999999);
define('SHOW_LETTERS_COUNT', true);
//define('COUNT_BY_WEIGHT', true);
define('SHOW_CONFLICTS', true);
define('PASS_WEIGHT', 500);
//define('SHOW_REPLACED', true);
define('BUILD_FIRST_DICT', true);
define('BUILD_SECOND_DICT', true);

// load spells
$spells = require __DIR__ . '/../data/spells_chars.php';

// build first dict
$first = array();
$dict = fopen(__DIR__ . '/../build/zdvorak.first.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../template/zdvorak.first.dict.yaml');
fwrite($dict, $header . PHP_EOL);
if (defined('BUILD_FIRST_DICT')) {
	$scanner = new Scanner(__DIR__ . '/../data/first.txt');
	$scanner->scan(function($line)use(&$first, &$dict){
		list($code, $char) = explode("\t", trim($line));
		if (empty($code)) return;
		fwrite($dict, $code . "\t" . $char . "\t" . FIRST_WEIGHT . PHP_EOL);
		$first[$code] = array(
			'char' => $char,
			'weight' => FIRST_WEIGHT
		);
	});
}
fclose($dict);

// build second dict
$second = array();
$dict = fopen(__DIR__ . '/../build/zdvorak.second.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../template/zdvorak.second.dict.yaml');
fwrite($dict, $header . PHP_EOL);
if (defined('BUILD_SECOND_DICT')) {
	$scanner = new Scanner(__DIR__ . '/../data/second.txt');
	$scanner->scan(function($line)use(&$second, &$dict){
		list($spell, $char) = explode("\t", trim($line));
		if (empty($spell)) return;
		$code = Encoder::ins()->encodeSpell($spell);
		fwrite($dict, $code . "\t" . $char . "\t" . SECOND_WEIGHT . PHP_EOL);
		$second[$code] = array(
			'char' => $char,
			'weight' => SECOND_WEIGHT
		);
	});
}
fclose($dict);

// build main dict
$miss = 0;
$hit = 0;
$codes = array();
$replaced = array();
$conflicts = array();

foreach ($spells as $spell => $chars) {
	foreach ($chars as $char => $weight) {
		$charCode = Encoder::ins()->encodeChar($char);
		$spellCode = Encoder::ins()->encodeSpell($spell);
		if ($charCode && $spellCode) {
			$code = $spellCode . $charCode;
			$short = $spellCode . substr($charCode, 0, 1);
			
			// skip if already take first or second code
			if ($char == $first[substr($spellCode, 0, 1)]['char'] 
		     || $char == $second[$spellCode]['char'] ) continue;
					
			// use short code first
			if (!isset($codes[$short])) {
				$codes[$short] = array(array(
					'char' => $char,
					'weight' => $weight
				));
			}
			// if char have full code
			if ($code != $short) {
				// record char
				if (!isset($codes[$code])) {
					$codes[$code] = array(array(
						'char' => $char,
						'weight' => $weight
					));
					if ($short=='btb') {
						//var_dump($codes[$code]);
					}
				}
				// if the old one take short code, replace it
				else if ($codes[$code][0]['char'] == $codes[$short][0]['char']) {
					$codes[$code][0] = array(
						'char' => $char,
						'weight' => $weight
					);
					$replaced[] = array(
						'short' => array_merge($codes[$short][0], array('code' => $short)),
						'long' => array_merge($codes[$code][0], array('code' => $code))
					); 
				} 
				// mark conflicts
				else {
					if (!isset($conflicts[$code])) {
						$conflicts[$code] = array(
							'chars' => array($codes[$code][0]['char']),
							'weight' => $codes[$code][0]['weight']
						);					
					}
					$conflicts[$code]['chars'][] = $char;
					$conflicts[$code]['weight'] = max($weight, $conflicts[$code]['weight']);
					$codes[$code][] = array(
						'char' => $char,
						'weight' => $weight
					);
				}
			}
			$hit++;
		} else {
			$miss++;
		}
	}
}

// save dict
$dict = fopen(__DIR__ . '/../build/zdvorak.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../template/zdvorak.dict.yaml');
fwrite($dict, $header);
$lettersCount = array();
foreach ($codes as $code => $chars) {
	foreach ($chars as $char) {
		fputs($dict, $code . "\t" . $char['char'] . "\t" . $char['weight'] . PHP_EOL);
		// record count
		$len = mb_strlen($code, 'utf-8');
		for ($i = 2; $i < $len; $i++) {
			$letter = mb_substr($code, $i, 1, 'utf-8');
			if (!isset($lettersCount[$letter])) {
				$lettersCount[$letter] = 0;
			}
			$lettersCount[$letter] += defined('COUNT_BY_WEIGHT') ? $char['weight'] : 1;
		}
	}
}
fclose($dict);

// count letters
if (defined('SHOW_LETTERS_COUNT')) {
	echo 'letters count: ' . PHP_EOL;
	arsort($lettersCount);
	$col = 1;
	foreach ($lettersCount as $letter => $count) {
		echo $letter . ':' . $count . ', ';
		if ($col % 8 == 0) {
			echo PHP_EOL;
		}
		$col++;
	}
	echo PHP_EOL;
}

// print replaced chars
$file = fopen(__DIR__ . '/../data/replaced_chars.txt', 'w');
foreach ($replaced as $replace) {
	fputs($file, $replace['short']['char'] . "\t" . PHP_EOL);
	if (defined('SHOW_REPLACED')) {
		echo $replace['short']['char'] . ' [' . $replace['short']['code'] . '] , ' 
	   	   . $replace['long']['char'] . ' [' . $replace['long']['code']. ']' . PHP_EOL;
	}
}
fclose($file);
if (count($replaced) > 0) {
	echo 'replaced chars: ' . count($replaced) . PHP_EOL;
} 

// print conflict sort by weight desc
if (defined('SHOW_CONFLICTS')) {
	$index = array();
	foreach ($conflicts as $code => $conflict) {
		$index[$code] = $conflict['weight'];
	}
	asort($index);
	foreach ($index as $code => $weight) {
		if ($weight >= PASS_WEIGHT) {
			echo $code . ' (' . $weight .') ' . implode('', $conflicts[$code]['chars']) . PHP_EOL;
		}
	}
}
if (count($conflicts) > 0) {
	echo 'conflict chars: ' . count($conflicts) . PHP_EOL;
}

if ($missed = Encoder::ins()->missed()) {
	echo 'missed symbols: ' . implode('', $missed) . PHP_EOL;
}
if ($duplicated = Encoder::ins()->duplicated()) {
	echo 'duplicated symbols:' . implode('', $duplicated) . PHP_EOL;
}
echo 'hit chars: ' . $hit . ', missed chars: ' . $miss . PHP_EOL;

// done
echo 'Done.' . PHP_EOL;
