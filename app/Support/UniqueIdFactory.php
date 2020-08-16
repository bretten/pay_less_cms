<?php


namespace App\Support;


class UniqueIdFactory implements UniqueIdFactoryInterface
{
    /**
     * Generates a unique ID
     *
     * @return mixed
     */
    public function generateUniqueId()
    {
        return uniqid(null, true);
    }
}
