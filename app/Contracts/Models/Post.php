<?php


namespace App\Contracts\Models;


use DateTime;

class Post
{
    public int $id;
    public string $title;
    public string $content;
    public string $human_readable_url;
    public DateTime $created_at;
    public DateTime $updated_at;
    public ?Datetime $deleted_at;

    /**
     * Post constructor.
     *
     * @param int $id
     * @param string $title
     * @param string $content
     * @param string $human_readable_url
     * @param DateTime $created_at
     * @param DateTime $updated_at
     * @param DateTime|null $deleted_at
     */
    public function __construct(int $id, string $title, string $content, string $human_readable_url, DateTime $created_at, DateTime $updated_at, ?Datetime $deleted_at)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->human_readable_url = $human_readable_url;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->deleted_at = $deleted_at;
    }

}
