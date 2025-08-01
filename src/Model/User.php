<?php

namespace CRMAIze\Model;

class User
{
  private $id;
  private $username;
  private $email;
  private $passwordHash;
  private $role;
  private $isActive;
  private $lastLogin;
  private $createdAt;

  public function __construct(array $data = [])
  {
    $this->id = $data['id'] ?? null;
    $this->username = $data['username'] ?? '';
    $this->email = $data['email'] ?? '';
    $this->passwordHash = $data['password_hash'] ?? '';
    $this->role = $data['role'] ?? 'marketer';
    $this->isActive = $data['is_active'] ?? true;
    $this->lastLogin = $data['last_login'] ?? null;
    $this->createdAt = $data['created_at'] ?? null;
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getUsername(): string
  {
    return $this->username;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getRole(): string
  {
    return $this->role;
  }

  public function isActive(): bool
  {
    return $this->isActive;
  }

  public function isAdmin(): bool
  {
    return $this->role === 'admin';
  }

  public function isMarketer(): bool
  {
    return $this->role === 'marketer';
  }

  public function getLastLogin(): ?string
  {
    return $this->lastLogin;
  }

  public function getCreatedAt(): ?string
  {
    return $this->createdAt;
  }

  public function getPasswordHash(): string
  {
    return $this->passwordHash;
  }

  public function toArray(): array
  {
    return [
      'id' => $this->id,
      'username' => $this->username,
      'email' => $this->email,
      'role' => $this->role,
      'is_active' => $this->isActive,
      'last_login' => $this->lastLogin,
      'created_at' => $this->createdAt
    ];
  }

  public static function createFromArray(array $data): self
  {
    return new self($data);
  }
}
