<?php

namespace App\Services;

use App\Models\Attribute;
use Illuminate\Support\Str;

class SeoGeneratorService
{
    /**
     * Generate SEO content for a product based on its data.
     * Strictly following the "Don't Invent" rule but maximizing impact.
     */
    public function generate(array $data): array
    {
        // 1. Validate & Sanitize Input
        $context = $this->prepareContext($data);

        // 2. Generate Content
        return [
            'meta_title' => $this->generateMetaTitle($context),
            'meta_description' => $this->generateMetaDescription($context),
            'short_description' => $this->generateShortDescription($context),
            'long_description' => $this->generateLongDescription($context),
            'keywords' => $this->generateKeywords($context),
        ];
    }

    protected function prepareContext(array $data): array
    {
        // Extract and clean core data
        $context = [
            'name' => trim($data['name'] ?? ''),
            'brand' => !empty($data['brand']) && is_array($data['brand']) ? trim($data['brand']['name'] ?? '') : '',
            'category' => !empty($data['main_category']) && is_array($data['main_category']) ? trim($data['main_category']['name'] ?? '') : '',
            'price' => $data['list_price'] ?? null,
            'attributes' => [],
            'benefits' => [],
            'category_type' => 'general', // Default
            'existing_description' => null
        ];

        // Capture existing long description (strip tags to get raw text if user provided draft)
        if (!empty($data['long_description'])) {
            $rawText = strip_tags($data['long_description']);
            
            // Clean up previously generated sections to avoid duplication/corruption
            // This allows the user to edit the "Intro" text and re-generate without duplicating the specs/benefits
            $markers = ['Specificații Tehnice', 'De ce să alegi acest produs?', 'Domenii de Utilizare'];
            foreach ($markers as $marker) {
                $pos = mb_stripos($rawText, $marker);
                if ($pos !== false) {
                    $rawText = mb_substr($rawText, 0, $pos);
                }
            }

            if (strlen(trim($rawText)) > 3) { // Lowered threshold to catch almost any user input
                $context['existing_description'] = trim($rawText);
            }
        }

        // Determine category type for smarter generation
        $catLower = mb_strtolower($context['category']);
        if (Str::contains($catLower, ['scule', 'bormasina', 'fierastrau', 'polizor', 'ciocan', 'cheie', 'surubelnita'])) {
            $context['category_type'] = 'tools';
        } elseif (Str::contains($catLower, ['laptop', 'calculator', 'monitor', 'imprimanta', 'server', 'retea', 'software'])) {
            $context['category_type'] = 'it';
        } elseif (Str::contains($catLower, ['electrice', 'priza', 'intrerupator', 'cablu', 'iluminat', 'bec'])) {
            $context['category_type'] = 'electric';
        } elseif (Str::contains($catLower, ['mobilier', 'scaun', 'birou', 'dulap', 'masa'])) {
            $context['category_type'] = 'furniture';
        } elseif (Str::contains($catLower, ['sanitare', 'baterie', 'chiuveta', 'teava', 'robinet'])) {
            $context['category_type'] = 'sanitary';
        } elseif (Str::contains($catLower, ['protectie', 'manusi', 'casca', 'bocanci', 'salopeta'])) {
            $context['category_type'] = 'safety';
        }

        // Process Attributes
        if (!empty($data['attribute_values'])) {
            // Pre-fetch missing attribute names logic (kept from previous version)
            $missingIds = [];
            foreach ($data['attribute_values'] as $idx => $attr) {
                if (empty($attr['attribute']['name']) && !empty($attr['attribute_id'])) {
                    $missingIds[$attr['attribute_id']][] = $idx;
                }
            }

            if (!empty($missingIds)) {
                $attributes = Attribute::whereIn('id', array_keys($missingIds))->pluck('name', 'id');
                foreach ($missingIds as $id => $indices) {
                    if (isset($attributes[$id])) {
                        foreach ($indices as $idx) {
                            $data['attribute_values'][$idx]['attribute']['name'] = $attributes[$id];
                        }
                    }
                }
            }

            foreach ($data['attribute_values'] as $attr) {
                if (!empty($attr['value']) && !empty($attr['attribute']['name'])) {
                    $key = Str::slug($attr['attribute']['name'], '_');
                    // Store with nice formatting
                    $context['attributes'][] = [
                        'name' => $attr['attribute']['name'],
                        'value' => $attr['value'],
                        'key' => $key
                    ];
                }
            }
        }

        return $context;
    }

    protected function generateMetaTitle(array $context): string
    {
        // Formula: Name - Brand (if set) | Category | "Profesional"
        // Limit to ~60 chars ideally, but we generate full first.
        $parts = [$context['name']];
        
        if ($context['brand']) {
            $parts[] = $context['brand'];
        }
        
        if ($context['category'] && !Str::contains(mb_strtolower($context['name']), mb_strtolower($context['category']))) {
            $parts[] = $context['category'];
        }

        // Add a power word if length permits
        $base = implode(' - ', $parts);
        if (strlen($base) < 50) {
            $base .= ' | Profesional';
        }

        return $base;
    }

