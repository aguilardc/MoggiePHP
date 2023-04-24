<?php

namespace App\interface;

use Core\Request\Request;

interface crud
{
    /**
     * create a resource
     * @return mixed
     */
    public function create(Request $request);

    /**
     * read a resource
     * @return mixed
     */
    public function read(Request $request);

    /**
     * update a resource
     * @return mixed
     */
    public function update(Request $request);

    /**
     * delete a resource
     * @return mixed
     */
    public function delete(Request $request);
}
