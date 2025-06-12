<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'admins';

    protected $fillable = [
        'username',
        'email',
        'password',
        'nama_lengkap',
        'nomor_hp',
        'alamat',
        'role'
    ];

    protected $hidden = ['password', 'remember_token'];


    public function posts()
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
