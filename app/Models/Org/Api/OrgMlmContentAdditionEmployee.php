<?php

namespace App\Models\Org\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgMlmContentAdditionEmployee extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'mlm_content_addition_employees';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'mlm_content_addition_rec_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['mlm_content_addition_id', 'employee_id', 'status'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['mlm_content_addition_rec_id'];
    
    public function content()
    {
        return $this->hasOne('App\Models\Org\Api\OrgMlmContentAddition', 'mlm_content_addition_id', 'mlm_content_addition_id');
    }
}
