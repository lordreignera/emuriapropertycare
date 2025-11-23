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
