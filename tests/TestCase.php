<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * กำหนด connections ที่จะใช้ transaction (เฉพาะ default connection)
     */
    protected $connectionsToTransact = ['sqlite'];

    /**
     * Setup การทดสอบ
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Log การเริ่มต้น test
        Log::channel('testing')->info('Setting up test case', [
            'test_class' => static::class,
            'default_connection' => DB::getDefaultConnection(),
            'available_connections' => array_keys(config('database.connections'))
        ]);
        
        // Setup testing_mmddata connection แยกต่างหาก
        $this->setupTestingMmddataConnection();
    }

    /**
     * Setup testing_mmddata connection แบบแยกจาก RefreshDatabase
     */
    protected function setupTestingMmddataConnection(): void
    {
        try {
            // กำหนดค่า connection testing_mmddata
            config(['database.connections.testing_mmddata' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);
            
            // Clear connection cache
            DB::purge('testing_mmddata');
            
            // ทดสอบการเชื่อมต่อ
            DB::connection('testing_mmddata')->getPdo();
            
            Log::channel('testing')->info('Testing mmddata connection established successfully');
        } catch (\Exception $e) {
            Log::channel('testing')->error('Failed to establish testing_mmddata connection', [
                'error' => $e->getMessage(),
                'connection_config' => config('database.connections.testing_mmddata')
            ]);
            
            throw $e;
        }
    }

    /**
     * ล้างข้อมูลหลังจาก test เสร็จ
     */
    protected function tearDown(): void
    {
        // ล้างข้อมูลใน testing_mmddata แบบ manual
        try {
            $tables = DB::connection('testing_mmddata')
                ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            
            foreach ($tables as $table) {
                DB::connection('testing_mmddata')->statement("DROP TABLE IF EXISTS {$table->name}");
            }
        } catch (\Exception $e) {
            // ไม่ต้องทำอะไรถ้า clear ไม่ได้
        }
        
        // Purge connection
        DB::purge('testing_mmddata');
        
        parent::tearDown();
    }

    /**
     * ใช้ mmddata connection สำหรับ testing
     */
    protected function useMmddataConnection(?string $model = null): mixed
    {
        if ($model !== null) {
            return $model::on('testing_mmddata');
        }
        
        return DB::connection('testing_mmddata');
    }

    /**
     * สร้างข้อมูลทดสอบใน mmddata connection
     */
    protected function createMmddataTestData(string $table, array $data): bool
    {
        try {
            foreach ($data as $record) {
                DB::connection('testing_mmddata')->table($table)->insert($record);
            }
            
            Log::channel('testing')->info('Test data created in mmddata connection', [
                'table' => $table,
                'records_count' => count($data)
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('testing')->error('Failed to create test data in mmddata connection', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * ล้างข้อมูลทดสอบ
     */
    protected function clearTestData(string $table, string $connection = 'testing_mmddata'): void
    {
        try {
            DB::connection($connection)->table($table)->delete();
            
            Log::channel('testing')->info('Test data cleared', [
                'table' => $table,
                'connection' => $connection
            ]);
        } catch (\Exception $e) {
            Log::channel('testing')->error('Failed to clear test data', [
                'table' => $table,
                'connection' => $connection,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * สร้าง view_census table สำหรับ testing
     */
    protected function createCensusTable(): void
    {
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
    }
}
