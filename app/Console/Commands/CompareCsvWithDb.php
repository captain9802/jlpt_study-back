<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JlptWord;

class CompareCsvWithDb extends Command
{
    protected $signature = 'compare:jlpt-csv';
    protected $description = 'CSV 전체 단어 중 DB에 없는 word만 출력합니다.';

    public function handle()
    {
        $url = 'https://raw.githubusercontent.com/elzup/jlpt-word-list/master/out/all.csv';
        $csv = file_get_contents($url);
        $rows = array_map('str_getcsv', explode("\n", $csv));
        $header = array_shift($rows); // 첫 줄 제거

        $csvWords = collect($rows)
            ->map(fn($row) => $row[0] ?? null)
            ->filter()
            ->unique();

        $dbWords = JlptWord::pluck('word');

        $missing = $csvWords->diff($dbWords);

        if ($missing->isEmpty()) {
            $this->info('✅ 모든 CSV 단어가 DB에 존재합니다.');
            return;
        }

        $this->info("📋 DB에 없는 단어 수: {$missing->count()}");
        foreach ($missing as $i => $word) {
            $this->line("[$i] $word");
        }

        // 필요시 저장 가능
        // file_put_contents(storage_path('missing_words.txt'), $missing->implode("\n"));
    }
}
