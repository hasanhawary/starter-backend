<?php

namespace AiChat\Scanner;

use Illuminate\Support\Facades\Route;

class RouteScanner
{
    public function scan(): array
    {
        $routes = [];
        $grouped = [];

        foreach (Route::getRoutes() as $route) {
            $uri = '/'.ltrim($route->uri(), '/');
            $methods = array_values(array_filter($route->methods(), fn (string $m) => $m !== 'HEAD'));
            $action = $route->getAction();
            $controller = null;
            $actionName = null;

            if (isset($action['controller'])) {
                $controllerAction = $action['controller'];

                if (str_contains($controllerAction, '@')) {
                    [$controller, $actionName] = explode('@', $controllerAction, 2);
                } elseif (str_contains($controllerAction, '::')) {
                    $parts = explode('::', $controllerAction, 2);
                    $controller = $parts[0];
                    $actionName = ltrim($parts[1], '__invoke');
                    $actionName = $actionName !== '' ? $actionName : '__invoke';
                } else {
                    $controller = $controllerAction;
                    $actionName = '__invoke';
                }
            }

            $middleware = $route->gatherMiddleware();

            $prefix = $action['prefix'] ?? '';
            $domain = $route->getDomain();

            $routeData = [
                'uri' => $uri,
                'methods' => $methods,
                'controller' => $controller,
                'action' => $actionName,
                'middleware' => $middleware,
                'prefix' => $prefix,
                'domain' => $domain,
                'name' => $route->getName(),
            ];

            $routes[] = $routeData;

            $groupKey = trim(($domain ?? '').'/'.$prefix, '/');

            if ($groupKey === '') {
                $groupKey = 'root';
            }

            $grouped[$groupKey][] = $routeData;
        }

        return [
            'routes' => $routes,
            'grouped' => $grouped,
            'total' => count($routes),
        ];
    }
}
