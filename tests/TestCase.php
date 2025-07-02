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
     * กำหนดการเชื่อมต่อฐานข้อมูลที่จะใช้ในการทดสอบ
     * 
     * @var array
     */
    protected $connectionsToTransact = ['sqlite', 'testing_mmddata'];

    /**
     * การตั้งค่าเริ่มต้นก่อนการทดสอบแต่ละรอบ
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Log เพื่อติดตามการตั้งค่า testing
        Log::channel('testing')->info('Setting up test case', [
            'test_class' => static::class,
            'default_connection' => DB::getDefaultConnection(),
            'available_connections' => array_keys(config('database.connections'))
        ]);
        
        // ตรวจสอบการเชื่อมต่อ testing_mmddata
        $this->ensureTestingMmddataConnection();
    }

    /**
     * ตรวจสอบและตั้งค่าการเชื่อมต่อ testing_mmddata
     */
    protected function ensureTestingMmddataConnection(): void
    {
        try {
            // ทดสอบการเชื่อมต่อ testing_mmddata
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
     * ใช้การเชื่อมต่อ testing_mmddata สำหรับ model หรือ query
     * แก้ไข: กำหนด nullable type อย่างชัดเจน
     * 
     * @param string|null $model
     * @return mixed
     */
    protected function useMmddataConnection(?string $model = null): mixed
    {
        if ($model !== null) {
            return $model::on('testing_mmddata');
        }
        
        return DB::connection('testing_mmddata');
    }

    /**
     * สร้างข้อมูลทดสอบใน testing_mmddata connection
     * 
     * @param string $table
     * @param array $data
     * @return bool
     */
    protected function createMmddataTestData(string $table, array $data): bool
    {
        try {
            DB::connection('testing_mmddata')->table($table)->insert($data);
            
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
     * ล้างข้อมูลทดสอบจากตารางที่กำหนด
     * 
     * @param string $table
     * @param string $connection
     * @return void
     */
    protected function clearTestData(string $table, string $connection = 'testing_mmddata'): void
    {
        try {
            DB::connection($connection)->table($table)->truncate();
            
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
}
