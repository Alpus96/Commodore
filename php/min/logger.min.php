<?php
	class logger{

		static private $path;

		function __construct($file_name) {
			date_default_timezone_set('Europe/Stockholm');
			$date = date('Y-m-d');
			self::$path = dirname(__FILE__).'/logs/'.$date.'_'.$file_name.'.txt';
		}

		function log ($str) {
			$file = fopen(self::$path, 'a');
			if (!$file) { return; }
			$time = date('H:i:s');
			fwrite($file, '\n'.$time.'\t'.$str);
			fclose($file);
		}

		function append_log ($str) {
			$file = fopen(self::$path, 'a');
			if (!$file) { return; }
			fwrite($file, $str);
			fclose($file);
		}

	}
?>