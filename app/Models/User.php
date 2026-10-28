<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_petugas_gudang',
        'unit_kerja',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_petugas_gudang' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isPetugasGudang(): bool
    {
        return $this->is_petugas_gudang;
    }

    /**
     * Panel Kerja (Filament, /admin) hanya untuk user aktif yang mengelola data:
     * Admin, Koordinator 5R, Management, dan Petugas Gudang.
     */
    public function canAccessWorkPanel(): bool
    {
        return $this->is_active
            && ($this->hasRole(Role::Admin, Role::Koordinator5R, Role::Management) || $this->is_petugas_gudang);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canAccessWorkPanel();
    }

    /**
     * Admin, atau PIC dari minimal satu ruang kolaborasi aktif yang bookingnya perlu persetujuan.
     */
    public function canApproveBookings(): bool
    {
        return $this->is_active
            && ($this->isAdmin() || $this->areasAsDefaultPic()->bookable()->active()->where('requires_approval', true)->exists());
    }

    /**
     * Berhak menyetujui booking di ruangan ini: Admin atau PIC ruangan tersebut.
     */
    public function approvesBookingsFor(Area $area): bool
    {
        return $this->is_active && ($this->isAdmin() || $area->default_pic_id === $this->id);
    }

    public function reportedFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'reported_by');
    }

    public function assignedFindings(): HasMany
    {
        return $this->hasMany(Finding::class, 'pic_id');
    }

    public function areasAsDefaultPic(): HasMany
    {
        return $this->hasMany(Area::class, 'default_pic_id');
    }

    public function wispoRequests(): HasMany
    {
        return $this->hasMany(WispoRequest::class, 'requester_user_id');
    }

    public function roomBookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class, 'requester_user_id');
    }
}
