<?php

namespace Icreative\OauthRefreshToken\Model\ResourceModel\RefreshToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected $_eventPrefix = 'icreative_oauth_refresh_token_collection';

    protected $_eventObject = 'collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Icreative\OauthRefreshToken\Model\RefreshToken::class,
            \Icreative\OauthRefreshToken\Model\ResourceModel\RefreshToken::class,
        );
    }

    public function addFilterByCustomerId($customerId): Collection
    {
        $this->addFilter('main_table.customer_id', $customerId);

        return $this;
    }

    public function addFilterByRevoked($flag): Collection
    {
        $this->addFilter('main_table.is_revoked', (int)$flag, 'public');

        return $this;
    }
}
