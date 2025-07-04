<?php

use App\Models\User;
use App\Models\CenSus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\MockSoapClient;

// ทดสอบการเข้าถึงหน้า loginEGAT
test('สามารถเข้าถึงหน้า loginEGAT ได้', function () {
    Log::info('เริ่มทดสอบการเข้าถึงหน้า loginEGAT');
    
    $response = $this->get('/loginEGAT');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('auth/LoginEGAT')
            ->has('status')
    );
    
    Log::info('ทดสอบการเข้าถึงหน้า loginEGAT สำเร็จ');
});

// ทดสอบการเชื่อมต่อ testing_mmddata
test('สามารถเชื่อมต่อ testing_mmddata ได้', function () {
    Log::info('เริ่มทดสอบการเชื่อมต่อ testing_mmddata');
    
    // ทดสอบการเชื่อมต่อ
    $connection = DB::connection('testing_mmddata');
    expect($connection)->not->toBeNull();
    
    // ทดสอบการ query พื้นฐาน
    $result = $connection->select('SELECT 1 as test');
    expect($result)->not->toBeEmpty();
    expect($result[0]->test)->toBe(1);
    
    Log::info('ทดสอบการเชื่อมต่อ testing_mmddata สำเร็จ');
});

// ทดสอบการสร้างตาราง view_census ใน testing_mmddata
test('สามารถสร้างตาราง view_census ใน testing_mmddata ได้', function () {
    Log::info('เริ่มทดสอบการสร้างตาราง view_census');
    
    // สร้างตาราง view_census สำหรับทดสอบ
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS view_census (
            EMPN VARCHAR(20) PRIMARY KEY,
            TITLE VARCHAR(50),
            NAME VARCHAR(255),
            emp_ename VARCHAR(255),
            fay VARCHAR(50),
            gong VARCHAR(50),
            pnang VARCHAR(50),
            a_position VARCHAR(20)
        )
    ');
    
    // ทดสอบว่าตารางถูกสร้างขึ้นแล้ว
    $tables = DB::connection('testing_mmddata')->select(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='view_census'"
    );
    
    expect($tables)->not->toBeEmpty();
    expect($tables[0]->name)->toBe('view_census');
    
    Log::info('ทดสอบการสร้างตาราง view_census สำเร็จ');
});

// ทดสอบการสร้างข้อมูลทดสอบใน CenSus
test('สามารถสร้างข้อมูลทดสอบใน CenSus ได้', function () {
    Log::info('เริ่มทดสอบการสร้างข้อมูลทดสอบใน CenSus');
    
    // สร้างตาราง view_census
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS view_census (
            EMPN VARCHAR(20) PRIMARY KEY,
            TITLE VARCHAR(50),
            NAME VARCHAR(255),
            emp_ename VARCHAR(255),
            fay VARCHAR(50),
            gong VARCHAR(50),
            pnang VARCHAR(50),
            a_position VARCHAR(20)
        )
    ');
    
    // สร้างข้อมูลทดสอบ
    $testData = [
        'EMPN' => 'test123',
        'TITLE' => 'นาย',
        'NAME' => 'สมชาย ใจดี',
        'emp_ename' => 'Mr. Somchai Jaidee',
        'fay' => 'อบค.',
        'gong' => 'กผงค-ธ.',
        'pnang' => 'หผค1-ธ.',
        'a_position' => 'วศ.8'
    ];
    
    // บันทึกข้อมูลทดสอบ
    $this->createMmddataTestData('view_census', [$testData]);
    
    // ทดสอบการดึงข้อมูลผ่าน CenSus model
    $census = CenSus::on('testing_mmddata')->where('EMPN', 'test123')->first();
    
    expect($census)->not->toBeNull();
    expect($census->EMPN)->toBe('test123');
    expect($census->NAME)->toBe('สมชาย ใจดี');
    expect($census->fay)->toBe('อบค.');
    expect($census->a_position)->toBe('วศ.8');
    
    Log::info('ทดสอบการสร้างข้อมูลทดสอบใน CenSus สำเร็จ', [
        'census_id' => $census->EMPN,
        'census_name' => $census->NAME
    ]);
});

