<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateNotesRequest;
use App\Http\Requests\UpdateNotesRequest;
use App\Http\Resources\NotesDeleteResource;
use App\Http\Resources\NotesResource;
use App\Http\Resources\NotesShowResource;
use App\Http\Resources\StarResource;
use App\Models\Image as IsImage;
use App\Models\Note;
use App\Models\Share;
use App\Models\Star;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use HtmlTruncator\Truncator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use Illuminate\Http\File;

class NotesController extends Controller
{

    public function index(string $category = null)
    {
        try {
            if ($category == null) {
                $notes = Note::where('user_id', Auth::user()->notes_user_id)
                    ->with(['images' => function ($q) {
                        $q->limit(3);
                    }])->withCount('images')
                    ->whereHas('stars', function ($q) {
                        $q->where('star', false);
                    })->orderBy('updated_at', 'desc')->paginate(12);
                foreach ($notes as $note) {
                    $note->note_content = Truncator::truncate($note->note_content, 70);
                }
            } else if ($category != "Unknown") {
                Note::where('category', $category)->firstOrFail();
                $notes = Note::where('user_id', Auth::user()->notes_user_id)
                    ->where('category', $category)->with(['images' => function ($q) {
                        $q->limit(3);
                    }])->withCount('images')->whereHas('stars', function ($q) {
                        $q->where('star', false);
                    })->orderBy('updated_at', 'desc')->paginate(12);
                foreach ($notes as $note) {
                    $note->note_content = Truncator::truncate($note->note_content, 70);
                }
            } else if ($category == "Unknown") {
                $notes = Note::where('user_id', Auth::user()->notes_user_id)
                    ->whereNull('category')->with(['images' => function ($q) {
                        $q->limit(3);
                    }])->withCount('images')->whereHas('stars', function ($q) {
                        $q->where('star', false);
                    })->orderBy('updated_at', 'desc')->paginate(12);
                foreach ($notes as $note) {
                    $note->note_content = Truncator::truncate($note->note_content, 70);
                }
            }


            if ($notes) {
                return response()->json([
                    'notes' => NotesResource::collection($notes)->response()->getData(),
                    'status' => 'success'
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'status' => 'failed'
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (QueryException $q) {
            return response()->json([
                'status' => 'failed',
                'q' => $q
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function category()
    {
        try {
            $category = Note::where('user_id', Auth::user()->notes_user_id)->select('category')->whereHas('stars', function ($q) {
                $q->where('star', false);
            })->orderBy('updated_at', 'desc')->get();
            $desiredArray = Collection::make($category)->pluck('category')->toArray();
            $result = collect($desiredArray)->unique()->values();
            $replacedCollection = $result->map(function ($value) {
                return $value === null ? 'Unknown' : $value;
            });
            return response()->json([
                'category' => $replacedCollection->all(),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $m) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function select()
    {
        try {
            $category = Note::where('user_id', Auth::user()->notes_user_id)->select('category')->whereNotNull('category')->orderBy('updated_at', 'desc')->get();
            $desiredArray = Collection::make($category)->pluck('category')->toArray();
            $result = collect($desiredArray)->unique();

            return response()->json([
                'category' => $result->values()->all(),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $m) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CreateNotesRequest $request)
    {
        $uniqueId = Str::random(20);
        $uniqueStart = Str::ulid();
        $files = $request->file('images');
        try {
            $created = Note::create([
                'user_id' => Auth::user()->notes_user_id,
                'note_id' => Str::uuid(),
                'title' => $request->title,
                'category' => $request->category,
                'star_notes_id' => $uniqueStart,
                'images_notes_id' => $uniqueId,
                'note_content' => $request->note,
            ]);
            if ($created) {
                Star::create(['star_id' => $uniqueStart]);
            }
            if ($request->hasFile('images')) {
                foreach ($files as $image) {
                    $pathOriginal = $image->store('original/note_images');
                    $pathThumbail = $image->store('thumbail/note_images');
                    $resizedImage = Image::make(public_path("storage/{$pathThumbail}"));
                    $resizedImage->resize(null, 120, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $resizedImage->save(public_path("storage/{$pathThumbail}"));
                    IsImage::create([
                        'image_id' => $uniqueId,
                        'image' => $pathOriginal,
                        'thumbail' => $pathThumbail
                    ]);
                }
            }

            return response()->json([
                'message' => 'Note created successfully.',
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Note creation failed.',
                'status' => 'failed',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(UpdateNotesRequest $request, Note $note)

    {
        $this->authorize('update', $note);
        try {

            $note->update([
                'title' => $request->title,
                'category' => $request->category,
                'note_content' => $request->note,
            ]);

            $id = $request->input('images');
            $idToArray = explode(',', $id);

            $files = $request->file('newimages');

            if ($request->input('images')) {
                $imagedelete = IsImage::where('image_id', $note->images_notes_id)
                    ->whereNotIn('id', $idToArray)->get();

                foreach ($imagedelete as $image) {
                    Storage::delete($image->image);
                    Storage::delete($image->thumbail);
                    $image->delete();
                }
            } else {
                $imagedelete = IsImage::where('image_id', $note->images_notes_id)->get();
                if ($imagedelete) {
                    foreach ($imagedelete as $image) {
                        Storage::delete($image->image);
                        Storage::delete($image->thumbail);
                        $image->delete();
                    }
                }
            }

            if ($request->hasFile('newimages')) {
                foreach ($files as $image) {
                    $pathOriginal = $image->store('original/note_images');
                    $pathThumbail = $image->store('thumbail/note_images');
                    $resizedImage = Image::make(public_path("storage/{$pathThumbail}"));
                    $resizedImage->resize(null, 130, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    $resizedImage->save(public_path("storage/{$pathThumbail}"));
                    IsImage::create([
                        'image_id' => $note->images_notes_id,
                        'image' => $pathOriginal,
                        'thumbail' => $pathThumbail
                    ]);
                }
            }
            return response()->json([
                'message' => 'Note successfully updated.',
                'status' => 'success',
            ], Response::HTTP_CREATED);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Note update failed.',
                'status' => 'failed',
                'error' => $q
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    public function show(Note $note)
    {
        $this->authorize('update', $note);

        try {
            $data = Note::where('note_id', $note->note_id)->with(['stars' => function ($q) {
                $q->latest()->limit(1);
            }])->with('images')->withCount('images')->firstOrFail();
            return response()->json([
                'note' => new NotesShowResource($data),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $m) {
            return response()->json([
                'status' => 'failed'
            ], Response::HTTP_NOT_FOUND);
        }
    }


    public function star()
    {
        try {
            $star = Star::where('star', 1)->whereHas('notes', function ($q) {
                $q->where('user_id', Auth::user()->notes_user_id);
            })->whereHas('notes', function ($q) {
                $q->whereNull('deleted_at');
            })->with(['notes' => function ($q) {
                $q->with(['images' => function ($q) {
                    $q->limit(3);
                }])->withCount('images');
            }])
                ->orderBy('updated_at', 'DESC')->paginate(4);
            return response()->json([
                'data' => StarResource::collection($star)->response()->getData(),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function starupdate($note_id)
    {
        try {
            $note = Note::where('note_id', $note_id)->with(['images' => function ($q) {
                $q->limit(3);
            }])->withCount('images')->firstOrFail();
            if ($note->stars->star) {
                $note->stars()->update([
                    'star' => false
                ]);
                $note->update([
                    'updated_at' => now()
                ]);
                $star = false;

                return response()->json([
                    'star' => $star,
                    'note' => new NotesResource($note),
                    'status' => 'success'
                ], Response::HTTP_OK);
            } else {
                $note->stars()->update([
                    'star' => true
                ]);
                $star = true;
                $startfirst = Star::where('star', 1)->with(['notes' => function ($q) {
                    $q->with(['images' => function ($q) {
                        $q->limit(3);
                    }])->withCount('images');
                }])->latest('updated_at')->first();

                return response()->json([
                    'star' => $star,
                    'note' => new StarResource($startfirst),
                    'status' => 'success'
                ], Response::HTTP_OK);
            }
        } catch (QueryException $th) {
            return response()->json([
                'erro' => $th,
                'status' => 'failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function softdestroy($note_id)
    {
        try {
            Note::where('note_id', $note_id)->firstOrFail()->delete();
            return response()->json([
                'message' => 'Note successfully temporarily deleted.',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Note deletion unsuccessful.',
                'status' => 'failed'
            ], Response::HTTP_NOT_FOUND);
        }
    }
    public function forcedestroy($note_id)
    {
        try {
            Note::where('user_id', Auth::user()->notes_user_id)->withTrashed('note_id', $note_id)->firstOrFail()->forceDelete();
            return response()->json([
                'message' => 'Note permanently deleted successfully.',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Note deletion unsuccessful.',
                'status' => 'failed'
            ], Response::HTTP_NOT_FOUND);
        }
    }
    public function forcedestroyall()
    {
        try {
            Note::where('user_id', Auth::user()->notes_user_id)->onlyTrashed()->forceDelete();
            return response()->json([
                'message' => 'All notes successfully deleted permanently.',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'All notes deletion unsuccessful.',
                'status' => 'failed'
            ], Response::HTTP_NOT_FOUND);
        }
    }


    public function showdestroy()
    {
        try {
            $notes = Note::where('user_id', Auth::user()->notes_user_id)->with('images')->withCount('images')->onlyTrashed()->paginate(12);
            foreach ($notes as $note) {
                $note->note_content = Truncator::truncate($note->note_content, 70);
            }
            return response()->json([
                'data' => NotesDeleteResource::collection($notes)->response()->getData(),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'status' => 'failed'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function restoreall()
    {
        try {
            Note::where('user_id', Auth::user()->notes_user_id)->onlyTrashed()->restore();

            return response()->json([
                'message' => 'All notes have been restored.',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'All notes failed to restore.',
                'status' => 'failed'
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public function restoreid($note_id)
    {
        try {
            Note::where('user_id', Auth::user()->notes_user_id)
                ->withTrashed()->where('note_id', $note_id)->restore();

            return response()->json([
                'message' => 'Notes have been restored.',
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'Notes failed to restored.',
                'status' => 'failed'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function storeshare(Note $note)
    {
        $this->authorize('storeshare', $note);
        try {
            $selectShare = Share::where('note_id', $note->note_id)->first();
            if ($selectShare) {
                if (Carbon::now()->gt($note->expired_at)) {
                    $note->delete();
                    $share =  Share::create([
                        'note_id' => $note->note_id,
                        'url_generate' => Str::uuid(),
                        'expired_at' => now()->addMinutes(30),
                    ]);
                } else {
                    $share = Share::where('note_id', $note->note_id)->first();
                }
            } else {
                $share = Share::create([
                    'note_id' => $note->note_id,
                    'url_generate' => Str::uuid(),
                    'expired_at' => now()->addMinutes(30),
                ]);
            }
            return response()->json([
                'url' => "http://127.0.0.1:3000/note/" . $share->url_generate . "/share",
                'status' => 'success'
            ], Response::HTTP_CREATED);
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed',
                'th' => $th
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function showshare($url)
    {
        try {
            $share = Share::where('url_generate', $url)->first();

            if ($share) {
                if (Carbon::now()->gt($share->expired_at)) {
                    $share->delete();
                    return response()->json([
                        'message' => 'The link has expired.',
                    ], 404);
                } else {
                    $note = Note::where('note_id', $share->note_id)->with('images')->withCount('images')->firstOrFail();
                }
            } else {
                return response()->json([
                    'message' => 'The link has expired.',
                ], 404);
            }

            return response()->json([
                'note' => new NotesShowResource($note),
                'status' => 'success'
            ], Response::HTTP_OK);
        } catch (QueryException $th) {
            return response()->json([
                'status' => 'failed',
                'tf' => $th
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function duplicate(Share $share)
    {
        $this->authorize('storeduplicate', $share);

        try {
            if ($share) {
                if (Carbon::now()->gt($share->expired_at)) {
                    $share->delete();
                    return response()->json([
                        'message' => 'The link has expired.',
                        'status' => 'failed'
                    ], Response::HTTP_NOT_FOUND);
                } else {
                    $uniqueStart = Str::ulid();
                    $uniqueId = Str::random(20);

                    $cloneNote = Note::where('note_id', $share->note_id)->first();

                    $newCloneNote = $cloneNote->replicate()->fill([
                        'user_id' => Auth::user()->notes_user_id,
                        'note_id' => Str::uuid(),
                        'duplicate_id' => $share->note_id,
                        'star_notes_id' => $uniqueStart,
                        'images_notes_id' => $uniqueId,
                    ]);
                    $newCloneNote->save();

                    if ($cloneNote->stars) {
                        Star::create(['star_id' => $uniqueStart]);
                    }

                    if ($cloneNote->images) {
                        foreach ($cloneNote->images as $image) {

                            $nameRandom  = Str::random(32);
                            $sourcePath = Storage::path($image->image);
                            $pathInfo = pathinfo($sourcePath);
                            $newImagePath = 'original/note_images/' . $nameRandom . '.' . $pathInfo['extension'];
                            $newImagePathThumbail = 'thumbail/note_images/' . $nameRandom . '.' . $pathInfo['extension'];
                            Storage::copy($image->image, $newImagePath);
                            Storage::copy($image->thumbail, $newImagePathThumbail);

                            IsImage::create([
                                'image_id' => $uniqueId,
                                'image' => $newImagePath,
                                'thumbail' => $newImagePathThumbail
                            ]);
                        }
                    }
                    return response()->json([
                        'message' => 'Note successfully duplicated.',
                        'status' => 'success'
                    ], Response::HTTP_OK);
                }
            } else {
                return response()->json([
                    'message' => 'The link has expired.',
                    'status' => 'failed'
                ], Response::HTTP_NOT_FOUND);
            }
        } catch (ModelNotFoundException $th) {
            return response()->json([
                'status' => 'failed'
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
