<?php
error_reporting(E_ALL^E_NOTICE);
define('WORKING_TABLE', __DIR__ . '/../data/working.txt');
define('STRUCTURE_TABLE', __DIR__ . '/../data/structure.txt');
require __DIR__ . '/helpers/Scanner.class.php';
require __DIR__ . '/helpers/Encoder.class.php';

var_dump(Encoder::ins());