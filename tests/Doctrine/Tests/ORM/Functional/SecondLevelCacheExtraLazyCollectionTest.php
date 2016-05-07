<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @group DDC-2183
 */
class SecondLevelCacheExtraLazyCollectionTest extends SecondLevelCacheAbstractTest
{
    public function setUp()
    {
        parent::setUp();

        $sourceEntity = $this->_em->getClassMetadata(Travel::CLASSNAME);
        $targetEntity = $this->_em->getClassMetadata(City::CLASSNAME);

        $sourceEntity->associationMappings['visitedCities']['fetch'] = ClassMetadata::FETCH_EXTRA_LAZY;
        $targetEntity->associationMappings['travels']['fetch']       = ClassMetadata::FETCH_EXTRA_LAZY;
    }

    public function tearDown()
    {
        parent::tearDown();

        $sourceEntity = $this->_em->getClassMetadata(Travel::CLASSNAME);
        $targetEntity = $this->_em->getClassMetadata(City::CLASSNAME);

        $sourceEntity->associationMappings['visitedCities']['fetch'] = ClassMetadata::FETCH_LAZY;
        $targetEntity->associationMappings['travels']['fetch']       = ClassMetadata::FETCH_LAZY;
    }
    
    public function testCacheCountAfterAddThenFlush()
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();

        $this->_em->clear();

        $ownerId    = $this->travels[0]->getId();
        $owner      = $this->_em->find(Travel::CLASSNAME, $ownerId);
        $ref        = $this->_em->find(State::CLASSNAME, $this->states[1]->getId());
        
        self::assertTrue($this->cache->containsEntity(Travel::CLASSNAME, $ownerId));
        self::assertTrue($this->cache->containsCollection(Travel::CLASSNAME, 'visitedCities', $ownerId));

        $newItem = new City("New City", $ref);
        $owner->getVisitedCities()->add($newItem);

        $this->_em->persist($newItem);
        $this->_em->persist($owner);

        $queryCount = $this->getCurrentQueryCount();

        self::assertFalse($owner->getVisitedCities()->isInitialized());
        self::assertEquals(4, $owner->getVisitedCities()->count());
        self::assertFalse($owner->getVisitedCities()->isInitialized());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());

        $this->_em->flush();

        self::assertFalse($owner->getVisitedCities()->isInitialized());
        self::assertFalse($this->cache->containsCollection(Travel::CLASSNAME, 'visitedCities', $ownerId));

        $this->_em->clear();

        $queryCount = $this->getCurrentQueryCount();
        $owner      = $this->_em->find(Travel::CLASSNAME, $ownerId);

        self::assertEquals(4, $owner->getVisitedCities()->count());
        self::assertFalse($owner->getVisitedCities()->isInitialized());
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());
    }
}