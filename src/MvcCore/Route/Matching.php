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

namespace MvcCore\Route;

trait Matching
{
	/**
	 * Return array of matched params, with matched controller and action names,
	 * if route matches request always `\MvcCore\Request::$path` property by `preg_match_all()`.
	 *
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's submethods.
	 *
	 * @param \MvcCore\Request $request Request object instance.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function & Matches (\MvcCore\IRequest & $request) {
		$matchedParams = [];
		$pattern = & $this->matchesGetPattern();
		$subject = $this->matchesGetSubject($request);
		preg_match_all($pattern, $subject, $matchedValues);
		if (isset($matchedValues[0]) && count($matchedValues[0]) > 0) {
			$matchedParams = $this->matchesParseRewriteParams($matchedValues, $this->GetDefaults());
			if (isset($matchedParams[$this->lastPatternParam])) 
				$matchedParams[$this->lastPatternParam] = rtrim(
				$matchedParams[$this->lastPatternParam], '/'
			);
		}
		return $matchedParams;
	}

	protected function & matchesGetPattern () {
		if ($this->match === NULL) {
			$this->initMatchAndReverse();
		} else {
			$this->initReverse();
		}
		return $this->match;
	}

	protected function matchesGetSubject (\MvcCore\IRequest & $request) {
		static $prefixes = NULL;
		if ($prefixes === NULL) $prefixes = [
			static::FLAG_SCHEME_NO		=> '',
			static::FLAG_SCHEME_ANY		=> '//',
			static::FLAG_SCHEME_HTTP	=> 'http://',
			static::FLAG_SCHEME_HTTPS	=> 'https://',
		];
		$schemeFlag = $this->flags[0];
		$hostFlag = $this->flags[1];
		$basePathDefined = FALSE;
		$basePath = '';
		if ($hostFlag >= static::FLAG_HOST_BASEPATH /* 10 */) {
			$hostFlag -= static::FLAG_HOST_BASEPATH;
			$basePath = static::PLACEHOLDER_BASEPATH;
			$basePathDefined = TRUE;
		}
		if ($schemeFlag) {
			$hostPart = '';
			if ($hostFlag == static::FLAG_HOST_HOST /* 1 */) {
				$hostPart = static::PLACEHOLDER_HOST;
			} else if ($hostFlag == static::FLAG_HOST_DOMAIN /* 2 */) {
				$hostPart = $request->GetThirdLevelDomain() . '.' . static::PLACEHOLDER_DOMAIN;
			} else if ($hostFlag == static::FLAG_HOST_TLD /* 3 */) {
				$hostPart = $request->GetThirdLevelDomain() 
					. '.' . $request->GetSecondLevelDomain()
					. '.' . static::PLACEHOLDER_TLD;
			} else if ($hostFlag == static::FLAG_HOST_SLD /* 4 */) {
				$hostPart = $request->GetThirdLevelDomain() 
					. '.' . static::PLACEHOLDER_SLD
					. '.' . $request->GetTopLevelDomain();
			} else if ($hostFlag == static::FLAG_HOST_TLD + static::FLAG_HOST_SLD /* 7 */) {
				$hostPart = $request->GetThirdLevelDomain() 
					. '.' . static::PLACEHOLDER_SLD
					. '.' . static::PLACEHOLDER_TLD;
			}
			if (!$basePathDefined)
				$basePath = $request->GetBasePath();
			$subject = $prefixes[$schemeFlag] . $hostPart . $basePath . $request->GetPath(TRUE);
		} else {
			$subject = ($basePathDefined ? $basePath : '') . $request->GetPath(TRUE);
		}
		if ($this->flags[2]) 
			$subject .= $request->GetQuery(TRUE, TRUE);
		return $subject;
	}

	protected function & matchesParseRewriteParams (& $matchedValues, & $defaults) {
		$controllerName = $this->controller ?: '';
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$matchedParams = [
			'controller'	=>	$toolClass::GetDashedFromPascalCase(str_replace(['_', '\\'], '/', $controllerName)),
			'action'		=>	$toolClass::GetDashedFromPascalCase($this->action ?: ''),
		];
		array_shift($matchedValues); // first item is always matched whole `$request->GetPath()` string.
		foreach ($matchedValues as $key => $matchedValueArr) {
			if (is_numeric($key)) continue;
			$matchedValue = (string) current($matchedValueArr);
			if (!isset($defaults[$key])) 
				$defaults[$key] = NULL;
			if (mb_strlen($matchedValue) === 0)
				$matchedValue = $defaults[$key];
			$matchedParams[$key] = $matchedValue;
		}
		return $matchedParams;
	}
}
