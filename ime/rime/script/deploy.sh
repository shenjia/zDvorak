#!/bin/bash

ZDVORAK_PATH="/Users/zhangshenjia/Cloud/works/github/zDvorak"
PHP_PARSER="/usr/bin/php"
RIME_EXEC_PATH="/Library/Input Methods/Squirrel.app/Contents/MacOS"
RIME_CONF_PATH="/Users/zhangshenjia/Library/Rime"
SYNC_PATH="/Volumes/share"

echo "commit working..."
$PHP_PARSER $ZDVORAK_PATH/ime/rime/script/commit.php

echo "build spells dictionary..."
$PHP_PARSER $ZDVORAK_PATH/ime/rime/script/build_spells_dict.php

echo "build symbols dictionary..."
$PHP_PARSER $ZDVORAK_PATH/ime/rime/script/build_symbols_dict.php

echo "build chars dictionary..." 
$PHP_PARSER $ZDVORAK_PATH/ime/rime/script/build_chars_dict.php

echo "build words dictionary..." 
$PHP_PARSER $ZDVORAK_PATH/ime/rime/script/build_words_dict.php

echo "reload rime..."
cp -f $ZDVORAK_PATH/ime/rime/build/*.yaml "$RIME_CONF_PATH"
"$RIME_EXEC_PATH/Squirrel" --reload

if [ -d "$SYNC_PATH" ];
then
	echo "sync dict..."
	cp -f $ZDVORAK_PATH/ime/rime/build/* $SYNC_PATH
fi

echo "Done."