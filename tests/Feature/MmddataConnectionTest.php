<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
#[Group('mmddata')]
class MmddataConnectionTest extends TestCase
{
    /**
     * ทดสอบการเชื่อมต่อ testing_mmddata
     * แก้ไข: ใช้ PHPUnit Attributes แทน doc-comment
     */
    #[Test]
    public function it_can_connect_to_testing_mmddata_database(): void
    {
        // ทดสอบการเชื่อมต่อ
        $connection = DB::connection('testing_mmddata');
        
        $this->assertNotNull($connection->getPdo());
        
        // ตรวจสอบ driver ที่ใช้
        $this->assertEquals('sqlite', $connection->getDriverName());
        
        Log::channel('testing')->info('Testing mmddata connection test passed');
    }

    /**
     * ทดสอบการสร้างและดึงข้อมูลจาก testing_mmddata
     * แก้ไข: ใช้ PHPUnit Attributes แทน doc-comment
     */
    #[Test]
    public function it_can_create_and_retrieve_data_from_testing_mmddata(): void
    {
        // สร้างตารางทดสอบ
        DB::connection('testing_mmddata')->statement('
            CREATE TABLE IF NOT EXISTS test_table (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // สร้างข้อมูลทดสอบ
        $testData = [
            ['name' => 'Test Record 1'],
            ['name' => 'Test Record 2']
        ];

        $this->assertTrue($this->createMmddataTestData('test_table', $testData));

        // ดึงข้อมูลและตรวจสอบ
        $results = DB::connection('testing_mmddata')
            ->table('test_table')
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Test Record 1', $results->first()->name);
        
        Log::channel('testing')->info('Testing mmddata CRUD operations test passed');
    }

    /**
     * ทดสอบการใช้งาน useMmddataConnection method
     */
    #[Test]
    public function it_can_use_mmddata_connection_helper(): void
    {
        // ทดสอบการเรียกใช้โดยไม่ส่ง parameter
        $connection = $this->useMmddataConnection();
        $this->assertNotNull($connection);
        $this->assertEquals('testing_mmddata', $connection->getName());

        // ทดสอบการเรียกใช้โดยส่ง null
        $connectionWithNull = $this->useMmddataConnection(null);
        $this->assertNotNull($connectionWithNull);
        $this->assertEquals('testing_mmddata', $connectionWithNull->getName());
        
        Log::channel('testing')->info('useMmddataConnection helper test passed');
    }

    /**
     * ทดสอบการจัดการ transaction ใน testing_mmddata
     */
    #[Test]
    public function it_can_handle_transactions_in_testing_mmddata(): void
    {
        // สร้างตารางทดสอบ
        DB::connection('testing_mmddata')->statement('
            CREATE TABLE IF NOT EXISTS transaction_test (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                value TEXT NOT NULL
            )
        ');

        // ทดสอบ transaction ที่สำเร็จ
        DB::connection('testing_mmddata')->transaction(function () {
            DB::connection('testing_mmddata')->table('transaction_test')->insert([
                ['value' => 'committed_data']
            ]);
        });

        $committedCount = DB::connection('testing_mmddata')
            ->table('transaction_test')
            ->where('value', 'committed_data')
            ->count();

        $this->assertEquals(1, $committedCount);

        // ทดสอบ transaction ที่ rollback
        try {
            DB::connection('testing_mmddata')->transaction(function () {
                DB::connection('testing_mmddata')->table('transaction_test')->insert([
                    ['value' => 'rollback_data']
                ]);
                
                // สร้าง exception เพื่อทำให้ rollback
                throw new \Exception('Intentional rollback');
            });
        } catch (\Exception $e) {
            // คาดหวังให้เกิด exception
        }

        $rollbackCount = DB::connection('testing_mmddata')
            ->table('transaction_test')
            ->where('value', 'rollback_data')
            ->count();

        $this->assertEquals(0, $rollbackCount);
        
        Log::channel('testing')->info('Transaction handling test passed');
    }
}
