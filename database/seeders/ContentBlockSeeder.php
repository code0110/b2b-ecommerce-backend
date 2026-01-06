<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContentBlock;

class ContentBlockSeeder extends Seeder
{
    public function run()
    {
        $blocks = [
            // HOME HERO SECTION
            [
                'key' => 'home_hero_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Soluții B2B Premium pentru Afacerea Ta',
                'title' => 'Home Hero Title',
            ],
            [
                'key' => 'home_hero_subtitle',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Descoperă catalogul nostru complet de produse și servicii dedicate partenerilor.',
                'title' => 'Home Hero Subtitle',
            ],
            [
                'key' => 'home_hero_cta_text',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Vezi Catalogul',
                'title' => 'Home Hero CTA Text',
            ],
            [
                'key' => 'home_hero_cta_link',
                'group' => 'home',
                'type' => 'text',
                'content' => '/categories',
                'title' => 'Home Hero CTA Link',
            ],

            // HOME FEATURES SECTION
            [
                'key' => 'home_features_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'De ce să alegi platforma noastră?',
                'title' => 'Home Features Title',
            ],
            [
                'key' => 'home_features_list',
                'group' => 'home',
                'type' => 'json',
                'content' => json_encode([
                    [
                        'icon' => 'bi-truck',
                        'title' => 'Livrare Rapidă',
                        'description' => 'Expediere în 24h pentru produsele din stoc.'
                    ],
                    [
                        'icon' => 'bi-shield-check',
                        'title' => 'Calitate Garantată',
                        'description' => 'Produse verificate și certificate conform standardelor UE.'
                    ],
                    [
                        'icon' => 'bi-headset',
                        'title' => 'Suport Dedicat',
                        'description' => 'Consultanță tehnică specializată pentru parteneri.'
                    ],
                    [
                        'icon' => 'bi-wallet2',
                        'title' => 'Prețuri Competitive',
                        'description' => 'Oferte personalizate și discount-uri de volum.'
                    ]
                ]),
                'title' => 'Home Features List',
            ],

            // FOOTER SECTION
            [
                'key' => 'footer_about_text',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Suntem partenerul tău de încredere în distribuția de echipamente și soluții industriale. Calitate și profesionalism din 2010.',
                'title' => 'Footer About Text',
            ],
            [
                'key' => 'footer_contact_address',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Strada Industriei nr. 15, București',
                'title' => 'Footer Contact Address',
            ],
            [
                'key' => 'footer_contact_phone',
                'group' => 'footer',
                'type' => 'text',
                'content' => '+40 722 123 456',
                'title' => 'Footer Contact Phone',
            ],
            [
                'key' => 'footer_contact_email',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'contact@b2b-portal.ro',
                'title' => 'Footer Contact Email',
            ],
            [
                'key' => 'footer_copyright',
                'group' => 'footer',
                'type' => 'text',
                'content' => '© 2026 B2B Portal. Toate drepturile rezervate.',
                'title' => 'Footer Copyright',
            ],
            [
                'key' => 'footer_social_links',
                'group' => 'footer',
                'type' => 'json',
                'content' => json_encode([
                    ['icon' => 'bi bi-facebook', 'url' => 'https://facebook.com', 'label' => 'Facebook'],
                    ['icon' => 'bi bi-linkedin', 'url' => 'https://linkedin.com', 'label' => 'LinkedIn'],
                    ['icon' => 'bi bi-instagram', 'url' => 'https://instagram.com', 'label' => 'Instagram'],
                ]),
                'title' => 'Footer Social Links',
            ],
            [
                'key' => 'footer_column_1',
                'group' => 'footer',
                'type' => 'json',
                'content' => json_encode([
                    'title' => 'Companie',
                    'links' => [
                        ['text' => 'Despre Noi', 'url' => '/despre-noi'],
                        ['text' => 'Cariere', 'url' => '/cariere'],
                        ['text' => 'Sustenabilitate', 'url' => '/sustenabilitate'],
                        ['text' => 'Blog', 'url' => '/blog'],
                    ]
                ]),
                'title' => 'Footer Column 1',
            ],
            [
                'key' => 'footer_column_2',
                'group' => 'footer',
                'type' => 'json',
                'content' => json_encode([
                    'title' => 'Suport',
                    'links' => [
                        ['text' => 'Contact', 'url' => '/contact'],
                        ['text' => 'Întrebări Frecvente', 'url' => '/faq'],
                        ['text' => 'Livrare și Retur', 'url' => '/livrare-retur'],
                        ['text' => 'Garanții', 'url' => '/garantii'],
                    ]
                ]),
                'title' => 'Footer Column 2',
            ],
            
             // TESTIMONIALS SECTION
            [
                'key' => 'home_testimonials_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Ce spun partenerii noștri',
                'title' => 'Home Testimonials Title',
            ],
            [
                'key' => 'home_testimonials_list',
                'group' => 'home',
                'type' => 'json',
                'content' => json_encode([
                    [
                        'name' => 'Alexandru Popescu',
                        'company' => 'Construct Vest SRL',
                        'text' => 'Colaborăm de 5 ani și suntem extrem de mulțumiți de promptitudinea livrărilor.',
                        'rating' => 5
                    ],
                    [
                        'name' => 'Maria Ionescu',
                        'company' => 'Design Interior SA',
                        'text' => 'Gama variată de produse ne ajută să satisfacem cerințele clienților noștri.',
                        'rating' => 5
                    ],
                    [
                        'name' => 'Ionut Radu',
                        'company' => 'Tech Solutions SRL',
                        'text' => 'Suportul tehnic este excelent, ne-au ajutat să alegem soluțiile potrivite.',
                        'rating' => 4
                    ]
                ]),
                'title' => 'Home Testimonials List',
            ],

            // MISSING BLOCKS
            [
                'key' => 'footer_contact_schedule',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Luni - Vineri: 09:00 - 18:00',
                'title' => 'Footer Contact Schedule',
            ],
            [
                'key' => 'home_categories_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Categorii populare',
                'title' => 'Home Categories Title',
            ],
            [
                'key' => 'home_promotions_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Promoții active',
                'title' => 'Home Promotions Title',
            ],
            [
                'key' => 'home_new_products_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Produse noi',
                'title' => 'Home New Products Title',
            ],
            [
                'key' => 'home_recommended_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Produse recomandate',
                'title' => 'Home Recommended Title',
            ],
            [
                'key' => 'home_brands_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Branduri partenere',
                'title' => 'Home Brands Title',
            ],
            [
                'key' => 'home_blog_title',
                'group' => 'home',
                'type' => 'text',
                'content' => 'Noutăți de pe blog',
                'title' => 'Home Blog Title',
            ],
            [
                'key' => 'footer_newsletter_title',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Abonează-te la newsletter',
                'title' => 'Footer Newsletter Title',
            ],
            [
                'key' => 'footer_newsletter_text',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Primește ultimele oferte și noutăți direct pe email.',
                'title' => 'Footer Newsletter Text',
            ],
            [
                'key' => 'footer_newsletter_placeholder',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Adresa ta de email',
                'title' => 'Footer Newsletter Placeholder',
            ],
            [
                'key' => 'footer_newsletter_button',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Abonează-te',
                'title' => 'Footer Newsletter Button',
            ],
            [
                'key' => 'footer_social_title',
                'group' => 'footer',
                'type' => 'text',
                'content' => 'Social Media',
                'title' => 'Footer Social Title',
            ],
        ];

        foreach ($blocks as $block) {
            ContentBlock::updateOrCreate(
                ['key' => $block['key']],
                $block
            );
        }
    }
}
