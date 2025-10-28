<?php

// Controllers
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Security\RolePermission;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\Security\PermissionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LanguageController;

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
// Packages
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ChatThreadController;
use App\Http\Controllers\CategoryDietController;
use App\Http\Controllers\WorkoutTypeController;
use App\Http\Controllers\DietController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\BodyPartController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ExclusiveOfferController;

use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOrderController;
use App\Http\Controllers\DiscountCodeController;
use App\Http\Controllers\SuccessStoryController;
use App\Http\Controllers\ClinicBranchController;
use App\Http\Controllers\ClinicSpecialistController;
use App\Http\Controllers\ClinicSpecialistScheduleController;
use App\Http\Controllers\ClinicFreeBookingRequestController;
use App\Http\Controllers\ClinicAppointmentController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\EmailController;

use App\Http\Controllers\PushNotificationController;

use App\Http\Controllers\SubscriptionController;

use App\Http\Controllers\QuotesController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\DefaultkeywordController;
use App\Http\Controllers\LanguageListController;
use App\Http\Controllers\LanguageWithKeywordListController;
use App\Http\Controllers\SubAdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';

Route::get('storage/{path}', function ($path) {
    if (Storage::disk('public')->exists($path)) {
        return Storage::disk('public')->response($path);
    }

    abort(404);
})->where('path', '.*');

Route::get('optimize', function () {
    Artisan::call('optimize:clear');
});

use Illuminate\Support\Facades\Schema;
Route::get('migrate', function(){
    try {
        // check user table exist or not
        $schema = Schema::hasTable('users');
        // Run migrations
        Artisan::call('migrate', ['--force' => true]);

        // if users table not exit than run seeder command
        if( !$schema ) {
            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);
        }

        return redirect()->route('dashboard');
    } catch (\Exception $e) {
        return 'Migration failed: ' . $e->getMessage();
    }
});

Route::get('language/{locale}', [ HomeController::class, 'changeLanguage'])->name('change.language');

