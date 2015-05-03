<?php

namespace Stamps;

use Address;

/**
 * ApiClient
 *
 * @author Victor Lantigua <vmlantigua@gmail.com>
 *
 * Example:
 *      $fromAddress = new \Stamps\Address("Lebron James", "1 Center Court", "", "Cleveland", "OH", "44115");
 *      $toAddress = new \Stamps\Address("Dwayne Wade", "601 Biscayne Boulevard", "", "Miami", "FL", "33132");
 *
 *      $result = \Stamps\ApiClient::factory()
 *          ->setFromAddress($fromAddress)
 *          ->setToAddress($toAddress)
 *          ->setSampleOnly(FALSE)
 *          ->saveToPdf(/Users/slicvic/Development/sample-label.pdf);
 */
class ApiClient {

    const API_URL               = 'https://swsim.stamps.com/swsim/swsimv35.asmx?WSDL';
    const API_INTEGRATION_ID    = 'YOUR_API_INTEGRATION_ID';
    const API_USERID            = 'YOUR_API_USERID';
    const API_PASSWORD          = 'YOUR_API_PASSWORD';

    const SERVICE_TYPE_PRIORITY = 'US-PM';
    const SERVICE_TYPE_FC       = 'US-FC';

    const IMAGE_TYPE_PDF = 'Png';
    const IMAGE_TYPE_PNG = 'Pdf';

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
     * @var boolean
     */
    private $sampleOnly = TRUE;

    /**
     * The image type of shipping label.
     * @var boolean
     */
    private $imageType = 'Png';

    /**
     * The package type.
     * @var string
     */
    private $packageType = 'Thick Envelope';

    /**
     * The return adddress.
     * @var Address
     */
    private $from;

    /**
     * The destination address.
     * @var Address
     */
    private $to;

    /**
     * The mail service type.
     * @var string
     */
    private $serviceType = 'US-FC';

    /**
     * The weight of the package in ounces.
     * @var float
     */
    private $weightOz = '0.0';

    /**
     * This is the date the package will be picked up or officially enter the mail system.
     * Defaults to the current date('Y-m-d').
     * @var string
     */
    private $shipDate = NULL;

