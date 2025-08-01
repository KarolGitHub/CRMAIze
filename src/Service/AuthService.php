<?php

namespace CRMAIze\Service;

use CRMAIze\Repository\UserRepository;
use CRMAIze\Model\User;

class AuthService
{
  private $userRepository;

  public function __construct(UserRepository $userRepository)
  {
    $this->userRepository = $userRepository;
  }

  public function login(string $username, string $password): ?User
  {
    $user = $this->userRepository->findByUsername($username);

    if (!$user || !$user->isActive()) {
      return null;
    }

    if (!$this->verifyPassword($password, $user->getPasswordHash())) {
      return null;
    }

    // Update last login
    $this->userRepository->updateLastLogin($user->getId());

    // Start session
    $this->startSession();
    $_SESSION['user_id'] = $user->getId();
    $_SESSION['username'] = $user->getUsername();
    $_SESSION['role'] = $user->getRole();

    return $user;
  }

  public function logout(): void
  {
    $this->startSession();
    session_destroy();
  }

  public function getCurrentUser(): ?User
  {
    $this->startSession();

    if (!isset($_SESSION['user_id'])) {
      return null;
    }

    return $this->userRepository->findById($_SESSION['user_id']);
  }

  public function isLoggedIn(): bool
  {
    return $this->getCurrentUser() !== null;
  }

  public function requireAuth(): void
  {
    if (!$this->isLoggedIn()) {
      header('Location: /login');
      exit;
    }
  }

  public function requireRole(string $role): void
  {
    $this->requireAuth();

    $user = $this->getCurrentUser();
    if ($user->getRole() !== $role) {
      header('Location: /dashboard');
      exit;
    }
  }

  public function requireAdmin(): void
  {
    $this->requireAuth();

    $user = $this->getCurrentUser();
    if (!$user->isAdmin()) {
      header('Location: /dashboard');
      exit;
    }
  }

  public function hashPassword(string $password): string
  {
    return password_hash($password, PASSWORD_DEFAULT);
  }

  private function verifyPassword(string $password, string $hash): bool
  {
    return password_verify($password, $hash);
  }

  private function startSession(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }

  public function createUser(array $data): int
  {
    $data['password_hash'] = $this->hashPassword($data['password']);
    unset($data['password']); // Don't store plain password

    return $this->userRepository->create($data);
  }
}
