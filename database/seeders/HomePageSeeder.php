<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;

class HomePageSeeder extends Seeder
{
    public function run()
    {
        $homePage = Page::updateOrCreate(
            ['slug' => 'acasa'],
            [
                'title' => 'Acasă',
                'content' => '', // Content is handled by sections
                'is_published' => true,
                'meta_title' => 'Acasă - B2B Ecommerce',
                'meta_description' => 'Bine ați venit pe platforma B2B. Găsiți cele mai bune materiale de construcții.',
                'sections' => [
                    [
                        'type' => 'hero',
                        'is_active' => true,
                        'items' => [
                            [
                                'title' => 'Materiale de Construcții Premium',
                                'subtitle' => 'Soluții complete pentru proiectele tale rezidențiale și industriale.',
                                'image_url' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=2070',
                                'url' => '/produse'
                            ],
                            [
                                'title' => 'Oferte Speciale de Sezon',
                                'subtitle' => 'Profită de reduceri la scule și unelte profesionale.',
                                'image_url' => 'https://images.unsplash.com/photo-1581094794329-cd1096a7a5ea?auto=format&fit=crop&q=80&w=2070',
                                'url' => '/promotii'
                            ]
                        ]
                    ],
                    [
                        'type' => 'features',
                        'is_active' => true,
                        'items' => [
                            [
                                'icon' => 'bi-truck',
                                'title' => 'Livrare Rapidă',
                                'description' => 'Livrare în 24-48h oriunde în țară.'
                            ],
                            [
                                'icon' => 'bi-shield-check',
                                'title' => 'Produse Garantate',
                                'description' => 'Calitate certificată și garanție extinsă.'
                            ],
                            [
                                'icon' => 'bi-headset',
                                'title' => 'Suport Tehnic',
                                'description' => 'Consultanță specializată pentru proiecte.'
                            ],
                            [
                                'icon' => 'bi-arrow-counterclockwise',
                                'title' => 'Retur Simplu',
                                'description' => '30 de zile drept de retur.'
                            ]
                        ]
                    ],
                    [
                        'type' => 'categories',
                        'is_active' => true,
                        'title' => 'Categorii Populare',
                        'count' => 6
                    ],
                    [
                        'type' => 'products',
                        'is_active' => true,
                        'title' => 'Produse Noi',
                        'type' => 'latest',
                        'limit' => 4
                    ],
                    [
                        'type' => 'products',
                        'is_active' => true,
                        'title' => 'Promoții',
                        'source' => 'promo',
                        'limit' => 4
                    ]
                ]
            ]
        );
    }
}