    protected function generateMetaDescription(array $context): string
    {
        // If we have a user-provided description, try to use the first sentence as a hook
        $intro = "";
        if ($context['existing_description']) {
            $firstSentence = strtok($context['existing_description'], '.');
            if (strlen($firstSentence) > 10 && strlen($firstSentence) < 150) {
                $intro = $firstSentence . ".";
            }
        }

        if (empty($intro)) {
            // Fallback to generated hook
            $hooks = [
                "Descoperă {$context['name']}",
                "Comandă {$context['name']}",
                "Nou în stoc: {$context['name']}",
            ];
            $intro = $hooks[array_rand($hooks)];
            if ($context['brand']) {
                $intro .= " de la {$context['brand']}";
            }
            $intro .= ".";
        }

        // 2. Key Specs (Middle)
        $specs = "";
        if (!empty($context['attributes'])) {
            $topAttrs = array_slice($context['attributes'], 0, 2);
            $specList = [];
            foreach ($topAttrs as $attr) {
                $specList[] = mb_strtolower($attr['name']) . " " . $attr['value'];
            }
            if (!empty($specList)) {
                $specs = " Caracteristici: " . implode(', ', $specList) . ".";
            }
        }

        // 3. Benefit/CTA (End)
        $benefits = [
            'tools' => "Ideal pentru profesioniști. Livrare rapidă.",
            'it' => "Performanță și fiabilitate garantată.",
            'electric' => "Siguranță și calitate certificată.",
            'furniture' => "Design ergonomic și durabil.",
            'general' => "Calitate superioară la cel mai bun preț."
        ];
        
        $benefit = $benefits[$context['category_type']] ?? $benefits['general'];

        return $intro . $specs . " " . $benefit;
    }

    protected function generateShortDescription(array $context): string
    {
        // HTML format for Rich Text Editor
        // Structure: 
        // <p>Strong summary sentence.</p>
        // <ul>Key features</ul>

        $intro = "";
        
        // 1. Try to extract from existing description
        if ($context['existing_description']) {
            // Take the first paragraph or sentence
            $parts = explode("\n", $context['existing_description']);
            $firstPara = trim($parts[0] ?? '');
            
            if (strlen($firstPara) > 20 && strlen($firstPara) < 300) {
                 $intro = $firstPara;
            } else {
                // Fallback to first sentence
                $sentence = strtok($context['existing_description'], '.');
                if (strlen($sentence) > 20) {
                    $intro = $sentence . ".";
                }
            }
        }

        // 2. Fallback to generic templates
        if (empty($intro)) {
            $summaries = [
                "<strong>{$context['name']}</strong> este soluția ideală pentru proiectele tale, oferind un echilibru perfect între performanță și cost.",
                "Alege <strong>{$context['name']}</strong> pentru fiabilitate și eficiență maximă în categoria {$context['category']}.",
                "Cu <strong>{$context['name']}</strong> beneficiezi de calitate superioară garantată de " . ($context['brand'] ?: 'standardele noastre stricte') . "."
            ];
            $intro = $summaries[array_rand($summaries)];
        }

        $html = "<p>" . $intro . "</p>";

        if (!empty($context['attributes'])) {
            $html .= "<ul>";
            $count = 0;
            foreach ($context['attributes'] as $attr) {
                if ($count >= 5) break;
                $html .= "<li><strong>" . ucfirst($attr['name']) . ":</strong> " . $attr['value'] . "</li>";
                $count++;
            }
            $html .= "</ul>";
        }

        return $html;
    }

    protected function generateLongDescription(array $context): string
    {
        $html = [];
        $html[] = "<div class='product-description'>";
        $html[] = "<h2>Descriere Detaliată: {$context['name']}</h2>";

        // HYBRID MODE: If user provided text, use it as the main content body
        if ($context['existing_description']) {
            // Smart Parsing: Detect lists and paragraphs
            $lines = explode("\n", $context['existing_description']);
            $inList = false;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Check for list items (starts with -, *, •)
                if (preg_match('/^[-*•]\s+(.*)/', $line, $matches)) {
                    if (!$inList) {
                        $html[] = "<ul>";
                        $inList = true;
                    }
                    $content = ucfirst(trim($matches[1]));
                    if (substr($content, -1) !== '.') $content .= '.'; // Add period to list items
                    $html[] = "<li>" . $content . "</li>";
                } else {
                    if ($inList) {
                        $html[] = "</ul>";
                        $inList = false;
                    }
                    // Check if it looks like a header (short, no punctuation at end, capitalized)
                    if (strlen($line) < 60 && !in_array(substr($line, -1), ['.', ',', ':', ';']) && preg_match('/[A-Z]/', $line)) {
                        $html[] = "<h3>{$line}</h3>";
                    } else {
                        $content = ucfirst($line);
                        if (!in_array(substr($content, -1), ['.', '!', '?'])) $content .= '.'; // Ensure punctuation
                        $html[] = "<p>{$content}</p>";
                    }
                }
            }
            if ($inList) {
                $html[] = "</ul>";
            }

        } else {
            // AUTO MODE: Generate content if empty
            $introText = "";
            switch ($context['category_type']) {
                case 'tools':
                    $introText = "Conceput pentru profesioniștii care nu fac compromisuri, <strong>{$context['name']}</strong> se remarcă prin robustețe și precizie. Indiferent de complexitatea lucrării, acest produs din gama {$context['category']} asigură rezultate consistente și durabile.";
                    break;
                case 'it':
                    $introText = "În era digitală, performanța este cheia. <strong>{$context['name']}</strong> vine echipat cu tehnologie de ultimă generație pentru a-ți optimiza fluxul de lucru. Fiabil și versatil, este partenerul ideal pentru orice birou modern.";
                    break;
                case 'electric':
                    $introText = "Siguranța instalațiilor tale este prioritară. <strong>{$context['name']}</strong> respectă cele mai stricte norme de calitate, oferind o soluție durabilă și eficientă pentru proiectele tale electrice.";
                    break;
                default:
                    $introText = "<strong>{$context['name']}</strong> reprezintă o alegere excelentă în categoria {$context['category']}. Fabricat cu atenție la detalii" . ($context['brand'] ? " de către <strong>{$context['brand']}</strong>" : "") . ", acest produs îndeplinește cerințele utilizatorilor exigenți, oferind fiabilitate pe termen lung.";
                    break;
            }
            $html[] = "<p>{$introText}</p>";
        }

