<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();
        $tags = Tag::all();
        $inputs = $request->old() ?: $request->session()->get('contact_input', $request->all());

        return view('contact.index', compact('categories', 'tags', 'inputs'));
    }

    public function confirm(ContactRequest $request)
    {
        $validated = $request->validated();
        $request->session()->put('contact_input', $validated);
        $category = Category::find($validated['category_id']);
        $tags = Tag::findMany($validated['tag_ids'] ?? []);

        if (empty($request->old()) && $request->session()->has('contact_input')) {
            $request->session()->flashInput($request->session()->get('contact_input'));
        }

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(Request $request)
    {
        $input = $request->session()->get('contact_input');

        if (! $input) {
            return redirect()->route('contact.index');
        }
        if ($request->has('back')) {
            return redirect()->route('contact.index')->withInput($input);
        }

        $contact = Contact::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'gender' => $input['gender'],
            'email' => $input['email'],
            'tel' => $input['tel'],
            'address' => $input['address'],
            'building' => $input['building'] ?? null,
            'detail' => $input['detail'],
            'category_id' => $input['category_id'],
        ]);

        if (isset($input['tag_ids'])) {
            $contact->tags()->attach($input['tag_ids']);
        }

        $request->session()->forget('contact_input');

        return redirect()->route('contact.thanks');

    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}
