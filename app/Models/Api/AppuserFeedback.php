<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppuserFeedback extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'appuser_feedbacks';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'appuser_feedback_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'feedback_id', 'appuser_id', 'feedback_text'
    ];

    protected $guarded = ['appuser_feedback_id'];

    public function appuser()
    {
        return $this->hasOne('App\Models\Appuser', 'appuser_id');
    }

    public function scopeOfUser($query, $userId)
    {
        return $query->where('appuser_id', $userId);
    }

    public function scopeFindByUserAndId($query, $userId, $feedbackId)
    {
        return $query->where($this->table.'.appuser_id', $userId)->where($this->table.'.feedback_id', $feedbackId);
    }
}
