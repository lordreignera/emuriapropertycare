<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
        'signature_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
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
            'requires_subscription' => 'boolean',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Override Jetstream's profile_photo_url to serve signed S3 URLs when on S3 disk.
     * Falls back to the default Jetstream behaviour for local/public disk.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        $disk   = config('filesystems.default', 'public');
        $driver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($this->profile_photo_path) {
            if ($driver === 's3') {
                return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                    $this->profile_photo_path,
                    now()->addHours(1)
                );
            }

            return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->profile_photo_path);
        }

        // No photo — use UI-Avatars initials
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name)
            .'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Return a URL for the user's signature image, or null if none uploaded.
     * Uses signed S3 URLs on S3 disk, plain URLs on public disk.
     */
    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature_path) {
            return null;
        }

        $disk   = config('filesystems.default', 'public');
        $driver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($driver === 's3') {
            return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                $this->signature_path,
                now()->addHours(1)
            );
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->signature_path);
    }

    /**
     * Get all properties owned by this user (client)
     */
    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get all tenants managed by this user (client)
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'client_id');
    }

    /**
     * Get all subscriptions for this user
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all custom products offered to this client
     */
    public function customProducts()
    {
        return $this->hasMany(ClientCustomProduct::class, 'client_id');
    }

    /**
     * Get all products created by this user (admin)
     */
    public function createdProducts()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    /**
     * Get projects managed by this user (Project Manager)
     */
    public function managedProjects()
    {
        return $this->hasMany(Project::class, 'managed_by');
    }

    /**
     * Get inspections assigned to this user (Inspector)
     */
    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    /**
     * Get emergency reports assigned to this user (Technician)
     */
    public function assignedEmergencyReports()
    {
        return $this->hasMany(TenantEmergencyReport::class, 'assigned_to');
    }

    /**
     * Get properties approved by this user (PM/Admin)
     */
    public function approvedProperties()
    {
        return $this->hasMany(Property::class, 'approved_by');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if user is a client
     */
    public function isClient(): bool
    {
        return $this->account_type === 'client' || $this->hasRole('Client');
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->account_type === 'staff' || $this->hasAnyRole([
            'Super Admin',
            'Administrator',
            'Store Manager',
            'Project Manager',
            'Inspector',
            'Technician',
            'Finance Officer'
        ]);
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        // Staff don't need subscriptions
        if ($this->isStaff()) {
            return true;
        }

        // Check if client has active subscription
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->exists();
    }
}
