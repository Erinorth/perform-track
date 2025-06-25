<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\OrganizationalRisk;
use App\Models\OrganizationalRiskAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganizationalRiskTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function organizational_risk_has_many_attachments()
    {
        // สร้าง OrganizationalRisk
        $organizationalRisk = OrganizationalRisk::factory()->create();
        
        // สร้าง Attachments
        OrganizationalRiskAttachment::factory()
            ->count(3)
            ->create(['organizational_risk_id' => $organizationalRisk->id]);
        
        // ทดสอบ relationship - ใช้ attachments() ไม่ใช่ organizationalRiskAttachment()
        $this->assertCount(3, $organizationalRisk->attachments);
        $this->assertInstanceOf(
            OrganizationalRiskAttachment::class, 
            $organizationalRisk->attachments->first()
        );
    }

    /** @test */
    public function organizational_risk_can_access_attachments_relationship()
    {
        $organizationalRisk = OrganizationalRisk::factory()->create();
        
        // ทดสοบว่า relationship ทำงานได้
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $organizationalRisk->attachments()
        );
    }

    /** @test */
    public function organizational_risk_has_required_fillable_fields()
    {
        $organizationalRisk = new OrganizationalRisk();
        
        $expectedFillable = ['risk_name', 'description'];
        
        $this->assertEquals($expectedFillable, $organizationalRisk->getFillable());
    }

    /** @test */
    public function organizational_risk_uses_soft_deletes()
    {
        $organizationalRisk = OrganizationalRisk::factory()->create();
        
        // ลบแบบ soft delete
        $organizationalRisk->delete();
        
        // ควรยังอยู่ในฐานข้อมูล แต่ถูก mark ว่าถูกลบ
        $this->assertSoftDeleted($organizationalRisk);
    }

    /** @test */
    public function organizational_risk_has_many_division_risks()
    {
        $organizationalRisk = OrganizationalRisk::factory()->create();
        
        // ทดสอบ relationship กับ DivisionRisk
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $organizationalRisk->divisionRisks()
        );
    }
}
