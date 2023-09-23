<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;


class Image extends Model
{
    use HasFactory, HasEagerLimit;

    protected $fillable = [
        'image_id',
        'image',
        'thumbail'

    ];

    public function note()
    {
        return $this->belongsTo(Note::class, 'image_id', 'images_note_id');
    }
}
