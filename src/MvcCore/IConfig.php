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

namespace MvcCore;

//include_once(__DIR__.'/../Application.php');

/**
 * Responsibility - reading config file(s), detecting environment in system config.
 * - Config file(s) reading:
 *   - Reading any `config.ini` file by relative path.
 *   - Parsing and typing ini data into `stdClass|array` by key types or typing
 *	 ini values into `int|float|bool|string` for all other detected primitives.
 * - Environment management:
 *   - Simple environment name detection by comparing server and client ip.
 *   - Environment name detection by config records about computer name or ip.
 */
interface IConfig
{
	/**
	 * Development environment.
	 */
	const ENVIRONMENT_DEVELOPMENT = 'dev';

	/**
	 * Common team testing environment.
	 */
	const ENVIRONMENT_ALPHA = 'alpha';

	/**
	 * Release testing environment.
	 */
	const ENVIRONMENT_BETA = 'beta';

	/**
	 * Release environment.
	 */
	const ENVIRONMENT_PRODUCTION = 'production';

	/**
	 * Return `TRUE` if environment is `"dev"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsDevelopment ($autoloadSystemConfig = FALSE);

	/**
	 * Return `TRUE` if environment is `"beta"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsBeta ($autoloadSystemConfig = FALSE);

	/**
	 * Return `TRUE` if environment is `"alpha"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsAlpha ($autoloadSystemConfig = FALSE);

	/**
	 * Return `TRUE` if environment is `"production"`.
	 * @param bool $autoloadSystemConfig If `TRUE`, environment will be detected by loaded system config.
	 * @return bool
	 */
	public static function IsProduction ($autoloadSystemConfig = FALSE);

	/**
	 * Get environment name as string,
	 * defined by constants: `\MvcCore\IConfig::ENVIRONMENT_<environment>`.
	 * @return string
	 */
	public static function GetEnvironment ();

	/**
	 * Set environment name as string,
	 * defined by constants: `\MvcCore\IConfig::ENVIRONMENT_<environment>`.
	 * @param string $environment
	 * @return string
	 */
	public static function SetEnvironment ($environment = \MvcCore\IConfig::ENVIRONMENT_PRODUCTION);

	/**
	 * Get system config relative path from app root.
	 * @return string
	 */
	public static function GetSystemConfigPath ();
	
	/**
	 * Set system config relative path from app root.
	 * @param string $systemConfigPath
	 * @return string
	 */
	public static function SetSystemConfigPath ($systemConfigPath);

	/**
	 * This is INTERNAL method.
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is loaded.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @param array $data Configuration raw data.
	 * @param string $appRootRelativePath Relative config path from app root.
	 * @return \MvcCore\IConfig
	 */
	public static function & CreateInstance (array $data = [], $appRootRelativePath = NULL);

	/**
	 * Get cached singleton system config INI file as `stdClass`es and `array`s,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \stdClass|array|boolean
	 */
	public static function & GetSystem ();

	/**
	 * Get cached config INI file as `stdClass`es and `array`s,
	 * placed relatively from application document root.
	 * @param string $appRootRelativePath Any config relative path like `'/%appPath%/website.ini'`.
	 * @return \stdClass|array|boolean
	 */
	public static function & GetConfig ($appRootRelativePath);

	/**
	 * Load config file and return `TRUE` for success or `FALSE` in failure.
	 * - Second environment value setup:
	 *   - Only if `$this->system` property is defined as `TRUE`.
	 *   - By defined IPs or computer names in `environments` section.
	 * - Load only sections for current environment name.
	 * - Retype all `raw string` values into `array`, `float`, `int` or `boolean` types.
	 * - Retype whole values level into `\stdClass`, if there are no numeric keys.
	 * @param string $fullPath
	 * @param bool $systemConfig
	 * @return bool
	 */
	public function Read ($fullPath, $systemConfig = FALSE);

	/**
	 * Encode all data into string and store it in `$this->fullPath` property.
	 * @return bool
	 */
	public function & Save ();

	/**
	 * Get internal array store as reference.
	 * @return array
	 */
	public function & GetData ();

	/**
	 * Get not defined property from `$this->data` array store, 
	 * if there is nothing, return `NULL`.
	 * @param string $key 
	 * @return mixed
	 */
	public function __get ($key);

	/**
	 * Store not defined property inside `$this->data` array store.
	 * @param string $key 
	 * @return mixed
	 */
	public function __set ($key, $value);
	
	/**
	 * Magic function triggered by: `isset($cfg->key);`.
	 * @param string $key
	 * @return bool
	 */
	public function __isset ($key);

	/**
	 * Magic function triggered by: `unset($cfg->key);`.
	 * @param string $key
	 * @return void
	 */
	public function __unset ($key);
	
	/**
	 * Get how many records is in the config internal store.
	 * Example: `count($cfg);`
	 * @return int
	 */
	public function count ();

	/**
	 * Return new iterator from the internal data store 
	 * to use config instance in for each cycle.
	 * Example: `foreach ($cfg as $key => $value) { var_dump([$key, $value]); }`
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator ();

	/**
	 * Set the value at the specified index in the internal store.
	 * Example: `$cfg['any'] = 'thing';`
	 * @param mixed $offset 
	 * @param mixed $value 
	 */
	public function offsetSet ($offset, $value);

	/**
	 * Get the value at the specified index from the internal store.
	 * Example: `$thing = $cfg['any'];`
	 * @param mixed $offset 
	 * @param mixed $value 
	 */
    public function offsetGet ($offset);

    /**
     * Return whether the requested index exists in the internal store.
	 * Example: `isset($cfg['any']);`
     * @param mixed $offset 
     * @return bool
     */
    public function offsetExists ($offset);

    /**
     * Unset the value at the specified index in the internal store.
	 * Example: `unset($cfg['any']);`
     * @param mixed $offset 
     */
    public function offsetUnset ($offset);
}
