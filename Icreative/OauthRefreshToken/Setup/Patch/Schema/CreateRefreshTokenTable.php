<?php

namespace Icreative\OauthRefreshToken\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class CreateRefreshTokenTable implements SchemaPatchInterface
{
    private ModuleDataSetupInterface $_moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->_moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $installer = $this->_moduleDataSetup;
        $installer->startSetup();

        $tableName = $installer->getTable('oauth_refresh_token');

        $table = $installer->getConnection()
            ->newTable($tableName)
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Token ID'
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                ],
                'Customer ID'
            )
            ->addColumn(
                'token',
                Table::TYPE_TEXT,
                32,
                [
                    'nullable' => false,
                ],
                'Token value'
            )
            ->addColumn(
                'is_revoked',
                Table::TYPE_BOOLEAN,
                null,
                [
                    'nullable' => false,
                    'default' => 0,
                ],
                'Is token revoked'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT,
                ],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT_UPDATE,
                ],
                'Updated At'
            )
            ->setComment('OAuth Refresh Token');

        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addIndex(
            $tableName,
            $installer->getConnection()->getIndexName(
                $tableName,
                ['token'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['token'],
            AdapterInterface::INDEX_TYPE_FULLTEXT
        );

        $installer->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
