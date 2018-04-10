<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Application;

include_once('Request.php');
include_once('Response.php');
include_once('Debug.php');
include_once('Session.php');
include_once('Router.php');
include_once('View.php');
include_once('Controller.php');
include_once('Config.php');

/**
 * `\MvcCore\Application` - Normal Request & Error Request Dispatching
 */
trait Dispatching
{
	/***********************************************************************************
	 *                   `\MvcCore\Application` - Normal Dispatching                   *
	 ***********************************************************************************/

	/**
	 * Run application.
	 * - 1. Complete and init:
	 *      - `\MvcCore\Application::$compiled` flag.
	 *      - Complete describing request object `\MvcCore\Request`.
	 *      - Complete response storage object `\MvcCore\Response`.
	 *      - Init debugging and logging by `\MvcCore\Debug::Init();`.
	 * - 2. (Process pre-route handlers queue.)
	 * - 3. Route request by your router or with `\MvcCore\Router::Route()` by default.
	 * - 4. (Process pre-dispatch handlers queue.)
	 * - 5. Dispatch controller lifecycle:
	 *  	- Create and set up controller.
	 *  	- Call `\MvcCore\Controller::Init()` and `\MvcCore\Controller::PreDispatch()`.
	 *      - Call routed action method.
	 *      - Call `\MvcCore\Controller::Render()` to render all views.
	 * - 6. Terminate request:
	 *      - (Process post-dispatch handlers queue.)
	 *      - Write session in `register_shutdown_function()` handler.
	 *      - Send response headers if possible and echo response body.
	 * @param bool $singleFileUrl Set 'Single File Url' mode to `TRUE` to compile and test
	 *                            all assets and everything before compilation processing.
	 * @return \MvcCore\Application
	 */
	public function Run ($singleFileUrl = FALSE) {
		if ($singleFileUrl) $this->compiled = static::COMPILED_SFU;
		$requestClass = $this->requestClass;
		$responseClass = $this->responseClass;
		$this->request = $requestClass::GetInstance($_SERVER, $_GET, $_POST);
		$this->response = $responseClass::GetInstance();
		$debugClass = $this->debugClass;
		$debugClass::Init();
		if (!$this->processCustomHandlers($this->preRouteHandlers))			return $this->Terminate();
		if (!$this->routeRequest())											return $this->Terminate();
		if (!$this->processCustomHandlers($this->preDispatchHandlers))		return $this->Terminate();
		if (!$this->DispatchMvcRequest($this->router->GetCurrentRoute()))	return $this->Terminate();
		// Post-dispatch handlers processing moved to: `$this->Terminate();` to process them every time.
		// if (!$this->processCustomHandlers($this->postDispatchHandlers))	return $this->Terminate();
		return $this->Terminate();
	}

	/**
	 * Starts a session, standardly called from `\MvcCore\Controller::Init();`.
	 * But is shoud be called anytime sooner, for example in any pre request handler
	 * to redesign request before MVC dispatching or anywhere else.
	 * @return void
	 */
	public function SessionStart () {
		$sessionClass = $this->sessionClass;
		$sessionClass::Start();
	}

	/**
	 * Route request by router obtained by default by calling:
	 * `\MvcCore\Router::GetInstance();`.
	 * Store requested route inside configured
	 * router class to get it later by calling:
	 * `\MvcCore\Router::GetCurrentRoute();`
	 * @return bool
	 */
	protected function routeRequest () {
		$routerClass = $this->routerClass;
		$this->router = $routerClass::GetInstance()->SetRequest($this->request);
		try {
			$this->router->Route();
			return TRUE;
		} catch (\Exception $e) {
			return $this->DispatchException($e);
		}
	}

	/**
	 * Process pre-route, pre-request or post-dispatch
	 * handlers queue by queue index. Call every handler in queue
	 * in try catch mode to catch any exceptions to call:
	 * `\MvcCore\Application::DispatchException($e);`.
	 * @param callable[] $handlers
	 * @return bool
	 */
	protected function processCustomHandlers (& $handlers = array()) {
		if (!$this->request->IsAppRequest()) return TRUE;
		$result = TRUE;
		foreach ($handlers as $handler) {
			try {
				call_user_func($handler, $this->request, $this->response);
				// $handler($this->request, $this->response);
			} catch (\Exception $e) {
				$this->DispatchException($e);
				$result = FALSE;
				break;
			}
		}
		return $result;
	}

