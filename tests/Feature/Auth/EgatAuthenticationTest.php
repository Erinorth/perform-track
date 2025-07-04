<?php

use App\Models\User;
use App\Models\CenSus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\MockSoapClient;

beforeEach(function () {
    // ล้าง rate limiting cache
    RateLimiter::clear('login:127.0.0.1');
    RateLimiter::clear('loginEGAT:127.0.0.1');
    
    // สร้างตาราง Census สำหรับทดสอบ
    $this->createCensusTable();
    
    // ล้างข้อมูล User
    User::truncate();
});

test('สามารถเข้าถึงหน้า loginEGAT ได้', function () {
    Log::info('เริ่มทดสอบการเข้าถึงหน้า loginEGAT');
    
    // ทดสอบเข้าถึงหน้า loginEGAT
    $response = $this->get('/loginEGAT');
    
    // ตรวจสอบผลลัพธ์
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('auth/LoginEGAT'));
    
    Log::info('ทดสอบการเข้าถึงหน้า loginEGAT สำเร็จ');
});

test('สามารถล็อกอิน EGAT ได้สำเร็จ', function () {
    Log::info('เริ่มทดสอบการล็อกอิน EGAT สำเร็จ');
    
    // เตรียมข้อมูล Census
    $censusData = [
        'EMPN' => 'test123',
        'TITLE' => 'นาย',
        'NAME' => 'ทดสอบ ระบบ',
        'emp_ename' => 'Mr. Test System',
        'fay' => 'อบค.',
        'gong' => 'กผงค-ธ.',
        'pnang' => 'หผค1-ธ.',
        'a_position' => 'วศ.8'
    ];
    
    // สร้างข้อมูล Census ในฐานข้อมูลทดสอบ
    $this->createMmddataTestData('view_census', [$censusData]);
    
    // สร้าง Mock SOAP Client
    $this->app->bind('nusoap_client', function () {
        $mock = new MockSoapClient('http://test.wsdl', true);
        $mock->addValidCredentials('test123', 'password123');
        return $mock;
    });
    
    // ทดสอบล็อกอิน
    $response = $this->post('/loginEGAT', [
        'egatid' => 'test123',
        'password' => 'password123'
    ]);
    
    // ตรวจสอบผลลัพธ์
    $response->assertRedirect('/dashboard');
    
    // ตรวจสอบว่าผู้ใช้ถูกสร้างขึ้น
    $user = User::where('email', 'test123@egat.co.th')->first();
    expect($user)->not->toBeNull();
    expect($user->egat_id)->toBe('test123'); // ตรวจสอบเป็น string
    expect($user->name)->toBe('test123');
    expect($user->company)->toBe('EGAT');
    expect($user->department)->toBe('หผค1-ธ.');
    expect($user->position)->toBe('วศ.8');
    
    // ตรวจสอบว่าผู้ใช้ถูกล็อกอินแล้ว
    $this->assertAuthenticated();
    
    Log::info('ทดสอบการล็อกอิน EGAT สำเร็จ สำเร็จ');
});

