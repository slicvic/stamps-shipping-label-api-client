<?php

namespace Slicvic\Stamps\Address;

/**
 * Class to represent a mailing address for a shipping label.
 */
class Address implements AddressInterface
{
    /**
     * Recipient's or sender's name.
     * @var string
     */
    protected $fullname;

    /**
     * @var string
     */
    protected $address1;

    /**
     * @var string
     */
    protected $address2;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $zipcode;

    /**
     * @var string
     */
    protected $country = 'US';

    /**
     * {@inheritdoc}
     */
    public function setFullname($fullname)
    {
        $this->fullname = (string) $fullname;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * {@inheritdoc}
     */
    public function setAddress1($address1)
    {
        $this->address1 = (string) $address1;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * {@inheritdoc}
     */
    public function setAddress2($address2)
    {
        $this->address2 = (string) $address2;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * {@inheritdoc}
     */
    public function setCity($city)
    {
        $this->city = (string) $city;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * {@inheritdoc}
     */
    public function setState($state)
    {
        $this->state = (string) $state;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = (string) $zipcode;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * {@inheritdoc}
     */
    public function setCountry($country = 'US')
    {
        $this->country = (string) $country;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountry()
    {
        return $this->country;
    }
}
