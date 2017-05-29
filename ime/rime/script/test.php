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
	list($phrase, $weight) = explode("\t", trim($line));
	$spell = Speller::ins()->spellWord($phrase);
	if (!$spell) continue;
	$phrases[$phrase] = array('spell' => $spell, 'weight' => $weight);
});

$dict = fopen(__DIR__ . '/../data/phrases2.txt', 'w');
foreach ($phrases as $phrase => $config) {
    fputs($dict, $phrase . "\t" . $config['spell'] . "\t" . $config['weight'] . PHP_EOL);
}
fclose($dict);