test('สามารถล็อกอิน EGAT กับผู้ใช้ที่มีอยู่แล้ว', function () {
    Log::info('เริ่มทดสอบการล็อกอิน EGAT กับผู้ใช้ที่มีอยู่แล้ว');
    
    // เตรียมข้อมูล Census
    $censusData = [
        'EMPN' => 'existing123',
        'TITLE' => 'นาย',
        'NAME' => 'ผู้ใช้ เดิม',
        'emp_ename' => 'Mr. Existing User',
        'fay' => 'อลก.',
        'gong' => 'กผล1-ธ.',
        'pnang' => 'หผล11-ธ.',
        'a_position' => 'ช.7'
    ];
    
    // สร้างข้อมูล Census ในฐานข้อมูลทดสอบ
    $this->createMmddataTestData('view_census', [$censusData]);
    
    // บันทึกวันที่ verify เริ่มต้น
    $initialVerifiedAt = now()->subDays(10);
    
    // สร้างผู้ใช้เดิม
    $existingUser = User::create([
        'name' => 'ผู้ใช้ เก่า',
        'email' => 'existing123@egat.co.th',
        'password' => Hash::make('oldpassword'),
        'egat_id' => 'existing123', // ใช้ string
        'company' => 'EGAT',
        'department' => 'กผงค-ธ.',
        'position' => 'วศ.9',
        'email_verified_at' => $initialVerifiedAt, // กำหนดวันที่ verify เริ่มต้น
    ]);
    
    Log::info('สร้างผู้ใช้เดิมสำเร็จ', [
        'user_id' => $existingUser->id,
    ]);
    
    // สร้าง Mock SOAP Client
    $this->app->bind('nusoap_client', function () {
        $mock = new MockSoapClient('http://test.wsdl', true);
        $mock->addValidCredentials('existing123', 'newpassword');
        return $mock;
    });
    
    // ทดสอบล็อกอิน
    $response = $this->post('/loginEGAT', [
        'egatid' => 'existing123',
        'password' => 'newpassword'
    ]);
    
    // ตรวจสอบผลลัพธ์
    $response->assertRedirect('/dashboard');
    
    // ตรวจสอบข้อมูลผู้ใช้ที่อัปเดต
    $updatedUser = User::where('email', 'existing123@egat.co.th')->first();
    
    // ตรวจสอบข้อมูลที่อัปเดต
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->id)->toBe($existingUser->id);
    expect($updatedUser->department)->toBe('หผล11-ธ.'); // อัปเดตจาก Census
    expect($updatedUser->position)->toBe('ช.7'); // อัปเดตจาก Census
    
    Log::info('ข้อมูลผู้ใช้หลังอัปเดต', [
        'user_id' => $updatedUser->id,
        'department' => $updatedUser->department,
        'position' => $updatedUser->position
    ]);
    
    // ตรวจสอบว่าผู้ใช้ถูกล็อกอินแล้ว
    $this->assertAuthenticated();
    
    Log::info('ทดสอบการล็อกอิน EGAT กับผู้ใช้ที่มีอยู่แล้ว สำเร็จ');
});

test('การล็อกอิน EGAT ล้มเหลวเมื่อข้อมูลไม่ถูกต้อง', function () {
    Log::info('เริ่มทดสอบการล็อกอิน EGAT ล้มเหลว');
    
    // สร้าง Mock SOAP Client ที่ไม่มี credentials ที่ถูกต้อง
    $this->app->bind('nusoap_client', function () {
        $mock = new MockSoapClient('http://test.wsdl', true);
        // ไม่เพิ่ม valid credentials
        return $mock;
    });
    
    // ทดสอบล็อกอินด้วยข้อมูลที่ผิด
    $response = $this->post('/loginEGAT', [
        'egatid' => 'wronguser',
        'password' => 'wrongpass'
    ]);
    
    // ตรวจสอบว่ามี error
    $response->assertSessionHasErrors(['egatid']);
    
    // ตรวจสอบว่าผู้ใช้ไม่ถูกล็อกอิน
    $this->assertGuest();
    
    // ตรวจสอบว่าไม่มีผู้ใช้ถูกสร้าง
    $user = User::where('email', 'wronguser@egat.co.th')->first();
    expect($user)->toBeNull();
    
    Log::info('ทดสอบการล็อกอิน EGAT ล้มเหลว สำเร็จ');
});

test('การล็อกอิน EGAT ถูก rate limit เมื่อพยายามล็อกอินผิดหลายครั้ง', function () {
    Log::info('เริ่มทดสอบ Rate Limiting สำหรับ loginEGAT');
    
    // สร้าง Mock SOAP Client ที่ไม่มี credentials ที่ถูกต้อง
    $this->app->bind('nusoap_client', function () {
        $mock = new MockSoapClient('http://test.wsdl', true);
        return $mock;
    });
    
    // พยายามล็อกอินผิด 5 ครั้ง
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/loginEGAT', [
            'egatid' => 'testuser',
            'password' => 'wrongpass'
        ]);
        
        $response->assertSessionHasErrors(['egatid']);
    }
    
    // ครั้งที่ 6 ควรถูก rate limit
    $response = $this->post('/loginEGAT', [
        'egatid' => 'testuser',
        'password' => 'wrongpass'
    ]);
    
    $response->assertSessionHasErrors(['egatid']);
    
    // ตรวจสอบข้อความ rate limiting
    $errors = session('errors');
    expect($errors->get('egatid')[0])->toContain('Too many login attempts');
    
    Log::info('ทดสอบ Rate Limiting สำหรับ loginEGAT สำเร็จ');
});

