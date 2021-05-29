<?php


namespace App\Models;

use App\Contracts\Models\Site as SiteContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;

    /**
     * Converts the Eloquent Site Model to a simple Site object
     *
     * @return SiteContract
     */
    public function toSimple()
    {
        return new SiteContract($this->domainName, $this->title, $this->created_at, $this->updated_at, $this->deleted_at);
    }
}
