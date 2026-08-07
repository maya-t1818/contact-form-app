<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTagRequest;

class TagController extends Controller
{

    public function store(StoreTagRequest $request)
    {
        Tag::create($request->validated());

        return redirect()->route('admin.index')->with('success', 'タグを作成しました');
    }
    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(StoreTagRequest $request, Tag $tag)
    {
        $tag->update($request->validated());

        return redirect()->route('admin.index')->with('success', 'タグ名を更新しました');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('admin.index')->with('success', 'タグを削除しました。');
    }
}

