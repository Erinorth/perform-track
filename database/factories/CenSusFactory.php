<?php

namespace Database\Factories;

use App\Models\CenSus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * Factory สำหรับสร้างข้อมูล CenSus ทดสอบ
 */
class CenSusFactory extends Factory
{
    protected $model = CenSus::class;

    /**
     * โครงสร้างองค์กร
     */
    private static array $divisionStructure = [
        'อบค.' => [
            'departments' => ['กผงค-ธ.', 'กกห-ธ.', 'กกอ-ธ.', 'กมน-ธ.', 'กสร-ธ.'],
            'sections_map' => [
                'กผงค-ธ.' => ['หผค1-ธ.', 'หผค2-ธ.', 'หผค3-ธ.'],
                'กกห-ธ.' => ['หกห1-ธ.', 'หกห2-ธ.', 'หกห3-ธ.'],
                'กกอ-ธ.' => ['หกอ1-ธ.', 'หกอ2-ธ.', 'หกอ3-ธ.'],
                'กมน-ธ.' => ['หมน1-ธ.', 'หมน2-ธ.', 'หมน3-ธ.'],
                'กสร-ธ.' => ['หสร1-ธ.', 'หสร2-ธ.', 'หสร3-ธ.'],
            ]
        ],
        'ผลก.' => [
            'departments' => ['กผล1-ธ.', 'กผล2-ธ.', 'กผล3-ธ.', 'กผล4-ธ.', 'กคผล-ธ.'],
            'sections_map' => [
                'กผล1-ธ.' => ['หผล11-ธ.', 'หผล12-ธ.', 'หผล13-ธ.'],
                'กผล2-ธ.' => ['หผล21-ธ.', 'หผล22-ธ.', 'หผล23-ธ.'],
                'กผล3-ธ.' => ['หผล31-ธ.', 'หผล32-ธ.', 'หผล33-ธ.'],
                'กผล4-ธ.' => ['หผล41-ธ.', 'หผล42-ธ.', 'หผล43-ธ.'],
                'กคผล-ธ.' => ['หคผล1-ธ.', 'หคผล2-ธ.', 'หคผล3-ธ.'],
            ]
        ],
        'วศก.' => [
            'departments' => ['กวศ1-ธ.', 'กวศ2-ธ.', 'กวศ3-ธ.', 'กวศ4-ธ.', 'กวศส-ธ.'],
            'sections_map' => [
                'กวศ1-ธ.' => ['หวศ11-ธ.', 'หวศ12-ธ.', 'หวศ13-ธ.'],
                'กวศ2-ธ.' => ['หวศ21-ธ.', 'หวศ22-ธ.', 'หวศ23-ธ.'],
                'กวศ3-ธ.' => ['หวศ31-ธ.', 'หวศ32-ธ.', 'หวศ33-ธ.'],
                'กวศ4-ธ.' => ['หวศ41-ธ.', 'หวศ42-ธ.', 'หวศ43-ธ.'],
                'กวศส-ธ.' => ['หวศส1-ธ.', 'หวศส2-ธ.', 'หวศส3-ธ.'],
            ]
        ],
        'บคจ.' => [
            'departments' => ['กบค-ธ.', 'กบจ-ธ.', 'กสท-ธ.', 'กคจ-ธ.', 'กวจ-ธ.'],
            'sections_map' => [
                'กบค-ธ.' => ['หบค1-ธ.', 'หบค2-ธ.', 'หบค3-ธ.'],
                'กบจ-ธ.' => ['หบจ1-ธ.', 'หบจ2-ธ.', 'หบจ3-ธ.'],
                'กสท-ธ.' => ['หสท1-ธ.', 'หสท2-ธ.', 'หสท3-ธ.'],
                'กคจ-ธ.' => ['หคจ1-ธ.', 'หคจ2-ธ.', 'หคจ3-ธ.'],
                'กวจ-ธ.' => ['หวจ1-ธ.', 'หวจ2-ธ.', 'หวจ3-ธ.'],
            ]
        ]
    ];

    /**
     * กำหนดค่าเริ่มต้นของ Model
     */
    public function definition(): array
    {
        if (!app()->environment('testing')) {
            throw new \Exception('CenSus Factory ใช้ได้เฉพาะในการทดสอบเท่านั้น');
        }

        $isMale = $this->faker->boolean(60);
        
        return [
            'EMPN' => $this->generateEmployeeId(),
            'TITLE' => $this->getRandomTitle($isMale),
            'NAME' => $this->getRandomThaiName($isMale),
            'emp_ename' => $this->getRandomEnglishName($isMale),
            'fay' => $this->getRandomDivision(),
            'gong' => $this->getRandomDepartment(),
            'pnang' => $this->getRandomSection(),
            'a_position' => $this->getRandomPosition(),
        ];
    }

