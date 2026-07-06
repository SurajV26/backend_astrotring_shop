<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $dates = ['deleted_at'];

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        // BASIC INFO
        'code',
        'name',
        'email',
        'password',
        'username',
        'country_code',
        'mobile',
        'address',
        'pincode',
        'is_family_astrologer',
        'family_astrology_details',
        'daily_available_hours',
        'otp',
        'otp_created_at',
        'last_otp_sent_at',
        'otp_attempts',
        'is_two_factor_auth_enabled',

        // ROLE & ACCESS
        'type',
        'role_id',
        'permissions',
        'parent_id',
        'status',
        "terms_accepted",
        'hash_token',
        'device_token',

        // PROFILE DETAILS
        'dob',
        'gender',
        'profile_image',

        // ASTROLOGER DETAILS
        'about',
        'birth_time',
        'birth_place',
        'astro_education',
        'experience',
        'expertise',
        'category',
        'languages',
        'chat_price',
        'call_price',
        'is_online',
        'is_busy',
        'last_seen_at',

        // LOCATION
        'region_id',
        'country_id',
        'state_id',
        'city_id',
        'pincode_id',

        // WORK / ADMIN
        'salary',
        'date_of_joining',
        'commission_percentage',
        'company_name',
        'affiliate_type',
        'traffic_sources',
        'promotion_plan',
        'expected_leads',
        'kyc_status',
        'created_by',
        'modified_by',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'kyc_status' => 'boolean',
        'dob' => 'date',
        'date_of_joining' => 'date',
        'last_seen_at' => 'datetime',
        'is_online' => 'boolean',
        'is_verified' => 'boolean',
        'astro_education' => 'array',
        'expertise' => 'array',
        'category' => 'array',
        'languages' => 'array',
        'is_family_astrologer' => 'boolean',
        'traffic_sources' => 'array',
    ];

    /* =====================================================
        ROLE CHECK HELPERS
    ===================================================== */

    public function isSuperAdmin()
    {
        return $this->type === 'admin';
    }

    public function isStaff()
    {
        return $this->type === 'staff';
    }

    public function isUser()
    {
        return $this->type === 'user';
    }

    public function isAstro()
    {
        return $this->type === 'astro';
    }

    public function isEmployee()
    {
        return $this->type === 'employee';
    }

    /* =====================================================
        LOCATION RELATIONS
    ===================================================== */

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function pincode()
    {
        return $this->belongsTo(PinCode::class);
    }

    /* =====================================================
        USER RELATIONSHIPS
    ===================================================== */

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /* =====================================================
        ASTROLOGER / CLIENT RELATED
    ===================================================== */

    public function availability()
    {
        return $this->hasOne(AstrologerAvailability::class, 'user_id');
    }

    public function services()
    {
        return $this->hasMany(UserService::class, 'client_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id');
    }

    public function chats()
    {
        return $this->hasMany(ChatSession::class, 'astrologer_id');
    }

    public function calls()
    {
        return $this->hasMany(CallSession::class, 'astrologer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'astrologer_id');
    }

    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class, 'user_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {

            if (!$user->isForceDeleting()) {

                $timestamp = now()->timestamp;

                // CHANGE UNIQUE FIELDS
                if ($user->email) {
                    $user->email = $timestamp . '_deleted_' . $user->email;
                }

                if ($user->mobile) {
                    $user->mobile = $timestamp . '_deleted_' . $user->mobile;
                }

                if ($user->username) {
                    $user->username = $timestamp . '_deleted_' . $user->username;
                }

                if ($user->code) {
                    $user->code = $timestamp . '_deleted_' . $user->code;
                }

                // SAVE CHANGES
                $user->saveQuietly();

                // SOFT DELETE WALLET
                if ($user->wallet) {
                    $user->wallet()->delete();
                }

            } else {

                // FORCE DELETE WALLET
                if ($user->wallet) {
                    $user->wallet()->forceDelete();
                }
            }
        });
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists')
            ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'employee_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(StoreWalletTransaction::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function storeReviews()
    {
        return $this->hasMany(StoreReview::class);
    }

    protected static function booted()
    {
        static::created(function ($user) {

            if ($user->role_id == 2 && $user->status == 0) {

                \Mail::to('no-reply@astrotring.com')
                    ->send(new \App\Mail\AstroRegistrationNotification($user));
            }

            if ($user->role_id == 4 && $user->status == 0) {

                \Mail::to('no-reply@astrotring.shop')
                    ->send(new \App\Mail\AffiliateRegistrationNotification($user));
            }
        });

        static::updated(function ($user) {

            if ($user->isDirty('status') && $user->status == 1 && $user->role_id == 2) {

                \Mail::to($user->email)
                    ->send(new \App\Mail\AstroApprovedMail($user));
            }
            
             if (
                $user->role_id == 4 &&
                $user->wasChanged('status') &&
                $user->getOriginal('status') == 0 &&
                $user->status == 1
            ) {

                if (empty($user->date_of_joining)) {

                    $user->updateQuietly([
                        'date_of_joining' => now(),
                    ]);
                }

                \Mail::to($user->email)
                    ->send(new \App\Mail\AffiliateApprovedMail($user));
            }
        });
    }

    public function hasAccess($module)
    {
        $permissions = json_decode($this->permissions ?? '[]', true);

        return in_array($module, $permissions);
    }
}