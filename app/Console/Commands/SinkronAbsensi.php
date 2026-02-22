<?php

namespace App\Console\Commands;

use App\Models\JadwalPertemuanAbsensi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SinkronAbsensi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sinkron:absensi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        DB::disableQueryLog();

        $total = 0;

        DB::connection('siade_old')
            ->table('jadwal_pertemuan_absensi')
            ->orderBy('kode')
            ->chunkById(2000, function ($rows) use (&$total) {
                $data = [];
                foreach ($rows as $m) {
                    $data[] = [
                        'jadwal_id' => $m->id_jadwal,
                        'jadwal_pertemuan_id' => $m->id_jadwal_pertemuan,
                        'npm' => $m->nim,
                        'status_kehadiran_id' => $m->id_status_kehadiran,
                    ];
                }
                $total += count($data);
                if ($data) {
                    JadwalPertemuanAbsensi::insert($data);
                }

                echo "Processed: $total\n";
            },'kode');

        $this->info("Selesai total: $total");
    }
}
