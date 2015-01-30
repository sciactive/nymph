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

return (object) [
	'driver' => [
		'cname' => 'Nymph Database Driver',
		'description' => 'The database driver for Nymph to use.',
		'value' => 'MySQL',
	],
	'use_plperl' => [
		'cname' => 'Use PL/Perl Functions',
		'description' => '(Postgres only) This speeds up PCRE regular expression matching ("match" criteria type) a lot, but requires the Perl Procedural Language to be installed on your Postgres server.',
		'value' => false,
	],
	'cache' => [
		'cname' => 'Cache Entities',
		'description' => 'Cache recently retrieved entities to speed up database queries. Uses more memory.',
		'value' => false,
	],
	'cache_threshold' => [
		'cname' => 'Cache Threshold',
		'description' => 'Cache entities after they\'re accessed this many times.',
		'value' => 4,
	],
	'cache_limit' => [
		'cname' => 'Cache Limit',
		'description' => 'The number of recently retrieved entities to cache. If you\'re running out of memory, try lowering this value. 0 means unlimited.',
		'value' => 50,
	],
	'empty_list_error' => [
		'cname' => 'Empty List Returns an Error',
		'description' => 'When querying for multiple entities with NymphREST, if the list is empty, return a 404 error.',
		'value' => false,
	],
	'MySQL' => (object) [
		'host' => [
			'cname' => 'Host',
			'description' => 'The host on which to connect to MySQL. Can include a port, like hostname:port.',
			'value' => 'localhost',
		],
		'port' => [
			'cname' => 'Port',
			'description' => 'The port on which to connect to MySQL.',
			'value' => 3306,
		],
		'user' => [
			'cname' => 'User',
			'description' => 'The MySQL user.',
			'value' => 'nymph',
		],
		'password' => [
			'cname' => 'Password',
			'description' => 'The MySQL password.',
			'value' => 'password',
		],
		'database' => [
			'cname' => 'Database',
			'description' => 'The MySQL database.',
			'value' => 'nymph',
		],
		'prefix' => [
			'cname' => 'Table Prefix',
			'description' => 'The MySQL table name prefix.',
			'value' => 'nymph_',
		],
		'engine' => [
			'cname' => 'Table Engine',
			'description' => 'The MySQL table engine. You can use InnoDB if you are using MySQL >= 5.6.',
			'value' => 'MYISAM',
			'options' => [
				'MyISAM' => 'MYISAM',
				'InnoDB' => 'InnoDB',
			],
		],
	],
	'PostgreSQL' => (object) [
		'connection_type' => [
			'cname' => 'Connection Type',
			'description' => 'The type of connection to establish with PostreSQL. Choosing socket will attempt to use the default socket path. You can also choose host and provide the socket path as the host. If you get errors that it can\'t connect, check that your pg_hba.conf file allows the specified user to access the database through a socket.',
			'value' => 'host',
			'options' => [
				'Host' => 'host',
				'Unix Socket' => 'socket',
			],
		],
		'host' => [
			'cname' => 'Host',
			'description' => 'The host on which to connect to PostgreSQL.',
			'value' => 'localhost',
		],
		'port' => [
			'cname' => 'Port',
			'description' => 'The port on which to connect to PostgreSQL.',
			'value' => 5432,
		],
		'user' => [
			'cname' => 'User',
			'description' => 'The PostgreSQL user.',
			'value' => 'nymph',
		],
		'password' => [
			'cname' => 'Password',
			'description' => 'The PostgreSQL password.',
			'value' => 'password',
		],
		'database' => [
			'cname' => 'Database',
			'description' => 'The PostgreSQL database.',
			'value' => 'nymph',
		],
		'prefix' => [
			'cname' => 'Table Prefix',
			'description' => 'The PostgreSQL table name prefix.',
			'value' => 'nymph_',
		],
		'allow_persistent' => [
			'cname' => 'Allow Persistent Connections',
			'description' => 'Allow connections to persist, if that is how PHP is configured.',
			'value' => true,
		],
	],
];

