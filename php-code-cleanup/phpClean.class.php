<?php

require_once '../functions.php';

class phpClean {

	private $file = NULL;

	private $clean_file = NULL;

	private $contents = NULL;

	public function open_file($file) {

		if (empty($file)) {
			die('ERROR: File name empty!');
		}

		if (!is_readable($file)) {
			$file = getcwd() . '/' . $file;
			if (!is_readable($file)) {
				die('ERROR: File not readable!');
			}
		}

		$this->file = $file;

		$this->contents = file_get_contents($this->file);

		$this->clean_file = $this->file . '.clean';
	}

	public function cleanup() {
		# Convert to LF \n line endings.
		$this->contents = preg_replace('~\R~u', "\n", $this->contents);

		# Limit line returns to 2 at a time, no need for huge gaps.
		$this->contents = preg_replace('/\n{3,}/', "\n\n", $this->contents);

		# Add space between controls strucures and open parenthases and remove space after open parenthases.
		$this->contents = preg_replace('/(if|while|for|foreach|switch)\(\s*/', '$1 (', $this->contents);

		# Kill spaces after opening parenthases.
		$this->contents = preg_replace('/\(\s+/', '(', $this->contents);

		# Kill spaces before closing parenthases.
		$this->contents = preg_replace('/\s+\)/', ')', $this->contents);

		# Proper spacing around commas.
		$this->contents = preg_replace('/,(\S)/', ', $1', $this->contents);
		$this->contents = preg_replace('/\s+,\s+/', ', ', $this->contents);

		# Keep opening curley brackets at end of line with space before.
		$this->contents = preg_replace('/(\)|else|elseif|else if)(\s|\t|\n)*\{/', '$1 {', $this->contents);

		# Keep else on the same line as opening curley brackets.
		$this->contents = preg_replace('/\}( |\t|\n)+else/', '} else', $this->contents);

		# remove cases of exiting and going right back into PHP mode.
		$this->contents = preg_replace('/;\?\> +\<\?php echo /', " . ' ' . ", $this->contents);
		$this->contents = preg_replace('/\?\>(( |\t|\n)*)\<\?php/', '$1', $this->contents);

		$this->contents = preg_replace('/\>(( |\t|\n)+)\<\?php\s*(if|else|elseif|else if|\})/', '><?php$1$3', $this->contents);
		$this->contents = preg_replace('/\>( |\t|\n)*\<\?php/', '><?php', $this->contents);

		# Remove white spacing between opening or closing curley brackets and closing PHP tags.
		$this->contents = preg_replace('/(\{|\})( |\t|\n)*\?\>/', '$1?>', $this->contents);

		# Remove white spacing between opening or closing curley brackets and closing PHP tags.
		$this->contents = preg_replace('/; *\?\>/', '?>', $this->contents);

		# Process lines individually.
		$lines = explode("\n", $this->contents);
		foreach ($lines as $line_number => $line) {

			# Convert 4 spaces to a tab.
			$line = $lines[$line_number] = preg_replace('/ {4}+/', "\t", $line);

			# Convert double spaces to a single.
			$line = $lines[$line_number] = preg_replace('/ {2}/', ' ', $line);

			# Get rid of single spaces after a tab
			$line = $lines[$line_number] = preg_replace('/\t  /', "\t\t", $line);
			$line = $lines[$line_number] = preg_replace('/\t /', "\t", $line);

			# Get rid of single spaces at the beginning of the line.
			$line = $lines[$line_number] = preg_replace('/^ /', "\t", $line);

			if (
				# If line does not contain a less than sign (avoiding possible HTML)
				strpos($line, '<') === FALSE
				# and does not contain a dollar sign (avoiding possible variables in double quotes).
				&& strpos($line, '$') === FALSE
				# but does contain an opening php tag OR does contain 'echo '
				&& (strpos($line, '<?php') !== FALSE || strpos($line, 'echo ') !== FALSE)
			) {
				$line = $lines[$line_number] = preg_replace('/"([^\'\"]+)"/', '\'$1\'', $line);
			}

			if (
				# If line does not contain single quotes
				strpos($line, "'") === FALSE
				# but does contain double quotes
				&& strpos($line, '"') !== FALSE
				# but does not contain a less than sign (avoiding possible HTML)
				&& strpos($line, '<') === FALSE
			) {
				# If we have an even count of double quotes on a line, clean up use of double quotes where single quotes should be used.
				$dq_count = substr_count($line, '"');
				if (!empty($dq_count) && ($dq_count % 2) === 0) {
					$line = $lines[$line_number] = implode("'", explode('"', $line));
				}
			}

			if (
				# If line does contain a less than sign (possible HTML)
				strpos($line, '<') !== FALSE
				# but does not contain an opening php tag
				&& strpos($line, '<?php') === FALSE
				# and does not contain 'echo "' (avoiding possible variables in double quotes).
				&& strpos($line, 'echo "') === FALSE
			) {
				# Replace html attributes that are single quoted with double quotes.
				$line = $lines[$line_number] = preg_replace('/( [A-Za-z]+)=\'([^\'\"]*)\'/', '$1="$2"', $line);
			}

			if (
				# If line does not contain double quotes
				strpos($line, '"') === FALSE
				# but does contain single quotes
				&& strpos($line, "'") !== FALSE
				# but does contain a less than sign (possible HTML)
				&& strpos($line, '<') !== FALSE
			) {
				# If we have an even count of single quotes on a line, clean up use of single quotes where double quotes should be used.
				$sq_count = substr_count($line, "'");
				if (!empty($sq_count) && ($sq_count % 2) === 0) {
					$line = $lines[$line_number] = implode('"', explode("'", $line));
				}
			}

			# Clean up quotes in php date function format parameter.
			$line = $lines[$line_number] = preg_replace('/date\("([a-zA-Z:\\-\s]+)"/', 'date(\'$1\'', $line);;

			# Trim trailing white spaces.
			$line = $lines[$line_number] = rtrim($line);
		}
		$this->contents = implode("\n", $lines);
	}

