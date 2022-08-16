<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThoughtTip extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'thought_tips';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'thought_tip_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['thought_tip_text', 'for_date', 'created_by', 'updated_by'];
    
    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $guarded = ['thought_tip_id', 'is_active', 'is_deleted'];

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    use SoftDeletes;

    /**
     * Attributes that cannot be assigned.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function scopeActive($query)
    {
        return $query->where('is_active', '=', 1)->where('is_deleted', '=', 0);
    } 

    public function scopeExists($query)
    {
        return $query->where('is_deleted', '=', 0);
    } 

    public function scopeForDate($query, $consDate)
    {
        return $query->whereDate('for_date', '=', $consDate);
    } 
}
