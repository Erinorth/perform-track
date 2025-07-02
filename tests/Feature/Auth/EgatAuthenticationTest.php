<?php

use App\Models\User;
use App\Models\CenSus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

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
