<?php
namespace Stamps;

/**
 * Class to generate stamps.com shipping labels
 * @author Victor Lantigua
 * 
 * Example usage:
 * 
 *		$fromAddress = new Address("axl rose", "2800 Biscayne Blvd STE 200", "", "Miami", "FL", "33137");
 *		$toAddress = new Address("slash", "1419 Westwood Blvd", "", "Los Angeles", "CA", "90210");
 *
 * 		$result = \Stamps\API::factory()
 *			->setFromAddress($fromAddress)
 *			->setToAddress($toAddress)
 *			->setSampleOnly(FALSE)
 *			->saveToPdf(/gvs/sites/blackllama/2/pdfs/shippinglabels/mylabel.pdf);
 * 
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
	 * @param string $address
	 * @param string $city
	 * @param string $state
	 * @param string $zip
	 * @param string $country
	 */
	public function __construct($name, $address1, $address2, $city, $state, $zip, $country = 'US')
	{
		$this->name		= strtoupper($name);
		$this->address1	= strtoupper($address1);
		$this->address2	= strtoupper($address2);
		$this->city		= strtoupper($city);
		$this->state	= strtoupper($state);
		$this->zip		= $zip;
		$this->country	= $country;
	}
}

class ApiClient {
    const API_URL				= 'https://swsim.stamps.com/swsim/swsimv35.asmx?WSDL';
	const API_INTEGRATION_ID	= 'YOUR_API_INTEGRATION_ID';
	const API_USERID			= 'YOUR_API_USERID';
    const API_PASSWORD			= 'YOUR_API_PASSWORD';
	
	const SERVICE_TYPE_PRIORITY = 'US-PM';
	const SERVICE_TYPE_FC		= 'US-FC';

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
	private $_sampleOnly = TRUE;
	
	/**
	 * The image type of shipping label
	 * @var boolean
	 */
	private $_imageType	= 'Png';
    
    /**
     * The package type 
     * @var string
     */
    private $_packageType = 'Thick Envelope';
    
	/**
	 * Return adddress
	 * @var Address 
	 */
	private $_from;

	/**
	 * Destination address
	 * @var Address 
	 */
	private $_to;
	
	/**
	 * The mail service type 
	 * @var string
	 */
	private $_serviceType = 'US-FC';
	
    /**
     * The weight of the package in ounces
     * @var float 
     */
    private $_weightOz = '0.0';
    
    /**
     * This is the date the package will be picked up or officially enter the mail system. 
     * Defaults to the current date('Y-m-d')
     * @var date 
     */
    private $_shipDate = NULL;
    
