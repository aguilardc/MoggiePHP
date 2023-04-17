<?php

namespace App\Models;

use Core\DB\Database;

class User extends Database
{
    private Database $database;

    public function __construct()
    {
        $this->database = Database::init();
    }


    public function find(): array
    {
        $conn = $this->database->connection();
        return $conn->query('SELECT * FROM users')->fetchAll();
    }
}