	/**
	 * If controller class exists - try to dispatch controller,
	 * if only view file exists - try to render targeted view file
	 * with configured core controller instance (`\MvcCore\Controller` by default).
	 * @param \MvcCore\Route $route
	 * @return bool
	 */
	public function DispatchMvcRequest (& $route = NULL) {
		if (is_null($route)) return $this->DispatchException(new \Exception('No route for request', 404));
		list ($controllerNamePascalCase, $actionNamePascalCase) = array($route->Controller, $route->Action);
		$actionName = $actionNamePascalCase . 'Action';
		$viewClass = $this->viewClass;
		$viewScriptFullPath = $viewClass::GetViewScriptFullPath(
			$viewClass::$ScriptsDir,
			$this->request->GetControllerName() . '/' . $this->request->GetActionName()
		);
		if ($controllerNamePascalCase == 'Controller') {
			$controllerName = $this->controllerClass;
		} else {
			// App_Controllers_$controllerNamePascalCase
			$controllerName = $this->CompleteControllerName($controllerNamePascalCase);
			if (!class_exists($controllerName)) {
				// if controller doesn't exists - check if at least view exists
				if (file_exists($viewScriptFullPath)) {
					// if view exists - change controller name to core controller, if not let it go to exception
					$controllerName = $this->controllerClass;
				}
			}
		}
		return $this->DispatchControllerAction(
			$controllerName,
			$actionName,
			$viewScriptFullPath, function (\Exception & $e) {
				return $this->DispatchException($e);
			}
		);
	}

	/**
	 * Dispatch controller by:
	 * - By full class name and by action name
	 * - Or by view script full path
	 * Call exception callback if there is catched any
	 * exception in controller lifecycle dispatching process
	 * with first argument as catched exception.
	 * @param string $ctrlClassFullName
	 * @param string $actionName
	 * @param string $viewScriptFullPath
	 * @param callable $exceptionCallback
	 * @return bool
	 */
	public function DispatchControllerAction (
		$ctrlClassFullName,
		$actionName,
		$viewScriptFullPath,
		callable $exceptionCallback
	) {
		$this->controller = NULL;
		try {
			$this->controller = $ctrlClassFullName::GetInstance()
				->SetRequest($this->request)
				->SetResponse($this->response)
				->SetRouter($this->router);
		} catch (\Exception $e) {
			return $this->DispatchException(new \ErrorException($e->getMessage(), 404));
		}
		if (!method_exists($this->controller, $actionName) && $ctrlClassFullName !== $this->controllerClass) {
			if (!file_exists($viewScriptFullPath)) {
				return $this->DispatchException(new \ErrorException(
					"Controller '$ctrlClassFullName' has not method '$actionName' "
					."or view doesn't exists in path: '$viewScriptFullPath'.", 404
				));
			}
		}
		try {
			$this->controller->Dispatch($actionName);
		} catch (\Exception $e) {
			return $exceptionCallback($e);
		}
		return TRUE;
	}

	/**
	 * Generates url:
	 * - By `"Controller:Action"` name and params array
	 *   (for routes configuration when routes array has keys with `"Controller:Action"` strings
	 *   and routes has not controller name and action name defined inside).
	 * - By route name and params array
	 *	 (route name is key in routes configuration array, should be any string
	 *	 but routes must have information about controller name and action name inside).
	 * Result address (url string) should have two forms:
	 * - Nice rewrited url by routes configuration
	 *   (for apps with URL rewrite support (Apache `.htaccess` or IIS URL rewrite module)
	 *   and when first param is key in routes configuration array).
	 * - For all other cases is url form like: `"index.php?controller=ctrlName&amp;action=actionName"`
	 *	 (when first param is not founded in routes configuration array).
	 * @param string $controllerActionOrRouteName	Should be `"Controller:Action"` combination or just any route name as custom specific string.
	 * @param array  $params						Optional, array with params, key is param name, value is param value.
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', $params = array()) {
		return $this->router->Url($controllerActionOrRouteName, $params);
	}

	/**
	 * Terminate request.
	 * The only place in application where is called `echo '....'` without output buffering.
	 * - Process post-dispatch handlers queue.
	 * - Write session throught registered handler into `register_shutdown_function()`.
	 * - Send HTTP headers (if still possible).
	 * - Echo response body.
	 * This method is always called INTERNALLY after controller
	 * lifecycle has been dispatched. But you can use it any
	 * time sooner for custom purposes.
	 * @return \MvcCore\Application
	 */
	public function Terminate () {
		$this->processCustomHandlers($this->postDispatchHandlers);
		$sessionClass = $this->sessionClass;
		$sessionClass::Close();
		$this->response->Send(); // headers (if still possible) and echo
		// exit; // Why to force exit? What if we want to do something more?
		return $this;
	}


	/***********************************************************************************
	 *               `\MvcCore\Application` - Request Error Dispatching                *
	 ***********************************************************************************/

