<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
class SocialUserTest extends  TestCase {

    protected $client;


    protected function setUp(): void{
        $this->client = new Client([
            'base_uri' => 'http://localhost/social/index.php/',
        ]);
    }

    public function test_login_api(){
        
        
        $options = [
            'json' => [
                "email_address" => "abc@gmail.com",
                "password"     =>   "123456"
            ]
        
        ]; 


        $response = $this->client->post('SocialUser/login', $options);
        $this->assertEquals(200, $response->getStatusCode());
        $response_array = json_decode($response->getBody(), true); //response_array is array
        // $response_array = json_decode($response->getBody()); // reponse_array is stdclass Object
        $this->assertEquals(1, $response_array['respCode']);

    }


}