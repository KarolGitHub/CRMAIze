<?php

namespace CRMAIze\Core;

class Router
{
  private $routes = [];

  public function get(string $path, array $handler): void
  {
    $this->routes['GET'][$path] = $handler;
  }

  public function post(string $path, array $handler): void
  {
    $this->routes['POST'][$path] = $handler;
  }

  public function dispatch(Request $request, Application $app): Response
  {
    $method = $request->getMethod();
    $path = $request->getPathInfo();

    // Check for exact match first
    if (isset($this->routes[$method][$path])) {
      return $this->executeHandler($this->routes[$method][$path], $request, $app);
    }

    // Check for parameterized routes
    foreach ($this->routes[$method] ?? [] as $route => $handler) {
      $pattern = $this->convertRouteToRegex($route);
      if (preg_match($pattern, $path, $matches)) {
        array_shift($matches); // Remove full match
        return $this->executeHandler($handler, $request, $app, $matches);
      }
    }

    return new Response('Not Found', 404);
  }

  private function convertRouteToRegex(string $route): string
  {
    return '#^' . preg_replace('#\{([a-zA-Z]+)\}#', '([^/]+)', $route) . '$#';
  }

  private function executeHandler(array $handler, Request $request, Application $app, array $params = []): Response
  {
    [$controllerClass, $method] = $handler;
    $controller = new $controllerClass($app);

    return $controller->$method($request, ...$params);
  }
}
