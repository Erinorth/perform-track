<?php

use Tests\Support\MockSoapClient;

describe('MockSoapClient', function () {
    
    beforeEach(function () {
        // สร้าง MockSoapClient instance ใหม่สำหรับแต่ละ test
        $this->mockClient = new MockSoapClient('http://test.wsdl', true);
    });
    
    // ทดสอบการสร้าง instance
    it('สามารถสร้าง instance ได้', function () {
        expect($this->mockClient)->toBeInstanceOf(MockSoapClient::class);
        expect($this->mockClient->soap_defencoding)->toBe('UTF-8');
        expect($this->mockClient->decode_utf8)->toBe(false);
    });
    
    // ทดสอบ constructor พร้อม default credentials
    it('มี default credentials เมื่อสร้าง instance', function () {
        // ทดสอบ credentials เริ่มต้น
        $result1 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'password123']);
        $result2 = $this->mockClient->call('validate_user', ['a' => 'valid_user', 'b' => 'valid_pass']);
        $result3 = $this->mockClient->call('validate_user', ['a' => 'invalid', 'b' => 'wrong']);
        
        expect($result1)->toBeTrue();
        expect($result2)->toBeTrue();
        expect($result3)->toBeFalse();
    });
    
    describe('hello operation', function () {
        it('ส่งคืนข้อความต้อนรับ', function () {
            $result = $this->mockClient->call('hello');
            expect($result)->toBe('Hello from EGAT LDAP Authentication Service');
        });
        
        it('ไม่ต้องการ parameters', function () {
            $result = $this->mockClient->call('hello', []);
            expect($result)->toBe('Hello from EGAT LDAP Authentication Service');
        });
    });
    
    describe('validate_user operation', function () {
        it('สามารถ validate user credentials ได้ถูกต้อง', function () {
            // ทดสอบ credentials ที่ถูกต้อง
            $validResult = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'password123'
            ]);
            expect($validResult)->toBeTrue();
            
            // ทดสอบ credentials ที่ไม่ถูกต้อง
            $invalidResult = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'wrongpassword'
            ]);
            expect($invalidResult)->toBeFalse();
        });
        
        it('คืนค่า false เมื่อ parameters ไม่ครบ', function () {
            // ไม่มี parameter 'a'
            $result1 = $this->mockClient->call('validate_user', ['b' => 'password123']);
            expect($result1)->toBeFalse();
            
            // ไม่มี parameter 'b'
            $result2 = $this->mockClient->call('validate_user', ['a' => 'test123']);
            expect($result2)->toBeFalse();
            
            // ไม่มี parameters เลย
            $result3 = $this->mockClient->call('validate_user', []);
            expect($result3)->toBeFalse();
        });
        
        it('คืนค่า false เมื่อ parameters เป็นค่าว่าง', function () {
            // parameters เป็น empty string
            $result1 = $this->mockClient->call('validate_user', ['a' => '', 'b' => '']);
            expect($result1)->toBeFalse();
            
            // mixed empty และ valid
            $result2 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => '']);
            expect($result2)->toBeFalse();
        });
        
        it('ตรวจสอบ case sensitivity ของ credentials', function () {
            // ทดสอบ username case sensitive
            $result1 = $this->mockClient->call('validate_user', ['a' => 'TEST123', 'b' => 'password123']);
            expect($result1)->toBeFalse();
            
            // ทดสอบ password case sensitive
            $result2 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'PASSWORD123']);
            expect($result2)->toBeFalse();
            
            // ทดสอบ case ที่ถูกต้อง
            $result3 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'password123']);
            expect($result3)->toBeTrue();
        });
    });
    
    describe('search_info operation', function () {
        it('ส่งคืนชื่อผู้ใช้เมื่อพบข้อมูล', function () {
            $result = $this->mockClient->call('search_info', ['a' => 'test123', 'b' => 'key']);
            expect($result)->toBe('ทดสอบ ระบบ');
            
            $result2 = $this->mockClient->call('search_info', ['a' => 'egat001', 'b' => 'key']);
            expect($result2)->toBe('สมชาย ใจดี');
        });
        
        it('ส่งคืน error เมื่อไม่พบผู้ใช้', function () {
            $result = $this->mockClient->call('search_info', ['a' => 'unknown', 'b' => 'key']);
            expect($result)->toBe('Error: User not found');
        });
        
        it('ส่งคืน error เมื่อ EGAT ID เป็นค่าว่าง', function () {
            $result = $this->mockClient->call('search_info', ['a' => '', 'b' => 'key']);
            expect($result)->toBe('Error: EGAT ID is required');
        });
        
        it('ยังคงทำงานได้แม้ไม่มี parameter b', function () {
            $result = $this->mockClient->call('search_info', ['a' => 'test123']);
            expect($result)->toBe('ทดสอบ ระบบ');
        });
    });
    
    describe('credentials management', function () {
        it('สามารถเพิ่ม valid credentials ได้', function () {
            // เพิ่ม credentials ใหม่
            $this->mockClient->addValidCredentials('newuser', 'newpass');
            
            // ทดสอบ credentials ใหม่
            $result = $this->mockClient->call('validate_user', [
                'a' => 'newuser',
                'b' => 'newpass'
            ]);
            expect($result)->toBeTrue();
            
            // ทดสอบ credentials เดิมยังใช้ได้
            $result2 = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'password123'
            ]);
            expect($result2)->toBeTrue();
        });
        
        it('สามารถ override credentials ที่มีอยู่แล้วได้', function () {
            // เปลี่ยน password ของ test123
            $this->mockClient->addValidCredentials('test123', 'newpassword');
            
            // password เก่าไม่ควรใช้ได้แล้ว
            $oldResult = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'password123'
            ]);
            expect($oldResult)->toBeFalse();
            
            // password ใหม่ควรใช้ได้
            $newResult = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'newpassword'
            ]);
            expect($newResult)->toBeTrue();
        });
        
        it('สามารถลบ credentials ได้', function () {
            // ลบ credentials
            $this->mockClient->removeCredentials('test123');
            
            // ตรวจสอบว่า credentials ถูกลบแล้ว
            $afterRemove = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'password123'
            ]);
            expect($afterRemove)->toBeFalse();
            
            // credentials อื่นยังคงอยู่
            $otherCredentials = $this->mockClient->call('validate_user', [
                'a' => 'valid_user',
                'b' => 'valid_pass'
            ]);
            expect($otherCredentials)->toBeTrue();
        });
        
        it('สามารถล้าง credentials ทั้งหมดได้', function () {
            // ล้าง credentials ทั้งหมด
            $this->mockClient->clearCredentials();
            
            // ตรวจสอบว่า credentials ทั้งหมดถูกลบ
            $afterClear = $this->mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'password123'
            ]);
            expect($afterClear)->toBeFalse();
        });
    });
    
    describe('user info management', function () {
        it('สามารถเพิ่มข้อมูลผู้ใช้ได้', function () {
            $this->mockClient->addUserInfo('newuser', 'ผู้ใช้ ใหม่');
            
            $result = $this->mockClient->call('search_info', ['a' => 'newuser', 'b' => 'key']);
            expect($result)->toBe('ผู้ใช้ ใหม่');
        });
        
        it('สามารถลบข้อมูลผู้ใช้ได้', function () {
            $this->mockClient->removeUserInfo('test123');
            
            $result = $this->mockClient->call('search_info', ['a' => 'test123', 'b' => 'key']);
            expect($result)->toBe('Error: User not found');
        });
        
        it('สามารถล้างข้อมูลผู้ใช้ทั้งหมดได้', function () {
            $this->mockClient->clearUserInfo();
            
            $result = $this->mockClient->call('search_info', ['a' => 'test123', 'b' => 'key']);
            expect($result)->toBe('Error: User not found');
        });
    });
    
    describe('utility methods', function () {
        it('สามารถดู credentials ได้', function () {
            $credentials = $this->mockClient->getCredentials();
            expect($credentials)->toBeArray();
            expect($credentials)->toHaveKey('test123');
            expect($credentials['test123'])->toBe('password123');
        });
        
        it('สามารถดูข้อมูลผู้ใช้ได้', function () {
            $userInfo = $this->mockClient->getUserInfo();
            expect($userInfo)->toBeArray();
            expect($userInfo)->toHaveKey('test123');
            expect($userInfo['test123'])->toBe('ทดสอบ ระบบ');
        });
    });
    
    describe('setSuccessResponse method', function () {
        it('สามารถตั้งค่า success response สำหรับ method อื่นๆ ได้', function () {
            // ตั้งค่าให้ return false
            $this->mockClient->setSuccessResponse(false);
            $result = $this->mockClient->call('unknown_method', []);
            expect($result)->toBeFalse();
            
            // ตั้งค่าให้ return true
            $this->mockClient->setSuccessResponse(true);
            $result2 = $this->mockClient->call('unknown_method', []);
            expect($result2)->toBeTrue();
        });
        
        it('ไม่มีผลต่อ operation ที่กำหนดไว้แล้ว', function () {
            $this->mockClient->setSuccessResponse(false);
            
            // hello ยังคงส่งคืนข้อความ
            $helloResult = $this->mockClient->call('hello');
            expect($helloResult)->toBe('Hello from EGAT LDAP Authentication Service');
            
            // validate_user ยังคงทำงานตาม logic
            $validateResult = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'password123']);
            expect($validateResult)->toBeTrue();
            
            // search_info ยังคงทำงานตาม logic
            $searchResult = $this->mockClient->call('search_info', ['a' => 'test123', 'b' => 'key']);
            expect($searchResult)->toBe('ทดสอบ ระบบ');
        });
    });

});
