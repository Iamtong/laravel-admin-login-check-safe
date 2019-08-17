<?php

namespace Encore\LoginCheckSafe\Models;

use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLogModel extends Model
{
    protected $fillable = ['user_id', 'state', 'reason','create_at'];
    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.extensions.login-check-safe.db.login_log_table'));

        parent::__construct($attributes);
    }

    /**
     * Log belongs to users.
     *
     * @return BelongsTo
     */
    public function user() : BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
