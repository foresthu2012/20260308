<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ContractIntegrationTest extends TestCase
{
    protected $client;
    
    protected function setUp(): void
    {
        // Initialize HTTP client for API testing
        $this->client = new Client([
            'base_uri' => 'http://localhost:8080', // Adjust to your test environment
            'timeout'  => 10.0,
        ]);
    }
    
    public function testCreateContractWithMemberAndOrder()
    {
        // Test data
        $data = [
            'title' => 'Integration Test Contract',
            'member_id' => 1,
            'order_id' => 1,
            'file_path' => '/test/integration.pdf'
        ];
        
        // Test API response
        $response = $this->client->post('/adminapi/Contract/contract/add', [
            'form_params' => $data
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(0, $result['code']);
        $this->assertEquals('ADD_SUCCESS', $result['message']);
    }
    
    public function testCreateContractWithMemberOnly()
    {
        // Test data
        $data = [
            'title' => 'Integration Test Contract (No Order)',
            'member_id' => 1,
            'order_id' => 0,
            'content' => '<p>Test contract content</p>'
        ];
        
        // Test API response
        $response = $this->client->post('/adminapi/Contract/contract/add', [
            'form_params' => $data
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(0, $result['code']);
        $this->assertEquals('ADD_SUCCESS', $result['message']);
    }
    
    public function testCreateContractInvalidOrder()
    {
        // Test data with invalid order
        $data = [
            'title' => 'Integration Test Contract (Invalid Order)',
            'member_id' => 1,
            'order_id' => 999999, // Non-existent order
            'file_path' => '/test/invalid_order.pdf'
        ];
        
        // Test API response
        $response = $this->client->post('/adminapi/Contract/contract/add', [
            'form_params' => $data
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(1, $result['code']);
        $this->assertEquals('CONTRACT_ORDER_NOT_BELONG_TO_MEMBER', $result['message']);
    }
    
    public function testGetContractList()
    {
        // Test API response
        $response = $this->client->get('/adminapi/Contract/contract/lists');
        
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(0, $result['code']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('list', $result['data']);
    }
    
    public function testGetContractInfo()
    {
        // Test API response
        $contractId = 1;
        $response = $this->client->get("/adminapi/Contract/contract/info/{$contractId}");
        
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getBody(), true);
        $this->assertEquals(0, $result['code']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('member', $result['data']);
        $this->assertArrayHasKey('order', $result['data']);
    }
}
