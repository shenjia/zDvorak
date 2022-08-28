<?php
error_reporting(E_ERROR);
ini_set("display_errors","Off");
require __DIR__ . '/helpers/Scanner.class.php';

$maps = array();

$filepath = __DIR__ . '/../data/maps_symbols.txt';
$scanner = new Scanner($filepath);
$scanner->scan(function($line)use(&$maps){
	list($code, $parts) = explode("\t", trim($line));
	$count = mb_strlen($parts, 'utf-8');
	$maps[$code] = array();
	for ($i = 0; $i < $count; $i++) {
		$part = mb_substr($parts, $i, 1, 'utf-8');
		$maps[$code][] = $part;
	}
});

// build dict
$dict = fopen(__DIR__ . '/../build/zdvorak.symbols.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../template/zdvorak.symbols.dict.yaml');
fwrite($dict, $header);
$weight = count($maps);
foreach($maps as $code => $parts) {
	foreach ($parts as $part) {
		fputs($dict, $code . "\t" . $part . "\t" . $weight-- . PHP_EOL);
	}
}
fclose($dict);

// done
echo 'Done.' . PHP_EOL;
