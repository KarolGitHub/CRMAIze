<?php

namespace CRMAIze\Repository;

use CRMAIze\Service\DatabaseService;
use CRMAIze\Model\User;

class UserRepository
{
  private $db;

  public function __construct(DatabaseService $db)
  {
    $this->db = $db;
  }

  public function findByUsername(string $username): ?User
  {
    $result = $this->db->query(
      "SELECT * FROM users WHERE username = ? AND is_active = 1",
      [$username]
    );

    if (empty($result)) {
      return null;
    }

    return User::createFromArray($result[0]);
  }

  public function findByEmail(string $email): ?User
  {
    $result = $this->db->query(
      "SELECT * FROM users WHERE email = ? AND is_active = 1",
      [$email]
    );

    if (empty($result)) {
      return null;
    }

    return User::createFromArray($result[0]);
  }

  public function findById(int $id): ?User
  {
    $result = $this->db->query(
      "SELECT * FROM users WHERE id = ? AND is_active = 1",
      [$id]
    );

    if (empty($result)) {
      return null;
    }

    return User::createFromArray($result[0]);
  }

  public function create(array $data): int
  {
    $this->db->execute(
      "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
      [
        $data['username'],
        $data['email'],
        $data['password_hash'],
        $data['role'] ?? 'marketer'
      ]
    );

    return (int) $this->db->lastInsertId();
  }

  public function updateLastLogin(int $userId): bool
  {
    return $this->db->execute(
      "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?",
      [$userId]
    );
  }

  public function getAll(): array
  {
    $result = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");

    return array_map(function ($row) {
      return User::createFromArray($row);
    }, $result);
  }

  public function updateRole(int $userId, string $role): bool
  {
    return $this->db->execute(
      "UPDATE users SET role = ? WHERE id = ?",
      [$role, $userId]
    );
  }

  public function deactivate(int $userId): bool
  {
    return $this->db->execute(
      "UPDATE users SET is_active = 0 WHERE id = ?",
      [$userId]
    );
  }

  public function activate(int $userId): bool
  {
    return $this->db->execute(
      "UPDATE users SET is_active = 1 WHERE id = ?",
      [$userId]
    );
  }
}
