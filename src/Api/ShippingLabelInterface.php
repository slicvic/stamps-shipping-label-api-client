<?php

namespace Slicvic\Stamps\Api;

use Slicvic\Stamps\Address\AddressInterface;

/**
 * Client interface to generate shipping labels.
 */
interface ShippingLabelInterface
{
    /**
     * Generates shipping label and optionally saves to file.
     *
     * @param string $filename
     * @return string The URL to the generated label.
     * @throws \Exception
     */
    public function create($filename = null);

    /**
     * @param AddressInterface $from
     * @return $this
     */
    public function setFrom(AddressInterface $from);

    /**
     * @return AddressInterface
     */
    public function getFrom();

    /**
     * @param AddressInterface $to
     * @return $this
     */
    public function setTo(AddressInterface $to);

    /**
     * @return AddressInterface
     */
    public function getTo();

    /**
     * @param bool $flag
     * @return $this
     */
    public function setIsSampleOnly($flag);

    /**
     * @return bool
     */
    public function getIsSampleOnly();

    /**
     * @param string $type
     * @return $this
     */
    public function setImageType($type);

    /**
     * @return string
     */
    public function getImageType();

    /**
     * @param string $type
     * @return $this
     */
    public function setPackageType($type);

    /**
     * @return string
     */
    public function getPackageType();

    /**
     * @param string $type
     * @return $this
     */
    public function setServiceType($type);

    /**
     * @return string
     */
    public function getServiceType();

    /**
     * @param float $weight
     * @return $this
     */
    public function setWeightOz($weight);

    /**
     * @return float
     */
    public function getWeightOz();

    /**
     * @param string $date
     * @return $this
     */
    public function setShipDate($date);

    /**
     * @return string
     */
    public function getShipDate();

    /**
     * @param bool $flag
     * @return $this
     */
    public function setShowPrice($flag);

    /**
     * @return bool
     */
    public function getShowPrice();
}
