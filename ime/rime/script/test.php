<?php
error_reporting(E_ALL^E_NOTICE);
define('WORKING_TABLE', __DIR__ . '/../data/working.txt');
define('STRUCTURE_TABLE', __DIR__ . '/../data/structure.txt');
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Speller.class.php';
require __DIR__ . '/helpers/Encoder.class.php';


// load pharse
$phrases = array();
$scanner = new Scanner(__DIR__ . '/../data/phrases.txt');
$scanner->scan(function($line)use(&$phrases){
	$phrase = trim($line);
	$phrases[$phrase] = 1;
});

$scanner = new Scanner(__DIR__ . '/../data/phases2.txt');
$hit = 0;
$miss = 0;
$mixed = array();
$scanner->scan(function($line)use(&$phrases, &$hit, &$miss, &$mixed){
	$parts = explode(' ', trim($line));
	$phrase = $parts[0];
	if (!isset($phrases[$phrase])) return;
    $mixed[$phrase] = $parts[1];
});


// save skip 2-chars words
$file = fopen(__DIR__ . '/../data/mixed_phrases.txt', 'w');
foreach ($mixed as $word => $weight) {
		fputs($file, $word . "\t" . $weight. "\t" . PHP_EOL);
}
fclose($file);