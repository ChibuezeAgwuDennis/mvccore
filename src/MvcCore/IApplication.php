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
 * Responsibility - singleton, instancing all core classes and handling request.
 * - Global store and managing singleton application instance.
 * - Main application objects container (request, response, controller, etc.).
 * - MvcCore compile mode managing (single file mode, php, phar, or no package).
 * - Global store for all main core class names, to use them as modules,
 *   to be changed any time (request class, response class, debug class, etc.).
 * - Dispatching application http request/response (`\MvcCore\Application::Dispatch();`):
 *   - Completing request and response.
 *   - Calling pre/post handlers.
 *   - Controller/action dispatching.
 *   - Error handling and error responses.
 */
interface IApplication
{
	/***********************************************************************************
	 *					   `\MvcCore\Application` - Constants						*
	 ***********************************************************************************/

	/**
	 * MvcCore application mode describing that the application is compiled in <b>ONE BIG PHP FILE</b>.
	 * In PHP app mode should be packed php files or any asset files - PHTML templates, INI files
	 * or any static files. Unknown asset files or binary files are included as binary or base64 string.
	 * This mode has always best speed, because it should not work with hard drive if you don't want to.
	 * Only with many or with large asset files, there may be greater demands on memory and processor,
	 * which shouldn't be good for your application. Be aware to do that, if you have low memory limits.
	 * Result application packed in PHP mode has special `\Packager_Php_Wrapper` class included
	 * before any application content. This special class handles allowed file operations and assets
	 * as binary or base64 encoded. Everything should be configured before PHP packing.
	 * This mode has always four sub-modes started with PHP substring. All PHP package modes are:
	 * - `\Packager_Php_Wrapper::PHP_PRESERVE_HDD`
	 * - `\Packager_Php_Wrapper::PHP_PRESERVE_PACKAGE`
	 * - `\Packager_Php_Wrapper::PHP_STRICT_HDD`
	 * - `\Packager_Php_Wrapper::PHP_STRICT_PACKAGE`
	 * So to check if app is in PHP package mode - check it by `substr();`.
	 * @var string
	 */
	const COMPILED_PHP = 'PHP';

	/**
	 * MvcCore application mode describing that the application is compiled in <b>ONE BIG PHAR FILE</b>.
	 * There could be any content included. But in this mode, there is no speed advantages, but it's
	 * still good way to pack your app into single file tool for any web-hosting needs:-)
	 * This mode has always lower speed then `PHP` mode above, because it fully emulates hard drive
	 * for content of this file and it costs a time. But it has lower memory usage then `PHP` mode above.
	 * @see http://php.net/manual/en/phar.creating.php
	 * @var string
	 */
	const COMPILED_PHAR = 'PHAR';

	/**
	 * MvcCore application mode describing that the application is in <b>THE STATE BEFORE
	 * THEIR OWN COMPILATION INTO `PHP` OR `PHAR`</b> archive. This mode is always used to generate final
	 * javascript and css files into temporary directory to pack them later into result php/phar file.
	 * Shortcut `SFU` means "Single File Url". Application running in this mode has to generate
	 * single file URLs in form: "index.php?..." and everything has to work properly before
	 * application will be compiled into PHP/PHAR package. Use this mode in index.php before
	 * application compilation to generate and test everything necessary before app compilation by:
	 * `\MvcCore\Application::GetInstance()->Dispatch();`
	 * - `TRUE` means to switch application into temporary into SFU mode.
	 * @var string
	 */
	const COMPILED_SFU = 'SFU';

	/**
	 * MvcCore application mode describing that the application is running as <b>STANDARD PHP PROJECT</b>
	 * with many files on hard drive, using auto-loading or anything else. It's also standard development mode.
	 * @var string
	 */
	const NOT_COMPILED = '';


	/***********************************************************************************
	 *					  `\MvcCore\Application` - Static Calls					  *
	 ***********************************************************************************/

	/**
	 * Returns singleton `\MvcCore\Application` instance as reference.
	 * @return \MvcCore\IApplication
	 */
	public static function GetInstance ();


	/***********************************************************************************
	 *						`\MvcCore\Application` - Getters						 *
	 ***********************************************************************************/

	/**
	 * Get if application is running as standard php project or as single file application.
	 * It should has values from:
	 * - `\MvcCore\IApplication::COMPILED_PHP`
	 * - `\MvcCore\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\IApplication::COMPILED_SFU`
	 * - `\MvcCore\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\IApplication`.
	 * @var string
	 */
	public function GetCompiled ();