    // === Hierarchy Factory Methods ===

    /**
     * สร้าง hierarchy สำหรับทั้งองค์กร
     */
    public static function createHierarchy(): Collection
    {
        $allEmployees = collect();
        
        foreach (self::$divisionStructure as $division => $structure) {
            $divisionEmployees = self::createDivisionHierarchy($division, $structure);
            $allEmployees = $allEmployees->merge($divisionEmployees);
        }
        
        return $allEmployees;
    }

    /**
     * สร้าง hierarchy สำหรับฝ่ายเดียว
     */
    private static function createDivisionHierarchy(string $division, array $structure): Collection
    {
        $employees = collect();
        
        // 1. สร้างผู้อำนวยการฝ่าย (อ) 1 คน
        $director = self::new()->make([
            'a_position' => 'อ',
            'fay' => $division,
            'gong' => '', // ผู้อำนวยการไม่มีกอง
            'pnang' => '', // ผู้อำนวยการไม่มีแผนก
        ]);
        $employees->push($director);
        
        // 2. สร้างผู้ช่วยผู้อำนวยการฝ่าย (ช.อ) 2 คน
        for ($i = 0; $i < 2; $i++) {
            $assistantDirector = self::new()->make([
                'a_position' => 'ช.อ',
                'fay' => $division,
                'gong' => '', // ผู้ช่วยผู้อำนวยการไม่มีกอง
                'pnang' => '', // ผู้ช่วยผู้อำนวยการไม่มีแผนก
            ]);
            $employees->push($assistantDirector);
        }
        
        // 3. สร้างหัวหน้ากอง (ก) ฝ่ายละ 5 คน
        foreach ($structure['departments'] as $department) {
            $departmentHead = self::new()->make([
                'a_position' => 'ก',
                'fay' => $division,
                'gong' => $department,
                'pnang' => '', // หัวหน้ากองไม่มีแผนก
            ]);
            $employees->push($departmentHead);
            
            // 4. สร้างหัวหน้าแผนก (ห) กองละ 3 คน
            $sections = $structure['sections_map'][$department];
            foreach ($sections as $section) {
                $sectionHead = self::new()->make([
                    'a_position' => 'ห',
                    'fay' => $division,
                    'gong' => $department,
                    'pnang' => $section,
                ]);
                $employees->push($sectionHead);
                
                // 5. สร้างผู้ปฏิบัติงาน แผนกละ 5 คน
                for ($i = 0; $i < 5; $i++) {
                    $worker = self::new()->make([
                        'a_position' => fake()->randomElement(['วศ.8', 'วศ.7', 'ช.7', 'ช.6', 'ชก.3', 'พช.6']),
                        'fay' => $division,
                        'gong' => $department,
                        'pnang' => $section,
                    ]);
                    $employees->push($worker);
                }
            }
        }
        
        return $employees;
    }

    /**
     * สร้าง hierarchy สำหรับฝ่ายเดียว
     */
    public static function createSingleDivisionHierarchy(string $division = 'อบค.'): Collection
    {
        if (!isset(self::$divisionStructure[$division])) {
            throw new \InvalidArgumentException("Division {$division} not found in structure");
        }
        
        return self::createDivisionHierarchy($division, self::$divisionStructure[$division]);
    }

    /**
     * สร้าง hierarchy แบบปรับแต่งได้
     */
    public static function createCustomHierarchy(array $config): Collection
    {
        $employees = collect();
        
        $division = $config['division'] ?? 'อบค.';
        $departments = $config['departments'] ?? ['กผงค-ธ.'];
        $sectionsPerDept = $config['sections_per_department'] ?? 3;
        $workersPerSection = $config['workers_per_section'] ?? 5;
        
        // ผู้อำนวยการฝ่าย
        $director = self::new()->make([
            'a_position' => 'อ',
            'fay' => $division,
            'gong' => '',
            'pnang' => '',
        ]);
        $employees->push($director);
        
        // ผู้ช่วยผู้อำนวยการฝ่าย
        for ($i = 0; $i < 2; $i++) {
            $assistantDirector = self::new()->make([
                'a_position' => 'ช.อ',
                'fay' => $division,
                'gong' => '',
                'pnang' => '',
            ]);
            $employees->push($assistantDirector);
        }
        
        // หัวหน้ากองและลูกข้าง
        foreach ($departments as $deptIndex => $department) {
            // หัวหน้ากอง
            $departmentHead = self::new()->make([
                'a_position' => 'ก',
                'fay' => $division,
                'gong' => $department,
                'pnang' => '',
            ]);
            $employees->push($departmentHead);
            
            // หัวหน้าแผนก
            for ($sectionIndex = 1; $sectionIndex <= $sectionsPerDept; $sectionIndex++) {
                $section = "ห{$department}{$sectionIndex}.";
                
                $sectionHead = self::new()->make([
                    'a_position' => 'ห',
                    'fay' => $division,
                    'gong' => $department,
                    'pnang' => $section,
                ]);
                $employees->push($sectionHead);
                
                // ผู้ปฏิบัติงาน
                for ($workerIndex = 0; $workerIndex < $workersPerSection; $workerIndex++) {
                    $worker = self::new()->make([
                        'a_position' => fake()->randomElement(['วศ.8', 'วศ.7', 'ช.7', 'ช.6', 'ชก.3', 'พช.6']),
                        'fay' => $division,
                        'gong' => $department,
                        'pnang' => $section,
                    ]);
                    $employees->push($worker);
                }
            }
        }
        
        return $employees;
    }

