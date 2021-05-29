<?php


namespace App\Models;

use App\Contracts\Models\Site as SiteContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;

    /**
     * The primary key column name
     *
     * @var string
     */
    protected $primaryKey = "domain_name";

    /**
     * Indicates if the model's ID auto increments
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key
     *
     * @var string
     */
    protected $keyType = "string";

    /**
     * Converts the Eloquent Site Model to a simple Site object
     *
     * @return SiteContract
     */
    public function toSimple()
    {
        return new SiteContract($this->domain_name, $this->title, $this->created_at, $this->updated_at, $this->deleted_at);
    }
}
