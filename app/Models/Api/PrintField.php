<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrintField extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'print_fields';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'print_field_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'field_name'
    ];

    protected $guarded = ['print_field_id'];
}
