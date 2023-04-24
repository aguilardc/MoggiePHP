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
     * @return array
     */
    public function update(Request $request): array
    {
        // @TODO: update a resource
    }

    /**
     * delete a resource
     *
     * @param Request $request
     * @return array
     */
    public function delete(Request $request): array
    {
        // @TODO: delete a resource
    }
}