	/**
	 * Get application environment class implementing `\MvcCore\IEnvironment`.
	 * Class to detect and manage environment name.
	 * @return \MvcCore\IEnvironment|string
	 */
	public function GetEnvironmentClass ();

	/**
	 * Get application config class implementing `\MvcCore\IConfig`.
	 * Class to load and parse (system) config(s).
	 * @return \MvcCore\IConfig|string
	 */
	public function GetConfigClass ();

	/**
	 * Get application config class implementing `\MvcCore\IController`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * @return \MvcCore\IController|string
	 */
	public function GetControllerClass ();

	/**
	 * Get application debug class implementing `\MvcCore\IDebug`.
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * @return \MvcCore\IDebug|string
	 */
	public function GetDebugClass ();

	/**
	 * Get application request class implementing `\MvcCore\IRequest`.
	 * Class to create describing HTTP request object.
	 * @return \MvcCore\IRequest|string
	 */
	public function GetRequestClass ();

	/**
	 * Get application response class implementing `\MvcCore\IResponse`.
	 * Class to create HTTP response object to store response headers and response content.
	 * @return \MvcCore\IResponse|string
	 */
	public function GetResponseClass ();

	/**
	 * Get application route class implementing `\MvcCore\IRoute`.
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * @return \MvcCore\IRoute|string
	 */
	public function GetRouteClass ();

	/**
	 * Get application router class implementing `\MvcCore\IRouter`.
	 * Class to store all routes, dispatch request by routes and generate URL addresses by routes.
	 * @return \MvcCore\IRouter|string
	 */
	public function GetRouterClass ();

	/**
	 * Get application session class implementing `\MvcCore\ISession`.
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * @return \MvcCore\ISession|string
	 */
	public function GetSessionClass ();

	/**
	 * Get application tool class implementing `\MvcCore\ITool`.
	 * Class to handle helper calls from MvcCore core modules.
	 * @return \MvcCore\ITool|string
	 */
	public function GetToolClass ();

	/**
	 * Get application view class implementing `\MvcCore\IView`.
	 * Class to prepare and render controller view, sub-views and wrapper layout.
	 * @return \MvcCore\IView|string
	 */
	public function GetViewClass ();

	/**
	 * Returns environment detection instance.
	 * @var \MvcCore\Environment|\MvcCore\IEnvironment
	 */
	public function GetEnvironment ();

	/**
	 * Returns currently dispatched controller instance.
	 * @return \MvcCore\IController
	 */
	public function GetController ();

	/**
	 * Returns currently used request instance.
	 * @return \MvcCore\IRequest
	 */
	public function GetRequest ();

	/**
	 * Returns currently used response instance.
	 * @return \MvcCore\IResponse
	 */
	public function GetResponse ();

	/**
	 * Returns currently used router instance.
	 * @return \MvcCore\IRouter
	 */
	public function GetRouter ();

	/**
	 * Get application scripts and views directory name as `"App"` by default,
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetAppDir ();

	/**
	 * Get controllers directory name as `"Controllers"` by default, for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetControllersDir ();

	/**
	 * Get views directory name as `"views"` by default, for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * @return string
	 */
	public function GetViewsDir ();

	/**
	 * Returns array with:
	 * - `0 => "index"` - Default controller name, from protected `\MvcCore\Application::$defaultControllerName`.
	 * - `1 => "index"` - Default action name, from protected `\MvcCore\Application::$defaultControllerDefaultActionName`.
	 * @return string[]
	 */
	public function GetDefaultControllerAndActionNames ();


	/***********************************************************************************
	 *						`\MvcCore\Application` - Setters						 *
	 ***********************************************************************************/

	/**
	 * Set if application is running as standard php project or as single file application.
	 * First param `$compiled` should has values from:
	 * - `\MvcCore\IApplication::COMPILED_PHP`
	 * - `\MvcCore\IApplication::COMPILED_PHAR`
	 * - `\MvcCore\IApplication::COMPILED_SFU`
	 * - `\MvcCore\IApplication::NOT_COMPILED`
	 * Read more about every mode in interface: `\MvcCore\IApplication`.
	 * Core configuration method.
	 * @param string $compiled
	 * @return \MvcCore\IApplication
	 */
	public function SetCompiled ($compiled = '');


	/**
	 * Set application environment class implementing `\MvcCore\IEnvironment`.
	 * Class to detect and manage environment name.
	 * Core configuration method.
	 * @param string $environmentClass
	 * @return \MvcCore\IApplication
	 */
	public function SetEnvironmentClass ($environmentClass);

