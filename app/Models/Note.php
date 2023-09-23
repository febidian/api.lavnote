<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;

class Note extends Model
{
    use HasFactory, SoftDeletes, HasEagerLimit;

    protected $fillable = [
        'user_id', 'note_id', 'title', 'category', 'star_notes_id', 'images_notes_id', 'note_content', 'duplicate_id'
    ];

    // public function getShortNoteContentAttribute()
    // {
    //     return Str::words(strip_tags($this->attributes['note_content']), 10);
    // }

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'notes_user_id');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'image_id', 'images_notes_id');
    }

    public function stars()
    {
        return $this->hasOne(Star::class, 'star_id', 'star_notes_id');
    }
}
