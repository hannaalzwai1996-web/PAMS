<?php

use App\Domain\Program\Models\Program;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PermissionSeeder::class]);
});

$reportTypes = ['specification', 'objectives', 'learning-outcomes', 'matrix'];

foreach ($reportTypes as $reportType) {
    test("an admin can download the {$reportType} report as PDF", function () use ($reportType) {
        $program = Program::factory()->create();
        seedMatrixFixtures($program);

        $response = $this->actingAs(makeAdmin())
            ->get("/api/v1/programs/{$program->id}/reports/{$reportType}/pdf");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('application/pdf');
        expect(strlen($response->getContent()))->toBeGreaterThan(100);
    });

    test("an admin can download the {$reportType} report as Excel", function () use ($reportType) {
        $program = Program::factory()->create();
        seedMatrixFixtures($program);

        $response = $this->actingAs(makeAdmin())
            ->get("/api/v1/programs/{$program->id}/reports/{$reportType}/excel");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('spreadsheetml');
    });
}

test('the objectives Excel report contains the actual objective data', function () {
    $program = Program::factory()->create();
    ['overlapping' => $peo1] = seedMatrixFixtures($program);

    $response = $this->actingAs(makeAdmin())
        ->get("/api/v1/programs/{$program->id}/reports/objectives/excel");

    $path = tempnam(sys_get_temp_dir(), 'pams-report').'.xlsx';
    file_put_contents($path, $response->streamedContent() ?: $response->getContent());

    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();

    expect($sheet->getCell('A1')->getValue())->toBe('Code');
    expect($sheet->getCell('A2')->getValue())->toBe($peo1->code);
    expect($sheet->getCell('B2')->getValue())->toBe($peo1->statement);

    unlink($path);
});

test('a coordinator not assigned to the program is forbidden from its reports', function () {
    $program = Program::factory()->create();
    $outsider = User::factory()->create();
    $outsider->assignRole('program_coordinator');

    $this->actingAs($outsider)
        ->get("/api/v1/programs/{$program->id}/reports/specification/pdf")
        ->assertForbidden();
});

test('an assigned coordinator can download reports for their own program', function () {
    $program = Program::factory()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->get("/api/v1/programs/{$program->id}/reports/matrix/pdf")
        ->assertOk();
});

test('an invalid format segment 404s', function () {
    $program = Program::factory()->create();

    $this->actingAs(makeAdmin())
        ->get("/api/v1/programs/{$program->id}/reports/specification/csv")
        ->assertNotFound();
});

test('an unauthenticated request is rejected', function () {
    $program = Program::factory()->create();

    $this->get("/api/v1/programs/{$program->id}/reports/specification/pdf")
        ->assertUnauthorized();
});

test('reports remain accessible regardless of program status', function () {
    $program = Program::factory()->approved()->create();
    $coordinator = makeCoordinatorFor($program);

    $this->actingAs($coordinator)
        ->get("/api/v1/programs/{$program->id}/reports/objectives/pdf")
        ->assertOk();
});
