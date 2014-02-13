<?php
error_reporting(E_ALL^E_NOTICE);
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';

// load spells
$spells = require __DIR__ . '/../code/spells.php';

// build first dict
$dict = fopen('/Users/zhangshenjia/Library/Rime/zdvorak.first.dict.yaml', 'w');
$first = file_get_contents(__DIR__ . '/../code/first.txt');
$header = file_get_contents(__DIR__ . '/../code/first_head.txt');
fwrite($dict, $header . PHP_EOL);
fwrite($dict, $first . PHP_EOL);
fclose($dict);

// build main dict
$dict = fopen('/Users/zhangshenjia/Library/Rime/zdvorak.dict.yaml', 'w');
$header = file_get_contents(__DIR__ . '/../code/dict_head.txt');
fwrite($dict, $header);

$miss = 0;
$hit = 0;

foreach ($spells as $spell => $chars) {
	foreach ($chars as $char => $weight) {
		$code = Encoder::ins()->encode($char, $spell);
		if ($code && $code != $spell) {
			//echo $code . "\t" . $char . "\t" . $weight . PHP_EOL;
			fputs($dict, $code . "\t" . $char . "\t" . $weight . PHP_EOL);
			$hit++;
		} else {
			$miss++;
		}
	}
}
if ($missed = Encoder::ins()->missed()) {
	echo PHP_EOL . 'missed symbols:' . PHP_EOL;
	echo implode('', $missed) . PHP_EOL;
}
if ($duplicated = Encoder::ins()->duplicated()) {
	echo PHP_EOL . 'duplicated symbols:' . PHP_EOL;
	echo implode('', $duplicated) . PHP_EOL;
}
echo PHP_EOL . 'hit chars: ' . $hit . ', missed chars: ' . $miss . PHP_EOL;

fclose($dict);

// done
echo 'Done.' . PHP_EOL;
