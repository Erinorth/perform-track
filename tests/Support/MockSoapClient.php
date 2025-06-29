<?php

namespace Tests\Support;

/**
 * Mock SOAP Client สำหรับการทดสอบ EGAT authentication
 */
class MockSoapClient
{
    public string $soap_defencoding = 'UTF-8';
    public bool $decode_utf8 = false;
    
    private bool $shouldSucceed;
    private array $validCredentials;
    
    public function __construct(string $wsdl, bool $trace)
    {
        // ตั้งค่าเริ่มต้นสำหรับการทดสอบ
        $this->shouldSucceed = true;
        $this->validCredentials = [
            'test123' => 'password123',
            'valid_user' => 'valid_pass'
        ];
    }
    
    /**
     * Mock การเรียก SOAP method
     */
    public function call(string $method, array $params): bool
    {
        if ($method === 'validate_user') {
            $username = $params['a'] ?? '';
            $password = $params['b'] ?? '';
            
            // ตรวจสอบ credentials ที่กำหนดไว้
            return isset($this->validCredentials[$username]) && 
                   $this->validCredentials[$username] === $password;
        }
        
        return $this->shouldSucceed;
    }
    
    /**
     * ตั้งค่าผลลัพธ์การทดสอบ
     */
    public function setSuccessResponse(bool $success): void
    {
        $this->shouldSucceed = $success;
    }
    
    /**
     * เพิ่ม credentials ที่ถูกต้องสำหรับการทดสอบ
     */
    public function addValidCredentials(string $username, string $password): void
    {
        $this->validCredentials[$username] = $password;
    }
    
    /**
     * ลบ credentials
     */
    public function removeCredentials(string $username): void
    {
        unset($this->validCredentials[$username]);
    }
    
    /**
     * ล้าง credentials ทั้งหมด
     */
    public function clearCredentials(): void
    {
        $this->validCredentials = [];
    }
}
