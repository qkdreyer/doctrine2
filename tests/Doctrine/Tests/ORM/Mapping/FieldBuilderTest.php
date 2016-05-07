<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\OrmTestCase;

class FieldBuilderTest extends OrmTestCase
{
    public function testCustomIdGeneratorCanBeSet()
    {
        $cmBuilder = new ClassMetadataBuilder(new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser'));

        $fieldBuilder = $cmBuilder->createField('aField', 'string');

        $fieldBuilder->generatedValue('CUSTOM');
        $fieldBuilder->setCustomIdGenerator('stdClass');

        $fieldBuilder->build();

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_CUSTOM, $cmBuilder->getClassMetadata()->generatorType);
        self::assertEquals(['class' => 'stdClass'], $cmBuilder->getClassMetadata()->customGeneratorDefinition);
    }
}
