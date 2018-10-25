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

namespace MvcCore\Router;

trait UrlBuilding
{
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
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function Url ($controllerActionOrRouteName = 'Index:Index', array $params = []) {
		$result = '';
		$ctrlActionOrRouteNameKey = $this->urlGetCompletedCtrlActionKey(
			$controllerActionOrRouteName
		);
		if ($this->anyRoutesConfigured && !($this->routeByQueryString && $ctrlActionOrRouteNameKey === static::DEFAULT_ROUTE_NAME)) {
			// try to found url route in global `$this->urlRoutes` store
			if (isset($this->urlRoutes[$ctrlActionOrRouteNameKey]) && $this->urlRoutes[$ctrlActionOrRouteNameKey]->GetName() !== static::DEFAULT_ROUTE_NAME) {
				// if there was a route under `$ctrlActionOrRouteNameKey` key already, 
				// we can complete url by this route
				$result = $this->UrlByRoute(
					$this->urlRoutes[$ctrlActionOrRouteNameKey], 
					$params, $controllerActionOrRouteName
				);
			} else {
				// if there is no route under key `$ctrlActionOrRouteNameKey` yet, 
				// try to call configured `$this->preRouteUrlBuildingHandler` if any
				// to load more routes for example from database and than, try to
				// find route under key `$ctrlActionOrRouteNameKey` again
				$urlRouteFound = FALSE;
				if (!isset($this->noUrlRoutes)) {
					if ($this->preRouteUrlBuildingHandler !== NULL) 
						call_user_func($this->preRouteUrlBuildingHandler, $this, $ctrlActionOrRouteNameKey, $params);
					// try to found url route again
					if (isset($this->urlRoutes[$ctrlActionOrRouteNameKey]) && $this->urlRoutes[$ctrlActionOrRouteNameKey]->GetName() !== static::DEFAULT_ROUTE_NAME) {
						$urlRouteFound = TRUE;
					} else {
						$this->noUrlRoutes[$ctrlActionOrRouteNameKey] = TRUE;
					}
				}
				if ($urlRouteFound) {
					// if route under key `$ctrlActionOrRouteNameKey` has been loaded by calling
					// configured handler `$this->preRouteUrlBuildingHandler`, complete url by this route
					$result = $this->UrlByRoute(
						$this->urlRoutes[$ctrlActionOrRouteNameKey], 
						$params, $controllerActionOrRouteName
					);
				} else {
					// there is probably no route for given key `$ctrlActionOrRouteNameKey`,
					// so complete result url with query string logic
					$result = $this->UrlByQueryString(
						$ctrlActionOrRouteNameKey, 
						$params, $controllerActionOrRouteName
					);
				}
			}
		} else {
			// if there are no url routes configured - complete url with query string logic
			$result = $this->UrlByQueryString(
				$ctrlActionOrRouteNameKey, 
				$params, $controllerActionOrRouteName
			);
		}
		return $result;
	}

	protected function urlGetCompletedCtrlActionKey ($controllerAction) {
		$result = $controllerAction;
		if (strpos($controllerAction, ':') !== FALSE) {
			list($ctrlPc, $actionPc) = explode(':', $controllerAction);
			if (!$ctrlPc) {
				$toolClass = self::$toolClass;
				$ctrlPc = str_replace('/', '\\', 
					$toolClass::GetPascalCaseFromDashed($this->request->GetControllerName())
				);
			}
			if (!$actionPc) {
				$toolClass = self::$toolClass;
				$actionPc = $toolClass::GetPascalCaseFromDashed($this->request->GetActionName());
			}
			$result = "$ctrlPc:$actionPc";
		} else if ($controllerAction == 'self') {
			$result = $this->selfRouteName;
		}
		return $result;
	}
}
