<?php


namespace App\Support;


use DateTime;

interface DateTimeFactoryInterface
{
    /**
     * Should return the current time in UTC
     *
     * @return DateTime
     */
    public function getUtcNow();
}