	public function database() {
		# Convert deprecated MySQL function to our Database wrapper:

		$this->contents = preg_replace('/\) or die\(.+\);/', ');', $this->contents);

		$this->contents = preg_replace('/( *)mysql_?query\(/', '$1$databaseWrapper->query(', $this->contents);

		$this->contents = preg_replace('/\s+pdo_query\(([^,]+).*\)/', ' $databaseWrapper->query($1)', $this->contents);

		$this->contents = preg_replace('/\s+\$pdo_db-\>execute\(/', ' $databaseWrapper->query(', $this->contents);

		$this->contents = preg_replace('/\s+\$pdo_db-\>lastInsertId\(/', ' $databaseWrapper->last_insert_id(', $this->contents);

		$this->contents = preg_replace('/(mysql_fetch_assoc|mysql_fetch_row)\(/', '$databaseWrapper->fetch_row(', $this->contents);
		$this->contents = preg_replace('/mysql_num_rows\(/', '$databaseWrapper->rowCount(', $this->contents);
		$this->contents = preg_replace('/mysql_num_fields\(/', '$databaseWrapper->columnCount(', $this->contents);

		$this->contents = preg_replace('/mysql_fetch_field\(/', '$databaseWrapper->getColumnMeta(', $this->contents);


		$this->contents = preg_replace('/(mysql_real_escape_string|mysql_escape_string|\$pdo_db-\>escapeStr)\(/', '$databaseWrapper->escape(', $this->contents);
		$this->contents = preg_replace('/mysql_insert_id\(/', '$databaseWrapper->last_insert_id(', $this->contents);

		$this->contents = preg_replace('/mysql_select_db\(/', '$databaseWrapper->select_db(', $this->contents);


		$this->contents = preg_replace('/\$databaseWrapper-\>select_db\(([^,\);]*)[^\);]*\);/', '$databaseWrapper->select_db(\1);', $this->contents);

		$this->contents = preg_replace('/mysql_fetch_array\(([^,\);]+)[^\);]*\)/', '$databaseWrapper->fetch($1)', $this->contents);

		$this->contents = preg_replace('/\$([A-Za-z0-9_]+)-\>query\(/', '$databaseWrapper->query(', $this->contents);
		$this->contents = preg_replace('/\$([A-Za-z0-9_]+)-\>quote\(/', '$databaseWrapper->escape(', $this->contents);

		$this->contents = preg_replace('/\$([A-Za-z0-9_]+)-\>rowCount\(\)/', '$databaseWrapper->rowCount($$1)', $this->contents);
		$this->contents = preg_replace('/\$([A-Za-z0-9_]+)-\>columnCount\(\)/', '$databaseWrapper->columnCount($$1)', $this->contents);

		$this->contents = preg_replace('/\$([A-Za-z0-9_]+)-\>fetch\(PDO::FETCH_ASSOC\)/', '$databaseWrapper->fetch_row($$1)', $this->contents);

		$this->contents = str_ireplace('$connection = mysql_connect($host, $user, $password);', '$connection = NULL; /* Former MySQL connection */', $this->contents);
		$this->contents = str_ireplace('$connection = mysql_connect($host,$user,$password);', '$connection = NULL; /* Former MySQL connection */', $this->contents);

		$this->contents = str_ireplace('$connection = NULL; /* Former MySQL connection */', '', $this->contents);
		$this->contents = str_ireplace('$connection = NULL; // Former MySQL connection', '', $this->contents);


		$this->contents = str_ireplace('mysql_error()', '"(mysql error function removed)"', $this->contents);
		$this->contents = str_ireplace('mysql_errno()', '"(mysql errorno function removed)"', $this->contents);
	}