        // Always append structured benefits if missing
        $html[] = "<h3>De ce să alegi acest produs?</h3>";
        $html[] = "<ul>";
        $commonBenefits = [
            "<li><strong>Raport Calitate-Preț Excelent:</strong> Investiție inteligentă pe termen lung.</li>",
            "<li><strong>Garanție și Suport:</strong> Produs susținut de servicii post-vânzare de încredere.</li>"
        ];
        if ($context['category_type'] === 'tools') {
            array_unshift($commonBenefits, "<li><strong>Ergonomie Superioară:</strong> Design gândit pentru confort în utilizare prelungită.</li>");
        } elseif ($context['category_type'] === 'it') {
            array_unshift($commonBenefits, "<li><strong>Compatibilitate Extinsă:</strong> Se integrează ușor în ecosistemul tău actual.</li>");
        }
        foreach ($commonBenefits as $benefit) {
            $html[] = $benefit;
        }
        $html[] = "</ul>";

        // Technical Specs Table if missing and available
        if (!empty($context['attributes'])) {
            $html[] = "<h3>Specificații Tehnice</h3>";
            $html[] = "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
            $html[] = "<tbody>";
            
            foreach ($context['attributes'] as $idx => $attr) {
                $bgStyle = $idx % 2 === 0 ? "background-color: #f8f9fa;" : "";
                $html[] = "<tr style='{$bgStyle}'>";
                $html[] = "<td style='padding: 8px; border: 1px solid #dee2e6; font-weight: bold; width: 40%;'>" . ucfirst($attr['name']) . "</td>";
                $html[] = "<td style='padding: 8px; border: 1px solid #dee2e6;'>" . $attr['value'] . "</td>";
                $html[] = "</tr>";
            }
            
            $html[] = "</tbody>";
            $html[] = "</table>";
        }

        // Usage Context (Only if Auto Mode or to supplement)
        if (!$context['existing_description']) {
             $html[] = "<h3>Domenii de Utilizare</h3>";
             $html[] = "<p>Versatilitatea modelului <strong>{$context['name']}</strong> îl face potrivit pentru o gamă largă de aplicații:</p>";
             $html[] = "<ul>";
             if ($context['category_type'] === 'tools') {
                 $html[] = "<li>Construcții și renovări rezidențiale sau industriale.</li>";
                 $html[] = "<li>Ateliere de producție și reparații.</li>";
             } else {
                 $html[] = "<li>Utilizare profesională în domeniu.</li>";
                 $html[] = "<li>Proiecte comerciale și rezidențiale.</li>";
             }
             $html[] = "</ul>";
        }

        // Footer
        $html[] = "<p style='margin-top: 20px; font-style: italic; color: #6c757d;'><strong>Notă:</strong> Imaginile sunt cu titlu de prezentare. Pentru detalii suplimentare, consultați fișa tehnică sau contactați echipa noastră de suport.</p>";
        $html[] = "</div>"; 

        return implode("\n", $html);
    }

    protected function generateKeywords(array $context): string
    {
        $keywords = [
            $context['name'],
            $context['category'],
            $context['brand'] ?? '',
            'pret ' . $context['name'],
            'oferta ' . $context['category'],
            'magazin online b2b'
        ];

        if ($context['category_type'] === 'tools') {
            $keywords[] = 'scule profesionale';
            $keywords[] = 'echipamente constructii';
        }

        foreach ($context['attributes'] as $attr) {
            // Add attribute values as keywords if they are short words
            if (str_word_count($attr['value']) <= 2) {
                $keywords[] = $attr['value'];
                $keywords[] = $attr['name'] . ' ' . $attr['value'];
            }
        }

        return implode(', ', array_unique(array_filter(array_map('trim', $keywords))));
    }
}
