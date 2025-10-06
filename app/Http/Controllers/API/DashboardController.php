<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BodyPart;
use App\Models\Level;
use App\Models\Diet;
use App\Models\Equipment;
use App\Http\Resources\BodyPartResource;
use App\Http\Resources\LevelResource;
use App\Http\Resources\EquipmentResource;
use App\Http\Resources\DietResource;
use App\Models\WorkoutType;
use App\Http\Resources\WorkoutTypeResource;
use App\Models\Workout;
use App\Http\Resources\WorkoutResource;
use App\Models\Exercise;
use App\Http\Resources\ExerciseResource;
use App\Models\Setting;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Http\Resources\BannerResource;
use App\Models\SuccessStory;
use App\Http\Resources\SuccessStoryResource;
class DashboardController extends Controller
{
    public function dashboardDetail(Request $request)
    {
        $userId = auth()->id();

        $bodypart = BodyPart::where('status','active')->orderBy('id','desc')->take(10)->get();
        $level = Level::where('status','active')->orderBy('id','desc')->take(10)->get();
        $equipment = Equipment::where('status','active')->orderBy('id','desc')->take(10)->get();
        $diet = Diet::where('status','active')->orderBy('id','desc')->take(10)->get();
        $workouttype = WorkoutType::where('status','active')->orderBy('id','desc')->take(10)->get();
        $workout = Workout::where('status','active')->orderBy('id','desc')->take(10)->get();
        $exercise = Exercise::where('status','active')->orderBy('id','desc')->take(10)->get();
        $featured_diet = Diet::where('status','active')->where('is_featured', 'yes')->orderBy('id', 'desc')->take(10)->get();
        $productQuery = Product::query()
            ->where('status', 'active')
            ->when($userId, function ($q) use ($userId) {
                $q->with([
                    'favouriteProducts' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                    'cartItems' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                ]);
            });

        $featured_products = (clone $productQuery)
            ->where('featured', 'yes')
            ->orderBy('id', 'desc')
            ->take(20)
            ->get();

        if ($featured_products->isEmpty()) {
            $featured_products = (clone $productQuery)
                ->orderBy('id', 'desc')
                ->take(20)
                ->get();
        }

        $banners = Banner::active()->orderBy('display_order')->orderByDesc('created_at')->get();
        $successStories = SuccessStory::active()->orderBy('display_order')->orderByDesc('created_at')->get();

        $response = [
            'bodypart'      => BodyPartResource::collection($bodypart),
            'level'         => LevelResource::collection($level),
            'equipment'     => EquipmentResource::collection($equipment),
            'exercise'      => ExerciseResource::collection($exercise),
            'diet'          => DietResource::collection($diet),
            'workouttype'   => WorkoutTypeResource::collection($workouttype),
            'workout'       => WorkoutResource::collection($workout),
            'featured_diet' => DietResource::collection($featured_diet),
            'featured_products' => ProductResource::collection($featured_products),
            'product_banners' => BannerResource::collection($banners),
            'success_stories' => SuccessStoryResource::collection($successStories),
        ];
        $response['subscription'] = SettingData('subscription', 'subscription_system') ?? '1';
        $response['AdsBannerDetail'] = SettingData('AdsBannerDetail') ?? [];
        return json_custom_response($response);
    }

    public function dashboard(Request $request)
    {
        $bodypart = BodyPart::where('status','active')->orderBy('id','desc')->take(10)->get();
        $level = Level::where('status','active')->orderBy('id','desc')->take(10)->get();
        $equipment = Equipment::where('status','active')->orderBy('id','desc')->take(10)->get();
        $workout = Workout::where('status','active')->orderBy('id','desc')->take(10)->get();
                
        $banners = Banner::active()->orderBy('display_order')->orderByDesc('created_at')->get();
        $successStories = SuccessStory::active()->orderBy('display_order')->orderByDesc('created_at')->get();

        $response = [
            'bodypart'      => BodyPartResource::collection($bodypart),
            'level'         => LevelResource::collection($level),
            'equipment'     => EquipmentResource::collection($equipment),
            'workout'       => WorkoutResource::collection($workout),
            'product_banners' => BannerResource::collection($banners),
            'success_stories' => SuccessStoryResource::collection($successStories),
        ];
        $response['subscription'] = SettingData('subscription', 'subscription_system') ?? '1';
        $response['AdsBannerDetail'] = SettingData('AdsBannerDetail') ?? [];
        
        return json_custom_response($response);
    }

    public function getSetting()
    {
        $setting = Setting::query();
        
        $setting->when(request('type'), function ($q) {
            return $q->where('type', request('type'));
        });

        $setting = $setting->get();
        $response = [
            'data' => $setting,
        ];
        $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
        $currency = currencyArray($currency_code);
        $response['currency_setting'] = [
            'name' => $currency['name'] ?? 'United States (US) dollar',
            'symbol' => $currency['symbol'] ?? '$',
            'code' => strtolower($currency['code']) ?? 'usd',
            'position' => SettingData('CURRENCY', 'CURRENCY_POSITION') ?? 'left',
        ];

        return json_custom_response($response);
    }
}