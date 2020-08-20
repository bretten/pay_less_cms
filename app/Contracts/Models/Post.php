<?php


namespace App\Contracts\Models;


use DateTime;

class Post
{
    public $id;
    public string $site;
    public string $title;
    public string $content;
    public string $humanReadableUrl;
    public DateTime $createdAt;
    public DateTime $updatedAt;
    public ?Datetime $deletedAt;

    /**
     * Post constructor.
     *
     * @param int $id
     * @param string $site
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @param DateTime $createdAt
     * @param DateTime $updatedAt
     * @param DateTime|null $deletedAt
     */
    public function __construct($id, string $site, string $title, string $content, string $humanReadableUrl, DateTime $createdAt, DateTime $updatedAt, ?Datetime $deletedAt)
    {
        $this->id = $id;
        $this->site = $site;
        $this->title = $title;
        $this->content = $content;
        $this->humanReadableUrl = $humanReadableUrl;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }

}
