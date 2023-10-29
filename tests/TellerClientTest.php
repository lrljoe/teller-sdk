<?php

namespace TellerSDK\Tests;
use TellerSDK\Exceptions\MissingAccessTokenException;
use TellerSDK\Exceptions\MissingTellerConfigurationException;
use TellerSDK\TellerClient;

class TellerClientTest extends BaseTest
{

   /**
    * @throws MissingAccessTokenException
    */
   public function testListAccounts()
   {
       $token = config('teller.TEST_TOKEN');
       $teller = new TellerClient($token);
       $results = $teller->listAccounts();
       $this->assertIsArray($results);
   }

   /**
    * @throws MissingAccessTokenException
    */
    public function testListAccountsGetEnrollmentId()
    {
        $token = config('teller.TEST_TOKEN');
        $teller = new TellerClient($token);
        $results = $teller->listAccounts();
        foreach ($results as $result)
        {
          $this->assertIsString($result['enrollment_id']);
        }
    }
 
    public function testTellerTestTokenIsDefined()
    {
        $token = getenv('TELLER_TEST_TOKEN');
        $this->assertIsString($token);
    }

    public function testMissingTellerConfigurationExceptionThrown() {
        $this->expectException(MissingTellerConfigurationException::class);

        throw new MissingTellerConfigurationException();
    }

}
