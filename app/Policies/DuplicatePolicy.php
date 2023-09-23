<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\Share;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DuplicatePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
}
