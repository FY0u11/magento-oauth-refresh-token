<?php

namespace Icreative\OauthRefreshToken\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Oauth\Helper\Oauth;

/**
 * @method string getToken()
 * @method int getCustomerId()
 * @method void setCustomerId(int $customerId)
 * @method void setToken(string $token)
 * @method bool getIsRevoked()
 * @method setIsRevoked(bool $flag)
 * @method string getCreatedAt()
 */
class RefreshToken extends AbstractModel
{
    private Oauth $_oauthHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        Oauth $oauthHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_oauthHelper = $oauthHelper;
    }

    public function _construct()
    {
        $this->_init(ResourceModel\RefreshToken::class);
    }

    public function generateRefreshToken(int $customerId): self
    {
        $this->setToken($this->_oauthHelper->generateToken());
        $this->setCustomerId($customerId);

        return $this->save();
    }

    public function loadByToken($token)
    {
        return $this->load($token, 'token');
    }
}
