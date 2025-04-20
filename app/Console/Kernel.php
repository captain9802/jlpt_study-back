<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// 커맨드 클래스 추가
use App\Console\Commands\ImportJlptWords;

class Kernel extends ConsoleKernel
{
    /**
     * Artisan 커맨드 등록
     */
    protected $commands = [
        ImportJlptWords::class, // 👈 여기에 너가 만든 커맨드 등록!
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
