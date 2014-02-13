<?php
error_reporting(E_ALL^E_NOTICE);
define('STRUCTURE_TABLE', __DIR__ . '/../code/structure.txt');
define('SYMBOLS_TABLE', __DIR__ . '/../code/symbols.txt');
define('MAPS_TABLE', __DIR__ . '/../code/maps.txt');

require __DIR__ . '/helpers/Scanner.class.php';

$maps = array();
$symbols = array();

// load maps
$scanner = new Scanner(MAPS_TABLE);
$scanner->scan(function($line)use(&$maps, &$symbols){
	list($code, $parts) = explode("\t", trim($line));
	$count = mb_strlen($parts, 'utf-8');
	$maps[$code] = array();
	for ($i = 0; $i < $count; $i++) {
		$symbol = mb_substr($parts, $i, 1, 'utf-8');
		$maps[$code][] = $symbol;
		$symbols[$symbol] = $code;
	}
});

$scanner = new Scanner(SYMBOLS_TABLE);
$scanner->scan(function($line)use(&$maps, &$symbols){
	list($symbol, $chars) = explode("\t", trim($line));
	if (empty($symbols) || empty($chars)) return;
	$count = mb_strlen($chars, 'utf-8');
	for ($i = 0; $i < $count; $i++) {
		$char = mb_substr($chars, $i, 1, 'utf-8');
		if (isset($symbols[$char])) {
			$code = $symbols[$char];
			$index = array_search($char, $maps[$code]);
			if ($index === false) continue;
			echo 'found [' . $char . '] below [' . $symbol . '], removed.' . PHP_EOL;
			unset($maps[$code][$index]);
		}
	}
});

$output = fopen(MAPS_TABLE, 'w');
foreach ($maps as $code => $symbols) {
	$line = $code . "\t" . implode('', $symbols) . PHP_EOL;
	echo $line;
	fputs($output, $line);
}
fclose($output);

echo 'Done.';