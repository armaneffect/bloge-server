<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    protected $appends = ['image_full_url', 'is_bookmarked', 'like_count', 'is_liked']; // ✅ এটা এখন ঠিক জায়গায় আছে

    public function image()
    {
        return $this->hasOne(Image::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getImageFullUrlAttribute()
    {
        return $this->image && $this->image->image_url
            ? asset('storage/' . $this->image->image_url)
            : null;
    }

    public function getIsBookmarkedAttribute(): bool
    {
        return Auth::check() && Bookmark::where('post_id', $this->id)
            ->where('user_id', Auth::id())
            ->exists();
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function getLikeCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function getIsLikedAttribute(): bool
    {
        return Auth::check() && Like::where('post_id', $this->id)
            ->where('user_id', Auth::id())
            ->exists();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)
            ->with('user') // Include user relationship to access user data in comments
            ->orderBy('created_at', 'desc'); // Order comments by creation date
    }

   

}
