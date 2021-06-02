<?php


namespace App\Support;


interface UniqueIdFactoryInterface
{
    /**
     * Should return a unique ID that is sortable by time
     *
     * @return mixed
     */
    public function generateSortableByTimeUniqueId();
}
