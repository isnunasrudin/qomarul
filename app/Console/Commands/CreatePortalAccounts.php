<?php

namespace App\Console\Commands;

use App\Services\User\UserAccountService;
use Illuminate\Console\Command;

class CreatePortalAccounts extends Command
{
    protected $signature = 'portal:create-accounts {--work-unit-id= : Batasi pada satu satuan kerja}';

    protected $description = 'Buat akun portal untuk seluruh GTK aktif yang belum punya akun (username = NIGY)';

    public function handle(UserAccountService $service): int
    {
        $workUnitId = $this->option('work-unit-id') ? (int) $this->option('work-unit-id') : null;

        $result = $service->createForAllMissing($workUnitId);

        $this->info('Akun dibuat: '.count($result['created']));

        if ($result['created']) {
            $this->table(
                ['Nama', 'Username (NIGY)', 'Kata Sandi Awal', 'Satker'],
                array_map(fn ($row) => array_values($row), $result['created']),
            );
        }

        return self::SUCCESS;
    }
}
