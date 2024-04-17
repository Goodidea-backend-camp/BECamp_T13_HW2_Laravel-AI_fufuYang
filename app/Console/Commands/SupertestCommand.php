<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class SupertestCommand extends Command
{
    protected $signature = 'supertest';

    protected $description = '執行必要的測試、檢查';

    public function handle(): void
    {
        confirm(
            '這麼命令只是寫好玩的，可能會有非預期的結果，僅供參考，是否繼續？',
            default: false,
            required: true
        );

        info('------Super Test 開始執行------');

        $response = spin(
            function () {
                return Process::run('./vendor/bin/pint --test -v')->output();
            },
            '正在執行 Laravel Pint...'
        );

        echo $response.PHP_EOL;

        info('------Laravel Pint 執行完畢------');
        sleep(1);

        $response = spin(
            function () {
                return Process::run('./vendor/bin/phpstan analyse -c phpstan.neon')->output();
            },
            '正在執行 PHPStan...'
        );

        echo $response.PHP_EOL;

        info('------PHPStan 執行完畢------');
        sleep(1);

        $response = spin(
            function () {
                return Process::run('./vendor/bin/rector --dry-run')->output();
            },
            '正在執行 rector...'
        );

        echo $response.PHP_EOL;

        info('------Rector 執行完畢------');
        sleep(1);

        info('------Super Test 執行完畢------');
    }
}
