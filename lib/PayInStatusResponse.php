<?php
namespace AlgorithmicCash;

class PayInStatusResponse {
    public $responseData;
    public $response;

    public function __construct($responseData) {
        $this->responseData = $responseData;
        $this->response = @json_decode($responseData, true);
    }

    public function getResponse() : string {
        return $this->getParam('response');
    }

    public function getResult() {
        return $this->getParam('result');
    }


    public function getParam($name) {
        if (!is_array($this->response) || !isset($this->response[$name])) {
            return "";
        }
        return $this->response[$name];
    }

    public function getParams() {
        return $this->response;
    }

    public function getParamsKeys() {
        return array_keys($this->response);
    }


    public function getError() {
        return $this->getParam('error');
    }

    public function getErrorCode() {
        return $this->getParam('error_code');
    }


    public function getRedirectUrl() {
        return $this->getParam('redirect_url');
    }

    /**
    * @codeCoverageIgnore
    */
    public function send($response_override = null)
    {
        header('Content-Type: application/json');
        $response = (isset($response_override) && !empty($response_override)) ? $response_override : $this->response;
//        error_log("Response: " . json_encode($response));
        echo json_encode($response);
    }
}