<?php

use App\Models\Student;
use App\Models\StudentPii;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

test('it encrypts plain text pii data in student_pii and users', function () {
    // 1. Setup StudentPii with plain text (manual DB insert to bypass casts)
    $student = Student::factory()->create();
    
    DB::table('student_pii')->insert([
        'student_id' => $student->id,
        'address_encrypted' => 'Plain Address',
        'contact_phone_encrypted' => '1234567890',
    ]);

    // 2. Setup User with plain password
    $user = User::factory()->create([
        'plain_password' => 'secret_plain'
    ]);
    // Force set as plain text in DB bypassing casts if they were already there
    DB::table('users')->where('id', $user->id)->update([
        'plain_password' => 'secret_plain'
    ]);

    // 3. Verify they are NOT decryptable before the fix (should throw or already been plain)
    // Actually, accessing them via Eloquent would throw DecryptException now.
    
    // 4. Run the fix command
    $this->artisan('app:fix-pii-encryption')
        ->expectsOutput('Starting PII encryption fix...')
        ->assertExitCode(0);

    // 5. Verify they ARE decryptable and correct
    $pii = StudentPii::find($student->id);
    expect($pii->address_encrypted)->toBe('Plain Address');
    expect($pii->contact_phone_encrypted)->toBe('1234567890');

    $updatedUser = User::find($user->id);
    expect($updatedUser->plain_password)->toBe('secret_plain');
});
