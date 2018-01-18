<?php

namespace Slicvic\Stamps\Api;

use Exception as ApiException;
use Slicvic\Stamps\Address\AddressInterface;


/**
 * Shipping label API client.
 */
class ShippingLabel extends AbstractClient implements ShippingLabelInterface
{
    const SERVICE_TYPE_PRIORITY = 'US-PM';
    const SERVICE_TYPE_FC       = 'US-FC';

    const IMAGE_TYPE_PNG = 'Png';
    const IMAGE_TYPE_PDF = 'Pdf';

    const PACKAGE_TYPE_LARGE_ENVELOPE_OR_FLAT   = 'Large Envelope or Flat';
    const PACKAGE_TYPE_THICK_ENVELOPE           = 'Thick Envelope';
    const PACKAGE_TYPE_PACKAGE                  = 'Package';
    const PACKAGE_TYPE_FLAT_RATE_BOX            = 'Flat Rate Box';
    const PACKAGE_TYPE_SMALL_FLAT_RATE_BOX      = 'Small Flat Rate Box';
    const PACKAGE_TYPE_LARGE_FLAT_RATE_BOX      = 'Large Flat Rate Box';
    const PACKAGE_TYPE_FLAT_RATE_ENVELOPE       = 'Flat Rate Envelope';
    const PACKAGE_TYPE_LARGE_PACKAGE            = 'Large Package';
    const PACKAGE_TYPE_OVERSIZE_PACKAGE         = 'Oversize Package';

    /**
     * If true, generates a sample label without real value.
     * @var bool
     */
    protected $isSampleOnly = true;

    /**
     * If true, the price will not be printed on the label.
     * @var bool
     */
    protected $showPrice = false;

    /**
     * The weight of the package in ounces.
     * @var float
     */
    protected $weightOz = 0.0;

    /**
     * The file type of shipping label.
     * @var string
     */
    protected $imageType;

    /**
     * The package type.
     * @var string
     */
    protected $packageType;

    /**
     * The mail service type.
     * @var string
     */
    protected $serviceType;

    /**
     * The sender's adddress.
     * @var AddressInterface
     */
    protected $from;

    /**
     * The recipient's address.
     * @var AddressInterface
     */
    protected $to;

    /**
     * This is the date the package will be picked up or officially enter the mail system.
     * Defaults to the current date('Y-m-d').
     * @var string
     */
    protected $shipDate;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->imageType = self::IMAGE_TYPE_PNG;
        $this->packageType = self::PACKAGE_TYPE_THICK_ENVELOPE;
        $this->serviceType = self::SERVICE_TYPE_FC;
        $this->shipDate = date('Y-m-d');
    }

    /**
     * {@inheritdoc}
     */
    public function setFrom(AddressInterface $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * {@inheritdoc}
     */
    public function setTo(AddressInterface $to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * {@inheritdoc}
     */
    public function setIsSampleOnly($flag)
    {
        $this->isSampleOnly = (bool) $flag;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsSampleOnly()
    {
        return $this->isSampleOnly;
    }

    /**
     * {@inheritdoc}
     */
    public function setImageType($type)
    {
        $this->imageType = (string) $type;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageType()
    {
        return $this->imageType;
    }

    /**
     * {@inheritdoc}
     */
    public function setPackageType($type)
    {
        $this->packageType = (string) $type;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageType()
    {
        return $this->packageType;
    }

    /**
     * {@inheritdoc}
     */
    public function setServiceType($type)
    {
        $this->serviceType = (string) $type;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceType()
    {
        return $this->serviceType;
    }

    /**
     * {@inheritdoc}
     */
    public function setWeightOz($weight)
    {
        $this->weightOz = (float) $weight;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWeightOz()
    {
        return $this->weightOz;
    }

    /**
     * {@inheritdoc}
     */
    public function setShipDate($date)
    {
        $this->shipDate = date('Y-m-d', strtotime($date));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShipDate()
    {
        return $this->shipDate;
    }

    /**
     * {@inheritdoc}
     */
    public function setShowPrice($flag)
    {
        $this->showPrice = (bool) $flag;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShowPrice()
    {
        return $this->showPrice;
    }

    /**
     * {@inheritdoc}
     */
    public function create($filename = null)
    {
        // 1. Check account balance

        $accountInfoResponse = $this->soapClient->GetAccountInfo([
            'Authenticator' => $this->getAuthToken()
        ]);

        $availableBalance = (double) $accountInfoResponse->AccountInfo->PostageBalance->AvailablePostage;

        if ($availableBalance < 3) {
            throw new ApiException('Insufficient funds: ' . $availableBalance);
        }

        // 2. Cleanse recipient address

        $cleanseToAddressResponse = $this->soapClient->CleanseAddress([
            'Authenticator' => $this->getAuthToken(),
            'Address' => [
                'FullName' => $this->to->getFullname(),
                'Address1' => $this->to->getAddress1(),
                'Address2' => $this->to->getAddress2(),
                'City'     => $this->to->getCity(),
                'State'    => $this->to->getState(),
                'ZIPcode'  => $this->to->getZipcode()
            ]
        ]);

        if (!$cleanseToAddressResponse->CityStateZipOK) {
            throw new ApiException('Invalid to address.');
        }

        // 3. Get rates

        $rateOptions = [
            'FromZIPCode'  => $this->from->getZipcode(),
            'ToZIPCode'    => $this->to->getZipcode(),
            'WeightOz'     => $this->weightOz,
            'WeightLb'     => '0.0',
            'ShipDate'     => $this->shipDate,

            'ServiceType'  => $this->serviceType,
            'PackageType'  => $this->packageType,
            'InsuredValue' => '0.0',
            'AddOns'       => []
        ];

        if (!$this->showPrice) {
            $rateOptions['AddOns'][] = [
                'AddOnType' => 'SC-A-HP' // Hide price on label
            ];
        }

        $rates = $this->soapClient->GetRates([
            'Authenticator' => $this->getAuthToken(),
            'Rate'          => $rateOptions
        ]);

        $rateOptions['Rate']['Amount'] = $rates->Rates->Rate->Amount;

        // 4. Generate label

        $labelOptions = [
            'Authenticator'     => $this->getAuthToken(),
            'IntegratorTxID'    => time(),
            'SampleOnly'        => $this->isSampleOnly,
            'ImageType'         => $this->imageType,

            'Rate'              => $rateOptions,

            'From' => [
                'FullName'      => $this->from->getFullname(),
                'Address1'      => $this->from->getAddress1(),
                'Address2'      => $this->from->getAddress2(),
                'City'          => $this->from->getCity(),
                'State'         => $this->from->getState(),
                'ZIPCode'       => $this->from->getZipcode()
            ],

            'To' => [
                'FullName'      => $this->to->getFullname(),
                'Address1'      => $this->to->getAddress1(),
                'Address2'      => $this->to->getAddress2(),
                'City'          => $this->to->getCity(),
                'State'         => $this->to->getState(),
                'ZIPCode'       => $this->to->getZipcode()
            ]
        ];

        $indiciumResponse = $this->soapClient->CreateIndicium($labelOptions);

        if ($filename) {
            $ch = curl_init($indiciumResponse->URL);
            $fp = fopen($filename, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        return $indiciumResponse->URL;
    }
}
