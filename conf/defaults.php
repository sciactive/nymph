<?php
/**
 * Nymph's configuration defaults.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

return (object) array(
	'driver' => array(
		'cname' => 'Nymph Database Driver',
		'description' => 'The database driver for Nymph to use.',
		'value' => 'MySQL',
	),
	'use_plperl' => array(
		'cname' => 'Use PL/Perl Functions',
		'description' => '(Postgres only) This speeds up regular expression matching a lot, but requires the Perl Procedural Language to be installed on your Postgres server.',
		'value' => true,
	),
	'cache' => array(
		'cname' => 'Cache Entities',
		'description' => 'Cache recently retrieved entities to speed up database queries. Uses more memory.',
		'value' => false,
	),
	'cache_threshold' => array(
		'cname' => 'Cache Threshold',
		'description' => 'Cache entities after they\'re accessed this many times.',
		'value' => 4,
	),
	'cache_limit' => array(
		'cname' => 'Cache Limit',
		'description' => 'The number of recently retrieved entities to cache. If you\'re running out of memory, try lowering this value. 0 means unlimited.',
		'value' => 50,
	),
	'MySQL' => (object) array(
		'host' => array(
			'cname' => 'Host',
			'description' => 'The host on which to connect to MySQL.',
			'value' => 'localhost',
		),
		'user' => array(
			'cname' => 'User',
			'description' => 'The MySQL user.',
			'value' => 'nymph',
		),
		'password' => array(
			'cname' => 'Password',
			'description' => 'The MySQL password.',
			'value' => 'password',
		),
		'database' => array(
			'cname' => 'Database',
			'description' => 'The MySQL database.',
			'value' => 'nymph',
		),
		'prefix' => array(
			'cname' => 'Table Prefix',
			'description' => 'The MySQL table name prefix.',
			'value' => 'nymph_',
		),
	),
	'PostgreSQL' => (object) array(
		'connection_type' => array(
			'cname' => 'Connection Type',
			'description' => 'The type of connection to establish with PostreSQL. Choosing socket will attempt to use the default socket path. You can also choose host and provide the socket path as the host. If you get errors that it can\'t connect, check that your pg_hba.conf file allows the specified user to access the database through a socket.',
			'value' => 'host',
			'options' => array(
				'Host' => 'host',
				'Unix Socket' => 'socket',
			),
		),
		'host' => array(
			'cname' => 'Host',
			'description' => 'The host on which to connect to PostgreSQL.',
			'value' => 'localhost',
		),
		'user' => array(
			'cname' => 'User',
			'description' => 'The PostgreSQL user.',
			'value' => 'nymph',
		),
		'password' => array(
			'cname' => 'Password',
			'description' => 'The PostgreSQL password.',
			'value' => 'password',
		),
		'database' => array(
			'cname' => 'Database',
			'description' => 'The PostgreSQL database.',
			'value' => 'nymph',
		),
		'prefix' => array(
			'cname' => 'Table Prefix',
			'description' => 'The PostgreSQL table name prefix.',
			'value' => 'nymph_',
		),
		'allow_persistent' => array(
			'cname' => 'Allow Persistent Connections',
			'description' => 'Allow connections to persist, if that is how PHP is configured.',
			'value' => true,
		),
	),
);

