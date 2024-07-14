<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Faker\Factory as Faker;

class AuthTest extends TestCase
{
    use AuthTrait;
    
    protected $faker;
    protected $form_data;
    protected $login_data;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->form_data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password'
        ];

        $this->login_data = [
            'email' => env('TEST_EMAIL'),
            'password' => env('TEST_PASSWORD')
        ];

        $this->getAccessToken();

    }

    /**
     * A basic feature test example.
     */
    public function test_valid_register(): void
    {
        $data = $this->form_data;

        $response = $this->create('api/register', $data);

        $response->assertStatus(200);
    }
    /**
     * A basic feature test example.
     */
    public function test_invalid_register(): void
    {
        $data = $this->form_data;
        unset($data['name']);

        $response = $this->create('api/register', $data);

        $response->assertStatus(422);
    }


    public function test_valid_login() {
        $data = $this->login_data;

        $response = $this->create('api/login', $data);
        $response->assertStatus(200);
    }

    public function test_invalid_login() {
        $data = $this->login_data;
        $data['password'] = 'wrong_password';

        $response = $this->create('api/login', $data);
        $response->assertStatus(401);
    }

    public function test_valid_logout() {
        $response= $this->logout();

        $response->assertStatus(200);
    }
    
    protected function logout($token=null)
    {
        return $this->withHeader('accept', 'application/json')->withToken($token ? $token : $this->accessToken)->post('api/logout');

    }


    protected function create($url, $data)
    {
        return $this->withHeader('accept', 'application/json')->post($url, $data);
    }
}