// ทดสอบการค้นหาพนักงานด้วย findByEgatId
test('สามารถค้นหาพนักงานด้วย findByEgatId ได้', function () {
    Log::info('เริ่มทดสอบการค้นหาพนักงานด้วย findByEgatId');
    
    // สร้างตาราง view_census
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS view_census (
            EMPN VARCHAR(20) PRIMARY KEY,
            TITLE VARCHAR(50),
            NAME VARCHAR(255),
            emp_ename VARCHAR(255),
            fay VARCHAR(50),
            gong VARCHAR(50),
            pnang VARCHAR(50),
            a_position VARCHAR(20)
        )
    ');
    
    // สร้างข้อมูลทดสอบ
    $testData = [
        'EMPN' => 'egat001',
        'TITLE' => 'นาย',
        'NAME' => 'วิชัย สุขสันต์',
        'emp_ename' => 'Mr. Wichai Suksan',
        'fay' => 'อลก.',
        'gong' => 'กผล1-ธ.',
        'pnang' => 'หผล11-ธ.',
        'a_position' => 'ช.7'
    ];
    
    $this->createMmddataTestData('view_census', [$testData]);
    
    // ทดสอบการค้นหาพนักงาน
    $employee = CenSus::findByEgatId('egat001');
    
    expect($employee)->not->toBeNull();
    expect($employee->EMPN)->toBe('egat001');
    expect($employee->NAME)->toBe('วิชัย สุขสันต์');
    expect($employee->fay)->toBe('อลก.');
    expect($employee->a_position)->toBe('ช.7');
    
    // ทดสอบการค้นหาพนักงานที่ไม่มีอยู่
    $nonExistentEmployee = CenSus::findByEgatId('notfound');
    expect($nonExistentEmployee)->toBeNull();
    
    Log::info('ทดสอบการค้นหาพนักงานด้วย findByEgatId สำเร็จ');
});

// ทดสอบการใช้ CenSus Factory สำหรับสร้างข้อมูลทดสอบ
test('สามารถใช้ CenSus Factory สำหรับสร้างข้อมูลทดสอบได้', function () {
    Log::info('เริ่มทดสอบการใช้ CenSus Factory');
    
    // สร้างตาราง view_census
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS view_census (
            EMPN VARCHAR(20) PRIMARY KEY,
            TITLE VARCHAR(50),
            NAME VARCHAR(255),
            emp_ename VARCHAR(255),
            fay VARCHAR(50),
            gong VARCHAR(50),
            pnang VARCHAR(50),
            a_position VARCHAR(20)
        )
    ');
    
    // สร้างข้อมูลทดสอบด้วย Factory
    $employees = CenSus::factory()->count(5)->create();
    
    expect($employees)->toHaveCount(5);
    
    // ทดสอบการดึงข้อมูลจากฐานข้อมูล
    $dbEmployees = CenSus::on('testing_mmddata')->get();
    expect($dbEmployees)->toHaveCount(5);
    
    // ทดสอบโครงสร้างข้อมูล
    $firstEmployee = $dbEmployees->first();
    expect($firstEmployee->EMPN)->not->toBeNull();
    expect($firstEmployee->NAME)->not->toBeNull();
    expect($firstEmployee->fay)->not->toBeNull();
    expect($firstEmployee->a_position)->not->toBeNull();
    
    Log::info('ทดสอบการใช้ CenSus Factory สำเร็จ', [
        'employees_count' => $dbEmployees->count(),
        'first_employee' => $firstEmployee->EMPN
    ]);
});

// ทดสอบการสร้างโครงสร้างองค์กรด้วย Factory
test('สามารถสร้างโครงสร้างองค์กรด้วย Factory ได้', function () {
    Log::info('เริ่มทดสอบการสร้างโครงสร้างองค์กรด้วย Factory');
    
    // สร้างตาราง view_census
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS view_census (
            EMPN VARCHAR(20) PRIMARY KEY,
            TITLE VARCHAR(50),
            NAME VARCHAR(255),
            emp_ename VARCHAR(255),
            fay VARCHAR(50),
            gong VARCHAR(50),
            pnang VARCHAR(50),
            a_position VARCHAR(20)
        )
    ');
    
    // สร้างโครงสร้างองค์กรสำหรับฝ่ายเดียว
    $hierarchyData = CenSus::createSingleDivisionHierarchy('อบค.');
    
    // บันทึกข้อมูลลงฐานข้อมูล
    foreach ($hierarchyData as $employee) {
        DB::connection('testing_mmddata')->table('view_census')->insert([
            'EMPN' => $employee->EMPN,
            'TITLE' => $employee->TITLE,
            'NAME' => $employee->NAME,
            'emp_ename' => $employee->emp_ename,
            'fay' => $employee->fay,
            'gong' => $employee->gong,
            'pnang' => $employee->pnang,
            'a_position' => $employee->a_position,
        ]);
    }
    
    // ทดสอบการดึงข้อมูลจากฐานข้อมูล
    $director = CenSus::on('testing_mmddata')->where('a_position', 'อ')->first();
    expect($director)->not->toBeNull();
    expect($director->fay)->toBe('อบค.');
    
    $chiefs = CenSus::on('testing_mmddata')->where('a_position', 'ก')->get();
    expect($chiefs)->toHaveCount(5); // ตามโครงสร้างที่กำหนด
    
    $heads = CenSus::on('testing_mmddata')->where('a_position', 'ห')->get();
    expect($heads->count())->toBeGreaterThan(0);
    
    Log::info('ทดสอบการสร้างโครงสร้างองค์กรด้วย Factory สำเร็จ', [
        'director_count' => CenSus::on('testing_mmddata')->where('a_position', 'อ')->count(),
        'chief_count' => $chiefs->count(),
        'head_count' => $heads->count()
    ]);
});

