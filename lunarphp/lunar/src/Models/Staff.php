<?php

namespace Lunar\Admin\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lunar\Admin\Database\Factories\StaffFactory;
use Lunar\Base\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Stephenjude\FilamentTwoFactorAuthentication\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property bool $admin
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property ?\Illuminate\Support\Carbon $email_verified_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder search(?string $terms)
 */
class Staff extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    protected $guard_name = 'staff';

    protected $fillable = [
        'first_name',
        'last_name',
        'admin',
        'email',
        'password',
    ];

    protected $casts = [
        'admin' => 'bool',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'full_name',
    ];

    protected function firstname(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['first_name'],
            set: fn (string $value) => ['first_name' => $value],
        );
    }

    protected function lastname(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['last_name'],
            set: fn (string $value) => ['last_name' => $value],
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => "{$this->first_name} {$this->last_name}",
        );
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('lunar.database.table_prefix').$this->getTable());

        if ($connection = config('lunar.database.connection')) {
            $this->setConnection($connection);
        }
    }

    protected static function newFactory(): StaffFactory
    {
        return StaffFactory::new();
    }

    public function scopeSearch(Builder $query, ?string $terms): void
    {
        if (! $terms) {
            return;
        }

        foreach (explode(' ', $terms) as $term) {
            $query->whereAny(['email', 'first_name', 'last_name'], 'LIKE', "%{$term}%");
        }
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }
}
