<?php

namespace App\Models;

use App\Enums\RoleKey;
use App\Models\Concerns\Blameable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Blameable;

    protected $fillable = [
        'name',
        'login_id',
        'email',
        'password',
        'must_change_password',
        'is_active',
        'phone',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    // ---- リレーション ---------------------------------------------------

    public function memberships()
    {
        return $this->hasMany(UserStoreMembership::class);
    }

    public function castProfile()
    {
        return $this->hasOne(Cast::class);
    }

    public function staffProfile()
    {
        return $this->hasOne(StaffProfile::class);
    }

    // ---- 店舗・ロール解決 -----------------------------------------------

    /** MVPは1ユーザー=1店舗所属。有効なメンバーシップを返す。 */
    public function activeMembership(): ?UserStoreMembership
    {
        return $this->relationLoaded('memberships')
            ? $this->memberships->firstWhere('status', 'active')
            : $this->memberships()->active()->first();
    }

    public function currentStoreId(): ?int
    {
        return $this->activeMembership()?->store_id;
    }

    public function role(): ?RoleKey
    {
        $membership = $this->activeMembership();
        if (! $membership) {
            return null;
        }
        $role = $membership->relationLoaded('role') ? $membership->role : $membership->role()->first();

        return $role ? RoleKey::from($role->key) : null;
    }

    public function hasRole(RoleKey ...$roles): bool
    {
        $current = $this->role();

        return $current !== null && in_array($current, $roles, true);
    }

    public function isCast(): bool
    {
        return $this->role() === RoleKey::Cast;
    }

    public function isStaff(): bool
    {
        return $this->role() === RoleKey::Staff;
    }

    public function isManager(): bool
    {
        return $this->role() === RoleKey::Manager;
    }

    public function isAdmin(): bool
    {
        return $this->role() === RoleKey::Admin;
    }

    /** アカウントとして利用可能か（停止・退店を弾く）。 */
    public function isUsable(): bool
    {
        return $this->is_active && $this->activeMembership() !== null;
    }
}
