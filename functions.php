<?php

/**
 * A debugging function I developed years ago and use in all my code
 * to help me find the value of one or more items,
 * and let me know where it was called from.
 */
function debug() {
	$args = func_get_args();
	$dbt = debug_backtrace();
	echo '<b>' . $dbt[0]['file'] . ' on line ' . $dbt[0]['line'] . '</b>';
	echo '<pre>';
		if (!empty($args) && count($args) > 1) {
			foreach ($args as $a) {
				var_dump($a);
			}
		} else {
			var_dump($args[0]);
		}
	echo '</pre>';
}

/**
 * A simple wrapper function for the detailed htmlspecialchars,
 * to help make any html content safe to render, protecting against XSS attacks.
 */
function h($string) {
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', FALSE);
}

/**
 * A function I've used often through the years to quickly get remote content.
 */
function cURL($url, $data = [], $options = []) {
	$ch = curl_init();

	$curl_options = [
		CURLOPT_URL => $url,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_HEADER => FALSE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_FOLLOWLOCATION=> TRUE,
		CURLOPT_HTTPHEADER => ['X_REAL_IP: ' . $_SERVER['REMOTE_ADDR']],
	];

	if (isset($options['timeout'])) {
		$curl_options[CURLOPT_TIMEOUT] = $options['timeout'];
	}

	if (!empty($data)) {
		$curl_options[CURLOPT_POST] = TRUE;
		$curl_options[CURLOPT_POSTFIELDS] = $data;
	}

	curl_setopt_array($ch, $curl_options);
	$response = curl_exec($ch);

	curl_close($ch);
	return $response;
}

/**
 * A helper function to convert a string into something URL pretty,
 * for example, converting an article title for a permalink.
 */
function slugify($string) {
	# Remove accents from characters
	$string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

	# Then lowercase, convert anything not alphanumeric to a dash, remove any extra dashes, and return.
	return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '-', strtolower($string))), '-');
}
