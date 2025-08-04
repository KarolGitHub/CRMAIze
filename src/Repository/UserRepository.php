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
    $isActive = $this->db->isPostgreSQL() ? true : 1;
    $result = $this->db->query(
      "SELECT * FROM users WHERE username = ? AND is_active = ?",
      [$username, $isActive]
    );

    if (empty($result)) {
      return null;
    }

    return User::createFromArray($result[0]);
  }

  public function findByEmail(string $email): ?User
  {
    $isActive = $this->db->isPostgreSQL() ? true : 1;
    $result = $this->db->query(
      "SELECT * FROM users WHERE email = ? AND is_active = ?",
      [$email, $isActive]
    );

    if (empty($result)) {
      return null;
    }

    return User::createFromArray($result[0]);
  }

  public function findById(int $id): ?User
  {
    $isActive = $this->db->isPostgreSQL() ? true : 1;
    $result = $this->db->query(
      "SELECT * FROM users WHERE id = ? AND is_active = ?",
      [$id, $isActive]
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
    $isActive = $this->db->isPostgreSQL() ? false : 0;
    return $this->db->execute(
      "UPDATE users SET is_active = ? WHERE id = ?",
      [$isActive, $userId]
    );
  }

  public function activate(int $userId): bool
  {
    $isActive = $this->db->isPostgreSQL() ? true : 1;
    return $this->db->execute(
      "UPDATE users SET is_active = ? WHERE id = ?",
      [$isActive, $userId]
    );
  }
}
