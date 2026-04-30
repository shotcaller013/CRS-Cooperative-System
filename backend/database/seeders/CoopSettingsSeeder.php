<?php
// database/seeders/CoopSettingsSeeder.php
namespace Database\Seeders;

use App\Models\CoopProfile;
use App\Models\SystemPreference;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CoopSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Coop Profile ─────────────────────────────────────
        CoopProfile::updateOrCreate(['id' => 1], [
            'name'               => 'CRS Holdings Corporations Employees Credit Cooperative',
            'cda_reg_no'         => '9909-XXX',
            'address'            => 'A.C. Cortes Avenue, Alang-alang, Mandaue City, Cebu 6014',
            'contact'            => '',
            'email'              => '',
            'hr_signatory'       => 'HR Manager',
            'coop_signatory'     => 'COOP Manager / Loan Officer',
            'fiscal_year_start'  => 'January',
        ]);

        // ── System Preferences ───────────────────────────────
        $prefs = [
            ['key' => 'scheduler.overdue_check_time', 'value' => '02:00',     'group' => 'scheduler',      'description' => 'Daily time to run overdue detection (24h format)'],
            ['key' => 'loans.default_frequency',      'value' => 'bimonthly', 'group' => 'loans',          'description' => 'Default payment frequency for new loan applications'],
            ['key' => 'notifications.enabled',         'value' => 'false',    'group' => 'notifications',  'description' => 'Enable SMS/email notifications'],
            ['key' => 'notifications.provider',        'value' => 'semaphore','group' => 'notifications',  'description' => 'Notification provider (semaphore, mailgun)'],
        ];

        foreach ($prefs as $p) {
            \App\Models\SystemPreference::updateOrCreate(['key' => $p['key']], $p);
        }

        // ── New Permissions ───────────────────────────────────
        $newPerms = [
            'view-setting', 'edit-setting',
            'view-payment', 'create-payment', 'edit-payment', 'delete-payment',
        ];
        foreach ($newPerms as $perm) {
            Permission::findOrCreate($perm, 'sanctum');
        }

        // Update roles
        $admin   = Role::findOrCreate('super-admin', 'sanctum');
        $admin->givePermissionTo($newPerms);

        $officer = Role::findOrCreate('loan-officer', 'sanctum');
        $officer->givePermissionTo(['view-payment', 'create-payment']);

        $this->command->info('✓ Coop profile seeded');
        $this->command->info('✓ System preferences seeded');
        $this->command->info('✓ Payment permissions seeded');
    }
}
