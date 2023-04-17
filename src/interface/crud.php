<?php

namespace App\interface;

interface crud
{
    /**
     * create a resource
     * @return mixed
     */
    public function create();

    /**
     * read a resource
     * @return mixed
     */
    public function read();

    /**
     * update a resource
     * @return mixed
     */
    public function update();

    /**
     * delete a resource
     * @return mixed
     */
    public function delete();
}