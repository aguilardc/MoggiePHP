<?php

namespace App\Controllers;


use App\interface\crud;
use App\Models\User;
use Core\Request\Request;

class UsersController implements crud
{
    /**
     * create a resource
     *
     * @param Request $request
     * @return string
     */
    public function create(Request $request): string
    {
        // @TODO: create a resource
        return 'create a user';
    }

    /**
     * read a resource
     *
     * @param Request $request
     * @return array
     */
    public function read(Request $request): array
    {
        $users = new User();
        return $users->find();
    }

    /**
     * read one resource by id
     *
     * @param Request $request
     * @return array
     */
    public function readById(Request $request): array
    {
        $users = new User();
        return $users->findOne($request->get('id'));
    }

    /**
     * update a resource
     *
     * @param Request $request
     * @return string
     */
    public function update(Request $request): string
    {
        // @TODO: update a resource
        return 'update user';
    }

    /**
     * delete a resource
     *
     * @param Request $request
     * @return string
     */
    public function delete(Request $request): string
    {
        // @TODO: delete a resource
        return 'delete user';
    }
}
