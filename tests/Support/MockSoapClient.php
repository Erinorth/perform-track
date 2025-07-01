<?php

namespace Tests\Support;

/**
 * Mock SOAP Client สำหรับการทดสอบ EGAT authentication
 * รองรับ operations ตาม WSDL: hello, validate_user, search_info
 */
class MockSoapClient
{
    public string $soap_defencoding = 'UTF-8';
    public bool $decode_utf8 = false;
    
    private bool $shouldSucceed;
    private array $validCredentials;
    private array $userInfo;
    
    public function __construct(string $wsdl, bool $trace)
    {
        // ตั้งค่าเริ่มต้นสำหรับการทดสอบ
        $this->shouldSucceed = true;
        $this->validCredentials = [
            'test123' => 'password123',
            'valid_user' => 'valid_pass',
            'egat001' => 'egatpass1',
            'egat002' => 'egatpass2'
        ];
        
        // ข้อมูลผู้ใช้สำหรับ search_info
        $this->userInfo = [
            'test123' => 'ทดสอบ ระบบ',
            'valid_user' => 'ผู้ใช้ ทดสอบ',
            'egat001' => 'สมชาย ใจดี',
            'egat002' => 'สมหญิง รักงาน',
            'admin' => 'ผู้ดูแล ระบบ'
        ];
    }
    
    /**
     * Mock การเรียก SOAP method ตาม WSDL
     */
    public function call(string $method, array $params = [])
    {
        switch ($method) {
            case 'hello':
                return $this->handleHello();
                
            case 'validate_user':
                return $this->handleValidateUser($params);
                
            case 'search_info':
                return $this->handleSearchInfo($params);
                
            default:
                return $this->shouldSucceed;
        }
    }
    
    /**
     * จัดการ hello operation
     * @return string
     */
    private function handleHello(): string
    {
        return 'Hello from EGAT LDAP Authentication Service';
    }
    
    /**
     * จัดการ validate_user operation
     * @param array $params ['a' => username, 'b' => password]
     * @return bool status
     */
    private function handleValidateUser(array $params): bool
    {
        $username = $params['a'] ?? '';
        $password = $params['b'] ?? '';
        
        // ตรวจสอบว่า parameters ไม่เป็นค่าว่าง
        if (empty($username) || empty($password)) {
            return false;
        }
        
        // ตรวจสอบ credentials ที่กำหนดไว้
        return isset($this->validCredentials[$username]) && 
               $this->validCredentials[$username] === $password;
    }
    
    /**
     * จัดการ search_info operation
     * @param array $params ['a' => egatid, 'b' => password/key]
     * @return string ชื่อและนามสกุลหรือข้อความ error
     */
    private function handleSearchInfo(array $params): string
    {
        $egatid = $params['a'] ?? '';
        $key = $params['b'] ?? '';
        
        // ตรวจสอบว่า parameters ไม่เป็นค่าว่าง
        if (empty($egatid)) {
            return 'Error: EGAT ID is required';
        }
        
        // ตรวจสอบว่ามีข้อมูลผู้ใช้หรือไม่
        if (isset($this->userInfo[$egatid])) {
            return $this->userInfo[$egatid];
        }
        
        return 'Error: User not found';
    }
    
    /**
     * ตั้งค่าผลลัพธ์การทดสอบสำหรับ method อื่นๆ
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
    
    /**
     * เพิ่มข้อมูลผู้ใช้สำหรับ search_info
     */
    public function addUserInfo(string $egatid, string $fullname): void
    {
        $this->userInfo[$egatid] = $fullname;
    }
    
    /**
     * ลบข้อมูลผู้ใช้
     */
    public function removeUserInfo(string $egatid): void
    {
        unset($this->userInfo[$egatid]);
    }
    
    /**
     * ล้างข้อมูลผู้ใช้ทั้งหมด
     */
    public function clearUserInfo(): void
    {
        $this->userInfo = [];
    }
    
    /**
     * ดูข้อมูล credentials ที่มี (สำหรับ debug)
     */
    public function getCredentials(): array
    {
        return $this->validCredentials;
    }
    
    /**
     * ดูข้อมูลผู้ใช้ที่มี (สำหรับ debug)
     */
    public function getUserInfo(): array
    {
        return $this->userInfo;
    }
}
