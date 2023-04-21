<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace Core\DB;

use PDO;

class Database
{
    private string $db_engine;
    private string $db_host;
    private string $db_name;
    private string $db_user;
    private string $db_password;
    private string $db_charset;
    private static ?Database $instance = null;

    private function __construct()
    {
        $this->db_engine = env('DB_ENGINE') ?? '';
        $this->db_host = env('DB_HOST') ?? '';
        $this->db_name = env('DB_NAME') ?? '';
        $this->db_user = env('DB_USER') ?? '';
        $this->db_password = env('DB_PASSWORD') ?? '';
        $this->db_charset = env('DB_CHARSET') ?? '';
    }

    /**
     * @return self
     */
    public static function init(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return PDO
     */
    public function connection(): PDO
    {
        try {
            return new PDO(
                "{$this->db_engine}:dbname={$this->db_name};host={$this->db_host}",
                $this->db_user,
                $this->db_password, [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$this->db_charset}'"
            ]);
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }
}
