<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;


class Star extends Model
{
    use HasFactory, HasEagerLimit;

    protected $fillable = ['star_id', 'star'];

    public function notes()
    {
        return $this->belongsTo(Note::class,  'star_id', 'star_notes_id');
    }
}
