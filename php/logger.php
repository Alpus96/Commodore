<?php
	/**
	*   This class handels writing strings to
	*	a text file, to ease debuging.
	*
	*   @category       Debuging
	*   @package        Logger
	*   @version        1.0
	*   @since          1.0
	*   @deprecated     ---
	* */
	class logger {

		//	Absolute path to log file.
		static private $path;

		/**
		*	@method		Sets the absolute path to the log file with passed name.
		*
		*	@param		string	The name of the file to write logs to.
		* */
		function __construct($file_name) {
			//	Set the path to the log file with date before given name.
			date_default_timezone_set('Europe/Stockholm');
			$date = date('Y-m-d');
			self::$path = dirname(__FILE__).'/logs/'.$date.'_'.$file_name.'.txt';
		}

		/**
		*	@method		Opens log file and if successful writes
		*				log entry before closing the file again.
		*
		*	@param		string	The log entry string to write to the file.
		* */
		function log ($str) {
			//	Open the file.
			$file = fopen(self::$path, 'a');
			//	Return if unable to open file.
			if (!$file) { return; }
			//	Begin the log entry with the current
			//	timea nd write it to the file.
			$time = date('H:i:s');
			fwrite($file, '\n'.$time.'\t'.$str);
			//	Close the file when done.
			fclose($file);
		}

		/**
		*	@method		Opens log file and if successful writes log string on
		*				existing line before closing the file again.
		*
		*	@param		string	The log string to append to the current row.
		* */
		function append_log ($str) {
			//	Open the file.
			$file = fopen(self::$path, 'a');
			//	Return if unable to open file.
			if (!$file) { return; }
			//	Write the given string without
			//	begining a new row.
			fwrite($file, $str);
			//	Close file.
			fclose($file);
		}

	}
?>
