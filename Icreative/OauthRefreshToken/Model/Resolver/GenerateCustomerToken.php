<?php

namespace Icreative\OauthRefreshToken\Model\Resolver;

use Icreative\OauthRefreshToken\Model\TokenService;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Resolver\GenerateCustomerToken
    as MagentoGenerateCustomerToken;
use Magento\Integration\Api\CustomerTokenServiceInterface;

class GenerateCustomerToken extends MagentoGenerateCustomerToken implements ResolverInterface
{
    private TokenService $_tokenService;

    public function __construct(
        CustomerTokenServiceInterface $customerTokenService,
        TokenService $tokenService
    ) {
        parent::__construct($customerTokenService);

        $this->_tokenService = $tokenService;
    }

    /**
     * @inheirtDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $result = parent::resolve($field, $context, $info, $value, $args);

        $this->_tokenService->setAccessTokenByString($result['token']);
        $this->_tokenService->revokePreviousTokens();

        $result['accessToken'] = $result['token'];
        $result['refreshToken'] = $this->_tokenService->getRefreshToken()
            ->getToken();

        return $result;
    }
}
