<?php
require __DIR__ . '/helpers/Scanner.class.php';

$spells = array();
$chars = array();

// map spells
$filepath = __DIR__ . '/../code/spells.txt';
$scanner = new Scanner($filepath);
$scanner->scan(function($line)use(&$spells, &$chars){
	list($spell, $char, $weight) = explode("\t", trim($line));
	if (!isset($spells[$spell])) $spells[$spell] = array();
	$spells[$spell][$char] = $weight; 
	$chars[$char] = isset($chars[$char]) ? max($weight, $chars[$char]) : $weight;
});

// save spells as array
$output = fopen(__DIR__ . '/../code/spells.php', 'w');
fwrite($output, '<?php return ' . var_export($spells, true) . ';');
fclose($output);

// sort chars and save
$output = fopen(__DIR__ . '/../code/chars.txt', 'w');
arsort($chars);
foreach ($chars as $char => $weight) {
	fputs($output, $char . "\t" . PHP_EOL);
}
fclose($output);

// done
echo 'Done.' . PHP_EOL;
