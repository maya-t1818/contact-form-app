<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Requests\indexContactRequest;
use Symfony\Component\HttpFoundation\StreamedResponse; 

class AdminController extends Controller
{
    public function index(indexContactRequest $request)
    {
        $query = $this->buildSearchQuery($request);

        $contacts = $query->paginate(7);
        $categories = Category::all();
        $tags = Tag::all();
        $genderLabels = [
            '1.男性' => '男性',
            '2.女性' => '女性',
            '3.その他' => 'その他',
        ];

        return view('admin.index', compact('contacts', 'categories', 'tags', 'genderLabels'));
    }

    public function show($id)
    {
        $contact = Contact::with('category', 'tags')->findOrFail($id);
        $genderLabels = [
            '1.男性' => '男性',
            '2.女性' => '女性',
            '3.その他' => 'その他',
        ];

        return view('admin.show', ['user' => auth()->user()], compact('contact', 'genderLabels'));
    }

    private function buildSearchQuery(indexContactRequest $request)
    {
        $query = Contact::query()->with('category', 'tags');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($q) use ($keyword) {
                $q->where('last_name', 'like', '%' . $keyword . '%')
                ->orWhere('first_name', 'like', '%' . $keyword . '%')
                ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('gender') && $request->input('gender') != '0') {
            $genderValues = [
                '1' => '1.男性',
                '2' => '2.女性',
                '3' => '3.その他',
            ];

            $query->where('gender', $genderValues[$request->input('gender')]);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        return $query;
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return redirect()->route('admin')->with('success', 'お問い合わせを削除しました。');
    }
}