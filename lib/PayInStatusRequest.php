<?php
namespace AlgorithmicCash;

use AlgorithmicCash\PayInResponse;
use AlgorithmicCash\SignHelper;
use AlgorithmicCash\PaymentUrl;
use Web3\Utils;

class PayInStatusRequest {
    private $privateKey = "";
    private $rpcUrl = "";
    private $signHelper;

    private $merchantId = "";
    private $customerEmail = "";

    private $customerPhoneNumber = "";

    private $request = [
        'merchant_tx_id'=> "",
        'customerEmailHash' => "",

        'amount' => "0",
        'request_amount' => "0",

        'traderAddress'=> "",
        // Automatic generation
        'timestamp'=> "",

        // to be removed
        'return_url' => "",

        'support_url' => "",
        'ipn_url'=> "",
        'pm_callback_url'=> "",
        'success_url' => "",
        'failure_url' => "",

        // Signature on request
        'signature'=> "",
    ];

    private $payInUrl = "";

    public function __construct(string $merchantId, string $privateKey, string $rpcUrl = "") {
        $this->merchantId = $merchantId;
        $this->privateKey = $privateKey;
        $this->rpcUrl = $rpcUrl;
        $this->signHelper = new SignHelper($privateKey, $rpcUrl);
    }

    public function getRequestVars() : array {
        if (!$this->request['timestamp']) {
            $this->request['timestamp'] = time();
        }
        $this->request['customerEmailHash'] = "0x".hash('sha256', $this->merchantId."::".$this->customerEmail);

        $paramsHash = $this->signHelper->hashParams([
            $this->request['customerEmailHash'],
            $this->request['amount'],
            $this->request['merchant_tx_id'],
        ]);

        $paramsSignatureHash = $this->signHelper->hashParams([$this->merchantId, $paramsHash]);
        $paramsSignature = $this->signHelper->generateSignature($paramsSignatureHash);
        
        $this->request['signature'] = $paramsSignature;

        return $this->request;
    }

    public function getRequestSignature() : string {
        $request = $this->getRequestVars();

        $requestHash = "algorithmic-" . $this->signHelper->hashParams([
            $request['merchant_tx_id'],
            $request['timestamp'],
        ]);

        return $this->signHelper->generateSignature($requestHash);
    }

    public function send(): PayInStatusResponse {
        $request = $this->getRequestVars();
        $requestSignature = $this->getRequestSignature();
        $result = json_encode([
            'response' => PaymentResult::FAIL,
            'error' => 'Unknown error',
        ]);


        $client = PayHTTPClient::getClient();
        try {
            $response = $client->request(
                'GET',
                PaymentUrl::buildPayInStatusUrl([
                    'merchant_id' => $this->merchantId
                ]),
                [
//                    'verify' => false,
//                    'timeout' => 15,
                    'headers' => [
                        'x-signature' => $requestSignature,
                        'content-type' => 'application/json',
                    ],
                    'body' => json_encode(['merchant_tx_id' => $request['merchant_tx_id'], 'timestamp' => $request['timestamp']])
                ]
            );

            if ($response->getStatusCode() != 200) {
                $result = json_encode([
                    'response' => PaymentResult::FAIL,
                    'error' => 'Status code invalid: ' . $response->getStatusCode(),
                ]);
            } else {
                $responseData = $response->getBody()->getContents();
                if (empty($responseData)) {
                    $result = json_encode([
                        'response' => PaymentResult::FAIL,
                        'error' => 'Empty reponse received from server',
                    ]);
                }
                else {
                    $responseJson = @json_decode($responseData);
                    if (is_null($responseJson)) {
                        $result = json_encode([
                            'response' => PaymentResult::FAIL,
                            'error' => 'Response not json: ' . $responseData,
                        ]);
                    } else {
                        $result = $responseData;
                    }
                }
            }
        } catch (\Exception $ex) {
            $result = json_encode([
                'response' => PaymentResult::FAIL,
                'error' => $ex->getMessage(),
            ]);
        }

        return new PayInStatusResponse($result);
    }

    public function setMerchantTxId(string $txId) : PayInStatusRequest {
        $this->request['merchant_tx_id'] = $txId;
        return $this;
    }

    public function setCustomerEmail(string $email): PayInStatusRequest {
        $this->customerEmail = $email;
        return $this;
    }

    public function setCustomerPhoneNumber(string $phone_number): PayInStatusRequest {
        $this->customerPhoneNumber = $phone_number;
        return $this;
    }

    public function setAmount(string $amount) : PayInStatusRequest {
        $this->request['request_amount'] = (string) $amount;
        $this->request['amount'] = Utils::toWei($amount, 'ether')->toString();
        return $this;
    }

    public function setSupportUrl(string $url) : PayInStatusRequest {
        $this->request['support_url'] = $url;
        return $this;
    }

    public function setSuccessUrl(string $url) : PayInStatusRequest {
        $this->request['success_url'] = $url;
        return $this;
    }

    public function setFailureUrl(string $url) : PayInStatusRequest {
        $this->request['failure_url'] = $url;
        return $this;
    }

    public function setHandlerUrl(string $url) : PayInStatusRequest {
        $this->request['ipn_url'] = $url;
        return $this;
    }

    //@TODO: Move to a decorator to not introduce many changes compared to upstream
    public function setHandlerPmUrl(string $url): PayInStatusRequest
    {
        $this->request['pm_callback_url'] = $url;
        return $this;
    }

}
