<?php


namespace App\Support;


class UniqueIdFactory implements UniqueIdFactoryInterface
{
    /**
     * Generates a unique ID that is sortable by time
     *
     * @return mixed
     */
    public function generateSortableByTimeUniqueId()
    {
        // Underlying function of uniqid similar to first 8 hex chars = unix time, last 5 chars = microseconds, so will be sortable by time
        return uniqid(null, true);
    }
}
