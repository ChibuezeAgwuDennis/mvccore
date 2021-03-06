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

namespace MvcCore\Config;

trait ReadWrite
{
	/**
	 * This is INTERNAL method.
	 * Return always new instance of statically called class, no singleton.
	 * Always called from `\MvcCore\Config::GetSystem()` before system config is read.
	 * This is place where to customize any config creation process,
	 * before it's created by MvcCore framework.
	 * @param array $mergedData Configuration data for all environments.
	 * @param string $configFullPath Config absolute path.
	 * @return \MvcCore\Config|\MvcCore\IConfig
	 */
	public static function CreateInstance (array $mergedData = [], $configFullPath = NULL) {
		/** @var $config \MvcCore\Config */
		$config = new static();
		if ($mergedData)
			$config->mergedData = & $mergedData;
		if ($configFullPath)
			$config->fullPath = $configFullPath;
		return $config;
	}

	/**
	 * Get (optionally cached) system config INI file as `stdClass` or `array`,
	 * placed by default in: `"/App/config.ini"`.
	 * @return \MvcCore\Config|\MvcCore\IConfig|NULL
	 */
	public static function GetSystem () {
		/** @var $config \MvcCore\Config */
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$configClass = $app->GetConfigClass();
		$toolClass = $app->GetToolClass();
		$appRootRelativePath = $configClass::GetSystemConfigPath();
		$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();
		$configFullPath = $toolClass::RealPathVirtual($appRoot . '/' . str_replace(
			'%appPath%', $app->GetAppDir(), ltrim($appRootRelativePath, '/')
		));
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$config = $configClass::getConfigInstance($configFullPath, $configClass, TRUE);
			if ($config) {
				$environment = $app->GetEnvironment();
				if ($environment->IsDetected())
					$configClass::SetUpEnvironmentData($config, $environment->GetName());
			}
			self::$configsCache[$configFullPath] = $config;
		}
		return self::$configsCache[$configFullPath];
	}

	/**
	 * Get (optionally cached) config INI file as `stdClass` or `array`,
	 * placed relatively from application document root.
	 * @param string $appRootRelativePath Any config relative path like `'/%appPath%/website.ini'`.
	 * @return \MvcCore\Config|\MvcCore\IConfig|NULL
	 */
	public static function GetConfig ($appRootRelativePath) {
		/** @var $config \MvcCore\Config */
		$appRootRelativePath = ltrim($appRootRelativePath, '/');
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$appRoot = self::$appRoot ?: self::$appRoot = $app->GetRequest()->GetAppRoot();
		$toolClass = $app->GetToolClass();
		$configFullPath = $toolClass::RealPathVirtual($appRoot . '/' . str_replace(
			'%appPath%', $app->GetAppDir(), $appRootRelativePath
		));
		if (!array_key_exists($configFullPath, self::$configsCache)) {
			$systemConfigClass = $app->GetConfigClass();
			$isSystem = $systemConfigClass::GetSystemConfigPath() === '/' . $appRootRelativePath;
			$config = $systemConfigClass::getConfigInstance($configFullPath, $systemConfigClass, $isSystem);
			if ($config) {
				$environment = $app->GetEnvironment();
				if ($environment->IsDetected())
					$systemConfigClass::SetUpEnvironmentData($config, $environment->GetName());
			}
			self::$configsCache[$configFullPath] = $config;
		}
		return self::$configsCache[$configFullPath];
	}

	/**
	 * Encode all data into string and store it in `\MvcCore\Config::$fullPath`.
	 * @throws \Exception Configuration data was not possible to dump or write.
	 * @return bool
	 */
	public function Save () {
		$rawContent = $this->Dump();
		if ($rawContent === NULL)
			throw new \Exception('Configuration data was not possible to dump.');
		$app = self::$app ?: self::$app = \MvcCore\Application::GetInstance();
		$toolClass = $app->GetToolClass();
		try {
			$toolClass::AtomicWrite(
				$this->fullPath,
				$rawContent,
				'w',	// Open for writing only; place pointer at the beginning and truncate to zero length. If file doesn't exist, create it.
				10,		// Milliseconds to wait before next lock file existence is checked in `while()` cycle.
				5000,	// Maximum milliseconds time to wait before thrown an exception about not possible write.
				30000	// Maximum milliseconds time to consider lock file as operative or as old after some died process.
			);
		} catch (\Exception $ex) {
			throw $ex;
		}
		return TRUE;
	}

	/**
	 * Try to load and parse config file by absolute path.
	 * @param string $configFullPath
	 * @param string $systemConfigClass
	 * @param bool   $isSystemConfig
	 * @return \MvcCore\Config|\MvcCore\IConfig|bool
	 */
	protected static function getConfigInstance ($configFullPath, $systemConfigClass, $isSystemConfig = FALSE) {
		/** @var $config \MvcCore\Config */
		$config = $systemConfigClass::CreateInstance([], $configFullPath);
		if (!file_exists($configFullPath)) {
			$config = NULL;
		} else {
			$config->system = $isSystemConfig;
			if ($config->Read()) {
				$config->mergedData = [];
				$config->currentData = [];
			} else {
				$config = NULL;
			}
		}
		return $config;
	}
}
