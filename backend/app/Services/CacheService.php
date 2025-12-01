<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache prefix for recipe searches
     */
    private const CACHE_PREFIX = 'recipe_search:';

    /**
     * Cache TTL in seconds (default: 1 hour)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Generate cache key from filters
     */
    private function generateCacheKey(array $filters, ?int $page = 1, ?int $perPage = 15, ?string $sortBy = null): string
    {
        // Normalize filters to ensure consistent cache keys
        $normalizedFilters = [
            'category_id' => $filters['category_id'] ?? null,
            'servings_operator' => $filters['servings']['operator'] ?? null,
            'servings_value' => $filters['servings']['value'] ?? null,
            'prep_time_operator' => $filters['prep_time']['operator'] ?? null,
            'prep_time_value' => $filters['prep_time']['value'] ?? null,
            'rating_operator' => $filters['rating']['operator'] ?? null,
            'rating_value' => $filters['rating']['value'] ?? null,
            'comments_operator' => $filters['comments']['operator'] ?? null,
            'comments_value' => $filters['comments']['value'] ?? null,
            'my_recipes' => $filters['my_recipes'] ?? false,
            'user_id' => $filters['user_id'] ?? null, // Include user_id when filtering by my_recipes
            'search' => isset($filters['search']) ? trim(strtolower($filters['search'])) : null,
            'sort_by' => $sortBy,
            'page' => $page,
            'per_page' => $perPage,
        ];

        // Remove null values and create a hash
        $normalizedFilters = array_filter($normalizedFilters, fn($value) => $value !== null && $value !== false);
        ksort($normalizedFilters);

        $key = self::CACHE_PREFIX . md5(json_encode($normalizedFilters));
        
        return $key;
    }

    /**
     * Get cached recipe search results
     */
    public function getCachedRecipes(array $filters, ?int $page = 1, ?int $perPage = 15, ?string $sortBy = null): ?array
    {
        $key = $this->generateCacheKey($filters, $page, $perPage, $sortBy);
        
        $cached = Cache::get($key);
        
        if ($cached !== null) {
            Log::info('Cache HIT', [
                'key' => substr($key, 0, 50) . '...',
                'filters' => $this->getFiltersSummary($filters),
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
            ]);
        } else {
            Log::info('Cache MISS', [
                'key' => substr($key, 0, 50) . '...',
                'filters' => $this->getFiltersSummary($filters),
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
            ]);
        }
        
        return $cached;
    }
    
    /**
     * Get a summary of filters for logging
     */
    private function getFiltersSummary(array $filters): array
    {
        $summary = [];
        
        if (isset($filters['category_id'])) {
            $summary['category_id'] = $filters['category_id'];
        }
        if (isset($filters['search'])) {
            $summary['search'] = substr($filters['search'], 0, 20);
        }
        if (isset($filters['servings']['value'])) {
            $summary['servings'] = $filters['servings']['operator'] . ' ' . $filters['servings']['value'];
        }
        if (isset($filters['prep_time']['value'])) {
            $summary['prep_time'] = $filters['prep_time']['operator'] . ' ' . $filters['prep_time']['value'];
        }
        if (isset($filters['rating']['value'])) {
            $summary['rating'] = $filters['rating']['operator'] . ' ' . $filters['rating']['value'];
        }
        if (isset($filters['comments']['value'])) {
            $summary['comments'] = $filters['comments']['operator'] . ' ' . $filters['comments']['value'];
        }
        if (isset($filters['my_recipes']) && $filters['my_recipes']) {
            $summary['my_recipes'] = true;
        }
        
        return $summary;
    }

    /**
     * Cache recipe search results
     */
    public function cacheRecipes(array $filters, array $data, ?int $page = 1, ?int $perPage = 15, ?string $sortBy = null, int $ttl = self::DEFAULT_TTL): void
    {
        $key = $this->generateCacheKey($filters, $page, $perPage, $sortBy);
        
        Cache::put($key, $data, $ttl);
        
        Log::info('Cache STORED', [
            'key' => substr($key, 0, 50) . '...',
            'filters' => $this->getFiltersSummary($filters),
            'page' => $page,
            'per_page' => $perPage,
            'sort_by' => $sortBy,
            'total_recipes' => $data['total'] ?? 0,
            'ttl_seconds' => $ttl,
        ]);
    }

    /**
     * Invalidate all recipe search caches
     */
    public function invalidateRecipeCaches(): void
    {
        try {
            $cacheDriver = config('cache.default');
            $keysCount = 0;
            
            // For Redis driver, try to invalidate specific keys
            if ($cacheDriver === 'redis') {
                try {
                    $prefix = config('cache.prefix', '');
                    $pattern = $prefix . self::CACHE_PREFIX . '*';
                    
                    // Use Redis connection
                    $redis = Redis::connection(config('cache.stores.redis.connection', 'default'));
                    
                    // Try to use SCAN if available (works with both phpredis and predis)
                    $cursor = 0;
                    $keys = [];
                    
                    do {
                        // SCAN command works with both clients
                        $result = $redis->command('SCAN', [$cursor, 'MATCH', $pattern, 'COUNT', 100]);
                        
                        if (is_array($result) && count($result) >= 2) {
                            $cursor = is_numeric($result[0]) ? (int)$result[0] : 0;
                            $keys = array_merge($keys, is_array($result[1]) ? $result[1] : []);
                        } else {
                            break;
                        }
                    } while ($cursor !== 0 && $cursor !== '0');
                    
                    if (!empty($keys)) {
                        // Remove Laravel cache prefix if present
                        $keys = array_map(function ($key) use ($prefix) {
                            $key = is_string($key) ? $key : (string)$key;
                            if ($prefix && strpos($key, $prefix) === 0) {
                                return substr($key, strlen($prefix));
                            }
                            return $key;
                        }, $keys);
                        
                        $keysCount = count($keys);
                        
                        // Delete keys in batches
                        foreach (array_chunk($keys, 100) as $chunk) {
                            Cache::deleteMultiple($chunk);
                        }
                        
                        Log::info('Cache INVALIDATED', [
                            'method' => 'specific_keys',
                            'keys_count' => $keysCount,
                            'pattern' => self::CACHE_PREFIX . '*',
                        ]);
                    } else {
                        // If no keys found or SCAN not working, flush cache
                        Cache::flush();
                        Log::warning('Cache INVALIDATED', [
                            'method' => 'flush_all',
                            'reason' => 'No keys found or SCAN failed',
                        ]);
                    }
                } catch (\Exception $scanException) {
                    // Fallback: if SCAN fails, flush all cache
                    Log::warning('Cache INVALIDATED', [
                        'method' => 'flush_all',
                        'reason' => 'Redis SCAN failed: ' . $scanException->getMessage(),
                    ]);
                    Cache::flush();
                }
            } else {
                // For other cache drivers, flush all cache
                Cache::flush();
                Log::info('Cache INVALIDATED', [
                    'method' => 'flush_all',
                    'driver' => $cacheDriver,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate recipe caches: ' . $e->getMessage());
            // Last resort: try to flush
            try {
                Cache::flush();
                Log::warning('Cache INVALIDATED', [
                    'method' => 'flush_all',
                    'reason' => 'Fallback after exception',
                ]);
            } catch (\Exception $flushException) {
                Log::error('Failed to flush cache: ' . $flushException->getMessage());
            }
        }
    }

    /**
     * Invalidate caches for a specific recipe (when updated/deleted)
     */
    public function invalidateRecipeCache(int $recipeId): void
    {
        Log::info('Cache INVALIDATION triggered', [
            'recipe_id' => $recipeId,
            'reason' => 'Recipe created/updated/deleted',
        ]);
        
        // Invalidate all caches since a recipe change affects all search results
        $this->invalidateRecipeCaches();
    }

    /**
     * Check if Redis is available
     */
    public function isRedisAvailable(): bool
    {
        try {
            // Check if cache driver is redis
            if (config('cache.default') !== 'redis') {
                return false;
            }
            
            // Try to get a connection and execute a simple command
            // Works with both phpredis and predis
            $redis = Redis::connection(config('cache.stores.redis.connection', 'default'));
            $result = $redis->command('PING');
            return $result === 'PONG' || $result === true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

