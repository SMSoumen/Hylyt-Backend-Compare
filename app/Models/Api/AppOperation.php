<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppOperation extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'app_operations';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'app_operation_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'last_scanned_mail_id', 'last_scanned_spam_mail_id'
    ];

    protected $guarded = ['app_operation_id'];

    /**
    * We do not wish for Eloquent to maintain timestamps
    *
    * @var string
    */
    public $timestamps = false;
}
