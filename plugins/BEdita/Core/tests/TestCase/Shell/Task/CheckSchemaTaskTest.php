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

namespace BEdita\Core\Test\TestCase\Shell\Task;

use BEdita\Core\Shell\Task\CheckSchemaTask;
use Cake\Console\Shell;
use Cake\Core\Plugin;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * @coversDefaultClass \BEdita\Core\Shell\Task\CheckSchemaTask
 */
class CheckSchemaTaskTest extends ConsoleIntegrationTestCase
{

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->fixtureManager->shutDown();

        $this->exec('db_admin init -fs');
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        parent::tearDown();

        Plugin::load('Migrations');
    }

    /**
     * {@inheritDoc}
     */
    public static function tearDownAfterClass()
    {
        ConnectionManager::get('default')
            ->transactional(function (Connection $connection) {
                $tables = $connection->getSchemaCollection()->listTables();

                foreach ($tables as $table) {
                    $sql = $connection->getSchemaCollection()->describe($table)->dropConstraintSql($connection);
                    foreach ($sql as $query) {
                        $connection->query($query);
                    }
                }
                foreach ($tables as $table) {
                    $sql = $connection->getSchemaCollection()->describe($table)->dropSql($connection);
                    foreach ($sql as $query) {
                        $connection->query($query);
                    }
                }
            });

        parent::tearDownAfterClass();
    }

    /**
     * Test controlled failure on missing "Migrations" plugin.
     *
     * @return void
     * @covers ::main()
     */
    public function testMissingMigrationsPlugin()
    {
        Plugin::unload('Migrations');

        $this->exec(CheckSchemaTask::class);

        $this->assertExitCode(Shell::CODE_ERROR);
        $this->assertErrorContains('Plugin "Migrations" must be loaded');
    }

    /**
     * Test check on offended SQL conventions.
     *
     * @return void
     * @covers ::checkConventions()
     * @covers ::checkSymbol()
     * @covers ::formatMessages()
     */
    public function testOffendedConventions()
    {
        /* @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        $table = new Table('foo_bar');
        $table
            ->addColumn('foo_bar', [
                'type' => 'string',
                'length' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('password', [
                'type' => 'string',
                'length' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('42gustavo__suppOrto_', [
                'type' => 'string',
                'length' => 255,
                'null' => true,
                'default' => null,
            ])
            ->addIndex('mytestindex', [
                'type' => Table::INDEX_INDEX,
                'columns' => ['foo_bar'],
            ])
            ->addConstraint('foobar_uq', [
                'type' => Table::CONSTRAINT_UNIQUE,
                'columns' => ['foo_bar'],
            ]);
        foreach ($table->createSql($connection) as $statement) {
            $connection->query($statement);
        }

        $this->exec(CheckSchemaTask::class);

        if ($connection->getDriver() instanceof Mysql) {
            static::assertExitCode(Shell::CODE_ERROR);
            $this->assertOutputContains('Column name "foo_bar" is not valid (same name as table)');
            $this->assertOutputContains('Column name "password" is not valid (reserved word)');
            $this->assertOutputContains('Column name "42gustavo__suppOrto_" is not valid');
            $this->assertOutputContains('Index name "mytestindex" is not valid');
            $this->assertOutputRegExp('/Constraint name "[a-zA-Z0-9_]+" is not valid/');
        } else {
            static::assertExitCode(Shell::CODE_SUCCESS);
            $this->assertOutputContains('SQL conventions and schema differences can only be checked on MySQL');
        }
        $this->assertErrorEmpty();
    }

    /**
     * Test successful schema check.
     *
     * @return void
     * @covers \BEdita\Core\Shell\Task\CheckSchemaTask
     */
    public function testCheckSchema()
    {
        /* @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        $this->exec(CheckSchemaTask::class);

        $this->assertExitCode(Shell::CODE_SUCCESS);
        if (!($connection->getDriver() instanceof Mysql)) {
            $this->assertOutputContains('SQL conventions and schema differences can only be checked on MySQL');
        }
        $this->assertErrorEmpty();
    }

    /**
     * Test check on new table.
     *
     * @return void
     * @covers ::checkDiff()
     * @covers ::formatMessages()
     */
    public function testAddTable()
    {
        /* @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        $table = new Table('foo_bar', ['foo' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null]]);
        foreach ($table->createSql($connection) as $statement) {
            $connection->query($statement);
        }

        $this->exec(CheckSchemaTask::class);

        if ($connection->getDriver() instanceof Mysql) {
            $this->assertExitCode(Shell::CODE_ERROR);
            $this->assertOutputContains('Table "foo_bar" has been added');
        } else {
            $this->assertExitCode(Shell::CODE_SUCCESS);
            $this->assertOutputContains('SQL conventions and schema differences can only be checked on MySQL');
        }
        $this->assertErrorEmpty();
    }

    /**
     * Test check on removed table.
     *
     * @return void
     * @covers ::checkDiff()
     * @covers ::formatMessages()
     */
    public function testRemoveTable()
    {
        /* @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        $table = $connection->getSchemaCollection()->describe('config');
        foreach ($table->dropSql($connection) as $statement) {
            $connection->query($statement);
        }

        $this->exec(CheckSchemaTask::class);

        if ($connection->getDriver() instanceof Mysql) {
            $this->assertExitCode(Shell::CODE_ERROR);
            $this->assertOutputContains('Table "config" has been removed');
        } else {
            $this->assertExitCode(Shell::CODE_SUCCESS);
            $this->assertOutputContains('SQL conventions and schema differences can only be checked on MySQL');
        }
        $this->assertErrorEmpty();
    }

    /**
     * Test check on removed constraint.
     *
     * @return void
     * @covers ::checkDiff()
     * @covers ::formatMessages()
     */
    public function testUpdateConstraints()
    {
        /* @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        $table = $connection->getSchemaCollection()->describe('objects');
        $constraints = $table->constraints();
        foreach ($table->dropConstraintSql($connection) as $statement) {
            $connection->query($statement);
        }

        $this->exec(CheckSchemaTask::class);

        if ($connection->getDriver() instanceof Mysql) {
            $this->assertExitCode(Shell::CODE_ERROR);
            foreach ($constraints as $constraint) {
                $info = $table->getConstraint($constraint);
                if ($info && isset($info['type']) && $info['type'] !== Table::CONSTRAINT_FOREIGN) {
                    continue;
                }

                $this->assertOutputContains(sprintf('Constraint "%s" has been removed', $constraint));
            }
        } else {
            $this->assertExitCode(Shell::CODE_SUCCESS);
            $this->assertOutputContains('SQL conventions and schema differences can only be checked on MySQL');
        }
        $this->assertErrorEmpty();
    }
}
