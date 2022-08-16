<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgEmployeeBadge extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'employee_badges';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'employee_badge_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id', 'badge_id'
    ];

    protected $guarded = ['employee_badge_id'];

    public function employee()
    {
        return $this->hasOne('App\Models\Org\Api\OrgEmployee', 'employee_id', 'employee_id');
    }

    public function badge()
    {
        return $this->hasOne('App\Models\Org\Api\OrgBadge', 'badge_id', 'badge_id');
    }

    public function scopeOfEmployee($query, $id)
    {
        return $query->where('employee_id', $id);
    }
}