// ทดสอบการทำงานของ CenSus model กับ testing_mmddata connection
test('CenSus model สามารถทำงานกับ testing_mmddata connection ได้', function () {
    Log::info('เริ่มทดสอบการทำงานของ CenSus model กับ testing_mmddata connection');
    
    // สร้างตาราง view_census
    DB::connection('testing_mmddata')->statement('
        CREATE TABLE IF NOT EXISTS view_census (
            EMPN VARCHAR(20) PRIMARY KEY,
            TITLE VARCHAR(50),
            NAME VARCHAR(255),
            emp_ename VARCHAR(255),
            fay VARCHAR(50),
            gong VARCHAR(50),
            pnang VARCHAR(50),
            a_position VARCHAR(20)
        )
    ');
    
    // สร้างข้อมูลทดสอบ
    $testData = [
        [
            'EMPN' => 'director001',
            'TITLE' => 'นาย',
            'NAME' => 'ผู้อำนวยการ ทดสอบ',
            'emp_ename' => 'Mr. Director Test',
            'fay' => 'อบค.',
            'gong' => '',
            'pnang' => '',
            'a_position' => 'อ'
        ],
        [
            'EMPN' => 'chief001',
            'TITLE' => 'นาย',
            'NAME' => 'หัวหน้ากอง ทดสอบ',
            'emp_ename' => 'Mr. Chief Test',
            'fay' => 'อบค.',
            'gong' => 'กผงค-ธ.',
            'pnang' => '',
            'a_position' => 'ก'
        ],
        [
            'EMPN' => 'head001',
            'TITLE' => 'นาย',
            'NAME' => 'หัวหน้าแผนก ทดสอบ',
            'emp_ename' => 'Mr. Head Test',
            'fay' => 'อบค.',
            'gong' => 'กผงค-ธ.',
            'pnang' => 'หผค1-ธ.',
            'a_position' => 'ห'
        ]
    ];
    
    $this->createMmddataTestData('view_census', $testData);
    
    // ทดสอบ scope methods
    $directors = CenSus::on('testing_mmddata')->directors()->get();
    expect($directors)->toHaveCount(1);
    expect($directors->first()->a_position)->toBe('อ');
    
    $chiefs = CenSus::on('testing_mmddata')->chiefs()->get();
    expect($chiefs)->toHaveCount(1);
    expect($chiefs->first()->a_position)->toBe('ก');
    
    $heads = CenSus::on('testing_mmddata')->heads()->get();
    expect($heads)->toHaveCount(1);
    expect($heads->first()->a_position)->toBe('ห');
    
    // ทดสอบการค้นหาตามฝ่าย
    $divisionEmployees = CenSus::on('testing_mmddata')->byDivision('อบค.')->get();
    expect($divisionEmployees)->toHaveCount(3);
    
    Log::info('ทดสอบการทำงานของ CenSus model กับ testing_mmddata connection สำเร็จ', [
        'directors_count' => $directors->count(),
        'chiefs_count' => $chiefs->count(),
        'heads_count' => $heads->count(),
        'division_employees_count' => $divisionEmployees->count()
    ]);
});

// ทดสอบการตั้งค่า connection ใน CenSus model
test('CenSus model ใช้ connection ที่ถูกต้องตาม environment', function () {
    Log::info('เริ่มทดสอบการตั้งค่า connection ใน CenSus model');
    
    // ทดสอบในสภาพแวดล้อม testing
    $census = new CenSus();
    $connection = $census->getConnection();
    
    expect($connection->getName())->toBe('testing_mmddata');
    
    Log::info('ทดสอบการตั้งค่า connection ใน CenSus model สำเร็จ', [
        'connection_name' => $connection->getName()
    ]);
});
