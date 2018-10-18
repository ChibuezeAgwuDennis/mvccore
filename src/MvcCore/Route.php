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

/**
 * Responsibility - describing request(s) to match and reversely build url addresses.
 * - Describing request to match it (read more about properties).
 * - Matching request by given request object, see `\MvcCore\Route::Matches()`.
 * - Completing url address by given params array, see `\MvcCore\Route::Url()`.
 *
 * Main Properties:
 * - `$Pattern`
 *   Required, if you have not configured `\MvcCore\Route::$match` and
 *   `\MvcCore\Route::$reverse` property instead. Very basic URL address
 *   form to match and parse rewrited params by it. Address to parse
 *   and prepare `\MvcCore\Route::$match` property and `\MvcCore\Route::$reverse`
 *   property. automaticly in `\MvcCore\Route::Prepare();` call.
 * - `$Match`
 *   Required together with `\MvcCore\Route::$reverse` property, if you
 *   have not configured `\MvcCore\Route::$pattern` property instead.
 *   This property is always used to match request by `\MvcCore\Request::Path`
 *   by classic PHP regualar expression matching by `preg_match_all();`.
 * - `$Reverse`
 *   Required together with `\MvcCore\Route::$match` property, if you
 *   have not configured `\MvcCore\Route::$pattern` property instead.
 *   This property is always used to complete url address by called params
 *   array and by this string with rewrite params replacements inside.
 * - `$Controller`
 *   Required, if there is no `controller` param inside `\MvcCore\Route::$pattern`
 *   or inside `\MvcCore\Route::$match property`. Controller class name to dispatch
 *   in pascal case form, namespaces and including slashes as namespace delimiters.
 * - `$Action`
 *   Required, if there is no `action` param inside `\MvcCore\Route::$pattern`
 *   or inside `\MvcCore\Route::$match property`. Public method in controller
 *   in pascal case form, but in controller named as `public function <CoolName>Action () {...`.
 * - `$Name`
 *   Not required, if you want to create url addresses always by `Controller:Action`
 *   named records. It could be any string, representing route custom name to
 *   complete url address by that name inside your application.
 * - `$Defaults`
 *   Not required, matched route params default values and query params default values.
 *   Last entry in array may be used for property `\MvcCore\Route::$lastPatternParam`
 *   describing last rewrited param inside match pattern to be automaticly trimmed
 *   from right side for possible address trailing slash in route matched moment.
 * - `$Constraints`
 *   not required, array with param names and their custom regular expression
 *   matching rules. If no constraint string for any param defined, there is used
 *   for all rewrited params default constraint rule to match everything except next slash.
 *   Default static property for matching rule shoud be changed here:
 *   - by default: `\MvcCore\Route::$DefaultConstraint = '[^/]*';`
 */
class Route implements IRoute
{
	use \MvcCore\Route\Props;
	use \MvcCore\Route\GettersSetters;
	use \MvcCore\Route\Instancing;
	use \MvcCore\Route\Matching;
	use \MvcCore\Route\UrlBuilding;
	use \MvcCore\Route\InternalInits;

	/**
	 * Internal method for `\MvcCore\Route::initMatch();` processing,
	 * always called from `\MvcCore\Router::Matches();` request routing.
	 *
	 * Go throught given route pattern value and try to search for
	 * any url param occurances inside, like `<name>` or `<color*>`.
	 * Return and array with describing records for each founded param.
	 * Example:
	 *	Input (`$pattern`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`array(
	 *			array(
	 *				"name",		// param name
	 *				"<name>",	// param name for regex match pattern
	 *				16,			// `"<name>"` occurance position in escaped pattern for match
	 *				15,			// `"<name>"` occurance position in original pattern for reverse
	 *				6,			// `"<name>"` string length
	 *				FALSE		// greedy param star flag
	 *			),
	 *			array(
	 *				"color",	// param name
	 *				"<color>",	// param name for regex match pattern
	 *				23,			// `"<color*>"` occurance position in escaped pattern for match
	 *				22,			// `"<color*>"` occurance position in original pattern for reverse
	 *				7,			// `"<color>"` string length
	 *				TRUE		// greedy param star flag
	 *			)
	 *		);
	 * @param string $pattern Route pattern.
	 * @throws \LogicException Thrown, when founded any other param after greedy param.
	 * @return array Match pattern sring and statistics about founded params occurances.
	 */
	/*protected function _old_parsePatternParams (& $pattern) {
		$patternParams = [];
		$reverseIndex = 0;
		$matchIndex = 0;
		// escape all regular expression special characters before parsing except `<` and `>`:
		$match = addcslashes($pattern, "#[](){}-?!=^$.+|:\\");
		$patternLength = mb_strlen($pattern);
		$greedyCatched = FALSE;
		while ($reverseIndex < $patternLength) {
			// complete pattern opening and closing param positions
			$reverseParamOpenPos = mb_strpos($pattern, '<', $reverseIndex);
			if ($reverseParamOpenPos === FALSE) break;
			$reverseParamClosePos = mb_strpos($pattern, '>', $reverseParamOpenPos);
			if ($reverseParamClosePos === FALSE) break;
			$reverseParamClosePos += 1;
			// complete match opening and closing param positions
			$matchParamOpenPos = mb_strpos($match, '<', $matchIndex);
			$matchParamClosePos = mb_strpos($match, '>', $matchParamOpenPos);
			$matchParamClosePos += 1;
			// complete param section length
			$reverseLength = $reverseParamClosePos - $reverseParamOpenPos;
			// complete param name
			$paramName = mb_substr($pattern, $reverseParamOpenPos + 1, $reverseParamClosePos - $reverseParamOpenPos - 2);
			// complete greedy flag by star character inside param name
			$greedy = mb_strpos($paramName, '*');
			if ($greedy !== FALSE) {
				if ($greedyCatched) throw new \LogicException(
					"[".__CLASS__."] Route could have greedy `<param_name*>` with star "
					."to include slashes only as the very last parameter ($this)."
				);
				$greedyCatched = TRUE;
				$paramName = str_replace('*', '', $paramName);
			}
			$patternParams[] = [
				$paramName, 
				'<'.$paramName.'>', 
				$matchParamOpenPos, 
				$reverseParamOpenPos, 
				$reverseLength, 
				$greedyCatched,
				$match,
				$pattern
			];
			// shift parsing indexes
			$reverseIndex = $reverseParamClosePos;
			$matchIndex = $matchParamClosePos;
		}
		return [$match, $patternParams];
	}*/

