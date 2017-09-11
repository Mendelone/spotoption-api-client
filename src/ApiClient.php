<?php

namespace SpotOption;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp;
use SpotOption\Entities\Campaign;
use SpotOption\Requests\AddCustomerRequest;
use SpotOption\Responses\AddCustomerResponse;
use SpotOption\Responses\GetCampaignsResponse;
use SpotOption\Responses\GetCountriesResponse;
use SpotOption\Responses\ValidateCustomerResponse;

class ApiClient implements LoggerAwareInterface
{
    protected $url;

    protected $username;

    protected $password;

    /**
     * @var \GuzzleHttp\ClientInterface A Guzzle HTTP client.
     */
    protected $httpClient;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * ApiClient constructor.
     *
     * @param string $url SpotOption API endpoint.
     * @param string $username
     * @param string $password
     * @param mixed  $options
     */
    public function __construct($url, $username, $password, $options = [])
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;

        if (isset($options['httpClient']) && $options['httpClient'] instanceof GuzzleHttp\ClientInterface) {
            $this->httpClient = $options['httpClient'];
        }
    }


    /**
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return GetCountriesResponse
     */
    public function getCountries()
    {
        $data = [
            'MODULE'  => 'Country',
            'COMMAND' => 'view',
        ];

        $payload = new Payload($this->request($data));

        return new GetCountriesResponse($payload);
    }

    /**
     * @param string $type
     *
     * @return GetCampaignsResponse
     */
    public function getCampaigns($type = Campaign::TYPE_CPA)
    {
        $data = [
            'MODULE'  => 'Campaign',
            'COMMAND' => 'view',
            'FILTER' => [
                'type' => $type,
            ]
        ];

        $payload = new Payload($this->request($data));

        $response = new GetCampaignsResponse($payload);

        return $response;
    }

    public function addCustomer(AddCustomerRequest $request)
    {
        $data = [
            'MODULE'        => 'Customer',
            'COMMAND'       => 'add',
            'FirstName' => $request->getFirstName(),
            'LastName' => $request->getLastName(),
            'gender' => $request->getGender(),
            'email' => $request->getEmail(),
            'Phone' => $request->getPhone(),
            'Country' => $request->getCountry(),
            'password' => $request->getPassword(),
            'currency' => $request->getCurrency(),
            'campaignId' => $request->getCampaignId(),
            'subCampaign' => $request->getSubCampaign(),
            'subCampaignId' => $request->getSubCampaignId(),
            'birthday' => $request->getBirthday(),
            'referLink' => $request->getReferLink(),
            'a_aid' => $request->getAAid(),
            'a_bid' => $request->getABid(),
            'a_cid' => $request->getACid(),
            'regIP' => $request->getRegistrationIpAddress(),
        ];

        if ($request->getRegulateStatus() !== null) {
            $data['regulateStatus'] = $request->getRegulateStatus();
        }

        if ($request->getRegulateType() !== null) {
            $data['regulateType'] = $request->getRegulateType();
        }

        $payload = new Payload($this->request($data));

        return new AddCustomerResponse($payload);
    }

    public function validateCustomer($email, $password)
    {
        $data = [
            'MODULE'        => 'Customer',
            'COMMAND'       => 'validate',
            'FILTER' => [
                'email'     => $email,
                'password'  => $password,
            ]
        ];

        $payload = new Payload($this->request($data));

        return new ValidateCustomerResponse($payload);
    }

    /**
     * Adds API credentials to request data
     *
     * @param $data
     */
    protected function sign(&$data)
    {
        $data['api_username'] = $this->username;
        $data['api_password'] = $this->password;
    }

    /**
     * Sends request to SpotOption API endpoint.
     *
     * @param array  $data
     *
     * @return string
     */
    protected function request($data = [])
    {
        $url = rtrim($this->url, '?');
        $this->sign($data);
        try {
            return (string) $this->getHttpClient()->post($url, [
                GuzzleHttp\RequestOptions::FORM_PARAMS => $data,
                GuzzleHttp\RequestOptions::HEADERS => [
                    'User-Agent' => 'ResNext / SpotOption API Client',
                ]
            ])->getBody();
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            return new ClientException($e->getMessage());
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return (string) $e->getResponse()->getBody();
        } catch (GuzzleHttp\Exception\ServerException $e) {
            return (string) $e->getResponse()->getBody();
        }
    }

    <?php
use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

// installs global error and exception handlers
Rollbar::init(
    array(
        'access_token' => '3bd9df11f7b14c6992d44e39007b3daf',
        'environment' => 'development'
    )
);

// Message at level 'info'
Rollbar::log(Level::info(), 'testing 123');

// Catch an exception and send it to Rollbar
try {
    throw new \Exception('test exception');
} catch (\Exception $e) {
    Rollbar::log(Level::error(), $e);
}

// Will also be reported by the exception handler
throw new Exception('test 2');

?>

    /**
     * This method should be used instead direct access to property $httpClient
     *
     * @return \GuzzleHttp\ClientInterface|GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if (!is_null($this->httpClient)) {
            return $this->httpClient;
        }
        $stack = GuzzleHttp\HandlerStack::create();
        if ($this->logger instanceof LoggerInterface) {
            $stack->push(GuzzleHttp\Middleware::log(
                $this->logger,
                new GuzzleHttp\MessageFormatter(GuzzleHttp\MessageFormatter::DEBUG)
            ));
        }
        $this->httpClient = new GuzzleHttp\Client([
            'handler' => $stack,
        ]);
        return $this->httpClient;
    }
}
