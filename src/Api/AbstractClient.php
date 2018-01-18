<?php

namespace Slicvic\Stamps\Api;

use \SoapClient;

/**
 * Base class for any API client.
 */
abstract class AbstractClient implements ClientInterface
{
    /**
     * @var string
     */
    protected $apiUrl = 'https://swsim.stamps.com/swsim/swsimv35.asmx?WSDL';

    /**
     * @var string
     */
    protected $apiIntegrationId;

    /**
     * @var string
     */
    protected $apiUserId;

    /**
     * @var string
     */
    protected $apiPassword;

    /**
     * @var SoapClient
     */
    protected $soapClient;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setApiUrl($this->url);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function setApiUrl($url)
    {
        try {
            $this->soapClient = new SoapClient($this->apiUrl, [
                'exceptions' => true
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function setApiIntegrationId($integrationId)
    {
        $this->apiIntegrationId = (string) $integrationId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiIntegrationId()
    {
        return $this->apiIntegrationId;
    }

    /**
     * {@inheritdoc}
     */
    public function setApiUserId($userId)
    {
        $this->apiUserId = (string) $userId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiUserId()
    {
        return $this->apiUserId;
    }

    /**
     * {@inheritdoc}
     */
    public function setApiPassword($password)
    {
        $this->apiPassword = (string) $password;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * Gets the auth token for API requests.
     *
     * @return string
     */
    protected function getAuthToken()
    {
        $response = $this->soapClient->AuthenticateUser([
            'Credentials' => [
                'IntegrationID' => $this->apiIntegrationId,
                'Username'      => $this->apiUserId,
                'Password'      => $this->apiPassword
            ]
        ]);

        return $response->Authenticator;
    }
}
