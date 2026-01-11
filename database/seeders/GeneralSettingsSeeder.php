<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class GeneralSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'MB2B Industry',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Numele site-ului afișat în titlu și header'
            ],
            [
                'key' => 'site_description',
                'value' => 'Soluții profesionale B2B pentru industria ta.',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Descrierea site-ului pentru SEO'
            ],
            [
                'key' => 'site_logo',
                'value' => '/imgs/logo-3.png',
                'group' => 'appearance',
                'type' => 'string',
                'description' => 'Calea către logo-ul site-ului'
            ],
            [
                'key' => 'contact_phone',
                'value' => '0755 123 456',
                'group' => 'contact',
                'type' => 'string',
                'description' => 'Numărul de telefon de contact'
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@mb2b.ro',
                'group' => 'contact',
                'type' => 'string',
                'description' => 'Adresa de email de contact'
            ],
            [
                'key' => 'show_vat_toggle',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Afișează selectorul TVA în header'
            ],
            [
                'key' => 'enable_registration',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Permite înregistrarea utilizatorilor noi'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