    /**
     * SoapClient
     * @var SoapClient
     */
    private $soapClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->soapClient = new SoapClient(self::API_URL);
    }

    /**
     * Creates and returns a new instance.
     *
     * @return ApiClient
     */
    public static function factory()
    {
        return new ApiClient();
    }

    /**
     * Sets return address.
     *
     * @param  Address $from
     * @return $this
     */
    public function setFromAddress(Address $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Sets destination address.
     *
     * @param  Address
     * @return $this
     */
    public function setToAddress(Address $to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Sets sample only flag.
     *
     * @param  bool  $isSampleOnly
     * @return $this
     */
    public function setSampleOnly($isSampleOnly)
    {
        $this->sampleOnly = $isSampleOnly;
        return $this;
    }

    /**
     * Sets the image type of the shipping label.
     *
     * @param  string  $type
     * @return $this
     */
    public function setImageType($type)
    {
        $this->imageType = $type;
        return $this;
    }

    /**
     * Sets the package type.
     *
     * @param  string  $type
     * @return $this
     */
    public function setPackageType($type)
    {
        $this->packageType = $type;
        return $this;
    }

    /**
     * Sets weight of the package.
     *
     * @param  float $ounces  The weight in ounces
     * @return $this
     */
    public function setWeightOz($ounces)
    {
        $this->weightOz = $ounces;
        return $this;
    }

    /**
     * Sets the date the package will be picked up or officially enter the mail system.
     *
     * @param  date  $date
     * @return $this
     */
    public function setShipDate($date)
    {
        $this->shipDate = $date;
        return $this;
    }

    /**
     * Saves label to a file.
     *
     * @param  string  $filename  The destination path
     * @return bool
     */
    public function save($filename)
    {
        try
        {
            $result = $this->doCreateLabelRequest();

            $ch = curl_init($result->URL);
            $fp = fopen($filename, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            return $result->URL;
        }
        catch (SoapFault $ex)
        {
            var_dump($ex);
            return FALSE;
        }
    }

    /**
     * Performs requests to create label.
     *
     * @return SoapFault|stdClass
     */
    private function doCreateLabelRequest()
    {
        // 1. Check Available Balance

        $accountInfoResponse = $this->soapClient->GetAccountInfo(array(
            'Authenticator' => $this->doAuthRequest()
        ));

        /*
        $purchasePostageResponse = $this->soapClient->PurchasePostage(array(
            'Authenticator' => $this->doAuthRequest(),
            'PurchaseAmount' => 20,
            'ControlTotal'  => $accountInfoResponse->AccountInfo->PostageBalance->
        ));
        */

        $availableBalance = (double)$accountInfoResponse->AccountInfo->PostageBalance->AvailablePostage;

        if ($availableBalance < 3)
        {
            throw new SoapFault('700', 'Insufficient Funds (Available Balance: ' . $availableBalance . ')');
        }

        // 2. Cleanse Destination Address

        $cleanseToAddressResponse = $this->soapClient->CleanseAddress(array(
            'Authenticator' => $this->doAuthRequest(),
            'Address' => array(
                'FullName'  => $this->to->name,
                'Address1'  => $this->to->address1,
                'Address2'  => $this->to->address2,
                'City'      => $this->to->city,
                'State'     => $this->to->state,
                'ZIPcode'   => $this->to->zip
            )
        ));

        if ($cleanseToAddressResponse->CityStateZipOK == FALSE)
        {
            throw new SoapFault('701', 'To address does not appear to be valid.');
        }

        // 3. Get Rates

        $rateOptions = array(
            'FromZIPCode'       => $this->from->zip,
            'ToZIPCode'         => $this->to->zip,
            'WeightOz'          => $this->weightOz,
            'WeightLb'          => '0.0',
            'ShipDate'          => (empty($this->shipDate) ? date('Y-m-d') : $this->shipDate),

            'ServiceType'       => $this->serviceType,
            'PackageType'       => $this->packageType,
            'InsuredValue'      => '0.0',
            'AddOns' => array(
                array(
                    'AddOnType' => 'SC-A-HP' // Hide price on label
                )
            )
        );

        $rates = $this->soapClient->GetRates(array(
            'Authenticator' => $this->doAuthRequest(),
            'Rate'          => $rateOptions
        ));

        $rateOptions['Rate']['Amount'] = $rates->Rates->Rate->Amount;

        // 4. Generate Label

        $labelOptions = array(
            'Authenticator'     => $this->doAuthRequest(),
            'IntegratorTxID'    => time(),
            'SampleOnly'        => $this->sampleOnly,
            'ImageType'         => $this->imageType,

            'Rate'              => $rateOptions,

            'From' => array(
                'FullName'      => $this->from->name,
                'Address1'      => $this->from->address1,
                'Address2'      => $this->from->address2,
                'City'          => $this->from->city,
                'State'         => $this->from->state,
                'ZIPCode'       => $this->from->zip
            ),

            'To' => array(
                'FullName'      => $this->to->name,
                'Address1'      => $this->to->address1,
                'Address2'      => $this->to->address2,
                'City'          => $this->to->city,
                'State'         => $this->to->state,
                'ZIPCode'       => $this->to->zip
            )
        );

        $indiciumResponse = $this->soapClient->CreateIndicium($labelOptions);

        return $indiciumResponse;
    }

    /**
     * Gets the auth token for API requests.
     *
     * @return string
     */
    private function doAuthRequest()
    {
        $response = $this->soapClient->AuthenticateUser(array(
            'Credentials' => array(
                'IntegrationID' => self::API_INTEGRATION_ID,
                'Username'      => self::API_USERID,
                'Password'      => self::API_PASSWORD
            )
        ));

        return $response->Authenticator;
    }
}
