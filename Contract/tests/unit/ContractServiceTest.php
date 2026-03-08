<?php

use addon\Contract\app\service\admin\contract\ContractService;
use addon\Contract\app\model\Contract;
use PHPUnit\Framework\TestCase;

class ContractServiceTest extends TestCase
{
    protected $service;
    
    protected function setUp(): void
    {
        $this->service = new ContractService();
    }
    
    public function testAddContractWithOrder()
    {
        // Mock order model
        $mockOrder = [
            'order_id' => 1,
            'member_id' => 1
        ];
        
        // Test data
        $data = [
            'title' => 'Test Contract',
            'member_id' => 1,
            'order_id' => 1,
            'file_path' => '/test/file.pdf'
        ];
        
        // Test add contract
        $result = $this->service->add($data);
        $this->assertTrue($result);
    }
    
    public function testAddContractWithoutOrder()
    {
        // Test data
        $data = [
            'title' => 'Test Contract',
            'member_id' => 1,
            'order_id' => 0,
            'file_path' => '/test/file.pdf'
        ];
        
        // Test add contract
        $result = $this->service->add($data);
        $this->assertTrue($result);
    }
    
    public function testAddContractWithContentOnly()
    {
        // Test data
        $data = [
            'title' => 'Test Contract',
            'member_id' => 1,
            'order_id' => 0,
            'content' => '<p>Test contract content</p>'
        ];
        
        // Test add contract
        $result = $this->service->add($data);
        $this->assertTrue($result);
    }
    
    public function testAddContractWithInvalidOrder()
    {
        // Test data with invalid order
        $data = [
            'title' => 'Test Contract',
            'member_id' => 1,
            'order_id' => 999999, // Non-existent order
            'file_path' => '/test/file.pdf'
        ];
        
        // Test add contract should throw exception
        $this->expectException(Exception::class);
        $this->service->add($data);
    }
    
    public function testGetInfo()
    {
        // Test get contract info
        $contractId = 1;
        $result = $this->service->getInfo($contractId);
        
        // Test result structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('member_id', $result);
        $this->assertArrayHasKey('order_id', $result);
    }
}
