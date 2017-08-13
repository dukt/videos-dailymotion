<?php

namespace dukt\videos\dailymotion\oauth2\client\provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Dailymotion extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public function getBaseAuthorizationUrl()
    {
        return 'https://www.dailymotion.com/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://api.dailymotion.com/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.dailymotion.com/me/?fields=id,screenname,email';
    }

    protected function getDefaultScopes()
    {
        return [
            'email',
            'userinfo',
        ];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $code  = 0;
            $error = $data['error'];

            if (is_array($error)) {
                $code  = $error['code'];
                $error = $error['message'];
            }

            throw new IdentityProviderException($error, $code, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new DailymotionResourceOwner($response);
    }
}