	/**
	 * Internal method for `\MvcCore\Route::initMatch();` processing,
	 * always called from `\MvcCore\Router::Matches();` request routing.
	 *
	 * Compile and return value for `\MvcCore\Route::$match` pattern,
	 * (optionaly by `$compileReverse` also for `\MvcCore\Route::$reverse`)
	 * from escaped `\MvcCore\Route::$pattern` and given params statistics
	 * and from configured route constraints for regular expression:
	 * - If pattern starts with slash `/`, set automaticly into
	 *   result regular expression start rule (`#^/...`).
	 * - If there is detected trailing slash in match pattern,
	 *   set automaticly into result regular expression end rule
	 *   for trailing slash `...(?=/$|$)#` or just only end rule `...$#`.
	 * - If there is detected any last param with possible trailing slash
	 *   after, complete `\MvcCore\Route::$lastPatternParam` property
	 *   by this detected param name.
	 *
	 * Example:
	 *	Input (`$matchPattern`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Input (`$patternParams`):
	 *		`array(
	 *			array(
	 *				"name",		// param name
	 *				"<name>",	// param name for regex match pattern
	 *				16,			// `"<name>"` occurance position in escaped pattern for match
	 *				15,			// `"<name>"` occurance position in original pattern for reverse
	 *				6,			// `"<name>"` string length
	 *				FALSE		// greedy param star flag
	 *			),
	 *			array(
	 *				"color",	// param name
	 *				"<color>",	// param name for regex match pattern
	 *				23,			// `"<color*>"` occurance position in escaped pattern for match
	 *				22,			// `"<color*>"` occurance position in original pattern for reverse
	 *				7,			// `"<color>"` string length
	 *				TRUE		// greedy param star flag
	 *			)
	 *		);`
	 *	Input (`$compileReverse`):
	 *		`TRUE`
	 *	Input (`$this->constraints`):
	 *		`array(
	 *			"name"	=> "[^/]*",
	 *			"color"	=> "[a-z]*",
	 *		);`
	 *	Output:
	 *		`array(
	 *			"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"/products-list/<name>/<color>"
	 *		)`
	 * @param string $pattern
	 * @param string $matchPattern
	 * @param \array[] $patternParams
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \string[]
	 */
	/*protected function _old_initMatchAndReverse ($patterns, & $patternParams, $compileReverse, $localization = NULL) {
		$trailingSlash = FALSE;
		list($matchPattern,) = $patterns;
		if ($patternParams) {
			list ($matchPattern, $reverse, $reverseParams, $trailingSlash) = $this->initMatchAndReverseProcessParams(
				$patterns, $patternParams, $compileReverse, $localization
			);
		} else {
			if ($matchPattern == '/') {
				$reverse = '/';
				$reverseParams = [];
			} else {
				$lengthWithoutLastChar = mb_strlen($matchPattern) - 1;
				if (mb_strrpos($matchPattern, '/') === $lengthWithoutLastChar) {
					$matchPattern = mb_substr($matchPattern, 0, $lengthWithoutLastChar);
				}
				$trailingSlash = TRUE;
				if ($compileReverse) {
					$reverse = $this->GetPattern($localization);
					$reverseParams = [];
				} else {
					$reverse = '';
				}
			}
		}
		if ($compileReverse) {
			$this->initFlagsByPatternOrReverse($reverse);
			$this->setReverseParams($reverseParams, $localization);
		}
		return [
			'#' . (mb_strpos($matchPattern, '/') === 0 ? '^' : '') . $matchPattern
				. ($trailingSlash ? '(?=/$|$)' : '$') . '#',
			$reverse
		];
	}*/

