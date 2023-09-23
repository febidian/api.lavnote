<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\Share;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, Note $note)
    {
        return $user->notes_user_id === $note->user_id ? Response::allow()
            : Response::deny('You do not own this post.');
    }

    public function storeshare(User $user, Note $note)
    {
        return $user->notes_user_id === $note->user_id ? Response::allow()
            : Response::deny('You do not own this post.');
    }

    public function storeduplicate(User $user, Share $share)
    {
        $note = Note::where('note_id', $share->note_id)->first();

        if (!$note) {
            return Response::deny('Note not found');
        }

        if ($user->notes_user_id === $note->user_id) {
            return Response::deny('Cannot duplicate own note');
        }

        $noteDuplicate = Note::where('user_id', $user->notes_user_id)->where('duplicate_id', $share->note_id)->first();

        if ($noteDuplicate) {
            return Response::deny("You've already duplicated this note");
        }
        return Response::allow();
    }
}
