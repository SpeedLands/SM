<?php

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->teacher = User::factory()->create(['role' => 'TEACHER']);
    $this->parent = User::factory()->create(['role' => 'PARENT']);
});

test('only admins can access the export page', function () {
    $this->actingAs($this->admin)->get(route('data-exporter'))->assertOk();

    $this->actingAs($this->teacher)->get(route('data-exporter'))->assertForbidden();
    $this->actingAs($this->parent)->get(route('data-exporter'))->assertForbidden();
});

test('admins can download teachers export', function () {
    $response = $this->actingAs($this->admin)->get(route('export.teachers'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->assertHeader('content-disposition', 'attachment; filename=maestros_'.now()->format('Y-m-d').'.xlsx');
});

test('admins can download parents export', function () {
    $response = $this->actingAs($this->admin)->get(route('export.parents'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->assertHeader('content-disposition', 'attachment; filename=padres_'.now()->format('Y-m-d').'.xlsx');
});

test('admins can download students export', function () {
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);

    $response = $this->actingAs($this->admin)->get(route('export.students', [
        'cycle_id' => $cycle->id,
        'group_id' => $group->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('non-admins cannot download exports', function () {
    $this->actingAs($this->teacher)->get(route('export.teachers'))->assertForbidden();
    $this->actingAs($this->parent)->get(route('export.teachers'))->assertForbidden();
});
