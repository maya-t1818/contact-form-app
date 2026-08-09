<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexContactRequest $request)
    {
        $query = Contact::with(['category', 'tags']);

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('email', 'like', '%'.$keyword.'%');
            });
        }

        $perPage = $request->input('per_page', 20);

        $contacts = $query->latest()->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $contact = Contact::create($validated());

        if ($request->has('tag_ids')) {
            $contact->tags()->attach($request->input('tag_ids'));
        }

        return (new ContactResource($contact->load(['category', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return new ContactResource($contact);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $validated = $request->validated();
        $contact->update($validated);

        if ($request->has('tag_ids')) {
            $contact->tags()->sync($request->input('tag_ids'));
        }

        return new ContactResource($contact->load(['category', 'tags']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
