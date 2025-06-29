<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ข้อมูลตัวอย่างบริษัทใน กฟผ.
        $companies = [
            'การไฟฟ้าฝ่ายผลิตแห่งประเทศไทย',
            'บริษัท ผลิตไฟฟ้า จำกัด',
            'บริษัท ผลิตไฟฟ้าชุมชน จำกัด',
        ];

        // ข้อมูลตัวอย่างหน่วยงาน
        $departments = [
            'ฝ่ายวิศวกรรม',
            'ฝ่ายปฏิบัติการ',
            'ฝ่ายบริหาร',
            'ฝ่ายเทคโนโลยีสารสนเทศ',
            'ฝ่ายการเงิน',
            'ฝ่ายทรัพยากรบุคคล',
            'ฝ่ายจัดซื้อ',
            'ฝ่ายความปลอดภัย',
        ];

        // ข้อมูลตัวอย่างตำแหน่ง
        $positions = [
            'วิศวกร',
            'วิศวกรอาวุโส',
            'หัวหน้างาน',
            'ผู้จัดการ',
            'ผู้อำนวยการ',
            'เจ้าหน้าที่',
            'เจ้าหน้าที่อาวุโส',
            'นักวิเคราะห์',
            'ผู้เชี่ยวชาญ',
        ];

        return [
            'egat_id' => $this->faker->unique()->numberBetween(10000, 99999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'company' => $this->faker->randomElement($companies),
            'department' => $this->faker->randomElement($departments),
            'position' => $this->faker->randomElement($positions),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * สร้าง user สำหรับบริษัทเฉพาะ
     */
    public function forCompany(string $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => $company,
        ]);
    }

    /**
     * สร้าง user สำหรับหน่วยงานเฉพาะ
     */
    public function forDepartment(string $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => $department,
        ]);
    }

    /**
     * สร้าง user สำหรับตำแหน่งเฉพาะ
     */
    public function forPosition(string $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
