<?php

namespace LeviZoesch\TellerSDK;

use LeviZoesch\TellerSDK\Enums\EnvironmentTypes;
use LeviZoesch\TellerSDK\Exceptions\MissingAccessTokenException;
use LeviZoesch\TellerSDK\Exceptions\MissingTellerConfigurationException;

class TellerClient
{
    private string $BASE_URL = 'https://api.teller.io';

    private string $access_token;

    /**
     * @throws MissingAccessTokenException
     */
    public function __construct($accessToken)
    {
        if ($accessToken === null){
            throw new MissingAccessTokenException();
        }
        $this->access_token = $accessToken;
    }

    public function listAccounts()
    {
        return $this->get('/accounts');
    }

    public function accountsCount(): int
    {
        return count($this->listAccounts());
    }

    public function getAccountDetails($accountId)
    {
        return $this->get("/accounts/{$accountId}/details");
    }

    public function getAccountBalances($accountId)
    {
        return $this->get("/accounts/{$accountId}/balances");
    }

    public function listAccountTransactions($accountId)
    {
        return $this->get("/accounts/{$accountId}/transactions");
    }

    public function getTransactionDetails($accountId, $transactionId)
    {
        return $this->get("/accounts/{$accountId}/transactions/{$transactionId}");
    }

    public function destroyAccount($accountId)
    {
        return $this->destroy("/accounts/" . $accountId);
    }

    public function listAccountPayees($accountId, $scheme)
    {
        return $this->get("/accounts/{$accountId}/payments/{$scheme}/payees");
    }

    public function createAccountPayee($accountId, $data)
    {
        return $this->post("/accounts/{$accountId}/payees", $data);
    }

    public function createAccountPayment($accountId, $scheme, $data)
    {
        return $this->post("/accounts/{$accountId}/payments/{$scheme}", $data);
    }

    public function listIdentity()
    {
        return $this->get('/identity');
    }

    public function get($path)
    {
        return json_decode($this->request('GET', $path), false, 512, JSON_THROW_ON_ERROR);
    }

    private function post($path, $data)
    {
        return json_decode($this->request('POST', $path, $data), false, 512, JSON_THROW_ON_ERROR);
    }

    private function destroy($path)
    {
        return json_decode($this->request('DELETE', $path), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws MissingTellerConfigurationException
     */
    private function request($method, $path, $data = null): bool|string
    {

        $configFilePath = config_path('teller.php');

        if (!file_exists($configFilePath)) {
            throw new MissingTellerConfigurationException();
        }
        $url = $this->BASE_URL . $path;
        $accessToken = base64_encode($this->access_token .':');

        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . $accessToken
        ];

        $tellerEnvironment = config('teller.ENVIRONMENT');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ]);

        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_THROW_ON_ERROR));
        }

        if ($tellerEnvironment === EnvironmentTypes::PRODUCTION || $tellerEnvironment === EnvironmentTypes::DEVELOPMENT) {
            curl_setopt($curl, CURLOPT_SSLCERT, config('teller.CERT_PATH'));
            curl_setopt($curl, CURLOPT_SSLKEY, config('teller.KEY_PATH'));
        }

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Get the HTTP status code
        $error = curl_error($curl);

        curl_close($curl);

        if ($statusCode === 200) {
            return $response;
        } else {
            $errorObj = json_decode($response, true);
            if ($errorObj && isset($errorObj['error'])) {
                $errorCode = $errorObj['error']['code'];
                $errorMessage = $errorObj['error']['message'];
                return "Error (HTTP $statusCode): $errorCode - $errorMessage";
            } else {
                return "Error (HTTP $statusCode): $error";
            }
        }
    }

}
