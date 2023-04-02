<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\CronTaskWorkers;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Settings;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:task:recreate-workers')]
class RecreateCronTaskWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:task:recreate-workers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreates materialized table for CronTaskWorkers model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $timezone = Settings::scope('core')->get('timezone');
        $reportCreatedAt = Carbon::now($timezone)->format('Y-m-d H:i:s e');

        $this->withProgressBar(
            Task::whereNull('deleted_at')->lazyById(),
            static fn(Task $task) => rescue(static function () use ($task) {
                CronTaskWorkers::whereTaskId($task->id)->delete();

                CronTaskWorkers::insertUsing(
                    ['user_id', 'task_id', 'duration', 'created_by_cron'],
                    DB::table('time_intervals', 'i')
                        ->join('users as u', 'i.user_id', '=', 'u.id')
                        ->selectRaw('i.user_id, i.task_id, SUM(TIMESTAMPDIFF(SECOND, i.start_at, i.end_at)) as duration, "1" as created_by_cron')
                        ->whereNull('i.deleted_at')
                        ->where('task_id', '=', $task->id)
                        ->groupBy(['i.user_id', 'i.task_id'])
                );
            })
        );

        Settings::scope('core.reports')->set('planned_time_report_date', $reportCreatedAt);

        CronTaskWorkers::whereDoesntHave('task')
            ->orWhereDoesntHave('user')
            ->orWhereHas('task',
                static fn(EloquentBuilder $query) => $query
                    ->whereNotNull('deleted_at')
            )->delete();
    }
}
