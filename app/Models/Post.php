<?php

namespace App\Models;

use App\Contracts\Models\Post as PostContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Converts the Eloquent Post Model to a simple Post object
     *
     * @return PostContract
     */
    public function toSimple()
    {
        return new PostContract($this->id, $this->site, $this->title, $this->content, $this->human_readable_url, $this->created_at, $this->updated_at, $this->deleted_at);
    }
}