	/**
	 * Set application config class implementing `\MvcCore\IConfig`.
	 * Class to load and parse (system) config(s).
	 * Core configuration method.
	 * @param string $configClass
	 * @return \MvcCore\IApplication
	 */
	public function SetConfigClass ($configClass);

	/**
	 * Set application controller class implementing `\MvcCore\IController`.
	 * Class to create default controller for request targeting views only
	 * and to handle small assets inside packed application.
	 * Core configuration method.
	 * @param string $controllerClass
	 * @return \MvcCore\IApplication
	 */
	public function SetControllerClass ($controllerClass);

	/**
	 * Set application debug class implementing `\MvcCore\IDebug`.
	 * Class to handle any application error to render the error in browser or log in HDD.
	 * Core configuration method.
	 * @param string $debugClass
	 * @return \MvcCore\IApplication
	 */
	public function SetDebugClass ($debugClass);

	/**
	 * Set application request class implementing `\MvcCore\IRequest`.
	 * Class to create describing HTTP request object.
	 * Core configuration method.
	 * @param string $requestClass
	 * @return \MvcCore\IApplication
	 */
	public function SetRequestClass ($requestClass);

	/**
	 * Set application response class implementing `\MvcCore\IResponse`.
	 * Class to create HTTP response object to store response headers and response content.
	 * Core configuration method.
	 * @param string $responseClass
	 * @return \MvcCore\IApplication
	 */
	public function SetResponseClass ($responseClass);

	/**
	 * Set application route class implementing `\MvcCore\IRoute`.
	 * Class to describe single route with match and replace pattern,
	 * controller, action, params default values and params constraints.
	 * Core configuration method.
	 * @param string $routerClass
	 * @return \MvcCore\IApplication
	 */
	public function SetRouteClass ($routerClass);

	/**
	 * Set application router class implementing `\MvcCore\IRouter`.
	 * Class to store all routes, dispatch request by routes and generate URL addresses by routes.
	 * Core configuration method.
	 * @param string $routerClass
	 * @return \MvcCore\IApplication
	 */
	public function SetRouterClass ($routerClass);

	/**
	 * Set application session class implementing `\MvcCore\ISession`.
	 * Class to configure session namespaces, session opening, writing and expirations.
	 * Core configuration method.
	 * @param string $sessionClass
	 * @return \MvcCore\IApplication
	 */
	public function SetSessionClass ($sessionClass);

	/**
	 * Set application tool class implementing `\MvcCore\ITool`.
	 * Class to handle helper calls from MvcCore core modules.
	 * Core configuration method.
	 * @param string $toolClass
	 * @return \MvcCore\IApplication
	 */
	public function SetToolClass ($toolClass);

	/**
	 * Set application view class implementing `\MvcCore\IView`.
	 * Class to prepare and render controller view, sub-views and wrapper layout.
	 * Core configuration method.
	 * @param string $viewClass
	 * @return \MvcCore\IApplication
	 */
	public function SetViewClass ($viewClass);


	/**
	 * Set currently dispatched controller instance.
	 * @param \MvcCore\IController $controller
	 * @return \MvcCore\IApplication
	 */
	public function SetController (\MvcCore\IController $controller);


	/**
	 * Set application scripts and views directory name (`"App"` by default),
	 * where are following subdirectories by default:
	 * - `/App/Controllers`
	 * - `/App/Models`
	 * - `/App/Views`
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $appDir
	 * @return \MvcCore\IApplication
	 */
	public function SetAppDir ($appDir);

	/**
	 * Set controllers directory name (`"Controllers"` by default), for all controller classes,
	 * it's placed directly in application directory by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $controllersDir
	 * @return \MvcCore\IApplication
	 */
	public function SetControllersDir ($controllersDir);

	/**
	 * Set views directory name (`"views"` by default), for all view elements,
	 * it's placed directly in application directory above by default.
	 * It should by reconfigured to custom value in the very application beginning.
	 * Core configuration method.
	 * @param string $viewsDir
	 * @return \MvcCore\IApplication
	 */
	public function SetViewsDir ($viewsDir);

	/**
	 * Set default controller name. `"Index"` value by default.
	 * Core configuration method.
	 * @param string $defaultControllerName
	 * @return \MvcCore\IApplication
	 */
	public function SetDefaultControllerName ($defaultControllerName);

	/**
	 * Set default controller default action name. `"Index"` value by default.
	 * Core configuration method.
	 * @param string $defaultActionName
	 * @return \MvcCore\IApplication
	 */
	public function SetDefaultControllerDefaultActionName ($defaultActionName);

	/**
	 * Set default controller common error action name. `"Error"` value by default.
	 * Core configuration method.
	 * @param string $defaultControllerErrorActionName
	 * @return \MvcCore\IApplication
	 */
	public function SetDefaultControllerErrorActionName ($defaultControllerErrorActionName);