	/*protected function _old_initMatchAndReverseProcessParams (& $patterns, & $patternParams, $compileReverse, $localization = NULL) {
		$constraints = $this->GetConstraints($localization);
		list($matchPattern, $reversePattern) = $patterns;
		$defaultConstraint = static::$DefaultConstraint;
		$trailingSlash = FALSE;
		$reverseParams = [];
		$reverse = '';
		$match = mb_substr($matchPattern, 0, $patternParams[0][2]);
		if ($compileReverse) {
			$reverse = mb_substr($reversePattern, 0, $patternParams[0][3]);
			$reverseParams = [];
		}
		foreach ($patternParams as $i => $patternParam) {
			list($paramName, $paramSection, $matchIndex, $reverseIndex, $length, $greedy) = $patternParam;
			$customConstraint = isset($constraints[$paramName]);
			if (!$customConstraint && $greedy) $defaultConstraint = '.*';
			if (isset($patternParams[$i + 1])) {
				// if there is next matched param:
				$nextRecordIndexes = $patternParams[$i + 1];
				$matchNextItemStart = $nextRecordIndexes[2];
				$reverseNextItemStart = $nextRecordIndexes[3];
				$matchStart = $matchIndex + $length;
				$reverseStart = $reverseIndex + $length;
				$matchUrlPartBeforeNext = mb_substr(
					$matchPattern, $matchStart, $matchNextItemStart - $matchStart
				);
				$reverseUrlPartBeforeNext = mb_substr(
					$reversePattern, $reverseStart, $reverseNextItemStart - $reverseStart
				);
			} else {
				// else if this param is the last one:
				$matchUrlPartBeforeNext = mb_substr($matchPattern, $matchIndex + $length);
				$reverseUrlPartBeforeNext = mb_substr($reversePattern, $reverseIndex + $length);
				// if there is nothing more in url or just only a slash char `/`:
				if ($matchUrlPartBeforeNext == '' || $matchUrlPartBeforeNext == '/') {
					$trailingSlash = TRUE;
					$this->lastPatternParam = $paramName;
					$matchUrlPartBeforeNext = '';
				};
			}
			if ($customConstraint) {
				$constraint = $constraints[$paramName];
			} else {
				$constraint = $defaultConstraint;
			}
			$match .= '(?' . $paramSection . $constraint . ')' . $matchUrlPartBeforeNext;
			if ($compileReverse) {
				$reverse .= $paramSection . $reverseUrlPartBeforeNext;
				$reverseParams[$paramName] = [$reverseIndex, $reverseIndex + $length];
			}
		}
		return [$match, $reverse, $reverseParams, $trailingSlash];
	}*/

	/**
	 * Internal method, always called from `\MvcCore\Router::Matches();` request routing,
	 * when route has been matched and when there is still no `\MvcCore\Route::$reverseParams`
	 * defined (`NULL`). It means that matched route has been defined by match and reverse
	 * patterns, because there was no pattern property parsing to prepare values bellow before.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return string
	 */
	/*protected function _old_initReverse ($localization = NULL) {
		$index = 0;
		$reverse = $this->GetReverse($localization);
		if ($reverse === NULL && $this->GetPattern($localization) !== NULL) {
			list(, $reverse) = $this->initMatch($localization);
			return $reverse;
		}
		$reverseParams = [];
		$closePos = -1;
		$paramName = '';
		while (TRUE) {
			$openPos = mb_strpos($reverse, '<', $index);
			if ($openPos === FALSE) break;
			$openPosPlusOne = $openPos + 1;
			$closePos = mb_strpos($reverse, '>', $openPosPlusOne);
			if ($closePos === FALSE) break;
			$index = $closePos + 1;
			$paramName = mb_substr($reverse, $openPosPlusOne, $closePos - $openPosPlusOne);
			$reverseParams[$paramName] = [$openPos, $openPos + ($index - $openPos)];
		}
		$this->setReverseParams($reverseParams, $localization);
		// Init `\MvcCore\Route::$lastPatternParam`.
		// Init that property only if this function is
		// called from `\MvcCore\Route::Matches()`, after current route has been matched
		// and also when there were configured for this route `\MvcCore\Route::$match`
		// value and `\MvcCore\Route::$reverse` value together:
		if ($this->lastPatternParam === NULL && $paramName) {
			$reverseLengthMinusTwo = mb_strlen($reverse) - 2;
			$lastCharIsSlash = mb_substr($reverse, $reverseLengthMinusTwo, 1) == '/';
			$closePosPlusOne = $closePos + 1;
			if (
				// if pattern ends with param section closing bracket `...param>`
				$closePosPlusOne === $reverseLengthMinusTwo + 1 || 
				// or if last pattern char is slash after closed param section `...param>/`
				($lastCharIsSlash && $closePosPlusOne === $reverseLengthMinusTwo)
			) {
				$this->lastPatternParam = $paramName;
			}
		}
		$this->initFlagsByPatternOrReverse($reverse);
		return $reverse;
	}*/

}
