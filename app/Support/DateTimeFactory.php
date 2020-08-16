<?php


namespace App\Support;


use DateTime;
use DateTimeZone;

class DateTimeFactory implements DateTimeFactoryInterface
{

    /**
     * Returns the current time in UTC
     *
     * @return DateTime
     * @throws \Exception
     */
    public function getUtcNow()
    {
        return new DateTime('now', new DateTimeZone('UTC'));
    }
}
