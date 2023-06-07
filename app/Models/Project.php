<?php

namespace App\Models;

use App\Scopes\ProjectAccessScope;
use App\Traits\ExposePermissions;
use Database\Factories\ProjectFactory;
use DB;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Project
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $description
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $important
 * @property string $source
 * @property int|null $default_priority_id
 * @property-read Priority|null $defaultPriority
 * @property-read array $can
 * @property-read int|null $roles_count
 * @property-read Collection|Status[] $statuses
 * @property-read int|null $statuses_count
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @method static ProjectFactory factory(...$parameters)
 * @method static EloquentBuilder|Project newModelQuery()
 * @method static EloquentBuilder|Project newQuery()
 * @method static QueryBuilder|Project onlyTrashed()
 * @method static EloquentBuilder|Project query()
 * @method static EloquentBuilder|Project whereCompanyId($value)
 * @method static EloquentBuilder|Project whereCreatedAt($value)
 * @method static EloquentBuilder|Project whereDefaultPriorityId($value)
 * @method static EloquentBuilder|Project whereDeletedAt($value)
 * @method static EloquentBuilder|Project whereDescription($value)
 * @method static EloquentBuilder|Project whereId($value)
 * @method static EloquentBuilder|Project whereImportant($value)
 * @method static EloquentBuilder|Project whereName($value)
 * @method static EloquentBuilder|Project whereSource($value)
 * @method static EloquentBuilder|Project whereUpdatedAt($value)
 * @method static QueryBuilder|Project withTrashed()
 * @method static QueryBuilder|Project withoutTrashed()
 * @mixin EloquentIdeHelper
 * @property-read Collection|\App\Models\Property[] $properties
 * @property-read int|null $properties_count
 */

class Project extends Model
{
    use SoftDeletes;
    use ExposePermissions;
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'important',
        'source',
        'default_priority_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'company_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'important' => 'integer',
        'source' => 'string',
        'default_priority_id' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected const PERMISSIONS = ['update', 'update_members', 'destroy'];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new ProjectAccessScope);

        static::deleting(static function (Project $project) {
            $project->tasks()->delete();
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projects_users')
            ->withPivot('role_id')
            ->using(ProjectUserPivot::class)
            ->withoutGlobalScopes();
    }

    public function defaultPriority(): HasOne
    {
        return $this->hasOne(Priority::class, 'id', 'default_priority_id');
    }

    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(Status::class, 'projects_statuses', 'project_id', 'status_id')->withPivot('color');
    }

    public function getNameAttribute(): string
    {
        return empty($this->attributes['source']) || $this->attributes['source'] === 'internal'
            ? $this->attributes['name']
            : ucfirst($this->attributes['source']) . ": {$this->attributes['name']}";
    }

    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'entity');
    }

    public function project()
    {
        $project = Project::query()->where('id', '=', $this->id)->with([
            "defaultPriority",
            "users",
            "statuses",
        ])->first()->append('can');
        $totalTracked = 0;

        $taskIDs = array_map(static function ($task) {
            return $task['id'];
        }, $project->tasks->toArray());
        $workers = DB::table('time_intervals AS i')
            ->leftJoin('tasks AS t', 'i.task_id', '=', 't.id')
            ->leftJoin('users AS u', 'i.user_id', '=', 'u.id')
            ->select(
                'i.user_id',
                'u.full_name',
                'i.task_id',
                'i.start_at',
                'i.end_at',
                't.task_name',
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, i.start_at, i.end_at)) as duration')
            )
            ->whereNull('i.deleted_at')
            ->whereIn('task_id', $taskIDs)
            ->orderBy('duration', 'desc')
            ->groupBy('t.id')
            ->get();

        foreach ($workers as $worker) {
            $totalTracked += $worker->duration;
        }

        $project['workers'] = $workers;
        $project['total_spent_time'] = $totalTracked;
        return $project;
    }
}