Route::group(['middleware' => [ 'auth', 'useractive' ]], function () {
    // Permission Module
    Route::resource('permission', PermissionController::class);
    Route::get('permission/add/{type}',[ PermissionController::class, 'addPermission' ])->name('permission.add');
    Route::post('permission/save',[ PermissionController::class, 'savePermission' ])->name('permission.save');

	Route::resource('role', RoleController::class);

    // Dashboard Routes
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
	Route::get('changeStatus', [ HomeController::class, 'changeStatus'])->name('changeStatus');

    // Users Module
    Route::resource('users', UserController::class);
    Route::post('users/{user}/attachments', [UserController::class, 'storeAttachments'])->name('users.attachments.store');
    Route::delete('users/{user}/attachments/{media}', [UserController::class, 'destroyAttachment'])->name('users.attachments.destroy');
    Route::post('users/{user}/body-compositions', [UserController::class, 'storeBodyComposition'])->name('users.body-compositions.store');
    Route::delete('users/{user}/body-compositions/{composition}', [UserController::class, 'destroyBodyComposition'])->name('users.body-compositions.destroy');
    Route::post('users/{user}/weights', [UserController::class, 'storeWeightEntry'])->name('users.weights.store');
    Route::delete('users/{user}/weights/{weightEntry}', [UserController::class, 'destroyWeightEntry'])->name('users.weights.destroy');
    Route::post('users/{user}/freeze-subscription', [UserController::class, 'freezeSubscription'])->name('users.freeze-subscription');
    Route::get('sms', [SmsController::class, 'index'])->name('sms.index')->middleware('permission:sms-center-list');
    Route::post('sms', [SmsController::class, 'send'])->name('sms.send')->middleware('permission:sms-center-send');
    Route::get('emails', [EmailController::class, 'index'])->name('emails.index')->middleware('permission:email-center-list');
    Route::post('emails', [EmailController::class, 'send'])->name('emails.send')->middleware('permission:email-center-send');
    Route::resource('equipment', EquipmentController::class);

    Route::resource('subadmin', SubAdminController::class);

    Route::get('chat-center', [ChatThreadController::class, 'index'])->name('chat.index')->middleware('permission:chat-center-list');
    Route::get('chat-center/users', [ChatThreadController::class, 'searchUsers'])->name('chat.users.search')->middleware('permission:chat-center-reply');
    Route::post('chat-center/threads', [ChatThreadController::class, 'store'])->name('chat.threads.store')->middleware('permission:chat-center-reply');
    Route::get('chat-center/threads', [ChatThreadController::class, 'threads'])->name('chat.threads.index')->middleware('permission:chat-center-list');
    Route::get('chat-center/threads/{thread}', [ChatThreadController::class, 'show'])->name('chat.threads.show')->middleware('permission:chat-center-list');
    Route::post('chat-center/threads/{thread}/messages', [ChatThreadController::class, 'send'])->name('chat.threads.messages.store')->middleware('permission:chat-center-reply');

    Route::get('users-graph',[ UserController::class, 'fetchUserGraph' ])->name('user.fetchGraph');
    
    //assign deit
    Route::get('assigndiet/{user_id}',[ UserController::class, 'assignDietForm' ])->name('add.assigndiet');
    Route::get('assigndiet/{user_id}/diet/{diet_id}',[ UserController::class, 'editAssignDietMeals' ])->name('edit.assigndiet');
    Route::post('assigndiet',[ UserController::class, 'assignDietSave' ])->name('save.assigndiet');
    Route::post('assigndiet/meals',[ UserController::class, 'updateAssignDietMeals' ])->name('update.assigndiet.meals');
    Route::post('assigndiet-delete',[ UserController::class, 'assignDietDestroy' ])->name('delete.assigndiet');
    Route::post('users/{user}/health-profile',[ UserController::class, 'updateHealthProfile' ])->name('users.health.update');

    Route::get('assigndiet-list',[ UserController::class, 'getAssignDietList'])->name('get.assigndietlist');
    //assign workout
    Route::get('assignworkout/{user_id}',[ UserController::class, 'assignWorkoutForm' ])->name('add.assignworkout');
    Route::post('assignworkout',[ UserController::class, 'assignWorkoutSave' ])->name('save.assignworkout');
    Route::post('assignworkout-delete',[ UserController::class, 'assignWorkoutDestroy' ])->name('delete.assignworkout');

    Route::get('assignworkout-list',[ UserController::class, 'getAssignWorkoutList'])->name('get.assignworkoutlist');

    Route::get('recommendproduct/{user_id}',[ UserController::class, 'recommendProductForm' ])->name('add.recommendproduct');
    Route::post('recommendproduct',[ UserController::class, 'recommendProductSave' ])->name('save.recommendproduct');
    Route::post('recommendproduct-delete',[ UserController::class, 'recommendProductDestroy' ])->name('delete.recommendproduct');

    Route::get('recommendproduct-list',[ UserController::class, 'getRecommendProductList'])->name('get.recommendproductlist');

    //Fitness CategoryDiet
    Route::resource('categorydiet', CategoryDietController::class);
    
    //Fitness Workout 
    Route::resource('workouttype', WorkoutTypeController::class);

    Route::resource('diet', DietController::class);
    Route::get('diet/{diet}/servings', [DietController::class, 'servings'])->name('diet.servings');
    Route::resource('ingredient', IngredientController::class);
    Route::resource('category', CategoryController::class);
    
    //FitnessTags
    Route::resource('tags', TagsController::class);
    //Fitnessleval
    Route::resource('level', LevelController::class);

    Route::resource('bodypart', BodyPartController::class);

    Route::resource('exercise', ExerciseController::class);

    Route::resource('workout', WorkoutController::class);

    Route::post('workoutdays-exercise-delete', [ WorkoutController::class , 'workoutDaysExerciseDelete'])->name('workoutdays.exercise.delete');

    Route::resource('post', PostController::class);
    
    //product
    Route::resource('product',ProductController::class);
    Route::resource('exclusive-offer', ExclusiveOfferController::class);
    Route::resource('discount-codes', DiscountCodeController::class);
    Route::resource('banner', BannerController::class);
    Route::resource('successstory', SuccessStoryController::class);
    Route::prefix('clinic')->name('clinic.')->group(function () {
        Route::resource('branches', ClinicBranchController::class)->except(['show']);
        Route::resource('specialists', ClinicSpecialistController::class)->except(['show']);
        Route::resource('schedules', ClinicSpecialistScheduleController::class)->except(['show']);
        Route::resource('free-requests', ClinicFreeBookingRequestController::class)
            ->only(['index', 'store', 'edit', 'update'])
            ->names([
                'index' => 'free_requests.index',
                'store' => 'free_requests.store',
                'edit' => 'free_requests.edit',
                'update' => 'free_requests.update',
            ]);
        Route::get('appointments/available-slots', [ClinicAppointmentController::class, 'availableSlots'])->name('appointments.available_slots');
        Route::post('appointments/manual', [ClinicAppointmentController::class, 'store'])->name('appointments.store');
        Route::post('appointments/{appointment}/convert', [ClinicAppointmentController::class, 'convertManualFree'])->name('appointments.convert');
        Route::get('free-requests/available-slots', [ClinicFreeBookingRequestController::class, 'availableSlots'])
            ->name('free_requests.available_slots');
        Route::resource('appointments', ClinicAppointmentController::class)->only(['index', 'edit', 'update']);
    });
    Route::resource('product-orders', ProductOrderController::class)->only(['index', 'show', 'update']);
    Route::resource('productcategory',ProductCategoryController::class);

    Route::resource('package',PackageController::class);

    Route::post('remove-file',[ HomeController::class, 'removeFile' ])->name('remove.file');
    
    Route::get('setting/{page?}', [ SettingController::class, 'settings'])->name('setting.index');
    Route::post('layout-page', [ SettingController::class, 'layoutPage'])->name('layout_page');
    Route::post('settings/save', [ SettingController::class , 'settingsUpdates'])->name('settingsUpdates');
    Route::post('mobile-config-save',[ SettingController::class , 'settingUpdate'])->name('settingUpdate');
	Route::post('env-setting', [ SettingController::class , 'envChanges'])->name('envSetting');
    Route::post('payment-settings/save',[ SettingController::class , 'paymentSettingsUpdate'])->name('paymentSettingsUpdate');
    Route::post('subscription-settings/save',[ SettingController::class , 'subscriptionSettingsUpdate'])->name('subscriptionSettingsUpdate');

    Route::post('get-lang-file', [ LanguageController::class, 'getFile' ] )->name('getLanguageFile');
    Route::post('save-lang-file', [ LanguageController::class, 'saveFileContent' ] )->name('saveLangContent');

    Route::post('update-profile', [ SettingController::class , 'updateProfile'])->name('updateProfile');
    Route::post('change-password', [ SettingController::class , 'changePassword'])->name('changePassword');

    Route::get('pages/term-condition',[ SettingController::class, 'termAndCondition'])->name('pages.term_condition');
    Route::post('term-condition-save',[ SettingController::class, 'saveTermAndCondition'])->name('pages.term_condition_save');

    Route::get('pages/privacy-policy',[ SettingController::class, 'privacyPolicy'])->name('pages.privacy_policy');
    Route::post('privacy-policy-save',[ SettingController::class, 'savePrivacyPolicy'])->name('pages.privacy_policy_save');

    Route::resource('pushnotification', PushNotificationController::class);
    Route::get('resend-pushnotification/{id}',[ PushNotificationController::class, 'edit'])->name('resend.pushnotification');

    Route::resource('subscription', SubscriptionController::class);

    Route::resource('quotes', QuotesController::class);

    Route::resource('classschedule', ClassScheduleController::class);

    // Language Setting Route 
    Route::resource('screen', ScreenController::class);
    Route::resource('defaultkeyword', DefaultkeywordController::class);
    Route::resource('languagelist', LanguageListController::class);
    Route::resource('languagewithkeyword', LanguageWithKeywordListController::class);
    Route::get('download-language-with-keyword-list', [ LanguageWithKeywordListController::class, 'downloadLanguageWithKeywordList'])->name('download.language.with,keyword.list');

    Route::post('import-language-keyword', [ LanguageWithKeywordListController::class,'importlanguagewithkeyword' ])->name('import.languagewithkeyword');
    Route::get('bulklanguagedata', [ LanguageWithKeywordListController::class,'bulklanguagedata' ])->name('bulk.language.data');
    Route::get('help', [ LanguageWithKeywordListController::class,'help' ])->name('help');
    Route::get('download-template', [ LanguageWithKeywordListController::class,'downloadtemplate' ])->name('download.template');
});

Route::get('/ajax-list',[ HomeController::class, 'getAjaxList' ])->name('ajax-list');


//Auth pages Routs
Route::group(['prefix' => 'auth'], function() {
    Route::get('signin', [HomeController::class, 'signin'])->name('auth.signin');
    Route::get('signup', [HomeController::class, 'signup'])->name('auth.signup');
    Route::get('confirmmail', [HomeController::class, 'confirmmail'])->name('auth.confirmmail');
    Route::get('lockscreen', [HomeController::class, 'lockscreen'])->name('auth.lockscreen');
    Route::get('recover-password', [HomeController::class, 'recoverpw'])->name('auth.recover-password');
    Route::get('userprivacysetting', [HomeController::class, 'userprivacysetting'])->name('auth.userprivacysetting');
});

//Error Page Route
Route::group(['prefix' => 'errors'], function() {
    Route::get('error404', [HomeController::class, 'error404'])->name('errors.error404');
    Route::get('error500', [HomeController::class, 'error500'])->name('errors.error500');
    Route::get('maintenance', [HomeController::class, 'maintenance'])->name('errors.maintenance');
});

//Extra Page Route
Route::get('privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('terms-condition', [HomeController::class, 'termsCondition'])->name('terms.condition');