    // === Private Helper Methods ===

    private function generateEmployeeId(): string
    {
        return (string) $this->faker->unique()->numberBetween(450000, 600000);
    }

    private function getRandomTitle(bool $isMale): string
    {
        if ($isMale) {
            return $this->faker->randomElement(['นาย', 'Mr.', 'วาที่ ร.ต.']);
        } else {
            return $this->faker->randomElement(['นาง', 'น.ส.', 'Mrs.', 'Miss']);
        }
    }

    private function getRandomThaiName(bool $isMale): string
    {
        $maleNames = [
            'สมชาย ใจดี', 'วิชัย สุขสันต์', 'ธนวัฒน์ รุ่งเรือง', 'ประเวศ มั่นคง', 
            'สมศักดิ์ เจริญรัตน์', 'อนุชา ทองดี', 'ธีระวัฒน์ แสงทอง', 'ชัยพร บุญมาก',
            'วิทยา พันธุ์ดี', 'สุเทพ ศรีสุข', 'ปิยะ เพชรเงิน', 'ธนากร อินทร์แท้'
        ];
        
        $femaleNames = [
            'สมใส ใจดี', 'วิภาวี สุขสันต์', 'ธนิดา รุ่งเรือง', 'ประภัสสร มั่นคง',
            'สุดาพร เจริญรัตน์', 'อรุณี ทองดี', 'ธีรดา แสงทอง', 'ชวีวรรณ บุญมาก',
            'วิมลพร พันธุ์ดี', 'สุธีรา ศรีสุข', 'ปิยนุช เพชรเงิน', 'ธนัญญา อินทร์แท้'
        ];
        
        return $isMale 
            ? $this->faker->randomElement($maleNames)
            : $this->faker->randomElement($femaleNames);
    }

    private function getRandomEnglishName(bool $isMale): string
    {
        $title = $isMale ? 'Mr.' : $this->faker->randomElement(['Mrs.', 'Miss']);
        return $title . ' ' . $this->faker->firstName() . ' ' . $this->faker->lastName();
    }

    private function getRandomDivision(): string
    {
        return $this->faker->randomElement(array_keys(self::$divisionStructure));
    }

    private function getRandomDepartment(): string
    {
        $division = $this->getRandomDivision();
        return $this->faker->randomElement(self::$divisionStructure[$division]['departments']);
    }

    private function getRandomSection(): string
    {
        $division = $this->getRandomDivision();
        $department = $this->faker->randomElement(self::$divisionStructure[$division]['departments']);
        return $this->faker->randomElement(self::$divisionStructure[$division]['sections_map'][$department]);
    }

    private function getRandomPosition(): string
    {
        return $this->faker->randomElement(['วศ.8', 'วศ.7', 'ช.7', 'ช.6', 'ชก.3', 'พช.6']);
    }

    // === State Methods ===

    public function director(): static
    {
        return $this->state(['a_position' => $this->faker->randomElement(['อ', 'ช.อ'])]);
    }

    public function chief(): static
    {
        return $this->state(['a_position' => 'ก']);
    }

    public function head(): static
    {
        return $this->state(['a_position' => 'ห']);
    }

    public function engineer(): static
    {
        return $this->state(['a_position' => $this->faker->randomElement(['วศ.9', 'วศ.8', 'วศ.7'])]);
    }

    public function technician(): static
    {
        return $this->state(['a_position' => $this->faker->randomElement(['ช.8', 'ช.7', 'ช.6'])]);
    }

    public function male(): static
    {
        return $this->state([
            'TITLE' => 'นาย',
            'NAME' => 'สมชาย ใจดี',
            'emp_ename' => 'Mr. John Doe'
        ]);
    }

    public function female(): static
    {
        return $this->state([
            'TITLE' => 'น.ส.',
            'NAME' => 'สมใส ใจดี',
            'emp_ename' => 'Miss Jane Doe'
        ]);
    }

    /**
     * รับข้อมูลโครงสร้างฝ่าย
     */
    public static function getDivisionStructure(): array
    {
        return self::$divisionStructure;
    }
}
