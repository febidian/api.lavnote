<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchResource;
use App\Models\Note;
use HtmlTruncator\Truncator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $query = $request->input('search');
        $results = Note::where('user_id', Auth::user()->notes_user_id)
            ->where('title', 'like', '%' . $query . '%')->limit(3)->get();

        foreach ($results as $note) {
            $note->note_content = Truncator::truncate($note->note_content, 10);
        }

        return response()->json([
            'notes' => SearchResource::collection($results),
            'status' => 'success',
        ], 200);
    }
}
