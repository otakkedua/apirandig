<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';

    protected $fillable = ['post_id', 'user_id', 'content', 'status'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function admin()
    {
        return $this->belongsTo(\App\Models\Admin::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'comment_likes')->withTimestamps();
    }

    public function likesCount()
    {
        return $this->likedByUsers()->count();
    }
}
