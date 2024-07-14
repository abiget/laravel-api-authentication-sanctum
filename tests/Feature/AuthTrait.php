<?php
namespace Tests\Feature;

trait AuthTrait
{
    protected $accessToken;

    // setup function 
    protected function getAccessToken()
    {
        $data = [
            'email' => env('TEST_EMAIL'),
            'password' => env('TEST_PASSWORD'),
        ];

        $response = $this->withHeader('accept', 'application/json')->post('api/login', $data);

        $response->assertStatus(200);

        $this->accessToken = $response['access_token'];
    }
}
