<?php


namespace App\Support;


interface UniqueIdFactoryInterface
{
    /**
     * Should return a unique ID
     *
     * @return mixed
     */
    public function generateUniqueId();
}
