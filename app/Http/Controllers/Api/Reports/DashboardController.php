<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\DashboardRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\Project;
use App\Models\User;
use App\Reports\DashboardExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Settings;
use Throwable;

class DashboardController
{
    /**
     * @api             {post} /report/dashboard Dashboard Data
     * @apiDescription  Retrieve dashboard data based on provided parameters.
     *
     * @apiVersion      4.0.0
     * @apiName         DashboardData
     * @apiGroup        Dashboard
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {String} start_at      Start date-time in "Y-m-d H:i:s" format.
     * @apiParam {String} end_at        End date-time in "Y-m-d H:i:s" format.
     * @apiParam {String} user_timezone User's timezone.
     * @apiParam {Array}  [users]       Array of user IDs. If not provided, all users are considered.
     * @apiParam {Array}  [projects]    Array of project IDs. If not provided, all projects are considered.
     *
     * @apiParamExample {json} Request Example
     * {
     *   "start_at": "2006-05-31 16:15:09",
     *   "end_at": "2006-05-31 16:20:07",
     *   "user_timezone": "Asia/Omsk"
     * }
     *
     * @apiSuccess {Object}   data       Dashboard data keyed by user ID.
     * @apiSuccess {Array}    data.7     Array of records for user with ID 7.
     * @apiSuccess {String}   data.7.start_at  Start date-time of the record.
     * @apiSuccess {Integer}  data.7.activity_fill Activity fill percentage.
     * @apiSuccess {Integer}  data.7.mouse_fill Mouse activity fill percentage.
     * @apiSuccess {Integer}  data.7.keyboard_fill Keyboard activity fill percentage.
     * @apiSuccess {String}   data.7.end_at  End date-time of the record.
     * @apiSuccess {Boolean}  data.7.is_manual Indicates if the record is manual.
     * @apiSuccess {String}   data.7.user_email User's email address.
     * @apiSuccess {Integer}  data.7.id  Record ID.
     * @apiSuccess {Integer}  data.7.project_id Project ID.
     * @apiSuccess {String}   data.7.project_name Project name.
     * @apiSuccess {Integer}  data.7.task_id Task ID.
     * @apiSuccess {String}   data.7.task_name Task name.
     * @apiSuccess {Integer}  data.7.user_id User ID.
     * @apiSuccess {String}   data.7.full_name User's full name.
     * @apiSuccess {Integer}  data.7.duration Duration in seconds.
     * @apiSuccess {Integer}  data.7.from_midnight Time from midnight in seconds.
     * @apiSuccess {Object}   data.7.durationByDay Duration grouped by day.
     * @apiSuccess {Integer}  data.7.durationByDay.2018-05-31 Duration for the day 2018-05-31.
     * @apiSuccess {Integer}  data.7.durationByDay.2018-06-04 Duration for the day 2018-06-04.
     * @apiSuccess {Integer}  data.7.durationAtSelectedPeriod Duration for the selected period.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     * {
     *   "status": 200,
     *   "success": true,
     *   "data": {
     *     "7": [
     *       {
     *         "start_at": "2018-05-31 10:43:45",
     *         "activity_fill": 111,
     *         "mouse_fill": 64,
     *         "keyboard_fill": 47,
     *         "end_at": "2018-06-03 22:03:45",
     *         "is_manual": 0,
     *         "user_email": "projectManager1231@example.com",
     *         "id": 2109,
     *         "project_id": 159,
     *         "project_name": "Voluptas ab et ea.",
     *         "task_id": 54,
     *         "task_name": "Quo consequatur mollitia nam.",
     *         "user_id": 7,
     *         "full_name": "Dr. Adaline Toy",
     *         "duration": 300000,
     *         "from_midnight": 38625,
     *         "durationByDay": {
     *           "2018-05-31": 285375,
     *           "2018-06-04": 14625
     *         },
     *         "durationAtSelectedPeriod": 14625
     *       }
     *     ]
     *   }
     * }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     */
    public function __invoke(DashboardRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            DashboardExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                $request->input('projects') ?? Project::all()->pluck('id')->toArray(),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone,
                $request->input('user_timezone'),
            )->collection()->all(),
        )->respond();
    }

    /**
     * @throws Throwable
     * @api             {post} /report/dashboard/download Download Dashboard Report
     * @apiDescription  Generate and download a dashboard report
     *
     * @apiVersion      4.0.0
     * @apiName         DownloadDashboardReport
     * @apiGroup        Report
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   report_generate
     * @apiPermission   report_full_access
     *
     * @apiParam {String}   start_at        Start date and time (ISO 8601 format)
     * @apiParam {String}   end_at          End date and time (ISO 8601 format)
     * @apiParam {String}   user_timezone   User's timezone
     * @apiParam {String}   sort_column     Column to sort by
     * @apiParam {String}   sort_direction  Direction to sort (asc/desc)
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "start_at": "2024-08-06T18:00:00.000Z",
     *    "end_at": "2024-08-07T17:59:59.999Z",
     *    "user_timezone": "Asia/Omsk",
     *    "sort_column": "user",
     *    "sort_direction": "asc",
     *  }
     *
     * @apiSuccess {String}   url   URL to download the generated report
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": {
     *      "url": "/storage/reports/f7ac500e-a741-47ee-9e61-1b62a341fb8d/Dashboard_Report.csv"
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ItemNotFoundError
     */
    public function download(DashboardRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        $job = new GenerateAndSendReport(
            DashboardExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                $request->input('projects') ?? Project::all()->pluck('id')->toArray(),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone,
                $request->input('user_timezone'),
                DashboardSortBy::tryFrom($request->input('sort_column')),
                SortDirection::tryFrom($request->input('sort_direction')),
            ),
            $request->user(),
            ReportHelper::getReportFormat($request),
        );

        app(Dispatcher::class)->dispatchSync($job);

        return responder()->success(['url' => $job->getPublicPath()])->respond();
    }

    /**
     * @api             {post} /time-intervals/day-duration Day Duration
     * @apiDescription  Get info for dashboard summed by days
     *
     * @apiVersion      1.0.0
     * @apiName         Day Duration
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  user_ids  List of user ids
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_ids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {Object}    user_intervals                     Response, keys => requested user ids
     * @apiSuccess {Object[]}  user_intervals.intervals           Intervals info
     * @apiSuccess {Integer}   user_intervals.intervals.user_id   Intervals user ID
     * @apiSuccess {String}    user_intervals.intervals.date      Intervals date
     * @apiSuccess {Integer}   user_intervals.intervals.duration  Intervals duration
     * @apiSuccess {Integer}   user_intervals.duration            Total duration of intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "user_intervals": {
     *      "2": {
     *        "intervals": [
     *          {
     *            "user_id": 2,
     *            "date": "2020-01-23",
     *            "duration": 298
     *          },
     *          {
     *            "user_id": 2,
     *            "date": "2020-01-24",
     *            "duration": 298
     *          }
     *        ],
     *        "duration": 596
     *      }
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */

    /**
     * @api             {post} /time-intervals/dashboard Dashboard
     * @apiDescription  Get info for dashboard
     *
     * @apiVersion      1.0.0
     * @apiName         Dashboard
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  user_ids  List of user ids
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_ids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {Object}    userIntervals                       Response, keys => requested user ids
     * @apiSuccess {Integer}   userIntervals.user_id               ID of the user
     * @apiSuccess {Object[]}  userIntervals.intervals             Intervals info
     * @apiSuccess {Integer}   userIntervals.intervals.id          Interval ID
     * @apiSuccess {Integer}   userIntervals.intervals.user_id     Interval user ID
     * @apiSuccess {Integer}   userIntervals.intervals.task_id     Interval task ID
     * @apiSuccess {Integer}   userIntervals.intervals.project_id  Interval project ID
     * @apiSuccess {Integer}   userIntervals.intervals.duration    Interval duration
     * @apiSuccess {ISO8601}   userIntervals.intervals.start_at    DateTime of interval start
     * @apiSuccess {ISO8601}   userIntervals.intervals.end_at      DateTime of interval end
     * @apiSuccess {Integer}   userIntervals.duration              Total duration of intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "userIntervals": {
     *      "1": {
     *        "user_id": 1,
     *        "intervals": [
     *          {
     *            "id": 3261,
     *            "user_id": 1,
     *            "task_id": 1,
     *            "project_id": 1,
     *            "duration": 60,
     *            "start_at": "2013-04-12T20:40:00+00:00",
     *            "end_at": "2013-04-12T20:41:00+00:00"
     *          }
     *        ],
     *        "duration": 60
     *      }
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */
}
