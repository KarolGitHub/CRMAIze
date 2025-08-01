<?php

namespace CRMAIze\Core;

class Request
{
  private $get;
  private $post;
  private $server;
  private $method;
  private $path;

  public function __construct()
  {
    $this->get = $_GET;
    $this->post = $_POST;
    $this->server = $_SERVER;
    $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $this->path = $_SERVER['REQUEST_URI'] ?? '/';
  }

  public static function createFromGlobals(): self
  {
    return new self();
  }

  public function getMethod(): string
  {
    return $this->method;
  }

  public function getPathInfo(): string
  {
    $path = parse_url($this->path, PHP_URL_PATH);
    return $path ?: '/';
  }

  public function get(string $key, $default = null)
  {
    return $this->get[$key] ?? $default;
  }

  public function post(string $key, $default = null)
  {
    return $this->post[$key] ?? $default;
  }

  public function all(): array
  {
    return array_merge($this->get, $this->post);
  }

  public function isPost(): bool
  {
    return $this->method === 'POST';
  }

  public function isGet(): bool
  {
    return $this->method === 'GET';
  }
}
