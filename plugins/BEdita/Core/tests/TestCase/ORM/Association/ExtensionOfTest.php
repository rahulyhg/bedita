<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Core\Test\TestCase\ORM\Association;

use BEdita\Core\ORM\Association\ExtensionOf;
use Cake\ORM\Association;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * {@see \BEdita\Core\ORM\Association\ExtensionOf} Test Case
 *
 * @coversDefaultClass \BEdita\Core\ORM\Association\ExtensionOf
 */
class ExtensionOfTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.fake_animals',
        'plugin.BEdita/Core.fake_mammals',
        'plugin.BEdita/Core.fake_felines',
    ];

    /**
     * Table FakeAnimals
     *
     * @var \Cake\ORM\Table
     */
    public $fakeAnimals;

    /**
     * Table FakeMammals
     *
     * @var \Cake\ORM\Table
     */
    public $fakeMammals;

    /**
     * Table FakeFelines
     *
     * @var \Cake\ORM\Table
     */
    public $fakeFelines;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->fakeAnimals = TableRegistry::get('FakeAnimals');
        $this->fakeMammals = TableRegistry::get('FakeMammals');
        $this->fakeFelines = TableRegistry::get('FakeFelines');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->fakeFelines);
        unset($this->fakeMammals);
        unset($this->fakeAnimals);
        TableRegistry::clear();
        parent::tearDown();
    }

    /**
     * Test testNewAssociation
     *
     * @return void
     * @covers ::type()
     */
    public function testNewAssociation()
    {
        $assoc = new ExtensionOf('FakeAnimals', [
            'sourceTable' => $this->fakeMammals,
            'foreignKey' => $this->fakeMammals->primaryKey()
        ]);

        $this->assertEquals(Association::ONE_TO_ONE, $assoc->type());
        $this->assertEquals('INNER', $assoc->joinType());
        $this->assertTrue($assoc->dependent());
        $this->assertTrue($assoc->cascadeCallbacks());
        $this->assertEquals($this->fakeAnimals->primaryKey(), $assoc->bindingKey());
        $this->assertEquals('fake_animal', $assoc->property());
        $this->assertFalse($assoc->isOwningSide($this->fakeMammals));
        $this->assertTrue($assoc->isOwningSide($this->fakeAnimals));
    }

    /**
     * Data provider for `testSaveAssociated` test case.
     *
     * @return array
     */
    public function saveAssociatedProvider()
    {
        return [
            'noProperty' => [
                [
                    'subclass' => 'Eutheria'
                ]
            ],
            'protertySet' => [
                [
                    'subclass' => 'Marsupial',
                    'fake_animal' => [
                        'name' => 'kangaroo',
                        'legs' => 4
                    ]
                ]
            ],
        ];
    }

    /**
     * Test testSaveAssociated
     *
     * @return void
     * @dataProvider saveAssociatedProvider
     * @coversNothing
     */
    public function testSaveAssociated($entityData)
    {
        $assoc = new ExtensionOf('FakeAnimals', [
            'sourceTable' => $this->fakeMammals,
            'foreignKey' => $this->fakeMammals->primaryKey()
        ]);

        $this->fakeMammals->associations()->add($assoc->name(), $assoc);
        $mammal = $this->fakeMammals->newEntity($entityData);

        if (empty($entityData['fake_animal'])) {
            $prop = $mammal->visibleProperties();
            $assoc->saveAssociated($mammal);
            $this->assertEquals($prop, $mammal->visibleProperties());
        } else {
            $lastInserted = $this->fakeAnimals
                ->find()
                ->hydrate(false)
                ->last();

            $expectedId = $lastInserted['id'] + 1;

            $mammal = $assoc->saveAssociated($mammal);
            $this->assertEquals($expectedId, $mammal->fake_animal->id);
            $this->assertEquals($expectedId, $mammal->id);
        }
    }

    /**
     * Test testDependent
     *
     * @return void
     * @coversNothing
     */
    public function testDependent()
    {
        $assoc = new ExtensionOf('FakeAnimals', [
            'sourceTable' => $this->fakeMammals,
            'foreignKey' => $this->fakeMammals->primaryKey()
        ]);
        $this->fakeMammals->associations()->add($assoc->name(), $assoc);

        $assoc = new ExtensionOf('FakeMammals', [
            'sourceTable' => $this->fakeFelines,
            'foreignKey' => $this->fakeFelines->primaryKey()
        ]);
        $this->fakeFelines->associations()->add($assoc->name(), $assoc);

        $feline = $this->fakeFelines->find()
            ->contain('FakeMammals.FakeAnimals')
            ->last();

        $id = $feline->id;
        $this->fakeFelines->delete($feline);
        foreach (['fakeFelines', 'fakeMammals', 'fakeAnimals'] as $table) {
            try {
                $entity = $this->{$table}->get($id);
                $this->fail(ucfirst($table) . ' record not deleted');
            } catch (\Cake\Datasource\Exception\RecordNotFoundException $ex) {
                continue;
            }
        }
    }
}