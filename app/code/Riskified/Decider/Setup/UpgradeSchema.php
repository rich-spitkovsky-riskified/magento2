<?php

namespace Riskified\Decider\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('riskified_decider_declination_sent')
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Retry ID'
            )->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Order ID'
            )
            ->addForeignKey(
                $setup->getFkName(
                    $setup->getTable('riskified_decider_declination_sent'),
                    'order_id',
                    $setup->getTable('sales_order'),
                    'entity_id'
                ),
                'order_id',
                $setup->getTable('sales_order'),
                'entity_id',
                Table::ACTION_CASCADE
            );
            $setup->getConnection()->createTable($table);
        }
        $setup->endSetup();
    }
}
