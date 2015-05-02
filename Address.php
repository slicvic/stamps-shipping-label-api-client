<?php

namespace Stamps;

/**
 * Address
 *
 * @author Victor Lantigua <vmlantigua@gmail.com>
 */
class Address
{
    public $name;
    public $address1;
    public $address2;
    public $city;
    public $state;
    public $zip;
    public $country;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $address1
     * @param string $address2
     * @param string $city
     * @param string $state
     * @param string $zip
     * @param string $country
     */
    public function __construct($name, $address1, $address2, $city, $state, $zip, $country = 'US')
    {
        $this->name     = strtoupper($name);
        $this->address1 = strtoupper($address1);
        $this->address2 = strtoupper($address2);
        $this->city     = strtoupper($city);
        $this->state    = strtoupper($state);
        $this->zip      = $zip;
        $this->country  = $country;
    }
}
