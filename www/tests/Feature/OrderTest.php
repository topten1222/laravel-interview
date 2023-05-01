<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    /** @test */
    public function createSuccess()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@tt.com',
            'phone' => '0123456789',
            'address' => 'address 1',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(200)->json(['message' => 'success']);
    }

    /** @test */
    public function errorValidEmail()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top',
            'phone' => '0123456789',
            'address' => 'address 1',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'email' => [
                    'The email must be a valid email address.'
                ]
            ]
        ]);
    }

    /** @test */
    public function errorRequiredEmail()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => '',
            'phone' => '0123456789',
            'address' => 'address 1',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'email' => [
                    'The email field is required.'
                ]
            ]
        ]);
    }

    /** @test */
    public function errorRequiredPhone()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '',
            'address' => 'address 1',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'phone' => [
                    'The phone field is required.'
                ]
            ]
        ]);
    }

    /** @test */
    public function errorValidPhone()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '00',
            'address' => 'address 1',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'phone' => [
                    'The phone must be 10 digits.'
                ]
            ]
        ]);
    }

    /** @test */
    public function errorRequiredAddress()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '0123456789',
            'address' => '',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'address' => [
                    'The address field is required.'
                ]
            ]
        ]);
    }

    /** @test */
    public function errorRequiredItem()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '0123456789',
            'address' => '',
            'address_tax' => 'address_tax 1',
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'items' => [
                    'The items field is required.'
                ]
            ]
        ]);
    }

    /** @test  * */
    public function errorRequiredItemProductId()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '0123456789',
            'address' => '',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => '',
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'items.0.product_id' => [
                    'The items.0.product_id field is required.'
                ]
            ]
        ]);
    }

    /** @test  * */
    public function errorRequiredItemProductQuantity()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '0123456789',
            'address' => '',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 1,
                    'product_quantity' => ''
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'items.0.product_quantity' => [
                    'The items.0.product_quantity field is required.'
                ]
            ]
        ]);
    }

    /** @test  * */
    public function errorInvalidItemProductId()
    {
        $response = $this->postJson('/api/order/create', [
            'email' => 'top@test.com',
            'phone' => '0123456789',
            'address' => '',
            'address_tax' => 'address_tax 1',
            'items' => [
                0 => [
                    'product_id' => 0,
                    'product_quantity' => 2
                ]
            ]
        ]);
        $response->assertStatus(400)->assertJson([
            'error' => [
                'items.0.product_id' => [
                    'The selected items.0.product_id is invalid.'
                ]
            ]
        ]);
    }

    /** @test  * */
    public function errorRequiredAll()
    {
        $response = $this->postJson('/api/order/create');
        $response->assertStatus(400)->assertJson([
            'error' => [
                'email' => [
                    'The email field is required.'
                ],
                'phone' => [
                    'The phone field is required.'
                ],
                'address' => [
                    'The address field is required.'
                ],
                'items' => [
                    'The items field is required.'
                ]
            ]
        ]);
    }
}