	/**
	 * SoapClient
	 * 
	 * @var SoapClient 
	 */
	private $_soapClient;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_soapClient = new SoapClient(self::API_URL);
	}
	
	/**
	 * Creates and returns a new instance
	 * @return StampsShippingLabel
	 */
	public static function factory()
	{
		return new ApiClient();
	}
	
	/**
     * Set return address
	 * @param Address $from
	 */
	public function setFromAddress(Address $from)
	{
		$this->_from = $from;
		return $this;
	}
	
	/**
     * Set destination address
	 * @param Address $to
	 */
	public function setToAddress(Address $to)
	{
		$this->_to = $to;
		return $this;
	}
	
	/**
     * Set sample only
	 * @param boolean $isSampleOnly
	 */
	public function setSampleOnly($isSampleOnly)
	{
		$this->_sampleOnly = $isSampleOnly;
		return $this;
	}
    
    /**
     * Set the image type of the shipping label
	 * @param string $type
	 */
	public function setImageType($type)
	{
		$this->_imageType = $type;
		return $this;
	}
    
    /**
     * Set the package type
	 * @param string $type
	 */
	public function setPackageType($type)
	{
		$this->_packageType = $type;
		return $this;
	}
    
    /**
     * Set weight of the package
	 * @param float $ounces the weight in ounces
	 */
	public function setWeightOz($ounces)
	{
		$this->_weightOz = $ounces;
		return $this;
	}
    
    /**
     * Set the date the package will be picked up or officially enter the mail system.
	 * @param date $date
	 */
	public function setShipDate($date)
	{
		$this->_shipDate = $date;
		return $this;
	}

	/**
	 * Saves label to a file
	 * 
	 * @param string $filename the destination path
	 * @return boolean
	 */
	public function save($filename)
	{
		try 
		{
			$result = $this->_doCreateLabelRequest();
			
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
	 * 
	 * @return SoapFault|stdClass
	 */
	private function _doCreateLabelRequest()
	{
		// 1. Check Available Balance
			
		$accountInfoResponse = $this->_soapClient->GetAccountInfo(array(
			'Authenticator' => $this->_doAuthRequest()
		));
		
		/*$purchasePostageResponse = $this->_soapClient->PurchasePostage(array(
			'Authenticator' => $this->_doAuthRequest(),
			'PurchaseAmount' => 20,
			'ControlTotal'	=> $accountInfoResponse->AccountInfo->PostageBalance->
		));*/

		$availableBalance = (double)$accountInfoResponse->AccountInfo->PostageBalance->AvailablePostage;

		if ($availableBalance < 3)
		{
			throw new SoapFault('700', 'Insufficient Funds (Available Balance: ' . $availableBalance . ')');
		}

		// 2. Cleanse Destination Address

		$cleanseToAddressResponse = $this->_soapClient->CleanseAddress(array(
			'Authenticator' => $this->_doAuthRequest(),
			'Address' => array(
				'FullName'	=> $this->_to->name,
				'Address1'	=> $this->_to->address1,
				'Address2'	=> $this->_to->address2,
				'City'		=> $this->_to->city,
				'State'		=> $this->_to->state,
				'ZIPcode'	=> $this->_to->zip
			)	
		));

		if ($cleanseToAddressResponse->CityStateZipOK == FALSE)
		{
			throw new SoapFault('701', 'To address does not appear to be valid.');
		}

		// 3. Get Rates
		
		$rateOptions = array(
			'FromZIPCode'	=> $this->_from->zip,
			'ToZIPCode'		=> $this->_to->zip,
			'WeightOz'		=> $this->_weightOz,
			'WeightLb'		=> '0.0',
			'ShipDate'		=> (empty($this->_shipDate) ? date('Y-m-d') : $this->_shipDate),

			'ServiceType'	=> $this->_serviceType,
			'PackageType'	=> $this->_packageType,
			'InsuredValue'	=> '0.0',
			'AddOns' => array(
				array(
					'AddOnType' => 'SC-A-HP' // Hide price on label
				)
			)
		);
		
		$rates = $this->_soapClient->GetRates(array(
			'Authenticator' => $this->_doAuthRequest(),
			'Rate'			=> $rateOptions
		));
		
		$rateOptions['Rate']['Amount'] = $rates->Rates->Rate->Amount;
		
		// 4. Generate Label
				
		$labelOptions = array(
			'Authenticator'		=> $this->_doAuthRequest(),
			'IntegratorTxID'    => time(),
			'SampleOnly'		=> $this->_sampleOnly,
			'ImageType'			=> $this->_imageType,
			
			'Rate'				=> $rateOptions,

			'From' => array(
				'FullName'		=> $this->_from->name,
				'Address1'		=> $this->_from->address1,
				'Address2'		=> $this->_from->address2,
				'City'			=> $this->_from->city,
				'State'			=> $this->_from->state,
				'ZIPCode'		=> $this->_from->zip
			),
			
			'To' => array(
				'FullName'		=> $this->_to->name,
				'Address1'		=> $this->_to->address1,
				'Address2'		=> $this->_to->address2,
				'City'			=> $this->_to->city,
				'State'			=> $this->_to->state,
				'ZIPCode'		=> $this->_to->zip
			)
		);
		
		$indiciumResponse = $this->_soapClient->CreateIndicium($labelOptions);	
		
		return $indiciumResponse;
	}
	
	/**
	 * Returns auth token for API requests
	 * @return string
	 */
	private function _doAuthRequest()
	{
		$response = $this->_soapClient->AuthenticateUser(array(
			'Credentials' => array(
				'IntegrationID' => self::API_INTEGRATION_ID,
				'Username'		=> self::API_USERID,
				'Password'		=> self::API_PASSWORD
			)
		));

		return $response->Authenticator;	
	}
}
