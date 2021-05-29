<?php


namespace App\Contracts\Models;


use DateTime;

class Site
{
    public string $domainName;
    public string $title;
    public DateTime $createdAt;
    public DateTime $updatedAt;
    public ?Datetime $deletedAt;

    /**
     * Site constructor
     *
     * @param string $domainName
     * @param string $title
     * @param DateTime $createdAt
     * @param DateTime $updatedAt
     * @param DateTime|null $deletedAt
     */
    public function __construct(string $domainName, string $title, DateTime $createdAt, DateTime $updatedAt, ?Datetime $deletedAt)
    {
        $this->domainName = $domainName;
        $this->title = $title;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }
}
