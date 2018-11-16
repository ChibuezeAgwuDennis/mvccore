<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Model;

trait Props
{
	/**
	 * `\PDO` connection arguments.
	 *
	 * If you need to reconfigure connection string for any other special
	 * `\PDO` database implementation or you specific needs, patch this array
	 * in extended application base model class in base `__construct()` method by:
	 *	 `static::$connectionArguments = array_merge(static::$connectionArguments, array(...));`
	 * or by:
	 *	 `static::$connectionArguments['driverName']['dsn'] = '...';`
	 *
	 * Every key in this field is driver name, so you can use usual `\PDO` drivers:
	 * - `mysql`, `sqlite`, `sqlsrv` (mssql), `firebird`, `ibm`, `informix`, `4D`
	 * Following drivers should be used with defaults, no connection args from here are necessary:
	 * - `oci`, `pgsql`, `cubrid`, `sysbase`, `dblib`
	 *
	 * Every value in this configuration field should be defined as:
	 * - `dsn`		- connection query as first `\PDO` contructor argument
	 *				  with database config replacements.
	 * - `auth`		- if required to use database credentials for connecting or not.
	 * - `fileDb`	- if database if file database or not.
	 * - `options`	. any additional arguments array or empty array.
	 * @var array
	 */
	protected static $connectionArguments = [
		'4D'			=> [
			'dsn'		=> '{driver}:host={host};charset=UTF-8',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'firebird'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database};charset=UTF8',
			'auth'		=> TRUE,
			'fileDb'	=> TRUE,
			'options'	=> []
		],
		'ibm'			=> [
			'dsn'		=> 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE={database};HOSTNAME={host};PORT={port};PROTOCOL=TCPIP;',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'informix'		=> [
			'dsn'		=> '{driver}:host={host};service={service};database={database};server={server};protocol={protocol};EnableScrollableCursors=1',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'mysql'			=> [
			'dsn'		=> '{driver}:host={host};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [
				'\PDO::ATTR_EMULATE_PREPARES'		=> FALSE, // let params inserting on database
				'\PDO::MYSQL_ATTR_MULTI_STATEMENTS'	=> TRUE,
				'\PDO::MYSQL_ATTR_INIT_COMMAND'		=> "SET NAMES 'UTF8'",
			],
		],
		'sqlite'		=> [
			'dsn'		=> '{driver}:{database}',
			'auth'		=> FALSE,
			'fileDb'	=> TRUE,
			'options'	=> [],
		],
		'sqlsrv'		=> [
			'dsn'		=> '{driver}:Server={host};Database={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
		'default'		=> [
			'dsn'		=> '{driver}:host={host};dbname={database}',
			'auth'		=> TRUE,
			'fileDb'	=> FALSE,
			'options'	=> [],
		],
	];

	/**
	 * Default database connection name/index, in config ini defined in section `db.default = name`.
	 * In extended classes - use this for connection name/index of current model if different.
	 * @var string|int|NULL
	 */
	protected static $connectionName = NULL;

	/**
	 * `\PDO` connections array, keyed by connection indexes from system config.
	 * @var \PDO[]
	 */
	protected static $connections = [];

	/**
	 * Instance of current class, if there is necessary to use it as singleton.
	 * @var \MvcCore\Model[]|\MvcCore\IModel[]
	 */
	protected static $instances = [];

	/**
	 * System config sections array with `\stdClass` objects, keyed by connection indexes.
	 * @var \stdClass[]
	 */
	protected static $configs = NULL;

	/**
	 * Automatically initialize config, db connection and resource class.
	 * @var bool
	 */
	protected $autoInit = TRUE;

	/**
	 * `\PDO` instance.
	 * @var \PDO
	 */
	protected $db;

	/**
	 * System config section for database under called connection index in constructor.
	 * @var \stdClass
	 */
	protected $config;

	/**
	 * Resource model class with SQL statements.
	 * @var \MvcCore\Model|\MvcCore\IModel
	 */
	protected $resource;

	/**
	 * Originaly declared internal model properties to protect their
	 * possible overwriting by `__set()` or `__get()` magic methods.
	 * @var array
	 */
	protected static $protectedProperties = [
		'autoInit'	=> 1,
		'db'		=> 1,
		'config'	=> 1,
		'resource'	=> 1,
	];
}
