#!/bin/bash

PHP_PARSER=/usr/bin/php
SCRIPT_PATH=/Users/zhangshenjia/Cloud/works/github/zDvorak/ime/rime/script

echo "commit working..."
$PHP_PARSER $SCRIPT_PATH/commit.php

echo "build symbols dictionary..."
$PHP_PARSER $SCRIPT_PATH/symbols_dict.php

echo "build char dictionary..." 
$PHP_PARSER $SCRIPT_PATH/char_dict.php

