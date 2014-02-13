<?php
error_reporting(E_ALL^E_NOTICE);
define('WORKING_TABLE', __DIR__ . '/../code/working.txt');
define('STRUCTURE_TABLE', __DIR__ . '/../code/structure.txt');
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';

$remains = array();
$commits = array();
$maps = Encoder::ins()->maps();

$scanner = new Scanner(WORKING_TABLE);
$scanner->scan(function($line)use(&$commits, &$remains, &$maps){
	
	list($char, $parts) = explode("\t", trim($line));

	// skip char without code
	if (empty($parts)) {
		$remains[] = $char;
		return;
	}

	// must have two parts except code equal the char
	if (mb_strlen($parts, 'utf-8') == 1 && $parts != $char) {
		echo 'char [' . $char . '] only have one code [' . $parts . '].' . PHP_EOL;
		$remains[] = $char;
		return;	
	}

	// commit code
	$commits[$char] = $parts;
});
unset($scanner);

$structure = fopen(STRUCTURE_TABLE, 'a');
foreach ($commits as $char => $parts) {
	fwrite($structure, $char . "\t" . trim($parts) . PHP_EOL);
}
fclose($structure);

$working = fopen(WORKING_TABLE, 'w');
foreach ($remains as $char) {
	fputs($working, $char . "\t" . PHP_EOL);
}
fclose($working);

echo count($commits) . ' commited, ' . count($remains) . ' remains.' . PHP_EOL;