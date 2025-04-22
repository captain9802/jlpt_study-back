<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\ImportJlptWords;

class Kernel extends ConsoleKernel
{
    /**
     * Artisan 커맨드 등록
     */
    protected $commands = [
        ImportJlptWords::class,
    ];

    /**
     * 스케줄 등록
     */
    protected function schedule(Schedule $schedule)
    {
        // 예: $schedule->command('import:jlpt')->daily();
    }

    /**
     * 커맨드 자동 로딩 경로
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
