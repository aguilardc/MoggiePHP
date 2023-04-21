<?php

namespace App\Controllers;


use App\interface\crud;
use App\Models\User;

class UsersController implements crud
{
    /**
     * create a resource
     *
     * @return mixed
     */
    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * read a resource
     *
     * @return mixed
     */
    public function read()
    {
        $users = new User();
        return $users->find();
    }

    /**
     * read one resource by id
     *
     * @return mixed
     */
    public function readById($id): string
    {
        return "SELECT * FROM users WHERE id = {$id}";
    }

    /**
     * update a resource
     *
     * @return mixed
     */
    public function update()
    {
        // TODO: Implement update() method.
    }

    /**
     * delete a resource
     *
     * @return mixed
     */
    public function delete()
    {
        // TODO: Implement delete() method.
    }
//    public function create(): void
//    {
//        echo json_encode(['message' => 'hola mundo', 'method' => 'POST']);
//    }
//
//    public function read(): void
//    {
//        echo json_encode(['message' => 'hola mundo', 'method' => 'GET']);
//    }
//
//    public function readById($id): string
//    {
//        $query = "SELECT * FROM users WHERE id = {$id}";
//        return $query;
//    }
//
//    public function update(): void
//    {
//        echo json_encode(['message' => 'hola mundo', 'method' => 'PUT']);
//    }
//
//    public function delete(): void
//    {
//        echo json_encode(['message' => 'hola mundo', 'method' => 'DELETE']);
//    }
}
