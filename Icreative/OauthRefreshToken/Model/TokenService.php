<?php

namespace Icreative\OauthRefreshToken\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Framework\Stdlib\DateTime;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token as TokenResource;
use Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory;
use Icreative\OauthRefreshToken\Model\ResourceModel\RefreshToken\CollectionFactory
    as RefreshTokenCollectionFactory;
use Icreative\OauthRefreshToken\Model\ResourceModel\RefreshToken
    as RefreshTokenResource;

class TokenService
{
    public const REFRESH_TOKEN_TTL_IN_DAYS = 30;

    private Token $_accessToken;

    private RefreshToken $_refreshToken;

    private TokenFactory $_tokenFactory;

    private CollectionFactory $_tokenCollectionFactory;

    private TokenResource $_tokenResource;

    private RefreshTokenFactory $_refreshTokenFactory;

    private RefreshTokenCollectionFactory $_refreshTokenCollectionFactory;

    private RefreshTokenResource $_refreshTokenResource;

    private DateTime $_dateTime;

    private Date $_date;

    public function __construct(
        TokenFactory $tokenFactory,
        CollectionFactory $tokenCollectionFactory,
        TokenResource $tokenResource,
        RefreshTokenFactory $refreshTokenFactory,
        RefreshTokenCollectionFactory $refreshTokenCollectionFactory,
        RefreshTokenResource $refreshTokenResource,
        DateTime $dateTime,
        Date $date
    ) {
        $this->_tokenFactory = $tokenFactory;
        $this->_tokenCollectionFactory = $tokenCollectionFactory;
        $this->_tokenResource = $tokenResource;
        $this->_refreshTokenFactory = $refreshTokenFactory;
        $this->_refreshTokenCollectionFactory = $refreshTokenCollectionFactory;
        $this->_refreshTokenResource = $refreshTokenResource;
        $this->_dateTime = $dateTime;
        $this->_date = $date;
    }

    public function getAccessToken(): Token
    {
        return $this->_accessToken;
    }

    /**
     * @param string $token
     * @return void
     * @throws LocalizedException
     */
    public function setAccessTokenByString(string $token)
    {
        $accessToken = $this->_tokenFactory->create();
        $accessToken = $accessToken->loadByToken($token);

        if (!$accessToken->getEntityId()
            || !$this->_validateAccessToken($accessToken)) {
            throw new LocalizedException(__('The access token is invalid'));
        }

        $this->setAccessToken($accessToken);
    }

    private function _validateAccessToken(Token $token): bool
    {
        return !$token->getRevoked();
    }

    public function setAccessToken(Token $accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    /**
     * @return RefreshToken
     * @throws LocalizedException
     */
    public function getRefreshToken(): RefreshToken
    {
        if (!empty($this->_refreshToken)) {
            return $this->_refreshToken;
        }

        $this->_generateRefreshToken();

        return $this->_refreshToken;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function _generateRefreshToken()
    {
        if (empty($this->_accessToken)
            || !$this->_accessToken->getEntityId()) {
            throw new LocalizedException(__('Access token is not set'));
        }

        $refreshToken = $this->_refreshTokenFactory->create();
        $refreshToken = $refreshToken
            ->generateRefreshToken($this->_accessToken->getCustomerId());

        $this->setRefreshToken($refreshToken);
    }

    /**
     * @param string $token
     * @return void
     * @throws LocalizedException
     */
    public function setRefreshTokenByString(string $token)
    {
        $refreshToken = $this->_refreshTokenFactory->create();
        $refreshToken = $refreshToken->loadByToken($token);

        if (!$refreshToken->getEntityId()
            || !$this->_validateRefreshToken($refreshToken)) {
            throw new LocalizedException(__('The refresh token is invalid'));
        }

        $this->setRefreshToken($refreshToken);
    }

    private function _validateRefreshToken(RefreshToken $refreshToken): bool
    {
        $createdAt = $refreshToken->getCreatedAt();
        $ttlInSeconds = self::REFRESH_TOKEN_TTL_IN_DAYS * 3600 * 24;
        $timeDelta = $this->_date->gmtTimestamp() - $ttlInSeconds;
        $isExpired = $this->_dateTime->strToTime($createdAt) < $timeDelta;

        return !$refreshToken->getIsRevoked() && !$isExpired;
    }

    public function setRefreshToken(RefreshToken $refreshToken)
    {
        $this->_refreshToken = $refreshToken;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function revokePreviousTokens()
    {
        if (empty($this->_accessToken)
            || !$this->_accessToken->getEntityId()) {
            throw new LocalizedException(__('Access token is not set'));
        }

        $this->_revokePreviousAccessTokens();
        $this->_revokePreviousRefreshToken();
    }

    private function _revokePreviousAccessTokens()
    {
        $accessTokensCollection = $this->_tokenCollectionFactory->create();
        $accessTokens = $accessTokensCollection
            ->addFilterByCustomerId($this->_accessToken->getCustomerId())
            ->addFilterByRevoked(false)
            ->addFieldToFilter(
                'token',
                ['neq' => $this->_accessToken->getToken()]
            );

        /** @var Token $accessToken */
        foreach ($accessTokens as $accessToken) {
            $accessToken->setRevoked(true);
            $this->_tokenResource->save($accessToken);
        }
    }

    private function _revokePreviousRefreshToken()
    {
        $refreshTokensCollection = $this->_refreshTokenCollectionFactory->create();
        $refreshTokens = $refreshTokensCollection
            ->addFilterByCustomerId($this->_accessToken->getCustomerId())
            ->addFilterByRevoked(false);

        /** @var RefreshToken $refreshToken */
        foreach ($refreshTokens as $refreshToken) {
            $refreshToken->setIsRevoked(true);
            $this->_refreshTokenResource->save($refreshToken);
        }
    }

    /**
     * @param string $refreshToken
     * @return void
     * @throws LocalizedException
     */
    public function reissueTokens(string $refreshToken)
    {
        $this->setRefreshTokenByString($refreshToken);

        $accessToken = $this->_tokenFactory->create();
        $accessToken = $accessToken
            ->createCustomerToken($this->_refreshToken->getCustomerId());

        $this->setAccessToken($accessToken);
        $this->revokePreviousTokens();

        $this->_generateRefreshToken();
    }
}