	# Dump to temp file to diff and check for errors.
	public function write_clean_file() {
		file_put_contents($this->clean_file, $this->contents);
	}

	# Get a diff of all the changes.
	public function get_diff() {
		$out = [];
		exec('diff -b ' . $this->file . ' ' . $this->clean_file, $out);
		if (!empty($out)) {
			echo implode("\n", $out) . "\n";
		}
	}

	# Get a diff of all the changes.
	private function any_changes() {
		$out = [];
		exec('diff -b ' . $this->file . ' ' . $this->clean_file, $out);
		return (!empty($out));
	}


	# Check for PHP errors.
	public function php_error_check() {
		if ($this->any_changes()) {
			$out = [];
			echo "\n\n";
			exec('php -l ' . $this->clean_file, $out);
			echo implode("\n", $out) . "\n";
		}
	}

	public function save($confirm = TRUE) {

		if ($this->any_changes()) {
			# Confirm to accept changes.
			if ($confirm) {
				echo "\n" . 'The above changes would be made. Enter "y" to accept or any other key to discard.' . "\n";
				$handle = fopen ('php://stdin', 'r');
				$continue = strtolower(trim(fgets($handle)));
				fclose($handle);
			}
	
			if (!$confirm || (!empty($continue) && $continue == 'y')) {
				if (rename($this->clean_file, $this->file)) {
					if ($confirm) {
						echo $this->file . ' was updated.';
					}
				} else {
					echo $this->file . ' could not be updated.';
				}
			} else {
				echo 'Changes have been discarded.';
			}
		} else {
			echo 'Nothing changed!';
		}

		if (!empty($this->clean_file) && is_writable($this->clean_file)) {
			unlink($this->clean_file);
		}
		$this->contents = NULL;
	}

	public function __destruct() {
		$this->contents = NULL;

		if (!empty($this->clean_file) && is_writable($this->clean_file)) {
			unlink($this->clean_file);
		}
	}

}