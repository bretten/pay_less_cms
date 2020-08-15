<?php


namespace App\Contracts\Models;


use DateTime;

class Post
{
    public int $id;
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
     * @param string $title
     * @param string $content
     * @param string $humanReadableUrl
     * @param DateTime $createdAt
     * @param DateTime $updatedAt
     * @param DateTime|null $deletedAt
     */
    public function __construct(int $id, string $title, string $content, string $humanReadableUrl, DateTime $createdAt, DateTime $updatedAt, ?Datetime $deletedAt)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->humanReadableUrl = $humanReadableUrl;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }

}
