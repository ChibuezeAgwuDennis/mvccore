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
 * Responsibility - static helpers for core classes inheritance, string conversions and JSON.
 * - Static translation functions (supports containing folder or file path):
 *   - `"dashed-case"		=> "PascalCase"`
 *   - `"PascalCase"		=> "dashed-case"`
 *   - `"unserscore_case"	=> "PascalCase"`
 *   - `"PascalCase"		=> "unserscore_case"`
 * - Static functions to safely encode/decode JSON.
 * - Static functions to get client/server IPs.
 * - Static functions to get system temp directory.
 * - Static functions to safely invoke dangerous call.
 * - Static functions to write into file by one process only.
 * - Static function to check core classes inheritance.
 */
interface ITool
{
	/**
	 * Convert all strings `"from" => "to"`:
	 * - `"MyCustomValue"				=> "my-custom-value"`
	 * - `"MyCustom/Value/InsideFolder"	=> "my-custom/value/inside-folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetDashedFromPascalCase ($pascalCase = '');

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my-custom-value"					=> "MyCustomValue"`
	 * - `"my-custom/value/inside-folder"	=> "MyCustom/Value/InsideFolder"`
	 * @param string $dashed
	 * @return string
	 */
	public static function GetPascalCaseFromDashed ($dashed = '');

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"MyCutomValue"				=> "my_custom_value"`
	 * - `"MyCutom/Value/InsideFolder"	=> "my_custom/value/inside_folder"`
	 * @param string $pascalCase
	 * @return string
	 */
	public static function GetUnderscoredFromPascalCase ($pascalCase = '');

	/**
	 * Convert all string `"from" => "to"`:
	 * - `"my_custom_value"					=> "MyCutomValue"`
	 * - `"my_custom/value/inside_folder"	=> "MyCutom/Value/InsideFolder"`
	 * @param string $underscored
	 * @return string
	 */
	public static function GetPascalCaseFromUnderscored ($underscored = '');

	/**
	 * Safely encode json string from php value.
	 * @param mixed $data
	 * @param int   $flags
	 * JSON encoding flags used by default:
	 *  - `JSON_HEX_TAG`:
	 *     All < and > are converted to \u003C and \u003E. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_AMP`:
	 *    All & are converted to \u0026. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_APOS`:
	 *    All ' are converted to \u0027. Available as of PHP 5.3.0.
	 *  - `JSON_HEX_QUOT`:
	 *    All " are converted to \u0022. Available as of PHP 5.3.0.
	 *  - `JSON_NUMERIC_CHECK`:
	 *    Encodes numeric strings as numbers. Available as of PHP 5.3.3.
	 *  - `JSON_UNESCAPED_SLASHES`:
	 *    Don't escape /. Available as of PHP 5.4.0.
	 *  - `JSON_PRESERVE_ZERO_FRACTION`:
	 *    Ensures that float values are always encoded as a float value. Available as of PHP 5.6.6.
	 * Possible JSON encoding flags to add:
	 *  - `JSON_PRETTY_PRINT`:
	 *    Encode JSON into pretty print syntax, Available as of PHP 5.4.0.
	 *  - `JSON_UNESCAPED_UNICODE`:
	 *    Encode multibyte Unicode characters literally (default is to escape as \uXXXX). Available as of PHP 5.4.0.
	 *  - `JSON_UNESCAPED_LINE_TERMINATORS`:
	 *    The line terminators are kept unescaped when JSON_UNESCAPED_UNICODE
	 *    is supplied. It uses the same behaviour as it was before PHP 7.1
	 *    without this constant. Available as of PHP 7.1.0.	The following
	 *    constants can be combined to form options for json_decode()
	 *    and json_encode().
	 *  - `JSON_INVALID_UTF8_IGNORE`:
	 *    Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param int    $depth Set the maximum depth. Must be greater than zero, default: 512.
	 * @throws \RuntimeException|\JsonException JSON encoding error.
	 * @return string
	 */
	public static function EncodeJson ($data, $flags = 0, $depth = 512);

	/**
	 * Safely decode json string into php `stdClass/array`.
	 * @param string $jsonStr
	 * @param int    $flags
	 * - `JSON_BIGINT_AS_STRING`:
	 *    Decodes large integers as their original string value. Available as of PHP 5.4.0.
	 * - `JSON_OBJECT_AS_ARRAY`:
	 *   Decodes JSON objects as PHP array. This option can be added automatically by calling json_decode() with
	 *   the second parameter equal to TRUE. Available as of PHP 5.4.0.
	 * - `JSON_INVALID_UTF8_IGNORE`:
	 *   Ignore invalid UTF-8 characters. Available as of PHP 7.2.0.
	 *  - `JSON_INVALID_UTF8_SUBSTITUTE`:
	 *    Convert invalid UTF-8 characters to \0xfffd (Unicode Character
	 *    'REPLACEMENT CHARACTER') Available as of PHP 7.2.0.
	 *  - `JSON_THROW_ON_ERROR`:
	 *    Throws JsonException if an error occurs instead of setting the global
	 *    error state that is retrieved with json_last_error() and
	 *    json_last_error_msg(). JSON_PARTIAL_OUTPUT_ON_ERROR takes precedence
	 *    over JSON_THROW_ON_ERROR. Available as of PHP 7.3.0.
	 * @param int    $depth User specified recursion depth, default: 512.
	 * @throws \RuntimeException|\JsonException JSON decoding error.
	 * @return object
	 */
	public static function DecodeJson ($jsonStr, $flags = 0, $depth = 512);

	/**
	 * Recognize if given string is JSON or not without JSON parsing.
	 * @see https://www.ietf.org/rfc/rfc4627.txt
	 * @param string $jsonStr
	 * @return bool
	 */
	public static function IsJsonString ($jsonStr);

	/**
	 * Recognize if given string is query string without parsing.
	 * It recognizes query strings like:
	 * - `key1=value1`
	 * - `key1=value1&`
	 * - `key1=value1&key2=value2`
	 * - `key1=value1&key2=value2&`
	 * - `key1=&key2=value2`
	 * - `key1=value&key2=`
	 * - `key1=value&key2=&key3=`
	 * ...
	 * @param string $jsonStr
	 * @return bool
	 */
	public static function IsQueryString ($queryStr);

	/**
	 * Returns the OS-specific directory for temporary files.
	 * @return string
	 */
	public static function GetSystemTmpDir ();

	/**
	 * Safely invoke internal PHP function with it's own error handler.
	 * Error handler accepts arguments:
	 * - `string $errMessage`	- Error message.
	 * - `int $errLevel`		- Level of the error raised.
	 * - `string $errFile`		- Optional, full path to error file name where error was raised.
	 * - `int $errLine`			- Optional, The error file line number.
	 * - `array $errContext`	- Optional, array that points to the active symbol table at the
	 *							  point the error occurred. In other words, `$errContext` will contain
	 *							  an array of every variable that existed in the scope the error
	 *							  was triggered in. User error handler must not modify error context.
	 *							  Warning: This parameter has been DEPRECATED as of PHP 7.2.0.
	 *							  Relying on it is highly discouraged.
	 * If the custom error handler returns `FALSE`, normal internal error handler continues.
	 * This function is very PHP specific. It's proudly used from Nette Framework, optimized for PHP 5.4+:
	 * https://github.com/nette/utils/blob/b623b2deec8729c8285d269ad991a97504f76bd4/src/Utils/Callback.php#L63-L84
	 * @param string|callable $internalFnOrHandler
	 * @param array $args
	 * @param callable $onError
	 * @return mixed
	 */
	public static function Invoke ($internalFnOrHandler, array $args, callable $onError);

	/**
	 * Write or append file content by only one single PHP process.
	 * @see http://php.net/manual/en/function.flock.php
	 * @see http://php.net/manual/en/function.set-error-handler.php
	 * @see http://php.net/manual/en/function.clearstatcache.php
	 * @param string $fullPath File full path.
	 * @param string $content String content to write.
	 * @param string $writeMode PHP `fopen()` second argument flag, could be `w`, `w+`, `a`, `a+` etc...
	 * @param int $lockWaitMilliseconds Milliseconds to wait before next lock file existence is checked in `while()` cycle.
	 * @param int $maxLockWaitMilliseconds Maximum milliseconds time to wait before thrown an exception about not possible write.
	 * @param int $oldLockMillisecondsTolerance Maximum milliseconds time to consider lock file as operative or as old after some died process.
	 * @throws \Exception
	 * @return bool
	 */
	public static function AtomicWrite (
		$fullPath,
		$content,
		$writeMode = 'w',
		$lockWaitMilliseconds = 100,
		$maxLockWaitMilliseconds = 5000,
		$oldLockMillisecondsTolerance = 30000
	);

	/**
	 * PHP `realpath()` function without checking file/directory existence.
	 * @see https://www.php.net/manual/en/function.realpath.php
	 * @param string $path
	 * @return string
	 */
	public static function RealPathVirtual ($path);

	/**
	 * Check if given class implements given interface, else throw an exception.
	 *
	 * @param string $testClassName Full test class name.
	 * @param string $interfaceName Full interface class name.
	 * @param bool $checkStaticMethods Check implementation of all static methods by interface static methods.
	 * @param bool $throwException If `TRUE`, throw an exception if something is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassInterface ($testClassName, $interfaceName, $checkStaticMethods = FALSE, $throwException = TRUE);

	/**
	 * Check if given class implements given trait, else throw an exception.
	 * @param string $testClassName Full test class name.
	 * @param string $traitName Full trait class name.
	 * @param bool $throwException If `TRUE`, throw an exception if trait is not implemented or if `FALSE` return `FALSE` only.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function CheckClassTrait ($testClassName, $traitName, $throwException = TRUE);
}
