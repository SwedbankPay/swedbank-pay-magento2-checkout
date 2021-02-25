<?php


namespace SwedbankPay\Checkout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $orderTable = 'sales_order';
        $orderGridTable = 'sales_order_grid';

        if (version_compare($context->getVersion(), "1.2.0", "<")) {
            // Order table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'swedbank_pay_transaction_number',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'Swedbank Pay Transaction Number'
                    ]
                );

            // Order Grid table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderGridTable),
                    'swedbank_pay_transaction_number',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'Swedbank Pay Transaction Number'
                    ]
                );
        }

        $swedbankPayQuoteTable = 'swedbank_pay_quotes';
        $swedbankPayOrderTable = 'swedbank_pay_orders';

        if (version_compare($context->getVersion(), "1.3.0", "<")) {
            // Swedbank Pay Quotes table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($swedbankPayQuoteTable),
                    'payment_id_path',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'SwedbankPay Payment ID Path'
                    ]
                );

            // Swedbank Pay Orders table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($swedbankPayOrderTable),
                    'payment_id_path',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' =>'SwedbankPay Payment ID Path'
                    ]
                );
        }

        $setup->endSetup();
    }
}
