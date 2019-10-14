<?php

namespace SwedbankPay\Checkout\Setup;

use \Magento\Framework\DB\Ddl\Table;
use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'swedbank_pay_orders'
         */
        $tableName = 'swedbank_pay_orders';

        if (!$installer->tableExists($tableName)) {
            try {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable($tableName)
                )
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    255,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Primary Key'
                )
                ->addColumn(
                    'payment_order_id',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Payment Order ID'
                )
                ->addColumn(
                    'description',
                    Table::TYPE_TEXT,
                    3000,
                    ['nullable => false'],
                    'Description'
                )
                ->addColumn(
                    'operation',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Operation'
                )
                ->addColumn(
                    'state',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'State'
                )
                ->addColumn(
                    'intent',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable => true'],
                    'PreAuthorization|Authorization|AutoCapture|Sale'
                )
                ->addColumn(
                    'currency',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Currency'
                )
                ->addColumn(
                    'amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Amount'
                )
                ->addColumn(
                    'vat_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Vat Amount'
                )
                ->addColumn(
                    'remaining_capture_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Remaining Capture Amount'
                )
                ->addColumn(
                    'remaining_cancellation_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Remaining Cancellation Amount'
                )
                ->addColumn(
                    'remaining_reversal_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Remaining Reversal Amount'
                )
                ->addColumn(
                    'initiating_system_user_agent',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Initiating System User Agent'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true],
                    'Magento Order ID'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->addIndex(
                    'payment_order_index',
                    'payment_order_id'
                )
                ->addIndex(
                    'order_index',
                    'order_id'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        $tableName,
                        'order_id',
                        'sales_order',
                        'entity_id'
                    ),
                    'order_id',
                    $installer->getTable('sales_order'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('SwedbankPay Payment Post-Order Table');

                $installer->getConnection()->createTable($table);
            } catch (\Zend_Db_Exception $e) {
                // TODO: Implement Logger
                echo $e->getMessage();
                die();
            }
        }

        /**
         * Create table 'swedbank_pay_quotes'
         */
        $tableName = 'swedbank_pay_quotes';

        if (!$installer->tableExists($tableName)) {
            try {
                $table = $installer->getConnection()->newTable(
                    $installer->getTable($tableName)
                )
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    255,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Primary Key'
                )
                ->addColumn(
                    'payment_order_id',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false,
                    ],
                    'SwedbankPay Payment Order ID'
                )
                ->addColumn(
                    'description',
                    Table::TYPE_TEXT,
                    3000,
                    ['nullable => false'],
                    'Description'
                )
                ->addColumn(
                    'operation',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Operation'
                )
                ->addColumn(
                    'state',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'State'
                )
                ->addColumn(
                    'currency',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Currency'
                )
                ->addColumn(
                    'amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Amount'
                )
                ->addColumn(
                    'vat_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Vat Amount'
                )
                ->addColumn(
                    'remaining_capture_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Remaining Capture Amount'
                )
                ->addColumn(
                    'remaining_cancellation_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Remaining Cancellation Amount'
                )
                ->addColumn(
                    'remaining_reversal_amount',
                    Table::TYPE_INTEGER,
                    null,
                    [],
                    'Remaining Reversal Amount'
                )
                ->addColumn(
                    'payer_token',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Payer Consumer Token'
                )
                ->addColumn(
                    'quote_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['unsigned' => true],
                    'Magento Quote ID'
                )
                ->addColumn(
                    'is_updated',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => 0],
                    'Checks if quote is updated'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->addIndex(
                    'payment_order_index',
                    'payment_order_id'
                )
                ->addIndex(
                    'quote_index',
                    'quote_id'
                )
                ->addForeignKey(
                    $installer->getFkName(
                        $tableName,
                        'quote_id',
                        'quote',
                        'entity_id'
                    ),
                    'quote_id',
                    $installer->getTable('quote'),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('SwedbankPay Payment Pre-Order Table');

                $installer->getConnection()->createTable($table);
            } catch (\Zend_Db_Exception $e) {
                // TODO: Implement Logger
                echo $e->getMessage();
                die();
            }
        }

        $installer->endSetup();
    }
}
