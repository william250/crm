<?php

namespace App\Models;

use PDO;
use PDOException;

class User
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by email: " . $e->getMessage());
            return null;
        }
    }
    
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return null;
        }
    }
    
    public function create(array $userData): ?int
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password_hash, role, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $hashedPassword,
                $userData['role'] ?? 'basic'
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return null;
        }
    }
    
    public function update(int $id, array $userData): bool
    {
        try {
            $fields = [];
            $values = [];
            
            foreach ($userData as $field => $value) {
                if (in_array($field, ['name', 'email', 'role'])) {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $id;
            $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePassword(int $id, string $newPassword): bool
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$hashedPassword, $id]);
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }
    
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    public function updateLastLogin(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all users: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUsersByRole(string $role): array
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name, email FROM users WHERE role = ? ORDER BY name");
            $stmt->execute([$role]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting users by role: " . $e->getMessage());
            return [];
        }
    }
}