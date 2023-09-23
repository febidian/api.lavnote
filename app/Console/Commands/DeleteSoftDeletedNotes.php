<?php

namespace App\Console\Commands;

use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteSoftDeletedNotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-soft-deleted-notes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus note secara permanent, setelah di hapus sejak 7 hari.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        Note::where('deleted_at', '<=', $sevenDaysAgo)->forceDelete();

        $this->info('Catatan yang telah dihapus secara lembut telah dihapus secara permanen.');
    }
}
