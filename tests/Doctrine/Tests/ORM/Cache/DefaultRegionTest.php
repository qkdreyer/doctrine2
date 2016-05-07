<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Cache\CollectionCacheEntry;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;

/**
 * @group DDC-2183
 */
class DefaultRegionTest extends AbstractRegionTest
{
    protected function createRegion()
    {
        return new DefaultRegion('default.region.test', $this->cache);
    }

    public function testGetters()
    {
        self::assertEquals('default.region.test', $this->region->getName());
        self::assertSame($this->cache, $this->region->getCache());
    }

    public function testSharedRegion()
    {
        if ( ! extension_loaded('apc') || false === @apc_cache_info()) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of APC');
        }

        $key     = new CacheKeyMock('key');
        $entry   = new CacheEntryMock(array('value' => 'foo'));
        $region1 = new DefaultRegion('region1', new ApcCache());
        $region2 = new DefaultRegion('region2', new ApcCache());

        self::assertFalse($region1->contains($key));
        self::assertFalse($region2->contains($key));

        $region1->put($key, $entry);
        $region2->put($key, $entry);

        self::assertTrue($region1->contains($key));
        self::assertTrue($region2->contains($key));

        $region1->evictAll();

        self::assertFalse($region1->contains($key));
        self::assertTrue($region2->contains($key));
    }

    public function testDoesNotModifyCacheNamespace()
    {
        $cache = new ArrayCache();

        $cache->setNamespace('foo');

        new DefaultRegion('bar', $cache);
        new DefaultRegion('baz', $cache);

        self::assertSame('foo', $cache->getNamespace());
    }

    public function testEvictAllWithGenericCacheThrowsUnsupportedException()
    {
        /* @var $cache \Doctrine\Common\Cache\Cache */
        $cache = $this->createMock(Cache::class);

        $region = new DefaultRegion('foo', $cache);

        $this->expectException(\BadMethodCallException::class);

        $region->evictAll();
    }

    public function testGetMulti()
    {
        $key1 = new CacheKeyMock('key.1');
        $value1 = new CacheEntryMock(array('id' => 1, 'name' => 'bar'));

        $key2 = new CacheKeyMock('key.2');
        $value2 = new CacheEntryMock(array('id' => 2, 'name' => 'bar'));

        self::assertFalse($this->region->contains($key1));
        self::assertFalse($this->region->contains($key2));

        $this->region->put($key1, $value1);
        $this->region->put($key2, $value2);

        self::assertTrue($this->region->contains($key1));
        self::assertTrue($this->region->contains($key2));

        $actual = $this->region->getMultiple(new CollectionCacheEntry(array($key1, $key2)));

        self::assertEquals($value1, $actual[0]);
        self::assertEquals($value2, $actual[1]);
    }
}