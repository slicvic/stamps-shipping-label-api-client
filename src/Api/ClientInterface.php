<?php

namespace Slicvic\Stamps\Api;

/**
 * Base interface for API clients.
 */
interface ClientInterface
{
    /**
     * @param string $url
     * @return $this
     */
    public function setApiUrl($url);

    /**
     * @return string
     */
    public function getApiUrl();

    /**
     * @param string $integrationId
     * @return $this
     */
    public function setApiIntegrationId($integrationId);

    /**
     * @return string
     */
    public function getApiIntegrationId();

    /**
     * @param string $userId
     * @return $this
     */
    public function setApiUserId($userId);

    /**
     * @return string
     */
    public function getApiUserId();

    /**
     * @param string $password
     * @return $this
     */
    public function setApiPassword($password);

    /**
     * @return string
     */
    public function getApiPassword();
}