test('การล็อกอิน EGAT สำเร็จโดยไม่มีข้อมูลใน Census', function () {
    Log::info('เริ่มทดสอบการล็อกอิน EGAT โดยไม่มีข้อมูลใน Census');
    
    // สร้าง Mock SOAP Client
    $this->app->bind('nusoap_client', function () {
        $mock = new MockSoapClient('http://test.wsdl', true);
        $mock->addValidCredentials('nocensus', 'password123');
        return $mock;
    });
    
    // ทดสอบล็อกอิน (ไม่มีข้อมูล Census)
    $response = $this->post('/loginEGAT', [
        'egatid' => 'nocensus',
        'password' => 'password123'
    ]);
    
    // ตรวจสอบผลลัพธ์
    $response->assertRedirect('/dashboard');
    
    // ตรวจสอบว่าผู้ใช้ถูกสร้างขึ้น
    $user = User::where('email', 'nocensus@egat.co.th')->first();
    expect($user)->not->toBeNull();
    expect($user->egat_id)->toBe('nocensus');
    expect($user->company)->toBe('EGAT');
    expect($user->department)->toBeNull(); // ไม่มีข้อมูลจาก Census
    expect($user->position)->toBeNull(); // ไม่มีข้อมูลจาก Census
    
    // ตรวจสอบว่าผู้ใช้ถูกล็อกอินแล้ว
    $this->assertAuthenticated();
    
    Log::info('ทดสอบการล็อกอิน EGAT โดยไม่มีข้อมูลใน Census สำเร็จ');
});

test('การล็อกอิน EGAT ต้องใช้ข้อมูลที่จำเป็น', function () {
    Log::info('เริ่มทดสอบการ validation ของ loginEGAT');
    
    // ทดสอบไม่ส่งข้อมูลใดๆ
    $response = $this->post('/loginEGAT', []);
    
    // ตรวจสอบว่ามี error ทั้งสองฟิลด์
    $response->assertSessionHasErrors(['egatid', 'password']);
    
    // ทดสอบส่งเฉพาะ egatid
    $response = $this->post('/loginEGAT', [
        'egatid' => 'testuser'
    ]);
    
    $response->assertSessionHasErrors(['password']);
    
    // ทดสอบส่งเฉพาะ password
    $response = $this->post('/loginEGAT', [
        'password' => 'testpass'
    ]);
    
    $response->assertSessionHasErrors(['egatid']);
    
    Log::info('ทดสอบการ validation ของ loginEGAT สำเร็จ');
});

test('การล็อกอิน EGAT ล้มเหลวเมื่อมีข้อผิดพลาดของ SOAP Client', function () {
    Log::info('เริ่มทดสอบการจัดการข้อผิดพลาดของ SOAP Client');
    
    // สร้าง Mock SOAP Client ที่ throw exception
    $this->app->bind('nusoap_client', function () {
        throw new \Exception('SOAP connection error');
    });
    
    // ทดสอบล็อกอิน
    $response = $this->post('/loginEGAT', [
        'egatid' => 'erroruser',
        'password' => 'password'
    ]);
    
    // ตรวจสอบว่ามี error
    $response->assertSessionHasErrors(['egatid']);
    
    // ตรวจสอบว่าผู้ใช้ไม่ถูกล็อกอิน
    $this->assertGuest();
    
    Log::info('ทดสอบการจัดการข้อผิดพลาดของ SOAP Client สำเร็จ');
});

