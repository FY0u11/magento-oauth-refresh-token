<?php

namespace Icreative\OauthRefreshToken\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RefreshToken extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('oauth_refresh_token', 'entity_id');
    }
}
