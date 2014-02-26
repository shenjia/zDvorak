<?php
error_reporting(E_ALL^E_NOTICE);
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';

// load spells
$spellCode = Encoder::ins()->encodeSpell('zh');

// done
echo 'Done.' . PHP_EOL;
