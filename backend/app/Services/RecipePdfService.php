<?php

namespace App\Services;

use App\Models\Recipe;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecipePdfService
{
    /**
     * Generate PDF for a recipe
     */
    public function generatePdf(Recipe $recipe)
    {
        // Load relationships
        $recipe->load(['user', 'category']);

        // Format prep time
        $prepTime = $this->formatTime($recipe->prep_time_minutes);

        // Strip HTML tags from ingredients and instructions for cleaner PDF
        $ingredients = strip_tags($recipe->ingredients ?? '');
        $instructions = strip_tags($recipe->instructions ?? '');

        // Get image URL and convert to base64 (dompdf needs base64 for all images)
        $imageUrl = null;
        
        if ($recipe->image) {
            try {
                $imageData = null;
                $mimeType = 'image/jpeg';
                
                // Check if it's a URL (external image)
                if (filter_var($recipe->image, FILTER_VALIDATE_URL)) {
                    // External image - download it
                    try {
                        $response = Http::timeout(10)
                            ->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                                'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                            ])
                            ->get($recipe->image);
                            
                        if ($response->successful()) {
                            $imageData = $response->body();
                            
                            // Validate that we actually got image data
                            if (!empty($imageData) && strlen($imageData) > 100) {
                                // Try to detect mime type from response headers
                                $contentType = $response->header('Content-Type');
                                if ($contentType && strpos($contentType, 'image/') !== false) {
                                    $mimeType = explode(';', $contentType)[0];
                                } else {
                                    // Try to detect from image data
                                    $imageInfo = @getimagesizefromstring($imageData);
                                    if ($imageInfo && isset($imageInfo['mime'])) {
                                        $mimeType = $imageInfo['mime'];
                                    }
                                }
                            } else {
                                Log::warning('Downloaded image data seems invalid', [
                                    'recipe_id' => $recipe->id,
                                    'image_url' => $recipe->image,
                                    'data_size' => strlen($imageData ?? ''),
                                ]);
                                $imageData = null;
                            }
                        } else {
                            Log::warning('Failed to download external image - HTTP error', [
                                'recipe_id' => $recipe->id,
                                'image_url' => $recipe->image,
                                'status' => $response->status(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to download external image for PDF', [
                            'recipe_id' => $recipe->id,
                            'image_url' => $recipe->image,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    // Local file
                    if (Storage::disk('public')->exists($recipe->image)) {
                        $imagePath = Storage::disk('public')->path($recipe->image);
                        $imageData = file_get_contents($imagePath);
                        $imageInfo = getimagesize($imagePath);
                        if ($imageInfo) {
                            $mimeType = $imageInfo['mime'];
                        }
                    }
                }
                
                // Convert to base64 if we have image data
                if ($imageData) {
                    $imageBase64 = base64_encode($imageData);
                    $imageUrl = 'data:' . $mimeType . ';base64,' . $imageBase64;
                    
                    Log::info('Image converted to base64 for PDF', [
                        'recipe_id' => $recipe->id,
                        'mime_type' => $mimeType,
                        'data_size' => strlen($imageData),
                        'base64_size' => strlen($imageBase64),
                    ]);
                } else {
                    Log::warning('No image data available for PDF', [
                        'recipe_id' => $recipe->id,
                        'image' => $recipe->image,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to process image for PDF', [
                    'recipe_id' => $recipe->id,
                    'image' => $recipe->image,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Generate PDF with options
        $pdf = Pdf::loadView('pdf.recipe', [
            'recipe' => $recipe,
            'prepTime' => $prepTime,
            'ingredients' => $ingredients,
            'instructions' => $instructions,
            'imageUrl' => $imageUrl,
        ]);

        // Set PDF options
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('enable-remote', true);
        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isPhpEnabled', true);

        return $pdf;
    }

    /**
     * Format time in minutes to readable format
     */
    private function formatTime(?int $minutes): string
    {
        if (!$minutes) {
            return '-';
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }
}

