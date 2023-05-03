<?php

namespace Icreative\OauthRefreshToken\Model\Resolver;

use Icreative\OauthRefreshToken\Model\TokenService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ReissueCustomerToken implements ResolverInterface
{
    private TokenService $_tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->_tokenService = $tokenService;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['refreshToken'])) {
            throw new GraphQlInputException(
                __('Specify the "refreshToken" value.')
            );
        }

        try {
            $this->_tokenService->reissueTokens($args['refreshToken']);
        } catch (LocalizedException $exception) {
            throw new GraphQlAuthorizationException(
                __('Invalid refresh token')
            );
        }

        return [
            'accessToken' => $this->_tokenService->getAccessToken()
                ->getToken(),
            'refreshToken' => $this->_tokenService->getRefreshToken()
                ->getToken(),
        ];
    }
}
