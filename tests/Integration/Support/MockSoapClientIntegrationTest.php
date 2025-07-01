<?php

use Tests\Support\MockSoapClient;

describe('MockSoapClient Integration Tests', function () {
    
    // ทดสอบการใช้งานใน scenario จริง
    it('สามารถใช้ในการจำลอง EGAT authentication workflow ได้', function () {
        $mockClient = new MockSoapClient('http://egat.wsdl', true);
        
        // 1. ทดสอบ hello service
        $helloResponse = $mockClient->call('hello');
        expect($helloResponse)->toBe('Hello from EGAT LDAP Authentication Service');
        
        // 2. ทดสอบ authentication flow
        $egatid = 'egat001';
        $password = 'egatpass1';
        
        // ตรวจสอบ authentication
        $authResult = $mockClient->call('validate_user', ['a' => $egatid, 'b' => $password]);
        expect($authResult)->toBeTrue();
        
        // 3. ค้นหาข้อมูลผู้ใช้หลัง authentication สำเร็จ
        $userInfo = $mockClient->call('search_info', ['a' => $egatid, 'b' => $password]);
        expect($userInfo)->toBe('สมชาย ใจดี');
    });
    
    // ทดสอบ authentication flow หลายผู้ใช้
    it('สามารถจัดการ authentication หลายผู้ใช้พร้อมกันได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        
        $users = [
            ['id' => 'test123', 'pass' => 'password123', 'name' => 'ทดสอบ ระบบ'],
            ['id' => 'egat001', 'pass' => 'egatpass1', 'name' => 'สมชาย ใจดี'],
            ['id' => 'egat002', 'pass' => 'egatpass2', 'name' => 'สมหญิง รักงาน'],
        ];
        
        foreach ($users as $user) {
            // ทดสอบ authentication
            $authResult = $mockClient->call('validate_user', [
                'a' => $user['id'],
                'b' => $user['pass']
            ]);
            expect($authResult)->toBeTrue();
            
            // ทดสอบการค้นหาข้อมูล
            $userInfo = $mockClient->call('search_info', [
                'a' => $user['id'],
                'b' => 'key'
            ]);
            expect($userInfo)->toBe($user['name']);
        }
    });
    
    // ทดสอบ error handling
    it('จัดการ error cases ได้ถูกต้อง', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        
        // ทดสอบ authentication ล้มเหลว
        $authFail = $mockClient->call('validate_user', ['a' => 'invalid', 'b' => 'wrong']);
        expect($authFail)->toBeFalse();
        
        // ทดสอบค้นหาผู้ใช้ที่ไม่มี
        $searchFail = $mockClient->call('search_info', ['a' => 'notfound', 'b' => 'key']);
        expect($searchFail)->toBe('Error: User not found');
        
        // ทดสอบ EGAT ID เป็นค่าว่าง
        $emptyId = $mockClient->call('search_info', ['a' => '', 'b' => 'key']);
        expect($emptyId)->toBe('Error: EGAT ID is required');
    });
    
    // ทดสอบการจัดการข้อมูลแบบ dynamic
    it('สามารถจัดการข้อมูลแบบ dynamic ได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        
        // เคลียร์ข้อมูลเริ่มต้น
        $mockClient->clearCredentials();
        $mockClient->clearUserInfo();
        
        // เพิ่มผู้ใช้ใหม่แบบ dynamic
        $newUsers = [
            ['id' => 'dynamic1', 'pass' => 'pass1', 'name' => 'ผู้ใช้ 1'],
            ['id' => 'dynamic2', 'pass' => 'pass2', 'name' => 'ผู้ใช้ 2'],
            ['id' => 'dynamic3', 'pass' => 'pass3', 'name' => 'ผู้ใช้ 3'],
        ];
        
        foreach ($newUsers as $user) {
            $mockClient->addValidCredentials($user['id'], $user['pass']);
            $mockClient->addUserInfo($user['id'], $user['name']);
            
            // ทดสอบทันทีหลังเพิ่ม
            $authResult = $mockClient->call('validate_user', [
                'a' => $user['id'],
                'b' => $user['pass']
            ]);
            expect($authResult)->toBeTrue();
            
            $searchResult = $mockClient->call('search_info', [
                'a' => $user['id'],
                'b' => 'key'
            ]);
            expect($searchResult)->toBe($user['name']);
        }
        
        // ลบผู้ใช้บางคน
        $mockClient->removeCredentials('dynamic2');
        $mockClient->removeUserInfo('dynamic2');
        
        // ทดสอบหลังลบ
        $authRemoved = $mockClient->call('validate_user', ['a' => 'dynamic2', 'b' => 'pass2']);
        expect($authRemoved)->toBeFalse();
        
        $searchRemoved = $mockClient->call('search_info', ['a' => 'dynamic2', 'b' => 'key']);
        expect($searchRemoved)->toBe('Error: User not found');
        
        // ผู้ใช้อื่นยังใช้ได้
        $authOther = $mockClient->call('validate_user', ['a' => 'dynamic1', 'b' => 'pass1']);
        expect($authOther)->toBeTrue();
    });
    
    // ทดสอบ performance กับข้อมูลจำนวนมาก
    it('สามารถจัดการข้อมูลจำนวนมากได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        $mockClient->clearCredentials();
        $mockClient->clearUserInfo();
        
        // เพิ่มข้อมูล 100 รายการ
        $startTime = microtime(true);
        
        for ($i = 1; $i <= 100; $i++) {
            $mockClient->addValidCredentials("user$i", "pass$i");
            $mockClient->addUserInfo("user$i", "ผู้ใช้ที่ $i");
        }
        
        $addTime = microtime(true) - $startTime;
        
        // ทดสอบการ authenticate และ search 100 ครั้ง
        $testStartTime = microtime(true);
        
        for ($i = 1; $i <= 100; $i++) {
            // ทดสอบ authentication
            $authResult = $mockClient->call('validate_user', [
                'a' => "user$i",
                'b' => "pass$i"
            ]);
            expect($authResult)->toBeTrue();
            
            // ทดสอบ search
            $searchResult = $mockClient->call('search_info', [
                'a' => "user$i",
                'b' => 'key'
            ]);
            expect($searchResult)->toBe("ผู้ใช้ที่ $i");
        }
        
        $testTime = microtime(true) - $testStartTime;
        
        // ตรวจสอบ performance (ควรเสร็จภายใน 1 วินาที)
        expect($addTime)->toBeLessThan(1.0);
        expect($testTime)->toBeLessThan(1.0);
    });
    
    // ทดสอบ concurrent operations
    it('สามารถจัดการ operations หลายรูปแบบพร้อมกันได้', function () {
        $mockClient = new MockSoapClient('http://test.wsdl', true);
        
        // ทดสอบ mixed operations
        $operations = [
            ['op' => 'hello', 'params' => [], 'expected' => 'Hello from EGAT LDAP Authentication Service'],
            ['op' => 'validate_user', 'params' => ['a' => 'test123', 'b' => 'password123'], 'expected' => true],
            ['op' => 'search_info', 'params' => ['a' => 'test123', 'b' => 'key'], 'expected' => 'ทดสอบ ระบบ'],
            ['op' => 'validate_user', 'params' => ['a' => 'invalid', 'b' => 'wrong'], 'expected' => false],
            ['op' => 'search_info', 'params' => ['a' => 'notfound', 'b' => 'key'], 'expected' => 'Error: User not found'],
        ];
        
        foreach ($operations as $operation) {
            $result = $mockClient->call($operation['op'], $operation['params']);
            expect($result)->toBe($operation['expected']);
        }
    });
    
    // ทดสอบ service binding ใน Laravel container
    it('สามารถใช้ร่วมกับ Laravel service container ได้', function () {
        // จำลองการ bind ใน service container
        app()->bind('soap_client', function () {
            return new MockSoapClient('http://test.wsdl', true);
        });
        
        $soapClient = app('soap_client');
        
        // ทดสอบว่าทำงานได้เหมือน instance ปกติ
        expect($soapClient)->toBeInstanceOf(MockSoapClient::class);
        
        $result = $soapClient->call('validate_user', ['a' => 'test123', 'b' => 'password123']);
        expect($result)->toBeTrue();
        
        $info = $soapClient->call('search_info', ['a' => 'test123', 'b' => 'key']);
        expect($info)->toBe('ทดสอบ ระบบ');
    });

});