test('สามารถล็อกเอาท์หลังจากล็อกอิน EGAT ได้', function () {
    Log::info('เริ่มทดสอบการล็อกเอาท์หลังจากล็อกอิน EGAT');
    
    // สร้างผู้ใช้และล็อกอิน
    $user = User::create([
        'name' => 'ผู้ใช้ ทดสอบ',
        'email' => 'testlogout@egat.co.th',
        'password' => Hash::make('password'),
        'egat_id' => 'testlogout',
        'company' => 'EGAT',
        'email_verified_at' => now(),
    ]);
    
    $this->actingAs($user);
    
    // ตรวจสอบว่าถูกล็อกอินแล้ว
    $this->assertAuthenticated();
    
    // ทดสอบล็อกเอาท์
    $response = $this->post('/logout');
    
    // ตรวจสอบผลลัพธ์
    $response->assertRedirect('/');
    
    // ตรวจสอบว่าถูกล็อกเอาท์แล้ว
    $this->assertGuest();
    
    Log::info('ทดสอบการล็อกเอาท์หลังจากล็อกอิน EGAT สำเร็จ');
});

test('สามารถเชื่อมต่อ testing_mmddata ได้', function () {
    Log::info('เริ่มทดสอบการเชื่อมต่อฐานข้อมูล testing_mmddata');
    
    // ทดสอบการเชื่อมต่อ
    expect(function () {
        return DB::connection('testing_mmddata')->getPdo();
    })->not->toThrow(\Exception::class);
    
    // สร้างตารางทดสอบ
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS test_table (
            id INTEGER PRIMARY KEY,
            name VARCHAR(255)
        )
    ');
    
    // เพิ่มข้อมูลทดสอบ
    DB::connection('testing_mmddata')->table('test_table')->insert([
        'name' => 'Test Record'
    ]);
    
    // ทดสอบดึงข้อมูล
    $record = DB::connection('testing_mmddata')->table('test_table')->first();
    expect($record->name)->toBe('Test Record');
    
    Log::info('ทดสอบการเชื่อมต่อฐานข้อมูล testing_mmddata สำเร็จ');
});

test('สามารถค้นหาข้อมูล Census ได้', function () {
    Log::info('เริ่มทดสอบการค้นหาข้อมูล Census');
    
    // เตรียมข้อมูลทดสอบ
    $testData = [
        [
            'EMPN' => 'EMP001',
            'TITLE' => 'นาย',
            'NAME' => 'ทดสอบ หนึ่ง',
            'emp_ename' => 'Mr. Test One',
            'fay' => 'อบค.',
            'gong' => 'กผงค-ธ.',
            'pnang' => 'หผค1-ธ.',
            'a_position' => 'วศ.8'
        ],
        [
            'EMPN' => 'EMP002',
            'TITLE' => 'นาง',
            'NAME' => 'ทดสอบ สอง',
            'emp_ename' => 'Mrs. Test Two',
            'fay' => 'อลก.',
            'gong' => 'กผล1-ธ.',
            'pnang' => 'หผล11-ธ.',
            'a_position' => 'ช.7'
        ]
    ];
    
    // สร้างข้อมูลในฐานข้อมูลทดสอบ
    $this->createMmddataTestData('view_census', $testData);
    
    // ทดสอบค้นหาข้อมูลคนแรก
    $census1 = DB::connection('testing_mmddata')
        ->table('view_census')
        ->where('EMPN', 'EMP001')
        ->first();
    
    expect($census1)->not->toBeNull();
    expect($census1->NAME)->toBe('ทดสอบ หนึ่ง');
    expect($census1->gong)->toBe('กผงค-ธ.');
    expect($census1->a_position)->toBe('วศ.8');
    
    // ทดสอบค้นหาข้อมูลคนที่สอง
    $census2 = DB::connection('testing_mmddata')
        ->table('view_census')
        ->where('EMPN', 'EMP002')
        ->first();
    
    expect($census2)->not->toBeNull();
    expect($census2->NAME)->toBe('ทดสอบ สอง');
    expect($census2->gong)->toBe('กผล1-ธ.');
    expect($census2->a_position)->toBe('ช.7');
    
    // ทดสอบค้นหาข้อมูลที่ไม่มี
    $nonExistent = DB::connection('testing_mmddata')
        ->table('view_census')
        ->where('EMPN', 'NOTFOUND')
        ->first();
    
    expect($nonExistent)->toBeNull();
    
    Log::info('ทดสอบการค้นหาข้อมูล Census สำเร็จ');
});
