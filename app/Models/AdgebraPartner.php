<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdgebraPartner extends Model
{
    protected $table = 'adgebra_partners';

    protected $fillable = [
        'username',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];
}
