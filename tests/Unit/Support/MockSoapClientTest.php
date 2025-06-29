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
    
    // ทดสอบ call method สำหรับ validate_user
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
    
    // ทดสอบ call method สำหรับ method อื่นๆ
    it('return default success response สำหรับ method อื่นที่ไม่ใช่ validate_user', function () {
        // Method อื่นควร return shouldSucceed value (default: true)
        $result = $this->mockClient->call('other_method', ['param' => 'value']);
        expect($result)->toBeTrue();
        
        // เปลี่ยน shouldSucceed เป็น false
        $this->mockClient->setSuccessResponse(false);
        $result2 = $this->mockClient->call('another_method', []);
        expect($result2)->toBeFalse();
    });
    
    // ทดสอบ setSuccessResponse method
    it('สามารถตั้งค่า success response ได้', function () {
        // ตั้งค่าให้ return false
        $this->mockClient->setSuccessResponse(false);
        $result = $this->mockClient->call('test_method', []);
        expect($result)->toBeFalse();
        
        // ตั้งค่าให้ return true
        $this->mockClient->setSuccessResponse(true);
        $result2 = $this->mockClient->call('test_method', []);
        expect($result2)->toBeTrue();
    });
    
    // ทดสอบ addValidCredentials method
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
    
    // ทดสอบการ override credentials ที่มีอยู่แล้ว
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
    
    // ทดสอบ removeCredentials method
    it('สามารถลบ credentials ได้', function () {
        // ตรวจสอบว่า credentials อยู่
        $beforeRemove = $this->mockClient->call('validate_user', [
            'a' => 'test123',
            'b' => 'password123'
        ]);
        expect($beforeRemove)->toBeTrue();
        
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
    
    // ทดสอบการลบ credentials ที่ไม่มีอยู่
    it('สามารถลบ credentials ที่ไม่มีอยู่ได้โดยไม่ error', function () {
        // ลบ username ที่ไม่มีอยู่
        $this->mockClient->removeCredentials('nonexistent');
        
        // credentials อื่นยังคงทำงานได้ปกติ
        $result = $this->mockClient->call('validate_user', [
            'a' => 'test123',
            'b' => 'password123'
        ]);
        expect($result)->toBeTrue();
    });
    
    // ทดสอบ clearCredentials method
    it('สามารถล้าง credentials ทั้งหมดได้', function () {
        // เพิ่ม credentials ใหม่
        $this->mockClient->addValidCredentials('extra', 'extrapass');
        
        // ตรวจสอบว่ามี credentials อยู่
        $beforeClear1 = $this->mockClient->call('validate_user', [
            'a' => 'test123',
            'b' => 'password123'
        ]);
        $beforeClear2 = $this->mockClient->call('validate_user', [
            'a' => 'extra',
            'b' => 'extrapass'
        ]);
        expect($beforeClear1)->toBeTrue();
        expect($beforeClear2)->toBeTrue();
        
        // ล้าง credentials ทั้งหมด
        $this->mockClient->clearCredentials();
        
        // ตรวจสอบว่า credentials ทั้งหมดถูกลบ
        $afterClear1 = $this->mockClient->call('validate_user', [
            'a' => 'test123',
            'b' => 'password123'
        ]);
        $afterClear2 = $this->mockClient->call('validate_user', [
            'a' => 'extra',
            'b' => 'extrapass'
        ]);
        expect($afterClear1)->toBeFalse();
        expect($afterClear2)->toBeFalse();
    });
    
    // ทดสอบกรณี parameters ไม่ครบ
    it('จัดการกรณี parameters ไม่ครบได้', function () {
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
    
    // ทดสอบกรณี parameters เป็น null หรือ empty
    it('จัดการกรณี parameters เป็น null หรือ empty ได้', function () {
        // parameters เป็น empty string
        $result1 = $this->mockClient->call('validate_user', ['a' => '', 'b' => '']);
        expect($result1)->toBeFalse();
        
        // parameters เป็น null
        $result2 = $this->mockClient->call('validate_user', ['a' => null, 'b' => null]);
        expect($result2)->toBeFalse();
        
        // mixed empty และ valid
        $result3 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => '']);
        expect($result3)->toBeFalse();
    });
    
    // ทดสอบการทำงานร่วมกับหลาย credentials
    it('สามารถจัดการหลาย credentials พร้อมกันได้', function () {
        // เพิ่ม credentials หลายตัว
        $this->mockClient->addValidCredentials('user1', 'pass1');
        $this->mockClient->addValidCredentials('user2', 'pass2');
        $this->mockClient->addValidCredentials('user3', 'pass3');
        
        // ทดสอบทุก credentials
        expect($this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'password123']))->toBeTrue();
        expect($this->mockClient->call('validate_user', ['a' => 'valid_user', 'b' => 'valid_pass']))->toBeTrue();
        expect($this->mockClient->call('validate_user', ['a' => 'user1', 'b' => 'pass1']))->toBeTrue();
        expect($this->mockClient->call('validate_user', ['a' => 'user2', 'b' => 'pass2']))->toBeTrue();
        expect($this->mockClient->call('validate_user', ['a' => 'user3', 'b' => 'pass3']))->toBeTrue();
        
        // ทดสอบ credentials ที่ไม่ถูกต้อง
        expect($this->mockClient->call('validate_user', ['a' => 'user1', 'b' => 'wrongpass']))->toBeFalse();
        expect($this->mockClient->call('validate_user', ['a' => 'wronguser', 'b' => 'pass1']))->toBeFalse();
    });
    
    // ทดสอบ case sensitivity
    it('ตรวจสอบ case sensitivity ของ credentials', function () {
        // ทดสอบ username case sensitive
        $result1 = $this->mockClient->call('validate_user', ['a' => 'TEST123', 'b' => 'password123']);
        expect($result1)->toBeFalse(); // ควรเป็น false เพราะ case ไม่ตรง
        
        // ทดสอบ password case sensitive
        $result2 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'PASSWORD123']);
        expect($result2)->toBeFalse(); // ควรเป็น false เพราะ case ไม่ตรง
        
        // ทดสอบ case ที่ถูกต้อง
        $result3 = $this->mockClient->call('validate_user', ['a' => 'test123', 'b' => 'password123']);
        expect($result3)->toBeTrue();
    });

});
