<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\User\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->addFailuresToAdminUserTable($setup);
            $this->createAdminPasswordsTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * Adds 'failures_num', 'first_failure', and 'lock_expires' columns to 'admin_user' table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function addFailuresToAdminUserTable(SchemaSetupInterface $setup)
    {
        $tableAdmins = $setup->getTable('admin_user');

        $setup->getConnection()->addColumn(
            $tableAdmins,
            'failures_num',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => true,
                'default' => 0,
                'comment' => 'Failure Number'
            ]
        );
        $setup->getConnection()->addColumn(
            $tableAdmins,
            'first_failure',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'comment' => 'First Failure'
            ]
        );
        $setup->getConnection()->addColumn(
            $tableAdmins,
            'lock_expires',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'comment' => 'Expiration Lock Dates'
            ]
        );
    }

    /**
     * Create table 'admin_passwords'
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function createAdminPasswordsTable(SchemaSetupInterface $setup)
    {
        if ($setup->tableExists($setup->getTable('admin_passwords'))) {
            return;
        }

        $table = $setup->getConnection()->newTable(
            $setup->getTable('admin_passwords')
        )->addColumn(
            'password_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Password Id'
        )->addColumn(
            'user_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'User Id'
        )->addColumn(
            'password_hash',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            100,
            [],
            'Password Hash'
        )->addColumn(
            'expires',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Expires'
        )->addColumn(
            'last_updated',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Last Updated'
        )->addIndex(
            $setup->getIdxName('admin_passwords', ['user_id']),
            ['user_id']
        )->addForeignKey(
            $setup->getFkName('admin_passwords', 'user_id', 'admin_user', 'user_id'),
            'user_id',
            $setup->getTable('admin_user'),
            'user_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Admin Passwords'
        );
        $setup->getConnection()->createTable($table);
    }
}
