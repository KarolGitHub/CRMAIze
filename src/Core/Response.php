<?php

namespace CRMAIze\Core;

class Response
{
  private $content;
  private $statusCode;
  private $headers;

  public function __construct($content = '', int $statusCode = 200, array $headers = [])
  {
    $this->content = $content;
    $this->statusCode = $statusCode;
    $this->headers = array_merge([
      'Content-Type' => 'text/html; charset=UTF-8'
    ], $headers);
  }

  public static function json($data, int $statusCode = 200): self
  {
    return new self(
      json_encode($data, JSON_PRETTY_PRINT),
      $statusCode,
      ['Content-Type' => 'application/json']
    );
  }

  public function send(): void
  {
    // Set status code
    http_response_code($this->statusCode);

    // Set headers
    foreach ($this->headers as $name => $value) {
      header("$name: $value");
    }

    // Output content
    echo $this->content;
  }

  public function getContent()
  {
    return $this->content;
  }

  public function getStatusCode(): int
  {
    return $this->statusCode;
  }

  public function getHeaders(): array
  {
    return $this->headers;
  }
}