	/**
	 * Set default controller not found error action name. `"NotFound"` value by default.
	 * Core configuration method.
	 * @param string $defaultControllerNotFoundActionName
	 * @return \MvcCore\IApplication
	 */
	public function SetDefaultControllerNotFoundActionName ($defaultControllerNotFoundActionName);

	/**
	 * Add pre route handler into pre route handlers queue to process them after
	 * every request has been completed into `\MvcCore\Request` describing object and before
	 * every request will be routed by `\MvcCore\Router::Route();` call.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreRouteHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int|NULL $priorityIndex
	 * @return \MvcCore\IApplication
	 */
	public function AddPreRouteHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add post route handler into post route handlers queue to process them after
	 * every request has been completed into `\MvcCore\Request` describing object, after
	 * every request has been routed by `\MvcCore\Router::Route();` call and before
	 * every request has created target controller instance.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostRouteHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int|NULL $priorityIndex
	 * @return \MvcCore\IApplication
	 */
	public function AddPostRouteHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add pre dispatch handler into pre dispatch handlers queue to process them after
	 * every request has been routed by `\MvcCore\Router::Route();` call, after
	 * every request has been dispatched by `\MvcCore\Controller::Dispatch();` and
	 * after every request has created and prepared target controller instance to dispatch.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPreDispatchHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int|NULL $priorityIndex
	 * @return \MvcCore\IApplication
	 */
	public function AddPreDispatchHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add post dispatch handler into post dispatch handlers queue to process them
	 * before every request is terminated by `\MvcCore\Application::Terminate();`.
	 * Every request terminated sooner has executed this post dispatch handlers queue.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostDispatchHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		$request->customVar = 'custom_value';
	 * });`
	 * @param callable $handler
	 * @param int|NULL $priorityIndex
	 * @return \MvcCore\IApplication
	 */
	public function AddPostDispatchHandler (callable $handler, $priorityIndex = NULL);

	/**
	 * Add post terminate handler into post terminate handlers queue to process them
	 * after every request is terminated by `\MvcCore\Application::Terminate();`.
	 * Callable should be void and it's params should be two with following types:
	 *	- `\MvcCore\Request`
	 *	- `\MvcCore\Response`
	 * Example:
	 * `\MvcCore\Application::GetInstance()->AddPostTerminateHandler(function(
	 *		\MvcCore\Request $request,
	 *		\MvcCore\Response $response
	 * ) {
	 *		// close connection by previously configured
	 *		// header: header('Connection: close');
	 *		// and run background process now:
	 * });`
	 * @param callable $handler
	 * @param int|NULL $priorityIndex
	 * @return \MvcCore\IApplication
	 */
	public function AddPostTerminateHandler (callable $handler, $priorityIndex = NULL);


	/***********************************************************************************
	 *				   `\MvcCore\Application` - Normal Dispatching				   *
	 ***********************************************************************************/

	/**
	 * Dispatch http request/response.
	 * - 1. Complete and init:
	 *	  - Complete describing environment object `\MvcCore\Request`.
	 *	  - Complete describing request object `\MvcCore\Request`.
	 *	  - Complete response storage object `\MvcCore\Response`.
	 *	  - Init debugging and logging by `\MvcCore\Debug::Init();`.
	 * - 2. (Process pre-route handlers queue.)
	 * - 3. Route request by your router or with `\MvcCore\Router::Route()` by default.
	 * - 4. (Process post-route handlers queue.)
	 * - 5. Create and set up controller instance.
	 * - 6. (Process pre-dispatch handlers queue.)
	 * - 7. Dispatch controller life-cycle.
	 *  	- Call `\MvcCore\Controller::Init()` and `\MvcCore\Controller::PreDispatch()`.
	 *	  - Call routed action method.
	 *	  - Call `\MvcCore\Controller::Render()` to render all views.
	 * - 6. Terminate request:
	 *	  - (Process post-dispatch handlers queue.)
	 *	  - Write session in `register_shutdown_function()` handler.
	 *	  - Send response headers if possible and echo response body.
	 * @return \MvcCore\IApplication
	 */
	public function Dispatch ();

	/**
	 * Starts a session, standardly called from `\MvcCore\Controller::Init();`.
	 * But is should be called anytime sooner, for example in any pre request handler
	 * to redesign request before MVC dispatching or anywhere else.
	 * @return void
	 */
	public function SessionStart ();

	/**
	 * Route request by router obtained by default by calling:
	 * `\MvcCore\Router::GetInstance();`.
	 * Store requested route inside configured
	 * router class to get it later by calling:
	 * `\MvcCore\Router::GetCurrentRoute();`
	 * @return bool
	 */
	public function RouteRequest ();

	/**
	 * Process pre-route, pre-request or post-dispatch
	 * handlers queue by queue index. Call every handler in queue
	 * in try catch mode to catch any exceptions to call:
	 * `\MvcCore\Application::DispatchException($e);`.
	 * @param callable[] $handlers
	 * @return bool
	 */
	public function ProcessCustomHandlers (& $handlers = []);

	/**
	 * If controller class exists - try to dispatch controller,
	 * if only view file exists - try to render targeted view file
	 * with configured core controller instance (`\MvcCore\Controller` by default).
	 * @return bool
	 */
	public function DispatchRequest ();

	/**
	 * Dispatch controller by:
	 * - By full class name and by action name
	 * - Or by view script full path
	 * Call exception callback if there is caught any
	 * exception in controller life-cycle dispatching process
	 * with first argument as caught exception.
	 * @param string $ctrlClassFullName
	 * @param string $actionNamePc
	 * @param string $viewScriptFullPath
	 * @param callable $exceptionCallback
	 * @return bool
	 */
	public function DispatchControllerAction (
		$controllerClassFullName,
		$actionNamePc,
		$viewScriptFullPath,
		callable $exceptionCallback
	);

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewritten URL by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is URL form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = []);

	/**
	 * Terminate request.
	 * The only place in application where is called `echo '....'` without output buffering.
	 * - Process post-dispatch handlers queue.
	 * - Write session through registered handler into `register_shutdown_function()`.
	 * - Send HTTP headers (if still possible).
	 * - Echo response body.
	 * This method is always called INTERNALLY after controller
	 * life-cycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * @return \MvcCore\IApplication
	 */
	public function Terminate ();


	/***********************************************************************************
	 *			   `\MvcCore\Application` - Request Error Dispatching				*
	 ***********************************************************************************/

	/**
	 * Dispatch caught exception:
	 *	- If request is processing PHP package packing to determinate current script dependencies:
	 *		- Do not log or render nothing.
	 *	- If request is production mode:
	 *		- Print exception in browser.
	 *	- If request is not in development mode:
	 *		- Log error and try to render error page by configured controller and error action:,
	 *		  `\App\Controllers\Index::Error();` by default.
	 * @param \Exception|string $exceptionOrMessage
	 * @param int|NULL $code
	 * @return bool
	 */
	public function DispatchException ($exceptionOrMessage, $code = NULL);

	/**
	 * Render error by configured default controller and error action,
	 * `\App\Controllers\Index::Error();` by default.
	 * If there is no controller/action like that or any other exception happens,
	 * it's processed very simple plain text response with 500 http code.
	 * @param \Exception $e
	 * @return bool
	 */
	public function RenderError (\Throwable $e);

	/**
	 * Render error by configured default controller and not found error action,
	 * `\App\Controllers\Index::NotFound();` by default.
	 * If there is no controller/action like that or any other exception happens,
	 * it's processed very simple plain text response with 404 http code.
	 * @param \Exception $e
	 * @return bool
	 */
	public function RenderNotFound ($exceptionMessage = '');

	/**
	 * Prepare very simple response with internal server error (500)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param string $text
	 * @return bool
	 */
	public function RenderError500PlainText ($text = '');

	/**
	 * Prepare very simple response with not found error (404)
	 * as plain text response into `\MvcCore\Application::$response`.
	 * @param string $text
	 * @return bool
	 */
	public function RenderError404PlainText ();


	/***********************************************************************************
	 *					 `\MvcCore\Application` - Helper Methods					 *
	 ***********************************************************************************/

	/**
	 * Check if default application controller (`\App\Controllers\Index` by default) has specific action.
	 * If default controller has specific action - return default controller full name, else empty string.
	 * @param string $actionName
	 * @return string
	 */
	public function GetDefaultControllerIfHasAction ($actionName);

	/**
	 * Complete standard MvcCore application controller full name in form:
	 * `\App\Controllers\<$controllerNamePascalCase>`.
	 * @param string $controllerNamePascalCase
	 * @return string
	 */
	public function CompleteControllerName ($controllerNamePascalCase);

	/**
	 * Return `TRUE` if current request is default controller error action dispatching process.
	 * @return bool
	 */
	public function IsErrorDispatched ();

	/**
	 * Return `TRUE` if current request is default controller not found error action dispatching process.
	 * @return bool
	 */
	public function IsNotFoundDispatched ();
}
