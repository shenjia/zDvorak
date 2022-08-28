<?php
class Scanner {

	public $file = null;

	public function __construct($filepath) {
		if (!file_exists($filepath)) {
			echo '[' . $filepath . '] not found.' . PHP_EOL;
			exit();
		}
		$this->file = fopen($filepath, 'r');
	}	

	public function __destruct() {
		if ($this->file) {
			fclose($this->file);
		}
	}

	public function scan($function) {
		while ($line = fgets($this->file)) {
			$function($line);
		}
	}	
}
