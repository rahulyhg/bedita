<?php
use Migrations\AbstractMigration;

/**
 * Migration for:
 *
 * - add `parent_node_id`
 * - change `menu` to boolean
 * - add indexes
 *
 * @since 4.0.0
 */
class AddTreesParentNodeId extends AbstractMigration
{

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        $this->table('trees')
            ->removeIndexByName('trees_rootleft_idx')
            ->removeIndexByName('trees_rootright_idx')
            ->update();

        $this->table('trees')
            ->addColumn('parent_node_id', 'integer', [
                'comment' => 'parent node id',
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
                'after' => 'root_id',
            ])
            ->removeColumn('menu')
            ->update();

        $this->table('trees')
            ->addColumn('menu', 'boolean', [
                'comment' => 'menu on/off',
                'default' => '1',
                'limit' => null,
                'null' => false,
                'length' => null,
            ])
            ->update();

        $this->table('trees')
            ->addIndex(
                [
                    'tree_left',
                ],
                [
                    'name' => 'trees_left_idx',
                ]
            )
            ->addIndex(
                [
                    'tree_right',
                ],
                [
                    'name' => 'trees_right_idx',
                ]
            )
            ->addIndex(
                [
                    'parent_node_id',
                ],
                [
                    'name' => 'trees_parentnodeid_idx',
                ]
            )
            ->update();

        $this->table('trees')
            ->addForeignKey(
                'parent_node_id',
                'trees',
                'id',
                [
                    'constraint' => 'trees_parentnodeid_fk',
                    'update' => 'NO ACTION',
                    'delete' => 'CASCADE'
                ]
            )
            ->update();
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->table('trees')
            ->dropForeignKey(
                'parent_node_id'
            );

        $this->table('trees')
            ->removeIndexByName('trees_left_idx')
            ->removeIndexByName('trees_right_idx')
            ->removeIndexByName('trees_parentnodeid_idx')
            ->update();

        $this->table('trees')
            ->removeColumn('parent_node_id')
            ->removeColumn('menu')
            ->addIndex(
                [
                    'root_id',
                    'tree_left',
                ],
                [
                    'name' => 'trees_rootleft_idx',
                ]
            )
            ->addIndex(
                [
                    'root_id',
                    'tree_right',
                ],
                [
                    'name' => 'trees_rootright_idx',
                ]
            )
            ->update();

        $this->table('trees')
            ->addColumn('menu', 'integer', [
                'comment' => 'menu on/off',
                'default' => '1',
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->update();
    }
}
