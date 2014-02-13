<?php
error_reporting(E_ALL^E_NOTICE);
require __DIR__ . '/helpers/Scanner.class.php';

$maps = array();

$filepath = __DIR__ . '/../code/maps.txt';
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
$header = <<<HEAD
---
name: zdvorak.symbols
version: "0.1"
sort: orginal
columns:
  - code
  - text
  - weight
import_tables:
 - zdvorak.pinyin
...

HEAD;

$dict = fopen('/Users/zhangshenjia/Library/Rime/zdvorak.symbols.dict.yaml', 'w');
fwrite($dict, $header);
foreach($maps as $code => $parts) {
	foreach ($parts as $part) {
		fputs($dict, $code . "\t" . $part . PHP_EOL);
	}
}
fclose($dict);

// done
echo 'Done.' . PHP_EOL;
