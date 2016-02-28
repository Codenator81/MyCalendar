<?php namespace KurtJensen\MyCalendar\Models;

use Carbon\Carbon;
use KurtJensen\MyCalendar\Models\Settings;
use Model;
use RainLab\User\Models\User as UserModel;
use System\Classes\PluginManager;

/**
 * event Model
 */
class Event extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Permissions cache.
     */
    public $permarray = [];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'kurtjensen_mycal_events';

    /**
     * Validation rules
     */
    public $rules = [
        'date' => 'date|required',
        'name' => 'required',
    ];

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['*'];

    protected $dates = ['date'];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'categorys' => ['KurtJensen\MyCalendar\Models\Category',
            'table' => 'kurtjensen_mycal_categorys_events',
            'key' => 'event_id',
            'otherKey' => 'category_id',
        ],
    ];

    public $attributes = [
        'day' => 0,
        'month' => 0,
        'year' => 0,
        'human_time' => '',
        'carbon_time' => '',
        'owner_name' => '',
    ];

    public function getDayAttribute()
    {
        return $this->date->day;
    }

    public function getMonthAttribute()
    {
        return $this->date->month;
    }

    public function getYearAttribute()
    {
        return $this->date->year;
    }

    public function getHumanTimeAttribute()
    {
        $time = isset($this->time) ? $this->carbon_time->format(Settings::get('time_format', 'g:i a')) : '';
        return $time;
    }

    public function getCarbonTimeAttribute()
    {
        if (!$this->time) {
            return '';
        }
        list($h, $m) = explode(':', $this->time);
        $time = $this->date->copy();
        $time->hour = $h;
        $time->minute = $m;
        return $time;
    }

    public function getOwnerNameAttribute()
    {
        $manager = PluginManager::instance();
        if ($manager->exists('rainlab.user')) {
            if ($this->user) {
                return $this->user->name . ' ' . $this->user->surname;
            }
        }
        return '';
    }

    public function beforeSave()
    {
        unset(
            $this->attributes['human_time'],
            $this->attributes['owner_name'],
            $this->attributes['carbon_time']);
    }

    public function getDayOptions($month)
    {
        if ($this->month && $this->year) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
            $days = range(1, $daysInMonth);
            return array_combine($days, $days);
        }
        return [0 => 'Pick a Month AND Year'];
    }

    public function getMonthOptions()
    {
        $months = ['0', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        unset($months[0]);
        return $months;
    }

    public function getYearOptions()
    {
        $year = date('Y');
        $years = range($year, $year + 5);
        return array_combine($years, $years);
    }

    public function getUserIdOptions($keyValue = null)
    {
        $Users = [];
        $manager = PluginManager::instance();
        if ($manager->exists('rainlab.user')) {
            foreach (UserModel::orderBy('surname')->
                orderBy('name')->get() as $user) {
                $Users[$user->id] = $user->surname . ', ' . $user->name;
            }

            return $Users;
        }
        return [0 => 'Rainlab User Model Not Installed'];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Restricts to dates after $days days before today.
     * @param  object $query
     * @param  integer $days
     * @return object $query
     */
    public function scopePast($query, $days)
    {
        $date = new Carbon();
        $date->subDays($days);
        return $query->where('date', '>=', $date);
    }

    /**
     * Restricts to dates after $days days from today.
     * @param  object $query
     * @param  integer $days
     * @return object $query
     */
    public function scopeFuture($query, $days)
    {
        $date = new Carbon();
        $date->addDays($days);
        return $query->where('date', '<=', $date);
    }

    public function scopeWithOwner($query)
    {
        $manager = PluginManager::instance();
        if ($manager->exists('rainlab.user')) {
            return $query->with('user');
        }
        return $query;
    }

    public function scopePermisions($query, $user_id, $public_perm = [], $deny_perm = 0)
    {
        $manager = PluginManager::instance();

        if ($manager->exists('kurtjensen.passage')) {

            $akeys = array_keys(\KurtJensen\Passage\Plugin::globalPassageKeys());
            if ($user_id) {
                $permarray = array_merge($akeys, $public_perm);
            } else {
                $permarray = $public_perm;
            }

            $permarray = array_unique($permarray);

            $query->whereHas('categorys', function ($q) use ($permarray) {
                      $q->whereIn('permission_id', $permarray);
                  })
                  ->whereDoesntHave('categorys', function ($q) use ($deny_perm) {
                      $q->where('permission_id', $deny_perm);
                  });
            return $query;
        }
        return $query;
    }
}
