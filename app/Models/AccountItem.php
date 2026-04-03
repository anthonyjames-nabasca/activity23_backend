<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountItem extends Model
{
    protected $table = 'account_items';
    protected $primaryKey = 'account_id';

    protected $fillable = [
        'user_id',
        'site',
        'account_username',
        'account_password',
        'account_image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}