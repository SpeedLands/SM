<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixPiiEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-pii-encryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates plain text PII data to encrypted format in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting PII encryption fix...');

        // 1. Fix StudentPii table
        $piiRecords = \Illuminate\Support\Facades\DB::table('student_pii')->get();
        $piiCount = 0;

        foreach ($piiRecords as $record) {
            $updates = [];
            $fields = [
                'address_encrypted',
                'contact_phone_encrypted',
                'allergies_encrypted',
                'medical_conditions_encrypted',
                'emergency_contact_encrypted',
                'mother_name_encrypted',
                'father_name_encrypted',
                'other_contact_encrypted',
                'mother_workplace_encrypted',
                'father_workplace_encrypted'
            ];

            foreach ($fields as $field) {
                $value = $record->$field;
                if ($value && !$this->isValidEncryption($value)) {
                    $updates[$field] = \Illuminate\Support\Facades\Crypt::encryptString($value);
                }
            }

            if (!empty($updates)) {
                \Illuminate\Support\Facades\DB::table('student_pii')
                    ->where('student_id', $record->student_id)
                    ->update($updates);
                $piiCount++;
            }
        }

        $this->info("Fixed {$piiCount} StudentPii records.");

        // 2. Fix User table (plain_password)
        $users = \Illuminate\Support\Facades\DB::table('users')
            ->whereNotNull('plain_password')
            ->get();
        $userCount = 0;

        foreach ($users as $user) {
            if (!$this->isValidEncryption($user->plain_password)) {
                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'plain_password' => \Illuminate\Support\Facades\Crypt::encryptString($user->plain_password)
                    ]);
                $userCount++;
            }
        }

        $this->info("Fixed {$userCount} User records.");
        $this->info('PII encryption fix completed.');
    }

    /**
     * Check if a string is a validly encrypted payload.
     */
    protected function isValidEncryption(string $value): bool
    {
        try {
            \Illuminate\Support\Facades\Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
