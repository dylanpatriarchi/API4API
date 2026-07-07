<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Minimal regex-based router mapping method + path to a handler.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, handler: Closure}> */
    private array $routes = [];

    /**
     * Register a route. Path segments like "{id}" become named parameters.
     */
    public function add(string $method, string $path, Closure $handler): void
    {
        $pattern = preg_replace('#\{([a-z_]+)\}#i', '(?P<$1>[^/]+)', $path);

        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Match the request against the route table and invoke the handler.
     */
    public function dispatch(Request $request): void
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->path, $matches) !== 1) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])($request, $params);

            return;
        }

        if ($pathMatched) {
            throw new HttpException('Method not allowed', 405);
        }

        throw HttpException::notFound('Unknown endpoint');
    }
}
