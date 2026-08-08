<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;
use App\Http\Requests\ExportContactRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;



class ContactController extends Controller
{

    public function index(Request $request)
    {
        $categories = Category::all();
        $tags = Tag::all();
        $inputs = $request->old() ?: $request->session()->get('contact_input', $request->all());

        return view ('contact.index', compact('categories', 'tags','inputs'));
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

        if (!$input) {
            return redirect()->route('contact.index');
        }
        if ($request->has('back')) {
            return redirect()->route('contact.index')->withInput($input);
        }

        $contact = Contact::create([
            'first_name'  => $input['first_name'],
            'last_name'   => $input['last_name'],
            'gender'      => $input['gender'],
            'email'       => $input['email'],
            'tel'         => $input['tel'],
            'address'     => $input['address'],
            'building'    => $input['building'] ?? null,
            'detail'      => $input['detail'],
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
    
    public function export(ExportContactRequest $request): StreamedResponse
    {
        $query = Contact::query()->with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%' . $keyword . '%')
                ->orWhere('last_name', 'like', '%' . $keyword . '%')
                ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('gender') && $request->input('gender') != '0') {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $contacts = $query->latest()->get();

        $genderMap = [
            1 => '男性',
            2 => '女性',
            3 => 'その他',
        ];

        $response = new StreamedResponse(function () use ($contacts, $genderMap) {
            $stream = fopen('php://output', 'w');

            fwrite($stream, "\xEF\xBB\xBF");

            fputcsv($stream, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            foreach ($contacts as $contact) {
                
                $genderStr = $genderMap[$contact->gender] ?? '未回答';

                
                $fullName = trim(($contact->last_name ?? '') . ' ' . ($contact->first_name ?? ''));

                fputcsv($stream, [
                    $contact->id,
                    $fullName,
                    $genderStr,
                    $contact->email,
                    $contact->tell ?? $contact->tel ?? $contact->phone, 
                    $contact->address,
                    $contact->building,
                    $contact->category ? $contact->category->name : '',
                    $contact->detail, 
                    $contact->created_at ? $contact->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($stream);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="contacts_' . date('YmdHis') . '.csv"');

        return $response;
    }

}
