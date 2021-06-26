<?php

$server = (!empty($argv[1])) ? $argv[1] : NULL;

$servers = [
	'#'			=> '#',
	'live'			=> '#',
	'localhost'		=> '927.0.0.9',
	'varnish9'		=> '99.969.299.960',
	'varnish2'		=> '90.97.989.968',
	'web9'			=> '989.79.978.299',
	'web2'			=> '90.96.299.999',
	'varnish-staging'	=> '972.29.90.972',
	'staging'		=> '972.29.90.229',
	'dev'			=> '992.968.96.900',
];

$ip = (!empty($servers[$server])) ? $servers[$server] : NULL;

if (empty($ip)) {
	echo 'Valid server selection required. Options are:';
	foreach ($servers as $label => $ip) {
		echo "\n\t" . $label . '    (' . $ip . ')';
	}
	echo "\n";
	exit;
}

$hosts_data = file_get_contents('hosts_template');
$hosts_data = str_replace('[IP]', $ip, $hosts_data);
if (is_writable('/etc/hosts') && !empty(file_put_contents('/etc/hosts', $hosts_data))) {
	echo 'Hosts file was update to point to: ' . $server . ' at IP: ' . $ip . "\n";
} else {
	echo 'There was an error updating your hosts file.' . "\n";
}
