<?php

namespace CRMAIze\Controller;

use CRMAIze\Core\Application;
use CRMAIze\Core\Request;
use CRMAIze\Core\Response;
use CRMAIze\Service\AuthService;

class AuthController
{
  private $app;
  private $authService;

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->authService = $app->getAuthService();
  }

  public function showLogin(Request $request): Response
  {
    if ($this->authService->isLoggedIn()) {
      return new Response('', 302, ['Location' => '/dashboard']);
    }

    return new Response($this->app->getTwig()->render('login.twig'));
  }

  public function login(Request $request): Response
  {
    $username = $request->getInput('username');
    $password = $request->getInput('password');

    if (!$username || !$password) {
      return new Response($this->app->getTwig()->render('login.twig', [
        'error' => 'Username and password are required'
      ]));
    }

    $user = $this->authService->login($username, $password);

    if (!$user) {
      return new Response($this->app->getTwig()->render('login.twig', [
        'error' => 'Invalid username or password'
      ]));
    }

    return new Response('', 302, ['Location' => '/dashboard']);
  }

  public function logout(Request $request): Response
  {
    $this->authService->logout();
    return new Response('', 302, ['Location' => '/login']);
  }

  public function showRegister(Request $request): Response
  {
    $this->authService->requireAdmin();

    return new Response($this->app->getTwig()->render('register.twig'));
  }

  public function register(Request $request): Response
  {
    $this->authService->requireAdmin();

    $username = $request->getInput('username');
    $email = $request->getInput('email');
    $password = $request->getInput('password');
    $role = $request->getInput('role') ?? 'marketer';

    if (!$username || !$email || !$password) {
      return new Response($this->app->getTwig()->render('register.twig', [
        'error' => 'All fields are required'
      ]));
    }

    try {
      $this->authService->createUser([
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'role' => $role
      ]);

      return new Response('', 302, ['Location' => '/admin/users']);
    } catch (\Exception $e) {
      return new Response($this->app->getTwig()->render('register.twig', [
        'error' => 'Failed to create user: ' . $e->getMessage()
      ]));
    }
  }
}
