<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeContentTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_content_tags';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'content_tag_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_content_id', 'tag_id'
    ];

    protected $guarded = ['content_tag_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Org\Api\AppuserContent', 'employee_content_id', 'employee_content_id');
    }

    public function tag()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployeeTag', 'tag_id', 'tag_id');
    }

    public function scopeOfEmployeeContent($query, $id)
    {
        return $query->where('employee_content_id', $id);
    }
}