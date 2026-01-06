<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run()
    {
        $pages = [
            [
                'title' => 'Despre Noi',
                'slug' => 'despre-noi',
                'content' => '
                    <h2 class="h4 mb-4">Cine suntem</h2>
                    <p>Suntem o companie dedicată furnizării de soluții complete pentru construcții și industrie. Cu o experiență de peste 15 ani pe piață, ne mândrim cu un portofoliu vast de produse și servicii adaptate nevoilor clienților noștri.</p>
                    
                    <h3 class="h5 mt-4 mb-3">Misiunea Noastră</h3>
                    <p>Misiunea noastră este să oferim calitate și inovație, contribuind la succesul proiectelor partenerilor noștri prin livrarea rapidă a materialelor necesare.</p>
                    
                    <h3 class="h5 mt-4 mb-3">Valorile Noastre</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-check-circle text-primary me-2"></i><strong>Integritate:</strong> Ne respectăm promisiunile și acționăm etic.</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-primary me-2"></i><strong>Calitate:</strong> Nu facem compromisuri când vine vorba de produsele noastre.</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-primary me-2"></i><strong>Parteneriat:</strong> Construim relații de lungă durată cu clienții noștri.</li>
                    </ul>
                ',
                'meta_title' => 'Despre Noi - B2B Portal',
                'meta_description' => 'Află mai multe despre compania noastră, misiunea și valorile care ne ghidează.',
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => '
                    <p class="mb-4">Echipa noastră vă stă la dispoziție pentru orice întrebări sau solicitări. Nu ezitați să ne contactați folosind detaliile de mai jos.</p>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-primary mb-3"><i class="bi bi-building me-2"></i>Sediul Central</h5>
                                    <p class="card-text">
                                        Strada Industriei nr. 15<br>
                                        Sector 3, București<br>
                                        România
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-primary mb-3"><i class="bi bi-headset me-2"></i>Suport Clienți</h5>
                                    <p class="card-text">
                                        <strong>Telefon:</strong> +40 722 123 456<br>
                                        <strong>Email:</strong> contact@b2b-portal.ro<br>
                                        <strong>Program:</strong> Luni - Vineri, 09:00 - 18:00
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                ',
                'meta_title' => 'Contact - B2B Portal',
                'meta_description' => 'Contactează-ne pentru oferte personalizate și suport tehnic.',
            ],
            [
                'title' => 'Termeni și Condiții',
                'slug' => 'termeni-conditii',
                'content' => '
                    <h2 class="h5 mb-3">1. Introducere</h2>
                    <p>Folosirea acestui site implică acceptarea termenilor și condițiilor de mai jos. Recomandăm citirea cu atenție a acestora.</p>
                    
                    <h2 class="h5 mt-4 mb-3">2. Comenzi și Livrare</h2>
                    <p>Comenzile plasate pe site sunt procesate în ordinea primirii. Termenul de livrare este de 24-48 ore pentru produsele din stoc.</p>
                    
                    <h2 class="h5 mt-4 mb-3">3. Garanții și Retur</h2>
                    <p>Toate produsele beneficiază de garanție conform legislației în vigoare. Returul produselor se poate face în termen de 14 zile conform politicii noastre de retur.</p>
                ',
                'meta_title' => 'Termeni și Condiții - B2B Portal',
                'meta_description' => 'Termenii și condițiile de utilizare a platformei B2B Portal.',
            ],
            [
                'title' => 'Politica de Confidențialitate (GDPR)',
                'slug' => 'gdpr',
                'content' => '
                    <p>Respectăm confidențialitatea datelor dumneavoastră și ne angajăm să le protejăm. Această politică explică modul în care colectăm și utilizăm datele personale.</p>
                    
                    <h3 class="h6 mt-3">Colectarea datelor</h3>
                    <p>Colectăm date necesare procesării comenzilor și îmbunătățirii experienței pe site (nume, adresă, email, telefon).</p>
                ',
                'meta_title' => 'GDPR - B2B Portal',
                'meta_description' => 'Politica noastră de confidențialitate și protecția datelor.',
            ],
            [
                'title' => 'Politica Cookies',
                'slug' => 'cookies',
                'content' => '
                    <p>Acest site folosește cookie-uri pentru a vă oferi o experiență mai bună de navigare și servicii adaptate intereselor dumneavoastră.</p>
                ',
                'meta_title' => 'Politica Cookies - B2B Portal',
                'meta_description' => 'Informații despre utilizarea cookie-urilor pe platforma noastră.',
            ],
            [
                'title' => 'Livrare și Retur',
                'slug' => 'livrare-retur',
                'content' => '
                    <h2 class="h5 mb-3">Livrare</h2>
                    <p>Livrarea se face prin curier rapid sau flotă proprie, în funcție de volumul și greutatea comenzii.</p>
                    
                    <h2 class="h5 mt-4 mb-3">Retur</h2>
                    <p>Puteți returna produsele în termen de 14 zile calendaristice, fără penalități și fără invocarea unui motiv.</p>
                ',
                'meta_title' => 'Livrare și Retur - B2B Portal',
                'meta_description' => 'Detalii despre metodele de livrare și politica de retur.',
            ],
             [
                'title' => 'Garanții',
                'slug' => 'garantii',
                'content' => '
                    <p>Oferim garanție comercială pentru toate produsele vândute, conform specificațiilor producătorului.</p>
                ',
                'meta_title' => 'Garanții - B2B Portal',
                'meta_description' => 'Informații despre garanția produselor.',
            ],
            [
                'title' => 'Cariere',
                'slug' => 'cariere',
                'content' => '
                    <p>Momentan nu avem posturi disponibile. Vă rugăm să reveniți.</p>
                ',
                'meta_title' => 'Cariere - B2B Portal',
                'meta_description' => 'Alătură-te echipei noastre.',
            ],
             [
                'title' => 'Sustenabilitate',
                'slug' => 'sustenabilitate',
                'content' => '
                    <p>Ne angajăm să reducem amprenta de carbon prin optimizarea logisticii și promovarea produselor eco-friendly.</p>
                ',
                'meta_title' => 'Sustenabilitate - B2B Portal',
                'meta_description' => 'Eforturile noastre pentru un viitor sustenabil.',
            ],
            [
                'title' => 'Întrebări Frecvente (FAQ)',
                'slug' => 'faq',
                'content' => '
                    <div class="accordion" id="accordionFAQ">
                      <div class="accordion-item">
                        <h2 class="accordion-header">
                          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                            Cum pot deveni partener B2B?
                          </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionFAQ">
                          <div class="accordion-body">
                            Completați formularul de înregistrare B2B și un reprezentant vă va contacta pentru validarea contului.
                          </div>
                        </div>
                      </div>
                      <div class="accordion-item">
                        <h2 class="accordion-header">
                          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            Care este comanda minimă?
                          </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                          <div class="accordion-body">
                            Nu impunem o comandă minimă valorică, dar pentru livrare gratuită comanda trebuie să depășească 500 RON.
                          </div>
                        </div>
                      </div>
                    </div>
                ',
                'meta_title' => 'FAQ - B2B Portal',
                'meta_description' => 'Răspunsuri la cele mai frecvente întrebări.',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
