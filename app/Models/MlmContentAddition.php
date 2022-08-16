<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmContentAddition extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'mlm_content_additions';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'mlm_content_addition_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['content_text', 'server_filename', 'filename', 'is_draft', 'is_sent', 'sent_by', 'sent_at', 'created_by', 'updated_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['mlm_content_addition_id'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['sent_at'];

    public function appusers()
    {
        return $this->hasMany('App\Models\MlmContentAdditionAppuser');
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', '=', 1)->where('is_sent', '=', 0);
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', '=', 1);
    }

    public function scopeById($query, $id)
    {
        return $query->where('mlm_content_addition_id', '=', $id);
    }
}
