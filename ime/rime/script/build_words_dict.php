<?php
error_reporting(E_ALL^E_NOTICE);
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';
define('WORD_WEIGHT', 1);
define('HIGH_WORD_WEIGHT', 3000);
define('HIGH_CHAR_WEIGHT', 500);

// load chars dict
$codes = array();
$scanner = new Scanner(__DIR__ . '/../build/zdvorak.dict.yaml');
$scanner->scan(function($line)use(&$codes){
	// skip header
	if (strpos($line, "\t") == 0) return;
	list($code, $char, $weight) = explode("\t", trim($line));
	if (!isset($codes[$code])) $codes[$code] = array();
	$codes[$code][] = array(
		'char' => $char,
		'weight' => $weight
	);
});

// load pharse
$phrases = array();
$scanner = new Scanner(__DIR__ . '/../data/phrases.txt');
$scanner->scan(function($line)use(&$phrases){
	$phrase = trim($line);
	$phrases[$phrase] = 1;
});

// load words
$words = array();
$skip_words = array();
$conflict_words = array();
$scanner = new Scanner(__DIR__ . '/../data/spells_words.txt');
$scanner->scan(function($line)use(&$words, &$skip_words, &$conflict_words, &$codes, &$phrases){
	list($word, $spell, $weight) = explode("\t", trim($line));
	$code = Encoder::ins()->encodeSpells($spell);
	$short = substr($code, 0, 3);
	// conflict with char
	if (isset($codes[$code]) && !empty($codes[$code])) {
		// skip if deal with low weight word, or the code has conflict chars
		if ($weight < HIGH_WORD_WEIGHT || count($codes[$code]) > 1) {
			$conflict_words[] = $word;
			return;
		}
		// take code if char take both code
		if ($codes[$short][0]['char'] == $codes[$code][0]['char']) {
			echo 'word [' . $word . '] take code [' . $code . '] from char [' . $codes[$code][0]['char'] . '] because it have [' . $short . '].' . PHP_EOL;
			array_shift($codes[$code]);
		}
		// take code if a low weight char conflict with high weight word
		else if ($codes[$code][0]['weight'] < HIGH_CHAR_WEIGHT) {
			echo 'word [' . $word . '] take code [' . $code . '] from char [' . $codes[$code][0]['char'] . '] and replaced it with [' . $short . '].' . PHP_EOL;
			$codes[$short][] = array_shift($codes[$code]);
		}
	}
	// skip if have 4 chars but not a phrase
	if (mb_strlen($word, 'utf-8') >= 4 and !isset($phrases[$word])) {
		$skip_words[$word] = $weight;
		return;
	}
	// insert if not exists
	if (!isset($words[$code])) {
		$words[$code] = array(
			'word' => $word,
			'weight' => $weight
		);
	} 
	// don't replace 2-chars phrase with 3-chars
	else if (mb_strlen($word, 'utf-8') == 3 and mb_strlen($words[$code]['word'], 'utf-8') == 2) {
		$skip_words[$word] = $weight;
		return;
	}
	// don't replace 2-chars phrase with 4-chars
	else if (mb_strlen($word, 'utf-8') == 4 and mb_strlen($words[$code]['word'], 'utf-8') == 2) {
		$skip_words[$word] = $weight;
		return;
	}
	// swap x-chars phrase with 4-chars phrase
	else if (mb_strlen($word, 'utf-8') == 4 and isset($phrases[$word]) and $words[$code]['word'] < 4) {
		$skip_words[$words[$code]['word']] = $words[$code]['weight'];
		$words[$code] = array(
			'word' => $word,
			'weight' => $weight
		);
	}
	// swap if the new one got higher weight
	else if ($weight >= $words[$code]['weight']) {
		$skip_words[$words[$code]['word']] = $words[$code]['weight'];
		$words[$code] = array(
			'word' => $word,
			'weight' => $weight
		);
	} 
	// skip if the new one got lower weight
	else {
		$skip_words[$word] = $weight;
	}
});

// load my words
$scanner = new Scanner(__DIR__ . '/../data/my_words.txt');
$scanner->scan(function($line)use(&$words, &$skip_words){
	list($word, $spell, $weight) = explode("\t", trim($line));
	$code = Encoder::ins()->encodeSpells($spell);
	if (isset($words[$code])) {
		$skip_words[$words[$code]['word']] = $words[$code]['weight'];
	}
	$words[$code] = array(
		'word' => $word,
		'weight' => 999999
	);
});

echo 'collect words: ' . count($words) 
   . ', skip words: ' . count($skip_words) 
   . ', conflict words: ' . count($conflict_words) . PHP_EOL;

// save char dict
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

// build work dict
$dict = fopen(__DIR__ . '/../build/zdvorak.words.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../template/zdvorak.words.dict.yaml');
fwrite($dict, $header);
foreach ($words as $code => $word) {
	fputs($dict, $code . "\t" . $word['word'] . "\t" . WORD_WEIGHT . PHP_EOL);
}
fclose($dict);

// save skip 2-chars words
$file = fopen(__DIR__ . '/../data/skiped_words.txt', 'w');
arsort($skip_words);
foreach ($skip_words as $word => $weight) {
	if (mb_strlen($word, 'utf-8') == 2) {
		fputs($file, $word . "\t" . $weight. "\t" . PHP_EOL);
	}
}
fclose($file);

// done
echo 'Done.' . PHP_EOL;
