<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmContentAdditionAppuser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'mlm_content_addition_appusers';

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
    protected $fillable = ['mlm_content_addition_id', 'appuser_id', 'status'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['mlm_content_addition_rec_id'];
    
    public function notification()
    {
        return $this->hasOne('App\Models\MlmContentAddition', 'mlm_content_addition_id', 'mlm_content_addition_id');
    }
}
