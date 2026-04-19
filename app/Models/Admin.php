<?php
namespace App\Models;

class Admin {
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password_hash;
    public $role;
    public $phone;
    public $is_active;
    public $last_login_at;
    public $created_at;
    public $updated_at;
}