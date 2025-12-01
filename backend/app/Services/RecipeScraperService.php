<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecipeScraperService
{
    /**
     * Scrape recipe from TudoGostoso URL
     */
    public function scrapeTudoGostoso(string $url): array
    {
        try {
            // Try to fetch using Laravel Http first
            try {
                $response = Http::timeout(30)
                    ->withoutVerifying() // Desabilita verificação SSL para desenvolvimento
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Connection' => 'keep-alive',
                        'Upgrade-Insecure-Requests' => '1',
                    ])
                    ->get($url);
                
                $statusCode = $response->status();
                
                if ($statusCode >= 200 && $statusCode < 300) {
                    $html = $response->body();
                } else {
                    throw new \Exception("HTTP {$statusCode}");
                }
            } catch (\Exception $httpException) {
                // Fallback: try using cURL directly
                Log::warning('Laravel Http failed, trying cURL fallback', [
                    'url' => $url,
                    'error' => $httpException->getMessage()
                ]);
                
                $html = $this->fetchWithCurl($url);
            }
            
            // Check if we got valid HTML
            if (empty($html) || strlen($html) < 100) {
                throw new \Exception('Resposta vazia ou inválida do servidor');
            }
            
            // Create DOMDocument and load HTML
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $libxmlErrors = libxml_get_errors();
            libxml_clear_errors();
            
            if (!$dom || !$dom->documentElement) {
                throw new \Exception('Não foi possível processar o HTML da página');
            }
            
            $xpath = new DOMXPath($dom);

            // Debug: log HTML structure
            $ingredientSection = $xpath->query('//section[@class="recipe-section recipe-ingredients"]');
            $stepsSection = $xpath->query('//section[@class="recipe-section recipe-steps"]');
            Log::debug('Scraping debug', [
                'ingredient_sections_found' => $ingredientSection->length,
                'steps_sections_found' => $stepsSection->length,
            ]);

            // Extract title
            $title = $this->extractTitle($xpath);
            
            // Extract category from breadcrumb
            $category = $this->extractCategory($xpath);
            
            // Extract servings
            $servings = $this->extractServings($xpath);
            
            // Extract prep time
            $prepTime = $this->extractPrepTime($xpath);
            
            // Extract ingredients
            $ingredients = $this->extractIngredients($xpath);
            
            // Extract instructions
            $instructions = $this->extractInstructions($xpath);
            
            // Extract image URL
            $imageUrl = $this->extractImageUrl($xpath);
            
            // Debug: log extracted data
            Log::debug('Scraped data', [
                'title' => $title,
                'category' => $category,
                'servings' => $servings,
                'prep_time' => $prepTime,
                'ingredients_length' => strlen($ingredients),
                'instructions_length' => strlen($instructions),
                'image_url' => $imageUrl,
            ]);

            return [
                'success' => true,
                'data' => [
                    'name' => $title,
                    'category_name' => $category,
                    'servings' => $servings,
                    'prep_time_minutes' => $prepTime,
                    'ingredients' => $ingredients,
                    'instructions' => $instructions,
                    'image_url' => $imageUrl,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Recipe scraping error', [
                'message' => $e->getMessage(),
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract recipe title
     */
    private function extractTitle(DOMXPath $xpath): string
    {
        // Try multiple selectors for title
        $selectors = [
            '//span[@class="u-title-page"]',
            '//h1[@class="u-title-page"]',
            '//h1[contains(@class, "u-title")]',
            '//h1',
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        }

        // Fallback: try breadcrumb last item
        $breadcrumbNodes = $xpath->query('//ul[@class="breadcrumb"]/li[last()]//h1');
        if ($breadcrumbNodes->length > 0) {
            return trim($breadcrumbNodes->item(0)->textContent);
        }

        return '';
    }

    /**
     * Extract category from breadcrumb
     */
    private function extractCategory(DOMXPath $xpath): string
    {
        // Get the second-to-last breadcrumb item (category)
        $breadcrumbNodes = $xpath->query('//ul[@class="breadcrumb"]/li');
        
        if ($breadcrumbNodes->length >= 2) {
            // Get the second-to-last item (before the recipe name)
            $categoryNode = $breadcrumbNodes->item($breadcrumbNodes->length - 2);
            $link = $xpath->query('.//a', $categoryNode);
            
            if ($link->length > 0) {
                return trim($link->item(0)->textContent);
            }
            
            return trim($categoryNode->textContent);
        }

        return '';
    }

    /**
     * Extract servings from ingredients header
     */
    private function extractServings(DOMXPath $xpath): ?int
    {
        $headerNodes = $xpath->query('//section[@class="recipe-section recipe-ingredients"]//header/h2');
        
        if ($headerNodes->length > 0) {
            $text = $headerNodes->item(0)->textContent;
            // Look for pattern like "(8 porções)" or "(8 servings)"
            if (preg_match('/\((\d+)\s*(?:porções?|servings?)\)/i', $text, $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    /**
     * Extract prep time
     */
    private function extractPrepTime(DOMXPath $xpath): ?int
    {
        // Look for time element in recipe-steps-info
        $timeNodes = $xpath->query('//section[@class="recipe-section recipe-steps"]//div[contains(@class, "recipe-steps-info")]//time');
        
        if ($timeNodes->length > 0) {
            $timeNode = $timeNodes->item(0);
            
            // Try datetime attribute first
            $timeAttr = $timeNode->getAttribute('datetime');
            if (!empty($timeAttr)) {
                // Check for hours format: PT1H, PT2H, etc.
                if (preg_match('/PT(\d+)H/i', $timeAttr, $matches)) {
                    return (int) $matches[1] * 60; // Convert hours to minutes
                }
                // Check for minutes format: PT30M, PT45M, etc.
                if (preg_match('/PT(\d+)M/i', $timeAttr, $matches)) {
                    return (int) $matches[1];
                }
                // Check for combined format: PT1H30M
                if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/i', $timeAttr, $matches)) {
                    $hours = isset($matches[1]) && !empty($matches[1]) ? (int) $matches[1] : 0;
                    $minutes = isset($matches[2]) && !empty($matches[2]) ? (int) $matches[2] : 0;
                    return ($hours * 60) + $minutes;
                }
            }
            
            // Try title attribute
            $titleAttr = $timeNode->getAttribute('title');
            if (!empty($titleAttr)) {
                // Check for hours format: PT1H, PT2H, etc.
                if (preg_match('/PT(\d+)H/i', $titleAttr, $matches)) {
                    return (int) $matches[1] * 60; // Convert hours to minutes
                }
                // Check for minutes format: PT30M, PT45M, etc.
                if (preg_match('/PT(\d+)M/i', $titleAttr, $matches)) {
                    return (int) $matches[1];
                }
                // Check for combined format: PT1H30M
                if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/i', $titleAttr, $matches)) {
                    $hours = isset($matches[1]) && !empty($matches[1]) ? (int) $matches[1] : 0;
                    $minutes = isset($matches[2]) && !empty($matches[2]) ? (int) $matches[2] : 0;
                    return ($hours * 60) + $minutes;
                }
            }
            
            // Try text content
            $text = trim($timeNode->textContent);
            // Check for hours format: 1h, 2h, etc.
            if (preg_match('/(\d+)\s*h/i', $text, $matches)) {
                return (int) $matches[1] * 60; // Convert hours to minutes
            }
            // Check for minutes format: 30 min, 45 min, etc.
            if (preg_match('/(\d+)\s*min/i', $text, $matches)) {
                return (int) $matches[1];
            }
            // Check for combined format: 1h30min, 1h 30min
            if (preg_match('/(\d+)\s*h\s*(?:(\d+)\s*min)?/i', $text, $matches)) {
                $hours = (int) $matches[1];
                $minutes = isset($matches[2]) && !empty($matches[2]) ? (int) $matches[2] : 0;
                return ($hours * 60) + $minutes;
            }
        }

        return null;
    }

    /**
     * Extract ingredients and convert to HTML
     */
    private function extractIngredients(DOMXPath $xpath): string
    {
        $ingredientSections = $xpath->query('//section[@class="recipe-section recipe-ingredients"]//section');
        $hasSubsections = $ingredientSections->length > 0;
        
        if ($hasSubsections) {
            $ingredientsHtml = '<ul>';
            
            foreach ($ingredientSections as $section) {
                // Check if there's a subtitle (like "Massa" or "Cobertura")
                $subtitleNodes = $xpath->query('.//h3[@class="recipe-ingredients-subtitle"]', $section);
                $hasSubtitle = $subtitleNodes->length > 0;
                
                if ($hasSubtitle) {
                    $subtitle = trim($subtitleNodes->item(0)->textContent);
                    $ingredientsHtml .= '<li><strong>' . htmlspecialchars($subtitle) . '</strong><ul>';
                }
                
                // Get all ingredient items - search for spans with the label class directly
                $ingredientItems = $xpath->query('.//span[@class="recipe-ingredients-item-label"]', $section);
                
                foreach ($ingredientItems as $item) {
                    $ingredient = trim($item->textContent);
                    if (!empty($ingredient)) {
                        $ingredientsHtml .= '<li>' . htmlspecialchars($ingredient) . '</li>';
                    }
                }
                
                if ($hasSubtitle) {
                    $ingredientsHtml .= '</ul></li>';
                }
            }
            
            $ingredientsHtml .= '</ul>';
            
            // Check if we actually got ingredients
            if (strlen($ingredientsHtml) > 20) { // More than just the ul tags
                return $ingredientsHtml;
            }
        }
        
        // Fallback: try to get all ingredients without subsections - search directly for spans
        $allItems = $xpath->query('//section[@class="recipe-section recipe-ingredients"]//span[@class="recipe-ingredients-item-label"]');
        
        if ($allItems->length > 0) {
            $ingredientsHtml = '<ul>';
            foreach ($allItems as $item) {
                $ingredient = trim($item->textContent);
                if (!empty($ingredient)) {
                    $ingredientsHtml .= '<li>' . htmlspecialchars($ingredient) . '</li>';
                }
            }
            $ingredientsHtml .= '</ul>';
            return $ingredientsHtml;
        }
        
        return '';
    }

    /**
     * Extract instructions and convert to HTML
     */
    private function extractInstructions(DOMXPath $xpath): string
    {
        $instructionsHtml = '<ol>';
        // Try with class "grid" first, then without
        $stepNodes = $xpath->query('//section[@class="recipe-section recipe-steps"]//ol/li[contains(@class, "recipe-steps-item")]');
        
        if ($stepNodes->length === 0) {
            // Fallback: try without the class requirement
            $stepNodes = $xpath->query('//section[@class="recipe-section recipe-steps"]//ol/li');
        }
        
        if ($stepNodes->length > 0) {
            foreach ($stepNodes as $step) {
                // Skip if it's an ad or hidden element
                $classes = $step->getAttribute('class');
                if (str_contains($classes, 'no-print') || str_contains($classes, 'u-hidden') || str_contains($classes, 'ad-')) {
                    continue;
                }
                
                // Get step title if exists
                $titleNodes = $xpath->query('.//h3[@class="recipe-steps-title"]', $step);
                $stepTitle = '';
                if ($titleNodes->length > 0) {
                    $stepTitle = '<strong>' . htmlspecialchars(trim($titleNodes->item(0)->textContent)) . '</strong> ';
                }
                
                // Get step text from paragraphs - try with is-wysiwyg class
                $textNodes = $xpath->query('.//div[contains(@class, "recipe-steps-text")]//p', $step);
                $stepText = '';
                
                if ($textNodes->length > 0) {
                    foreach ($textNodes as $p) {
                        $text = trim($p->textContent);
                        if (!empty($text)) {
                            $stepText .= '<p>' . htmlspecialchars($text) . '</p>';
                        }
                    }
                }
                
                // If no paragraphs found, try to get text from the step div
                if (empty($stepText)) {
                    // Get text from recipe-steps-text div
                    $textDivNodes = $xpath->query('.//div[contains(@class, "recipe-steps-text")]', $step);
                    if ($textDivNodes->length > 0) {
                        $textContent = trim($textDivNodes->item(0)->textContent);
                        // Remove step number if present
                        $textContent = preg_replace('/^\d+\s*/', '', $textContent);
                        // Remove title if we already have it
                        if ($titleNodes->length > 0) {
                            $titleText = trim($titleNodes->item(0)->textContent);
                            $textContent = str_replace($titleText, '', $textContent);
                        }
                        $textContent = trim($textContent);
                        
                        if (!empty($textContent)) {
                            $stepText = '<p>' . htmlspecialchars($textContent) . '</p>';
                        }
                    }
                }
                
                if (!empty($stepText)) {
                    $instructionsHtml .= '<li>' . $stepTitle . $stepText . '</li>';
                }
            }
        }
        
        $instructionsHtml .= '</ol>';
        
        return $instructionsHtml !== '<ol></ol>' ? $instructionsHtml : '';
    }

    /**
     * Extract image URL from recipe page
     */
    private function extractImageUrl(DOMXPath $xpath): ?string
    {
        // Strategy 1: Try to find picture element with player-fallback-img class
        $pictureNodes = $xpath->query('//picture[@class="player-fallback-img u-hidden"]//img');
        
        if ($pictureNodes->length > 0) {
            $imgNode = $pictureNodes->item(0);
            $src = $imgNode->getAttribute('src');
            if (!empty($src)) {
                return $src;
            }
        }
        
        // Try source element with srcset from player-fallback-img
        $sourceNodes = $xpath->query('//picture[@class="player-fallback-img u-hidden"]//source[@srcset]');
        if ($sourceNodes->length > 0) {
            $srcset = $sourceNodes->item(0)->getAttribute('srcset');
            // Extract first URL from srcset (format: "url width, url2 width2")
            if (preg_match('/^([^\s]+)/', $srcset, $matches)) {
                return $matches[1];
            }
        }
        
        // Fallback: try any img within picture.player-fallback-img
        $imgNodes = $xpath->query('//picture[contains(@class, "player-fallback-img")]//img[@src]');
        if ($imgNodes->length > 0) {
            $src = $imgNodes->item(0)->getAttribute('src');
            if (!empty($src)) {
                return $src;
            }
        }
        
        // Strategy 2: Try to find image in recipe-cover div (glide slide)
        // Look for img within recipe-cover div
        $coverImgNodes = $xpath->query('//div[contains(@class, "recipe-cover")]//picture//img[@src]');
        if ($coverImgNodes->length > 0) {
            // Prefer the second picture (not the blur one) if available
            // The blur one is usually the first, the main image is usually the second
            $imgNode = $coverImgNodes->length > 1 ? $coverImgNodes->item(1) : $coverImgNodes->item(0);
            $src = $imgNode->getAttribute('src');
            if (!empty($src)) {
                return $src;
            }
        }
        
        // Try source element with srcset from recipe-cover
        $coverSourceNodes = $xpath->query('//div[contains(@class, "recipe-cover")]//picture//source[@srcset]');
        if ($coverSourceNodes->length > 0) {
            // Prefer the second source (not the blur one) if available
            $sourceNode = $coverSourceNodes->length > 1 ? $coverSourceNodes->item(1) : $coverSourceNodes->item(0);
            $srcset = $sourceNode->getAttribute('srcset');
            // Extract first URL from srcset (format: "url width, url2 width2")
            if (preg_match('/^([^\s]+)/', $srcset, $matches)) {
                return $matches[1];
            }
        }
        
        // Final fallback: try any picture within recipe-cover (without checking for img tag)
        $coverPictureNodes = $xpath->query('//div[contains(@class, "recipe-cover")]//picture[not(contains(@class, "recipe-cover-blur"))]//img[@src]');
        if ($coverPictureNodes->length > 0) {
            $src = $coverPictureNodes->item(0)->getAttribute('src');
            if (!empty($src)) {
                return $src;
            }
        }
        
        return null;
    }

    /**
     * Fallback method using cURL
     */
    private function fetchWithCurl(string $url): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($html === false || !empty($error)) {
            throw new \Exception('Erro ao buscar página: ' . ($error ?: 'Erro desconhecido'));
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception("Falha ao buscar a página. Status HTTP: {$httpCode}");
        }
        
        return $html;
    }
}

