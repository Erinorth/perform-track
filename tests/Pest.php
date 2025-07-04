<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// ใช้ TestCase สำหรับ Feature tests
uses(Tests\TestCase::class)->in('Feature');

// ใช้ RefreshDatabase สำหรับ Feature tests (จะ handle ใน TestCase แล้ว)
uses(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeEmpty', function () {
    return $this->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * สร้าง User สำหรับการทดสอบ
 */
function createTestUser(array $attributes = [])
{
    return \App\Models\User::factory()->create($attributes);
}

/**
 * สร้างข้อมูล Census สำหรับการทดสอบ (ใช้ผ่าน TestCase methods)
 */
function createTestCensus(array $attributes = [])
{
    $defaultAttributes = [
        'EMPN' => 'TEST001',
        'TITLE' => 'นาย',
        'NAME' => 'ทดสอบ ระบบ',
        'emp_ename' => 'Mr. Test System',
        'fay' => 'อบค.',
        'gong' => 'กผงค-ธ.',
        'pnang' => 'หผค1-ธ.',
        'a_position' => 'วศ.8'
    ];
    
    $data = array_merge($defaultAttributes, $attributes);
    
    // สร้าง table ถ้ายังไม่มี (อันนี้จะ handle ใน TestCase แล้ว)
    return $data;
}

/**
 * ล้างข้อมูลทดสอบ (ใช้ผ่าน TestCase methods)
 */
function clearTestData()
{
    // Method นี้จะ handle ใน TestCase tearDown แล้ว
    return true;
}
