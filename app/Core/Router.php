<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/**
 * Regex-based HTTP router.
 *
 * Routes are registered with get() / post() and mapped to
 * Controller::action pairs. URI placeholders ({param}) are
 * captured and forwarded as positional arguments to the action.
 *
 * Example:
 *   $router->get('/api/customers/{id}', CustomerController::class, 'show');
 *   // Calls: $controller->show('42')
 */
class Router
{
    /**
     * Registered routes, keyed by HTTP method then pattern.
     *
     * @var array<string, array<string, array{controller: string, action: string, protected: bool}>>
     */
    private array $routes = [];

    /**
     * Registers a GET route.
     *
     * @param string $pattern    URI pattern, e.g. '/api/customers/{id}'.
     * @param string $controller Fully-qualified controller class name.
     * @param string $action     Public method to call on the controller.
     */
    public function get(string $pattern, string $controller, string $action, bool $protected = false): void
    {
        $this->addRoute('GET', $pattern, $controller, $action, $protected);
    }

    /**
     * Registers a POST route.
     *
     * @param string $pattern    URI pattern.
     * @param string $controller Fully-qualified controller class name.
     * @param string $action     Public method to call on the controller.
     */
    public function post(string $pattern, string $controller, string $action, bool $protected = false): void
    {
        $this->addRoute('POST', $pattern, $controller, $action, $protected);
    }

    /**
     * Dispatches the incoming request to the matching controller action.
     *
     * @param Request $request The current HTTP request.
     * @throws HttpException 404 if no route matches, 500 if controller is missing.
     */
    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri    = $request->getUri();

        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            $regex = $this->patternToRegex($pattern);

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // Drop full-match capture (index 0)
                $params = array_values($matches);

                $controllerClass = $route['controller'];
                $action          = $route['action'];

                if (!class_exists($controllerClass)) {
                    throw new HttpException(500, "Controller [{$controllerClass}] not found.");
                }

                if ($route['protected']) {
                    ApiKeyGuard::check($request);
                }

                $controller = new $controllerClass($request);
                $controller->{$action}(...$params);
                return;
            }
        }

        throw new HttpException(404, "No route matched: {$method} {$uri}");
    }

    /**
     * Stores a route definition.
     */
    private function addRoute(string $method, string $pattern, string $controller, string $action, bool $protected = false): void
    {
        $this->routes[$method][$pattern] = [
            'controller' => $controller,
            'action'     => $action,
            'protected'  => $protected,
        ];
    }

    /**
     * Converts a route pattern with {param} placeholders into a PCRE regex.
     *
     * Example: '/api/customers/{id}' → '#^/api/customers/([^/]+)$#'
     */
    private function patternToRegex(string $pattern): string
    {
        $escaped = preg_quote($pattern, '#');
        $regex   = preg_replace('#\\\{[a-zA-Z_][a-zA-Z0-9_]*\\\}#', '([^/]+)', $escaped);

        return '#^' . $regex . '$#';
    }
}
