<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tagIds = Tag::pluck('id');

        Contact::factory(20)->create()->each(function ($contact) use ($tagIds) {
            $contact->tags()->attach($tagIds->random(rand(1, 3)));
        });

    }
}
