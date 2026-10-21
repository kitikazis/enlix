<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_el_worker_de_colas_se_agenda_con_opciones_validas(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn (Event $e) => str_contains($e->command, 'queue:work'));

        $this->assertNotNull($evento, 'Debe existir un queue:work agendado.');
        $this->assertStringContainsString('--stop-when-empty', $evento->command);
        $this->assertStringNotContainsString("--stop-when-empty='1'", $evento->command);
        $this->assertStringNotContainsString('--stop-when-empty=1', $evento->command);
        $this->assertStringContainsString('--max-time=50', $evento->command);
    }

    public function test_queue_work_acepta_las_opciones_agendadas(): void
    {
        $this->artisan('queue:work --stop-when-empty --max-time=1')->assertExitCode(0);
    }
}