	/**
	 * Dispatch catched exception:
	 *	- If request is processing PHP package packing to determinate current script dependencies:
	 *		- Do not log or render nothing.
	 *	- If request is production mode:
	 *		- Print exception in browser.
	 *	- If request is not in development mode:
	 *		- Log error and try to render error page by configured controller and error action:,
	 *		  `\App\Controllers\Index::Error();` by default.
	 * @param \Exception $e
	 * @return bool
	 */
	public function DispatchException (\Exception $e) {
		if (class_exists('\Packager_Php')) return FALSE; // packing process
		$debugClass = $this->debugClass;
		$configClass = $this->configClass;
		if ($e->getCode() == 404) {
			$debugClass::Log($e, \MvcCore\Interfaces\IDebug::ERROR);
			return $this->RenderNotFound($e->getMessage());
		} else if ($configClass::IsDevelopment()) {
			$debugClass::Exception($e);
			return FALSE;
		} else {
			$debugClass::Log($e, \MvcCore\Interfaces\IDebug::EXCEPTION);
			return $this->RenderError($e);
		}
	}

	/**
	 * Render error by configured default controller and error action,
	 * `\App\Controllers\Index::Error();` by default.
	 * If there is no controller/action like that or any other exception happends,
	 * it's processed very simple plain text response with 500 http code.
	 * @param \Exception $e
	 * @return bool
	 */
	public function RenderError (\Exception $e) {
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerErrorActionName
		);
		$exceptionMessage = $e->getMessage();
		if ($defaultCtrlFullName) {
			$toolClass = $this->toolClass;
			$debugClass = $this->debugClass;
			$this->request->Params = array_merge($this->request->Params, array(
				'code'		=> 500,
				'message'	=> $exceptionMessage,
				'controller'=> $toolClass::GetDashedFromPascalCase($this->defaultControllerName),
				'action'	=> $toolClass::GetDashedFromPascalCase($this->defaultControllerErrorActionName),
			));
			return $this->DispatchControllerAction(
				$defaultCtrlFullName,
				$this->defaultControllerErrorActionName . "Action",
				'',
				function (\Exception & $e) use ($exceptionMessage, $debugClass) {
					$debugClass::Log($e, \MvcCore\Interfaces\IDebug::EXCEPTION);
					$this->RenderError500PlainText($exceptionMessage . PHP_EOL . PHP_EOL . $e->getMessage());
				}
			);
		} else {
			return $this->RenderError500PlainText($exceptionMessage);
		}
	}

	/**
	 * Render error by configured default controller and not found error action,
	 * `\App\Controllers\Index::NotFound();` by default.
	 * If there is no controller/action like that or any other exception happends,
	 * it's processed very simple plain text response with 404 http code.
	 * @param \Exception $e
	 * @return bool
	 */
	public function RenderNotFound ($exceptionMessage = '') {
		if (!$exceptionMessage) $exceptionMessage = 'Page not found.';
		$defaultCtrlFullName = $this->GetDefaultControllerIfHasAction(
			$this->defaultControllerNotFoundActionName
		);
		if ($defaultCtrlFullName) {
			$toolClass = $this->toolClass;
			$debugClass = $this->debugClass;
			$this->request->Params = array_merge($this->request->Params, array(
				'code'		=> 404,
				'message'	=> $exceptionMessage,
				'controller'=> $toolClass::GetDashedFromPascalCase($this->defaultControllerName),
				'action'	=> $toolClass::GetDashedFromPascalCase($this->defaultControllerNotFoundActionName),
			));
			return $this->DispatchControllerAction(
				$defaultCtrlFullName,
				$this->defaultControllerNotFoundActionName . "Action",
				'',
				function (\Exception & $e) use ($exceptionMessage, $debugClass) {
					$debugClass::Log($e, \MvcCore\Interfaces\IDebug::EXCEPTION);
					$this->RenderError404PlainText($exceptionMessage);
				}
			);
		} else {
			return $this->RenderError404PlainText($exceptionMessage);
		}
	}

	/**
	 * Prepare very simple response with internal server error (500)
	 * as plain text response into `\MvcCore\Appication::$response`.
	 * @param string $text
	 * @return bool
	 */
	public function RenderError500PlainText ($text = '') {
		if (!$text) $text = 'Internal Server Error.';
		$responseClass = $this->responseClass;
		$this->response = $responseClass::GetInstance(
			\MvcCore\Interfaces\IResponse::INTERNAL_SERVER_ERROR,
			array('Content-Type' => 'text/plain'),
			'Error 500: '.PHP_EOL.PHP_EOL.$text
		);
		return TRUE;
	}

	/**
	 * Prepare very simple response with not found error (404)
	 * as plain text response into `\MvcCore\Appication::$response`.
	 * @param string $text
	 * @return bool
	 */
	public function RenderError404PlainText ($text = '') {
		$responseClass = $this->responseClass;
		$this->response = $responseClass::GetInstance(
			\MvcCore\Interfaces\IResponse::NOT_FOUND,
			array('Content-Type' => 'text/plain'),
			'Error 404: '.PHP_EOL.PHP_EOL.$text
		);
		return TRUE;
	}
}