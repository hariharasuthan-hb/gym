<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'age',
        'gender',
        'address',
        'qr_code',
        'rfid_card',
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
        ];
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription::class);
    }

    /**
     * Get the active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->hasOne(\App\Models\Subscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest();
    }

    /**
     * Get the workout plans for the user (as member).
     */
    public function workoutPlans()
    {
        return $this->hasMany(\App\Models\WorkoutPlan::class, 'member_id');
    }

    /**
     * Get the workout plans created by the user (as trainer).
     */
    public function trainerWorkoutPlans()
    {
        return $this->hasMany(\App\Models\WorkoutPlan::class, 'trainer_id');
    }

    /**
     * Get the diet plans for the user (as member).
     */
    public function dietPlans()
    {
        return $this->hasMany(\App\Models\DietPlan::class, 'member_id');
    }

    /**
     * Get the diet plans created by the user (as trainer).
     */
    public function trainerDietPlans()
    {
        return $this->hasMany(\App\Models\DietPlan::class, 'trainer_id');
    }

    /**
     * Get the workout videos uploaded by the user.
     */
    public function workoutVideos()
    {
        return $this->hasMany(\App\Models\WorkoutVideo::class);
    }
}
