<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run()
    {
        // Ensure category exists
        $category = BlogCategory::firstOrCreate(
            ['slug' => 'noutati'],
            ['name' => 'Noutăți', 'is_published' => true]
        );

        $posts = [
            [
                'title' => 'Tendințe în construcții pentru 2026',
                'excerpt' => 'Descoperă noile tehnologii și materiale care vor domina piața construcțiilor în acest an.',
                'content' => '<p>Industria construcțiilor este în continuă evoluție...</p>',
                'image_path' => 'https://placehold.co/800x450?text=Tendinte+2026',
                'published_at' => now()->subDays(2),
                'is_published' => true,
            ],
            [
                'title' => 'Cum să alegi materialele potrivite pentru izolație',
                'excerpt' => 'Ghid complet pentru alegerea celor mai eficiente materiale termoizolante.',
                'content' => '<p>Izolația termică este crucială pentru eficiența energetică...</p>',
                'image_path' => 'https://placehold.co/800x450?text=Izolatie',
                'published_at' => now()->subDays(5),
                'is_published' => true,
            ],
            [
                'title' => 'Lansăm noua gamă de scule profesionale',
                'excerpt' => 'Suntem mândri să anunțăm parteneriatul cu brandul X pentru scule de înaltă performanță.',
                'content' => '<p>Noua gamă de scule include...</p>',
                'image_path' => 'https://placehold.co/800x450?text=Scule+Profesionale',
                'published_at' => now()->subDays(10),
                'is_published' => true,
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                array_merge($post, ['category_id' => $category->id])
            );
        }
    }
}
