<?php

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\PriorityController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectGroupController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\Reports\DashboardController;
use App\Http\Controllers\Api\Reports\PlannedTimeReportController;
use App\Http\Controllers\Api\Reports\ProjectReportController;
use App\Http\Controllers\Api\Reports\TimeUseReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\IntervalController;
use App\Http\Controllers\Api\Reports\UniversalReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\Api\StatusController as ApiStatusController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\Api\TaskActivityController;
use Illuminate\Routing\Router;

// Routes for login/register processing
Route::group([
    'middleware' => ['throttle:120,1'],
    'prefix' => 'auth',
], static function (Router $router) {
    $router->middleware('auth:sanctum')->group(static function (Router $router) {
        $router->post('logout', [AuthController::class, 'logout'])
            ->name('auth.logout');
        $router->post('logout-from-all', [AuthController::class, 'logoutFromAll'])
            ->name('auth.logout_all');
        $router->post('refresh', [AuthController::class, 'refresh'])
            ->name('auth.refresh');
        $router->get('me', [AuthController::class, 'me'])
            ->name('auth.me');
        $router->post('password/reset/request', [PasswordResetController::class, 'request'])
            ->name('auth.reset.request');
        $router->post('password/reset/validate', [PasswordResetController::class, 'validate'])
            ->name('auth.reset.validate');
        $router->post('password/reset/process', [PasswordResetController::class, 'process'])
            ->name('auth.reset.process');

        $router->get('register/{key}', [RegistrationController::class, 'getForm'])
            ->name('auth.register.form');
        $router->post('register/{key}', [RegistrationController::class, 'postForm'])
            ->name('auth.register.process');

        $router->get('desktop-key', [AuthController::class, 'issueDesktopKey'])
            ->name('auth.desktop.request');
    });

    $router->withoutMiddleware('auth:sanctum')->group(static function (Router $router) {
        $router->post('login', [AuthController::class, 'login'])
            ->name('auth.login');
        $router->post('password/reset/request', [PasswordResetController::class, 'request'])
            ->name('auth.reset.request');
        $router->post('password/reset/validate', [PasswordResetController::class, 'validate'])
            ->name('auth.reset.validate');
        $router->post('password/reset/process', [PasswordResetController::class, 'process'])
            ->name('auth.reset.process');
    });

    $router->put('desktop-key', [AuthController::class, 'authDesktopKey'])
        ->name('auth.desktop.process');
});

Route::get('status', [StatusController::class, '__invoke'])
    ->name('status');

