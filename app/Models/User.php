<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\UserDisease;
use App\Models\UserProductRecommendation;
use App\Models\UserFavouriteProduct;
use App\Models\UserFavouriteDiet;
use App\Models\UserFavouriteWorkout;
use App\Models\CartItem;
use App\Models\SpecialistAppointment;
use App\Models\FreeBookingRequest;
use App\Models\UserBodyComposition;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'username', 'first_name', 'last_name', 'phone_number', 'status', 'email', 'password', 'gender', 'display_name', 'login_type', 'user_type', 'player_id', 'is_subscribe', 'timezone','last_notification_seen', 'can_access_all_branches' ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_subscribe'  => 'integer',
        'can_access_all_branches' => 'boolean',
    ];

    public function userProfile() {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function userGraph(){
        return $this->hasMany(UserGraph::class, 'user_id', 'id');
    }

    public function userAssignDiet(){
        return $this->hasMany(AssignDiet::class, 'user_id', 'id');
    }

    public function userAssignWorkout(){
        return $this->hasMany(AssignWorkout::class, 'user_id', 'id');
    }

    public function userFavouriteDiet(){
        return $this->hasMany(UserFavouriteDiet::class, 'user_id', 'id');
    }

    public function userFavouriteWorkout(){
        return $this->hasMany(UserFavouriteWorkout::class, 'user_id', 'id');
    }

    public function userFavouriteProducts()
    {
        return $this->hasMany(UserFavouriteProduct::class, 'user_id', 'id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'user_id', 'id');
    }

    public function recommendedProducts()
    {
        return $this->belongsToMany(Product::class, 'user_product_recommendations')->withTimestamps();
    }

    public function productRecommendations()
    {
        return $this->hasMany(UserProductRecommendation::class);
    }

    public function dislikedIngredients()
    {
        return $this->belongsToMany(Ingredient::class, 'user_disliked_ingredients')->withTimestamps();
    }

    public function userDiseases()
    {
        return $this->hasMany(UserDisease::class);
    }

    public function bodyCompositions()
    {
        return $this->hasMany(UserBodyComposition::class)->orderByDesc('recorded_at')->orderByDesc('id');
    }

    public function userNotification(){
        return $this->hasMany(Notification::class, 'notifiable_id', 'id');
    }

    public function chatgptFitBot(){
        return $this->hasMany(ChatgptFitBot::class, 'user_id', 'id');
    }

    public function specialistAppointments()
    {
        return $this->hasMany(SpecialistAppointment::class);
    }

    public function freeBookingRequests()
    {
        return $this->hasMany(FreeBookingRequest::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function hasAccessToAllBranches(): bool
    {
        return $this->user_type === 'admin' || (bool) $this->can_access_all_branches;
    }

    public function accessibleBranchIds(): array
    {
        if ($this->hasAccessToAllBranches()) {
            return [];
        }

        $this->loadMissing('branches');

        return $this->branches
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($row) {
            switch ($row->user_type) {
                case 'user':
                    $row->userProfile()->delete();
                    $row->userGraph()->delete();
                    $row->userAssignDiet()->delete();
                    $row->userAssignWorkout()->delete();
                    $row->userFavouriteDiet()->delete();
                    $row->userFavouriteWorkout()->delete();
                    $row->userFavouriteProducts()->delete();
                    $row->cartItems()->delete();
                    $row->dislikedIngredients()->detach();
                    $row->userDiseases()->delete();
                    $row->bodyCompositions()->delete();
                    $row->userNotification()->delete();
                    $row->chatgptFitBot()->delete();
                    $row->specialistAppointments()->delete();
                    $row->freeBookingRequests()->delete();
                    $row->productRecommendations()->delete();
                break;
                default:
                    # code...
                break;
            }

            $row->branches()->detach();
        });

        static::updated(function($model) {
            if ($model->isDirty('first_name') || $model->isDirty('last_name') ) {
                $model->display_name = $model->first_name.' '.$model->last_name;
                $model->saveQuietly(); 
            }
        });
    }

    public function routeNotificationForOneSignal()
    {
        return $this->player_id;
    }

    public function subscriptionPackage(){
        return $this->hasOne(Subscription::class, 'user_id','id')->where('status',config('constant.SUBSCRIPTION_STATUS.ACTIVE'));
    }

    public function classSchedulePlan(){
        return $this->hasMany(ClassSchedulePlan::class, 'user_id', 'id');
    }

    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['phone_number'] = $value ? str_replace('+', '', $value) : null;
    }

    public function getPhoneNumberAttribute()
    {
        return $this->attributes['phone_number'] ? '+' . $this->attributes['phone_number'] : null;
    }
}
