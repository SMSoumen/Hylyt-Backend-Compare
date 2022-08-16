<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgGroupContentTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'org_group_content_tags';

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
        'group_content_id', 'employee_id', 'tag_id'
    ];

    protected $guarded = ['content_tag_id'];

    public function content()
    {
        return $this->hasOne('App\Models\Api\GroupContent', 'group_content_id', 'group_content_id');
    }

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id', 'employee_id');
    }

    public function tag()
    {
        return $this->hasOne('App\Models\Api\AppuserTag', 'tag_id', 'tag_id');
    }

    public function scopeOfGroupContent($query, $groupContentId)
    {
        return $query->where('group_content_id', $groupContentId);
    }

    public function scopeOfGroupContentAndEmployee($query, $groupContentId, $empId)
    {
        return $query->where('group_content_id', '=', $groupContentId)->where('employee_id', '=', $empId);
    }

    public function scopeOfEmployee($query, $empId)
    {
        return $query->where('employee_id', '=', $empId);
    }
}