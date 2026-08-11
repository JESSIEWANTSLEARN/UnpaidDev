<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WBOUser extends Model
{
    protected $table = 'WBO_Users';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'password_hash',
        'role',
        'account_status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}