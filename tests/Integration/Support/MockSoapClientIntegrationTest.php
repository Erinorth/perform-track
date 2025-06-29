<?php

use Tests\Support\MockSoapClient;

describe('MockSoapClient Integration Tests', function () {
    
    // ทดสอบการใช้งานใน scenario จริง
    it('สามารถใช้ในการจำลอง EGAT authentication ได้', function () {
        $mockClient = new MockSoapClient('http://egat.wsdl', true);
        
        // เคลียร์ credentials เริ่มต้น
        $mockClient->clearCredentials();
        
        // เพิ่ม EGAT users
        $mockClient->addValidCredentials('egat001', 'egatpass1');
        $mockClient->addValidCredentials('egat002', 'egatpass2');
        $mockClient->addValidCredentials('admin', 'adminpass');
        
        // จำลอง authentication flow
        $loginAttempts = [
            ['user' => 'egat001', 'pass' => 'egatpass1', 'expected' => true],
            ['user' => 'egat002', 'pass' => 'egatpass2', 'expected' => true],
            ['user' => 'admin', 'pass' => 'adminpass', 'expected' => true],
            ['user' => 'egat001', 'pass' => 'wrongpass', 'expected' => false],
            ['user' => 'unknown', 'pass' => 'anypass', 'expected' => false],
        ];
        
        foreach ($loginAttempts as $attempt) {
            $result = $mockClient->call('validate_user', [
                'a' => $attempt['user'],
                'b' => $attempt['pass']
            ]);
            
            expect($result)->toBe($attempt['expected'], 
                "Failed for user: {$attempt['user']} with pass: {$attempt['pass']}"
            );
        }
    });
    
    // ทดสอบการจัดการ session หลายครั้ง
    it('สามารถจัดการ multiple authentication sessions ได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        
        // จำลอง user ล็อกอินหลายครั้ง
        for ($i = 1; $i <= 10; $i++) {
            $result = $mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'password123'
            ]);
            expect($result)->toBeTrue();
        }
        
        // จำลอง failed attempts
        for ($i = 1; $i <= 5; $i++) {
            $result = $mockClient->call('validate_user', [
                'a' => 'test123',
                'b' => 'wrongpass'
            ]);
            expect($result)->toBeFalse();
        }
    });
    
    // ทดสอบการจัดการ credentials แบบ dynamic
    it('สามารถจัดการ credentials แบบ dynamic ได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        
        // เพิ่ม credentials แบบ dynamic
        $users = ['user1', 'user2', 'user3', 'user4', 'user5'];
        
        foreach ($users as $index => $user) {
            $password = "pass" . ($index + 1);
            $mockClient->addValidCredentials($user, $password);
            
            // ทดสอบทันทีหลังเพิ่ม
            $result = $mockClient->call('validate_user', [
                'a' => $user,
                'b' => $password
            ]);
            expect($result)->toBeTrue();
        }
        
        // ลบ credentials แบบ selective
        $mockClient->removeCredentials('user2');
        $mockClient->removeCredentials('user4');
        
        // ทดสอบหลังลบ
        expect($mockClient->call('validate_user', ['a' => 'user1', 'b' => 'pass1']))->toBeTrue();
        expect($mockClient->call('validate_user', ['a' => 'user2', 'b' => 'pass2']))->toBeFalse();
        expect($mockClient->call('validate_user', ['a' => 'user3', 'b' => 'pass3']))->toBeTrue();
        expect($mockClient->call('validate_user', ['a' => 'user4', 'b' => 'pass4']))->toBeFalse();
        expect($mockClient->call('validate_user', ['a' => 'user5', 'b' => 'pass5']))->toBeTrue();
    });
    
    // ทดสอบ performance กับ credentials จำนวนมาก
    it('สามารถจัดการ credentials จำนวนมากได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        $mockClient->clearCredentials();
        
        // เพิ่ม credentials 100 ตัว
        $startTime = microtime(true);
        
        for ($i = 1; $i <= 100; $i++) {
            $mockClient->addValidCredentials("user$i", "pass$i");
        }
        
        $addTime = microtime(true) - $startTime;
        
        // ทดสอบการ authenticate 100 ครั้ง
        $authStartTime = microtime(true);
        
        for ($i = 1; $i <= 100; $i++) {
            $result = $mockClient->call('validate_user', [
                'a' => "user$i",
                'b' => "pass$i"
            ]);
            expect($result)->toBeTrue();
        }
        
        $authTime = microtime(true) - $authStartTime;
        
        // ตรวจสอบ performance (ควรเสร็จภายใน 1 วินาที)
        expect($addTime)->toBeLessThan(1.0);
        expect($authTime)->toBeLessThan(1.0);
    });

});