// Main API routes
Route::group([
    'middleware' => ['auth:sanctum'],
], static function (Router $router) {
    $router->group(['middleware' => ['throttle:120,1']], static function (Router $router) {
        //Invitations routes
        $router->any('invitations/list', [InvitationController::class, 'index'])
            ->name('invitations.list');
        $router->get('invitations/count', [InvitationController::class, 'count'])
            ->name('invitations.count');
        $router->post('invitations/create', [InvitationController::class, 'create'])
            ->name('invitations.create');
        $router->post('invitations/resend', [InvitationController::class, 'resend'])
            ->name('invitations.resend');
        $router->post('invitations/show', [InvitationController::class, 'show'])
            ->name('invitations.show');
        $router->post('invitations/remove', [InvitationController::class, 'destroy'])
            ->name('invitations.destroy');

        //Priorities routes
        $router->any('priorities/list', [PriorityController::class, 'index'])
            ->name('priorities.list');
        $router->get('priorities/count', [PriorityController::class, 'count'])
            ->name('priorities.count');
        $router->post('priorities/create', [PriorityController::class, 'create'])
            ->name('priorities.create');
        $router->post('priorities/edit', [PriorityController::class, 'edit'])
            ->name('priorities.edit');
        $router->any('priorities/show', [PriorityController::class, 'show'])
            ->name('priorities.show');
        $router->post('priorities/remove', [PriorityController::class, 'destroy'])
            ->name('priorities.destroy');

        //Statuses routes
        $router->any('statuses/list', [ApiStatusController::class, 'index'])
            ->name('statuses.list');
        $router->get('statuses/count', [ApiStatusController::class, 'count'])
            ->name('statuses.count');
        $router->post('statuses/create', [ApiStatusController::class, 'create'])
            ->name('statuses.create');
        $router->post('statuses/edit', [ApiStatusController::class, 'edit'])
            ->name('statuses.edit');
        $router->any('statuses/show', [ApiStatusController::class, 'show'])
            ->name('statuses.show');
        $router->post('statuses/remove', [ApiStatusController::class, 'destroy'])
            ->name('statuses.destroy');

        //Projects routes
        $router->any('projects/list', [ProjectController::class, 'index'])
            ->name('projects.list');
        $router->any('projects/count', [ProjectController::class, 'count'])
            ->name('projects.count');
        $router->post('projects/create', [ProjectController::class, 'create'])
            ->name('projects.create');
        $router->post('projects/edit', [ProjectController::class, 'edit'])
            ->name('projects.edit');
        $router->any('projects/show', [ProjectController::class, 'show'])
            ->name('projects.show');
        $router->post('projects/remove', [ProjectController::class, 'destroy'])
            ->name('projects.destroy');

        $router->any('project-groups/list', [ProjectGroupController::class, 'index'])
            ->name('project_groups.list');
        $router->any('project-groups/count', [ProjectGroupController::class, 'count'])
            ->name('project_groups.count');
        $router->post('project-groups/create', [ProjectGroupController::class, 'create'])
            ->name('project_groups.create');
        $router->post('project-groups/edit', [ProjectGroupController::class, 'edit'])
            ->name('project_groups.edit');
        $router->any('project-groups/show', [ProjectGroupController::class, 'show'])
            ->name('project_groups.show');
        $router->post('project-groups/remove', [ProjectGroupController::class, 'destroy'])
            ->name('project_groups.destroy');

        $router->any('project-members/list', [ProjectMemberController::class, 'list'])
            ->name('projects_members.list');
        $router->post('project-members/bulk-edit', [ProjectMemberController::class, 'bulkEdit'])
            ->name('projects_members.edit');

        // Gantt routes
        $router->get('projects/gantt-data', [ProjectController::class, 'ganttData'])
            ->name('projects.gantt-data');
        $router->get('projects/phases', [ProjectController::class, 'phases'])
            ->name('projects.phases');

        //Tasks routes
        $router->any('tasks/list', [TaskController::class, 'index'])
            ->name('tasks.list');
        $router->any('tasks/count', [TaskController::class, 'count'])
            ->name('tasks.count');
        $router->post('tasks/create', [TaskController::class, 'create'])
            ->name('tasks.create');
        $router->post('tasks/edit', [TaskController::class, 'edit'])
            ->name('tasks.edit');
        $router->any('tasks/show', [TaskController::class, 'show'])
            ->name('tasks.show');
        $router->post('tasks/remove', [TaskController::class, 'destroy'])
            ->name('tasks.destroy');
        $router->post('tasks/activity', [TaskActivityController::class, 'index'])
            ->name('task.activity');
        $router->post('tasks/calendar', [TaskController::class, 'calendar'])
            ->name('tasks.calendar');

        // Gantt routes
        $router->post('tasks/create-relation', [TaskController::class, 'createRelation'])
            ->name('tasks.create-relation');
        $router->post('tasks/remove-relation', [TaskController::class, 'destroyRelation'])
            ->name('tasks.remove-relation');

        // Task comments
        $router->any('task-comment/list', [TaskCommentController::class, 'index'])
            ->name('task_comments.list');
        $router->post('task-comment/create', [TaskCommentController::class, 'create'])
            ->name('task_comments.create');
        $router->post('task-comment/edit', [TaskCommentController::class, 'edit'])
            ->name('task_comments.edit');
        $router->any('task-comment/show', [TaskCommentController::class, 'show'])
            ->name('task_comments.show');
        $router->post('task-comment/remove', [TaskCommentController::class, 'destroy'])
            ->name('task_comments.destroy');

        //Users routes
        $router->any('users/list', [UserController::class, 'index'])
            ->name('users.list');
        $router->any('users/count', [UserController::class, 'count'])
            ->name('users.count');
        $router->post('users/create', [UserController::class, 'create'])
            ->name('users.create');
        $router->post('users/edit', [UserController::class, 'edit'])
            ->name('users.edit');
        $router->any('users/show', [UserController::class, 'show'])
            ->name('users.show');
        $router->post('users/remove', [UserController::class, 'destroy'])
            ->name('users.destroy');
        $router->post('users/send-invite', [UserController::class, 'sendInvite'])
            ->name('users.invite');
        $router->patch('users/activity', [UserController::class, 'updateActivity'])
            ->name('users.ping');

        $router->post('time-intervals/{interval}/screenshot', [IntervalController::class, 'putScreenshot'])
            ->where('interval', '[0-9]+')->name('intervals.screenshot.put');

        //Time Intervals routes
        $router->any('time-intervals/list', [IntervalController::class, 'index'])
            ->name('intervals.list');
        $router->any('time-intervals/count', [IntervalController::class, 'count'])
            ->name('intervals.count');
        $router->post('time-intervals/create', [IntervalController::class, 'create'])
            ->name('intervals.create');
        $router->post('time-intervals/edit', [IntervalController::class, 'edit'])
            ->name('intervals.edit');
        $router->post('time-intervals/bulk-edit', [IntervalController::class, 'bulkEdit'])
            ->name('intervals.edit.bulk');
        $router->any('time-intervals/show', [IntervalController::class, 'show'])
            ->name('intervals.show');
        $router->post('time-intervals/remove', [IntervalController::class, 'destroy'])
            ->name('intervals.destroy');
        $router->post('time-intervals/bulk-remove', [IntervalController::class, 'bulkDestroy'])
            ->name('intervals.destroy.bulk');

        $router->put('time-intervals/app', [IntervalController::class, 'trackApp'])
            ->name('intervals.app');

        // Offline Sync
        $router->get('offline-sync/download-projects-and-tasks/{user}', [TaskController::class, 'downloadProjectsAndTasks'])
            ->where('user', '[0-9]+')
            ->name('offline_sync.download_projects_and_tasks');
        $router->post('offline-sync/upload-intervals', [IntervalController::class, 'uploadOfflineIntervals'])
            ->name('offline_sync.upload_intervals');
        $router->post('offline-sync/upload-screenshots', [IntervalController::class, 'uploadOfflineScreenshots'])
            ->name('offline_sync.upload_screenshots');
        $router->get('offline-sync/public-key', [CompanySettingsController::class, 'getOfflineSyncPublicKey'])
            ->name('offline_sync.public_key');

        //Time routes
        $router->any('time/total', [IntervalController::class, 'total'])
            ->name('time.total');
        $router->any('time/tasks', [IntervalController::class, 'tasks'])
            ->name('time.tasks');

        //Role routes
        $router->any('roles/list', [RoleController::class, 'index'])
            ->withoutMiddleware('auth:sanctum')
            ->name('roles.list');

        // Statistic routes
        $router->post('report/project', [ProjectReportController::class, '__invoke'])
            ->name('report.project');
        $router->post('report/project/download', [ProjectReportController::class, 'download'])
            ->name('report.project.download');
        $router->post('report/time', [TimeUseReportController::class, '__invoke'])
            ->name('report.time');
        $router->post('report/dashboard', [DashboardController::class, '__invoke'])
            ->name('report.dashboard');
        $router->post('report/dashboard/download', [DashboardController::class, 'download'])
            ->name('report.dashboard.download');
        $router->post('report/planned-time', [PlannedTimeReportController::class, '__invoke'])
            ->name('report.planned-time');
        $router->post('report/planned-time/download', [PlannedTimeReportController::class, 'download'])
            ->name('report.planned-time.download');

        $router->get('report/universal-report/bases', [UniversalReportController::class, 'getBases']);
        $router->get('report/universal-report/data-objects-and-fields', [UniversalReportController::class, 'getDataObjectsAndFields']);
        $router->get('report/universal-report', [UniversalReportController::class, 'index']);
        $router->get('report/universal-report/show', [UniversalReportController::class, 'show']);
        $router->post('report/universal-report', [UniversalReportController::class, 'store']);
        $router->post('report/universal-report/edit', [UniversalReportController::class, 'edit']);
        $router->post('report/universal-report/generate', [UniversalReportController::class, '__invoke']);
        $router->post('report/universal-report/remove', [UniversalReportController::class, 'destroy']);
        $router->post('report/universal-report/download', [UniversalReportController::class, 'download'])
            ->name('report.universal-report.download');
        // About
        $router->get('about', [AboutController::class, '__invoke'])
            ->name('about.list');
        $router->get('about/storage', [AboutController::class, 'storage'])
            ->name('about.storage');
        $router->post('about/storage', [AboutController::class, 'startStorageClean'])
            ->name('about.storage.clean');

        $router->get('about/reports', [AboutController::class, 'reports'])
            ->name('about.reports');

        //Company settings
        $router->get('company-settings', [CompanySettingsController::class, 'index'])
            ->name('settings.list');
        $router->patch('company-settings', [CompanySettingsController::class, 'update'])
            ->name('settings.save');

        // Attachments routes
        $router->post('attachment', [AttachmentController::class, 'create'])
            ->name('attachment.create');
        $router->withoutMiddleware('auth:sanctum')->middleware('signed')
            ->get('tmp-attachment-link/{attachment}', [AttachmentController::class, 'tmpDownload'])
            ->whereUuid('attachment')->name('attachment.temporary-download');
        $router->get('attachment/{attachment}/temporary-url', [AttachmentController::class, 'createTemporaryUrl'])
            ->whereUuid('attachment')->name('attachment.temporary_url');
    });

    //Screenshots routes
    $router->get('time-intervals/{interval}/screenshot', [IntervalController::class, 'showScreenshot'])
        ->where('interval', '[0-9]+')->name('intervals.screenshot.original');
    $router->get('time-intervals/{interval}/thumb', [IntervalController::class, 'showThumbnail'])
        ->where('interval', '[0-9]+')->name('intervals.screenshot.thumb');
});

Route::any('(.*)', [Controller::class, 'universalRoute'])->name('universal_route');
