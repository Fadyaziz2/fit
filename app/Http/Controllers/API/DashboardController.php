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
class DashboardController extends Controller
{
    public function dashboardDetail(Request $request)
    {
        $bodypart = BodyPart::where('status','active')->orderBy('id','desc')->paginate(10);
        $level = Level::where('status','active')->orderBy('id','desc')->paginate(10);
        $equipment = Equipment::where('status','active')->orderBy('id','desc')->paginate(10);
        $diet = Diet::where('status','active')->orderBy('id','desc')->paginate(10);
        $workouttype = WorkoutType::where('status','active')->orderBy('id','desc')->paginate(10);
        $workout = Workout::where('status','active')->orderBy('id','desc')->paginate(10);
        $exercise = Exercise::where('status','active')->orderBy('id','desc')->paginate(10);
        $featured_diet = Diet::where('status','active')->where('is_featured', 'yes')->orderBy('id', 'desc')->paginate(10);
        
        $response = [
            'bodypart'      => BodyPartResource::collection($bodypart),
            'level'         => LevelResource::collection($level),
            'equipment'     => EquipmentResource::collection($equipment),
            'exercise'      => ExerciseResource::collection($exercise),
            'diet'          => DietResource::collection($diet),
            'workouttype'   => WorkoutTypeResource::collection($workouttype),
            'workout'       => WorkoutResource::collection($workout),
            'featured_diet' => DietResource::collection($featured_diet),
        ];
        $response['subscription'] = SettingData('subscription', 'subscription_system') ?? '1';
        $response['AdsBannerDetail'] = SettingData('AdsBannerDetail') ?? [];
        return json_custom_response($response);
    }

    public function dashboard(Request $request)
    {
        $bodypart = BodyPart::where('status','active')->orderBy('id','desc')->paginate(10);
        $level = Level::where('status','active')->orderBy('id','desc')->paginate(10);
        $equipment = Equipment::where('status','active')->orderBy('id','desc')->paginate(10);
        $workout = Workout::where('status','active')->orderBy('id','desc')->paginate(10);
                
        $response = [
            'bodypart'      => BodyPartResource::collection($bodypart),
            'level'         => LevelResource::collection($level),
            'equipment'     => EquipmentResource::collection($equipment),
            'workout'       => WorkoutResource::collection($workout),
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