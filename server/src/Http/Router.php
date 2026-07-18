<?php
declare(strict_types=1);

namespace Tna\Http;

final class Router
{
    /** @var array<string,array<string,callable(Request):Response>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $path = $this->normalize($request->path());
        $method = $request->method();
        foreach ($this->routes as $route => $methods) {
            $pattern = $this->patternFor($route);
            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }
            if (!isset($methods[$method])) {
                throw new ApiException(405, 'Method not allowed.');
            }
            return ($methods[$method])($request, ...array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
        }
        throw new ApiException(404, 'Route not found.');
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$this->normalize($path)][$method] = $handler;
    }

    private function patternFor(string $route): string
    {
        $quoted = preg_quote($route, '#');
        $pattern = preg_replace('#\\\{([A-Za-z][A-Za-z0-9_]*)\\\}#', '(?P<$1>[^/]+)', $quoted);
        return '#^' . $pattern . '$#';
    }

    private function normalize(string $path): string
    {
        return rtrim('/' . ltrim($path, '/'), '/') ?: '/';
    }
}
