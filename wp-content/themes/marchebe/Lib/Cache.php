<?php

namespace AcMarche\Theme\Lib;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;

class Cache
{
    private static ?CacheInterface $cache = null;
    private static ?SluggerInterface $slugger = null;

    private function __construct()
    {
    }

    public static function instance(): CacheInterface|RedisTagAwareAdapter
    {
        if (!self::$cache) {
            $client = RedisAdapter::createConnection('redis://localhost');
            self::$cache = new RedisTagAwareAdapter($client, 'marcheWp', 60 * 60 * 8);
        }

        return self::$cache;
    }

    public static function generateKey(string $cacheKey): string
    {
        if (!self::$slugger) {
            self::$slugger = new AsciiSlugger();
        }

        $keyUnicode = new UnicodeString($cacheKey);

        return self::$slugger->slug($keyUnicode->ascii()->toString());
    }

    // Helper method to get an item from cache
    public static function get(string $key, callable $callback, ?float $beta = null, ?array $tags = null)
    {
        $cacheKey = self::generateKey($key);

        return self::instance()->get($cacheKey, $callback, $beta, $tags);
    }

    // Helper method to delete an item from cache
    public static function delete(string $key): bool
    {
        $cacheKey = self::generateKey($key);

        return self::instance()->delete($cacheKey);
    }

    // Helper method to invalidate tags
    public static function invalidateTags(array $tags): bool
    {
        return self::instance()->invalidateTags($tags);
    }
    // Helper method to get an item from cache only if it exists (no computation)
    public static function getIfExists(string $cacheKey): mixed
    {
        $cache = self::instance();

        // Both ApcuAdapter and FilesystemAdapter implement CacheItemPoolInterface
        if ($cache instanceof CacheItemPoolInterface) {
            try {
                $item = $cache->getItem($cacheKey);
            } catch (InvalidArgumentException $e) {
                return null;
            }
            return $item->isHit() ? $item->get() : null;
        }

        return null;
    }

    private function sample()
    {
        // Example 1: Basic cache usage with callback
        $userData = Cache::get('user_data_123', function () {
            // This will only execute if cache miss
            return [
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ];
        });

// Example 2: Cache with tags for easy invalidation
        $products = Cache::get('products_category_5', function () {
            // Fetch products from database
            return fetchProductsFromDb(5);
        }, null, ['products', 'category_5']);

// Example 3: Generate cache key manually
        $cacheKey = Cache::generateKey('My Complex Cache Key!@#');
// Result: 'my-complex-cache-key'

// Example 4: Delete specific cache entry
        Cache::delete('user_data_123');

// Example 5: Invalidate all caches with specific tags
        Cache::invalidateTags(['products']); // Clears all product-related caches

// Example 6: Direct access to cache instance if needed
        $cacheInstance = Cache::instance();
        $item = $cacheInstance->getItem(Cache::generateKey('some_key'));
        if (!$item->isHit()) {
            $item->set('some value');
            $cacheInstance->save($item);
        }
    }
}