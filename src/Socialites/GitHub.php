<?php

namespace Pletfix\OAuth\Socialites;

use Pletfix\OAuth\Services\AbstractOAuth2;

class GitHub extends AbstractOAuth2
{
    /**
     * Get the full URL to redirect to the login screen on the OAuth provider.
     *
     * On this URL, the user can login their account and authorize your app to access their data.
     *
     * @param string $state Random string
     * @return string
     */
    protected function loginScreenURL($state)
    {
        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id'     => $this->config['client_id'],
            'redirect_uri'  => $this->config['redirect_to'],
            'state'         => $state,
            'scope'         => 'user:email',
            'response_type' => 'code',
        ]);
    }

    /**
     * Exchange the auth code for a a bearer access token.
     *
     * The method is called once the user has authorized the app.
     *
     * @param string $state Random string, originally passed to the authorize URL
     * @param string $code Auth code
     * @return string Access token
     */
    protected function exchangeAuthCodeForAccessToken($state, $code)
    {
        $token = $this->send('https://github.com/login/oauth/access_token', [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'  => $this->config['redirect_to'],
            'state'         => $state,
            'code'          => $code,
        ]);

        if (!isset($token->access_token) || !isset($token->scope) || !in_array('user:email', explode(',', $token->scope))) {
            abort(HTTP_STATUS_FORBIDDEN);
        }

        return $token->access_token;
    }

    /**
     * @inheritdoc
     */
    public function getAccount()
    {
        # fetch user information
        $account = $this->send('https://api.github.com/user');

        if (!isset($account->id)) {
            abort(HTTP_STATUS_FORBIDDEN);
        }

        return [
            'id'    => $account->id,
            'name'  => $account->name,
            'email' => isset($account->email) ? $account->email : null,
        ];
    }
}