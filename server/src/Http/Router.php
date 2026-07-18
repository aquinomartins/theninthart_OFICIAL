<?php
declare(strict_types=1);

namespace Tna\Http;

final class Router
{
    /** @var array<string,array<string,callable(Request):Response>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[$this->normalize($path)]['GET'] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $path = $this->normalize($request->path());
        if (!isset($this->routes[$path])) {
            throw new ApiException(404, 'Route not found.');
        }
        $method = $request->method();
        if (!isset($this->routes[$path][$method])) {
            throw new ApiException(405, 'Method not allowed.');
        }
        return ($this->routes[$path][$method])($request);
    }

    private function normalize(string $path): string
    {
        return rtrim('/' . ltrim($path, '/'), '/') ?: '/';
    }
}
