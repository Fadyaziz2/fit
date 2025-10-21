<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\UsersDataTable;
use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionFreeze;
use App\Models\AssignDiet;
use App\Models\AssignWorkout;
use App\Models\Diet;
use App\Models\Workout;
use App\Helpers\AuthHelper;
use App\Models\Role;
use App\Http\Requests\UserRequest;
use App\DataTables\SubscriptionDataTable;
use App\Models\UserGraph;
use Carbon\Carbon;
use App\Models\Ingredient;
use App\Models\UserBodyComposition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use App\Models\UserProductRecommendation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\Specialist;
use App\Support\MealPlan;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(UsersDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',[ 'form' => __('message.user') ] );
        $auth_user = AuthHelper::authSession();
        if( !$auth_user->can('user-list') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $assets = ['data-table'];
        
        $headerAction = $auth_user->can('user-add') ? '<a href="'.route('users.create').'" class="btn btn-sm btn-primary" role="button">'.__('message.add_form_title', [ 'form' => __('message.user')]).'</a>' : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user', 'assets', 'headerAction'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if( !auth()->user()->can('user-add') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $assets = ['phone'];
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.user')]);
        $roles = Role::where('status', 1)->where('name', 'user')->get()->pluck('title', 'name');
        return view('users.form', compact('pageTitle','roles','assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        if( !auth()->user()->can('user-add') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $request['password'] = bcrypt($request->password);
        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100,1000);
        $request['display_name'] = $request['first_name']." ".$request['last_name'];
        $user = User::create($request->all());

        storeMediaFile($user, $request->profile_image, 'profile_image');

        $user->assignRole($request->user_type);

        // Save user Profile data...
        // $user->userProfile()->create($request->userProfile);

        return redirect()->route('users.index')->withSuccess(__('message.save_form',['form' => __('message.user')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SubscriptionDataTable $dataTable,$id)
    {
        if( !auth()->user()->can('user-show') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = User::with([
            'userProfile.specialist.branch',
            'userProfile.specialist.branches',
            'roles',
            'dislikedIngredients',
            'userDiseases',
            'userFavouriteWorkout.workout.media',
            'userFavouriteDiet.diet.media',
            'userFavouriteProducts.product.media',
            'cartItems.product.media',
            'cartItems.product.productcategory',
            'bodyCompositions',
        ])->findOrFail($id);
        $data->load('media');

        $subscriptions = Subscription::where('user_id', $id)->get();

        $activeSubscription = Subscription::with(['package', 'activeFreeze', 'scheduledFreezes'])
            ->where('user_id', $id)
            ->whereIn('status', [
                config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
                config('constant.SUBSCRIPTION_STATUS.PAUSED'),
            ])
            ->orderByDesc('id')
            ->first();

        $activeFreeze = $activeSubscription?->activeFreeze;
        $upcomingFreezes = $activeSubscription?->scheduledFreezes ?? collect();
        $canFreezeSubscription = $activeSubscription
            ? ! $activeSubscription->freezes()
                ->whereIn('status', [
                    SubscriptionFreeze::STATUS_ACTIVE,
                    SubscriptionFreeze::STATUS_SCHEDULED,
                ])->exists()
            : false;

        $profileImage = getSingleMedia($data, 'profile_image');
        $attachments = $data->getMedia('attachments');
        $favouriteWorkouts = $data->userFavouriteWorkout->map(function ($favourite) {
            return $favourite->workout;
        })->filter();

        $favouriteDiets = $data->userFavouriteDiet->map(function ($favourite) {
            return $favourite->diet;
        })->filter();

        $favouriteProducts = $data->userFavouriteProducts->map(function ($favourite) {
            return $favourite->product;
        })->filter();

        $cartItems = $data->cartItems;

        $specialists = Specialist::with(['branch', 'branches'])->orderBy('name')->get();

        $weightEntries = $data->userGraph()
            ->where('type', 'weight')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return $dataTable->with('user_id',$id)->render('users.profile', compact(
            'data',
            'profileImage',
            'subscriptions',
            'attachments',
            'favouriteWorkouts',
            'favouriteDiets',
            'favouriteProducts',
            'cartItems',
            'specialists',
            'activeSubscription',
            'activeFreeze',
            'upcomingFreezes',
            'canFreezeSubscription',
            'weightEntries'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if( !auth()->user()->can('user-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = User::with('userProfile')->findOrFail($id);
        $assets = ['phone'];

        $pageTitle = __('message.update_form_title',[ 'form' => __('message.user')]);
        
        $profileImage = getSingleMedia($data, 'profile_image');
        $roles = Role::where('status', 1)->where('name', 'user')->get()->pluck('title', 'name');

        return view('users.form', compact('data', 'id', 'profileImage', 'pageTitle', 'roles','assets'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        if( !auth()->user()->can('user-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $user = User::with('userProfile')->findOrFail($id);      
        $request['display_name'] = $request['first_name']." ".$request['last_name'];
        $user->removeRole($user->user_type);
        
        // User user data..
        $user->fill($request->all())->update();

        $user->assignRole($request['user_type']);
        // Save user image...
        if (isset($request->profile_image) && $request->profile_image != null) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        // user profile data....
        // $user->userProfile->fill($request->userProfile)->update();

        if(auth()->check()){
            return redirect()->route('users.index')->withSuccess(__('message.update_form', ['form' => __('message.user')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form', ['form' => __('message.user')]));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if( !auth()->user()->can('user-delete') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $user = User::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.user')]);

        if($user != '') {
            $user->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.user')]);

        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message, 'datatable_reload' => 'dataTable_wrapper']);
        }

        return redirect()->back()->with($status,$message);

    }

    public function storeAttachments(Request $request, User $user)
    {
        if( !auth()->user()->can('user-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx,mp4,mov,avi,mkv', 'max:51200'],
        ], [], [
            'attachments' => __('message.attachments'),
            'attachments.*' => __('message.attachments'),
        ]);

        $files = $request->file('attachments', []);

        foreach ($files as $file) {
            $user->addMedia($file)->preservingOriginal()->toMediaCollection('attachments');
        }

        return redirect()->back()->withSuccess(__('message.save_form', ['form' => __('message.attachments')]));
    }

    public function destroyAttachment(User $user, Media $media)
    {
        if( !auth()->user()->can('user-edit') ) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        abort_if(
            $media->model_type !== $user->getMorphClass() ||
            $media->model_id !== $user->getKey() ||
            $media->collection_name !== 'attachments',
            404
        );

        $media->delete();

        return redirect()->back()->withSuccess(__('message.delete_form', ['form' => __('message.attachments')]));
    }

    public function storeBodyComposition(Request $request, User $user)
    {
        if (! auth()->user()->can('user-edit')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $validator = Validator::make($request->all(), [
            'recorded_at' => ['required', 'date'],
            'fat_weight' => ['nullable', 'numeric', 'min:0'],
            'water_weight' => ['nullable', 'numeric', 'min:0'],
            'muscle_weight' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'recorded_at' => __('message.body_composition_date'),
            'fat_weight' => __('message.fat_weight'),
            'water_weight' => __('message.water_weight'),
            'muscle_weight' => __('message.muscle_weight'),
        ]);

        $validated = $validator->validate();

        $user->bodyCompositions()->updateOrCreate(
            ['recorded_at' => $validated['recorded_at']],
            [
                'fat_weight' => $validated['fat_weight'],
                'water_weight' => $validated['water_weight'],
                'muscle_weight' => $validated['muscle_weight'],
            ]
        );

        return redirect()->back()->withSuccess(__('message.body_composition_saved'));
    }

    public function destroyBodyComposition(User $user, UserBodyComposition $composition)
    {
        if (! auth()->user()->can('user-edit')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        abort_if($composition->user_id !== $user->id, 404);

        $composition->delete();

        return redirect()->back()->withSuccess(__('message.delete_form', ['form' => __('message.body_composition_entry')]));
    }

    public function storeWeightEntry(Request $request, User $user)
    {
        if (! auth()->user()->can('user-edit')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        $validator = Validator::make($request->all(), [
            'weight_date' => ['required', 'date'],
            'weight_value' => ['required', 'numeric', 'min:0'],
            'weight_unit' => ['required', Rule::in(['kg', 'lbs'])],
        ], [], [
            'weight_date' => __('message.weight_entry_date'),
            'weight_value' => __('message.weight_value'),
            'weight_unit' => __('message.weight_unit'),
        ]);

        $validated = $validator->validate();

        UserGraph::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'weight',
                'date' => $validated['weight_date'],
            ],
            [
                'value' => $validated['weight_value'],
                'unit' => $validated['weight_unit'],
            ]
        );

        return redirect()->back()->withSuccess(__('message.weight_entry_saved'));
    }

    public function destroyWeightEntry(User $user, UserGraph $weightEntry)
    {
        if (! auth()->user()->can('user-edit')) {
            $message = __('message.permission_denied_for_account');

            return redirect()->back()->withErrors($message);
        }

        abort_if($weightEntry->user_id !== $user->id || $weightEntry->type !== 'weight', 404);

        $weightEntry->delete();

        return redirect()->back()->withSuccess(__('message.delete_form', ['form' => __('message.weight_entry')]));
    }

    public function assignDietForm(Request $request)
    {
        $user_id = request('user_id');
        $view = view('users.assign_diet',compact('user_id'))->render();
        return response()->json(['data' =>  $view, 'status'=> true]);
    }

    public function getAssignDietList(Request $request)
    {
        $user_id = request('user_id');
        $data = Diet::myDiet($user_id)
            ->with(['userAssignDiet' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            }])
            ->orderBy('id', 'desc')
            ->get();
        $dietPrintPlans = $this->prepareDietPrintPlans($data);

        $view = view('users.assign-diet-list', compact('user_id', 'data', 'dietPrintPlans'))->render();
        return response()->json([ 'data' => $view, 'status' => true ]);
    }
 
    public function assignDietSave(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'diet_id' => ['required', 'exists:diets,id'],
        ]);

        $diet = Diet::find($validated['diet_id']);
        $user = User::with('dislikedIngredients')->find($validated['user_id']);

        if ($diet && $user) {
            $dislikedIngredientIds = $user->dislikedIngredients
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique();

            if ($dislikedIngredientIds->isNotEmpty()) {
                $structure = $this->buildDietPlanStructure($diet);

                $dietIngredientIds = collect($structure['plan'])
                    ->flatten(2)
                    ->filter(fn ($value) => is_numeric($value))
                    ->map(fn ($value) => (int) $value)
                    ->unique();

                $conflicts = $dietIngredientIds->intersect($dislikedIngredientIds);

                if ($conflicts->isNotEmpty() && ! $request->boolean('force_confirm')) {
                    $conflictNames = Ingredient::whereIn('id', $conflicts->all())
                        ->pluck('title')
                        ->filter()
                        ->implode(', ');

                    return response()->json([
                        'status' => false,
                        'event' => 'confirm',
                        'message' => __('message.diet_contains_disliked', ['ingredients' => $conflictNames]),
                        'confirm_label' => __('message.continue_anyway'),
                        'cancel_label' => __('message.change_meal'),
                    ]);
                }
            }
        }

        $servings = (int) ($diet->servings ?? 0);

        $serveTimes = [];

        if ($servings > 0) {
            $serveTimesInput = $request->validate([
                'serve_times' => ['required', 'array', 'size:' . $servings],
                'serve_times.*' => ['required', 'date_format:H:i'],
            ]);

            $serveTimes = array_values($serveTimesInput['serve_times']);
        }

        AssignDiet::updateOrCreate(
            ['user_id' => $validated['user_id'], 'diet_id' => $validated['diet_id']],
            ['serve_times' => $serveTimes]
        );

        $message = __('message.assigndiet');

        return response()->json(['status' => true, 'type' => 'diet', 'event' => 'norefresh', 'message' => $message]);
    }

    public function updateHealthProfile(Request $request, $userId)
    {
        if (! auth()->user()->can('user-edit')) {
            $message = __('message.permission_denied_for_account');

            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => $message,
            ]);
        }

        $user = User::with(['userProfile', 'dislikedIngredients', 'userDiseases'])->findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'disliked_ingredients' => ['nullable', 'array'],
            'disliked_ingredients.*' => ['integer', 'exists:ingredients,id'],
            'diseases' => ['nullable', 'array'],
            'diseases.*.name' => ['nullable', 'string', 'max:255'],
            'diseases.*.started_at' => ['nullable', 'date'],
            'specialist_id' => ['nullable', 'integer', 'exists:specialists,id'],
            'notes' => ['nullable', 'string'],
        ], [], [
            'disliked_ingredients.*' => __('message.ingredient'),
            'diseases.*.name' => __('message.disease_name'),
            'diseases.*.started_at' => __('message.disease_start_date'),
        ]);

        $validator->after(function ($validator) use ($request) {
            $diseases = $request->input('diseases', []);

            foreach ($diseases as $index => $disease) {
                $name = isset($disease['name']) ? trim((string) $disease['name']) : '';
                $date = $disease['started_at'] ?? null;

                if ($name === '' && ($date === null || $date === '')) {
                    continue;
                }

                if ($name === '' || $date === null || $date === '') {
                    $validator->errors()->add("diseases.$index", __('message.disease_name_and_date_required'));
                }
            }
        });

        $validated = $validator->validate();

        DB::transaction(function () use ($user, $validated) {
            $dislikedIds = collect($validated['disliked_ingredients'] ?? [])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all();

            $user->dislikedIngredients()->sync($dislikedIds);

            $user->userDiseases()->delete();

            $diseasePayload = [];
            foreach ($validated['diseases'] ?? [] as $disease) {
                $name = isset($disease['name']) ? trim((string) $disease['name']) : '';
                $date = $disease['started_at'] ?? null;

                if ($name === '' || $date === null || $date === '') {
                    continue;
                }

                $diseasePayload[] = [
                    'name' => $name,
                    'started_at' => $date,
                ];
            }

            if (! empty($diseasePayload)) {
                $user->userDiseases()->createMany($diseasePayload);
            }

            $notes = $validated['notes'] ?? null;

            $profile = $user->userProfile ?: $user->userProfile()->create([]);
            $profile->specialist_id = $validated['specialist_id'] ?? null;
            $profile->notes = $notes;
            $profile->save();
        });

        return response()->json([
            'status' => true,
            'event' => 'callback',
            'message' => __('message.health_profile_updated'),
        ]);
    }

    public function freezeSubscription(Request $request, User $user)
    {
        if (! auth()->user()->can('user-edit')) {
            $message = __('message.permission_denied_for_account');

            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => $message,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'subscription_id' => ['required', 'integer'],
            'freeze_start_date' => ['required', 'date_format:Y-m-d H:i'],
            'freeze_end_date' => ['required', 'date_format:Y-m-d H:i', 'after:freeze_start_date'],
        ], [], [
            'freeze_start_date' => __('message.subscription_freeze_form_start'),
            'freeze_end_date' => __('message.subscription_freeze_form_end'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => $validator->errors()->first(),
            ]);
        }

        $subscription = Subscription::where('id', $request->integer('subscription_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $subscription || ! in_array($subscription->status, [config('constant.SUBSCRIPTION_STATUS.ACTIVE'), config('constant.SUBSCRIPTION_STATUS.PAUSED')], true)) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => __('message.subscription_freeze_not_allowed'),
            ]);
        }

        $start = Carbon::parse($request->input('freeze_start_date'));
        $end = Carbon::parse($request->input('freeze_end_date'));

        if ($start->lt(Carbon::now()->startOfDay())) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => __('message.subscription_freeze_invalid_dates'),
            ]);
        }

        $hasOverlap = $subscription->freezes()
            ->whereIn('status', [SubscriptionFreeze::STATUS_ACTIVE, SubscriptionFreeze::STATUS_SCHEDULED])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('freeze_start_date', [$start, $end])
                    ->orWhereBetween('freeze_end_date', [$start, $end])
                    ->orWhere(function ($inner) use ($start, $end) {
                        $inner->where('freeze_start_date', '<', $start)
                            ->where('freeze_end_date', '>', $end);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => __('message.subscription_freeze_conflict'),
            ]);
        }

        $status = $start->lessThanOrEqualTo(Carbon::now())
            ? SubscriptionFreeze::STATUS_ACTIVE
            : SubscriptionFreeze::STATUS_SCHEDULED;

        $freeze = $subscription->freezes()->create([
            'user_id' => $user->id,
            'freeze_start_date' => $start,
            'freeze_end_date' => $end,
            'status' => $status,
        ]);

        if ($status === SubscriptionFreeze::STATUS_ACTIVE) {
            $subscription->status = config('constant.SUBSCRIPTION_STATUS.PAUSED');
            $subscription->save();

            $user->is_subscribe = 0;
            $user->save();
        }

        $messageKey = $status === SubscriptionFreeze::STATUS_ACTIVE
            ? 'message.subscription_freeze_active'
            : 'message.subscription_freeze_scheduled';

        return response()->json([
            'status' => true,
            'event' => 'refresh',
            'message' => __($messageKey, [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]),
        ]);
    }

    protected function buildDietPlanStructure(Diet $diet): array
    {
        $planArray = MealPlan::normalizePlan($diet->ingredients ?? [], false, true);
        $planArray = MealPlan::reindexPlan($planArray);

        $existingDays = count($planArray);
        $daysCount = (int) ($diet->days ?? 0);
        if ($daysCount < $existingDays) {
            $daysCount = $existingDays;
        }

        $maxMeals = 0;
        foreach ($planArray as $meals) {
            $maxMeals = max($maxMeals, count($meals));
        }

        $servingsCount = (int) ($diet->servings ?? 0);
        if ($servingsCount > $maxMeals) {
            $maxMeals = $servingsCount;
        }

        if ($daysCount === 0) {
            $daysCount = $existingDays;
        }

        $normalizedPlan = [];

        for ($day = 0; $day < $daysCount; $day++) {
            $dayMeals = $planArray[$day] ?? [];
            if ($maxMeals > 0) {
                $dayMeals = array_pad($dayMeals, $maxMeals, []);
            }

            $normalizedPlan[$day] = array_map(function ($meal) {
                return MealPlan::normalizeMeal($meal);
            }, $dayMeals);
        }

        if ($daysCount === 0 && empty($normalizedPlan)) {
            $normalizedPlan = [];
        }

        return [
            'plan' => $normalizedPlan,
            'maxMeals' => $maxMeals,
            'days' => $daysCount,
        ];
    }

    protected function prepareDietPrintPlans(Collection $diets): array
    {
        if ($diets->isEmpty()) {
            return [];
        }

        $plans = [];
        $allIngredientIds = [];

        foreach ($diets as $diet) {
            $structure = $this->buildDietPlanStructure($diet);
            $basePlan = $structure['plan'];

            $assignment = $diet->userAssignDiet->first();
            $customPlan = [];
            $serveTimes = [];

            if ($assignment) {
                if (is_array($assignment->custom_plan)) {
                    $customPlan = MealPlan::normalizePlan($assignment->custom_plan, false, true);
                }

                $serveTimes = $this->normalizeServeTimes($assignment->serve_times ?? []);
            }

            $mergedPlan = MealPlan::mergeNormalizedPlans($basePlan, $customPlan);

            $plans[$diet->id] = [
                'plan' => $mergedPlan,
                'serve_times' => $serveTimes,
            ];

            foreach ($mergedPlan as $dayMeals) {
                if (!is_array($dayMeals)) {
                    continue;
                }

                foreach ($dayMeals as $mealEntries) {
                    if (!is_array($mealEntries)) {
                        continue;
                    }

                    foreach ($mealEntries as $entry) {
                        if (!is_array($entry)) {
                            continue;
                        }

                        $ingredientId = (int) ($entry['id'] ?? 0);

                        if ($ingredientId > 0) {
                            $allIngredientIds[] = $ingredientId;
                        }
                    }
                }
            }
        }

        $allIngredientIds = array_values(array_unique($allIngredientIds));
        $ingredients = !empty($allIngredientIds)
            ? Ingredient::whereIn('id', $allIngredientIds)->get()->keyBy('id')
            : collect();

        foreach ($plans as $dietId => $planInfo) {
            $plans[$dietId] = $this->transformPlanForPrint(
                $planInfo['plan'] ?? [],
                $planInfo['serve_times'] ?? [],
                $ingredients
            );
        }

        return $plans;
    }

    protected function normalizeServeTimes($serveTimes): array
    {
        $normalized = [];

        if (!is_array($serveTimes)) {
            return $normalized;
        }

        foreach ($serveTimes as $index => $time) {
            if ($time instanceof \DateTimeInterface) {
                $normalized[(int) $index] = $time->format('H:i');
                continue;
            }

            if (is_string($time) || (is_numeric($time) && !is_bool($time))) {
                $value = trim((string) $time);

                if ($value !== '') {
                    $normalized[(int) $index] = $value;
                }
            }
        }

        return $normalized;
    }

    protected function transformPlanForPrint(array $plan, array $serveTimes, Collection $ingredients): array
    {
        if (empty($plan)) {
            return [];
        }

        $details = [];

        foreach ($plan as $dayIndex => $dayMeals) {
            $meals = [];

            if (!is_array($dayMeals)) {
                $details[] = [
                    'day_number' => (int) $dayIndex + 1,
                    'meals' => [],
                ];

                continue;
            }

            foreach ($dayMeals as $mealIndex => $mealEntries) {
                $ingredientsList = [];

                if (is_array($mealEntries)) {
                    foreach ($mealEntries as $entry) {
                        if (!is_array($entry)) {
                            continue;
                        }

                        $ingredientId = (int) ($entry['id'] ?? 0);

                        if ($ingredientId <= 0) {
                            continue;
                        }

                        $ingredient = $ingredients->get($ingredientId);

                        if (! $ingredient) {
                            continue;
                        }

                        $ingredientsList[] = [
                            'id' => $ingredientId,
                            'title' => $ingredient->title ?? '',
                            'quantity' => $this->formatPrintQuantity($entry['quantity'] ?? null),
                        ];
                    }
                }

                $meals[] = [
                    'meal_number' => (int) $mealIndex + 1,
                    'time' => $serveTimes[$mealIndex] ?? null,
                    'ingredients' => $ingredientsList,
                ];
            }

            $details[] = [
                'day_number' => (int) $dayIndex + 1,
                'meals' => $meals,
            ];
        }

        return $details;
    }

    protected function formatPrintQuantity($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            return '';
        }

        $number = (float) $value;

        if ($number <= 0) {
            return '';
        }

        if (abs($number - round($number)) < 0.01) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    public function editAssignDietMeals($userId, $dietId)
    {
        $assignment = AssignDiet::where('user_id', $userId)
            ->where('diet_id', $dietId)
            ->first();

        if (!$assignment) {
            abort(404);
        }

        $diet = Diet::findOrFail($dietId);

        $structure = $this->buildDietPlanStructure($diet);

        $ingredients = Ingredient::orderBy('title')->get();
        $ingredientsMap = $ingredients->keyBy('id');

        $user = User::with('dislikedIngredients')->find($userId);

        $dislikedIngredientIds = $user
            ? $user->dislikedIngredients
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        $ingredientOptions = $ingredients
            ->map(function (Ingredient $ingredient) use ($dislikedIngredientIds) {
                return [
                    'id' => (int) $ingredient->id,
                    'title' => $ingredient->title,
                    'disliked' => in_array($ingredient->id, $dislikedIngredientIds, true),
                ];
            })
            ->values();

        $customPlan = $assignment->custom_plan ?? [];

        $view = view('users.edit_assign_diet', [
            'assignment' => $assignment,
            'diet' => $diet,
            'plan' => $structure['plan'],
            'maxMeals' => $structure['maxMeals'],
            'customPlan' => $customPlan,
            'ingredients' => $ingredients,
            'ingredientsMap' => $ingredientsMap,
            'dislikedIngredientIds' => $dislikedIngredientIds,
            'ingredientOptions' => $ingredientOptions,
        ])->render();

        return response()->json(['data' => $view, 'status' => true]);
    }

    public function updateAssignDietMeals(Request $request)
    {
        try {
            $planInput = $this->normalizePlanInput($request->input('plan', []), true);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => __('message.invalid_meal_selection'),
            ]);
        }

        $request->merge(['plan' => $planInput]);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'diet_id' => ['required', 'exists:diets,id'],
            'plan' => ['nullable', 'array'],
            'plan.*' => ['nullable', 'array'],
            'plan.*.*' => ['nullable', 'array'],
            'plan.*.*.*' => ['nullable', 'integer'],
        ]);

        $assignment = AssignDiet::where('user_id', $validated['user_id'])
            ->where('diet_id', $validated['diet_id'])
            ->first();

        if (!$assignment) {
            abort(404);
        }

        $diet = Diet::findOrFail($validated['diet_id']);

        $structure = $this->buildDietPlanStructure($diet);
        $normalizedDietPlan = $structure['plan'];

        $selectedPlan = $this->normalizePlanInput($validated['plan'] ?? []);

        $selectedIds = collect($selectedPlan)
            ->flatten()
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value);

        if ($selectedIds->isNotEmpty()) {
            $validIngredientIds = Ingredient::pluck('id')->map(fn ($id) => (int) $id);

            if ($selectedIds->diff($validIngredientIds)->isNotEmpty()) {
                return response()->json([
                    'status' => false,
                    'event' => 'validation',
                    'message' => __('message.invalid_meal_selection'),
                ]);
            }
        }

        $customPlan = [];

        foreach ($normalizedDietPlan as $dayIndex => $dayMeals) {
            $selectedDayMeals = $selectedPlan[$dayIndex] ?? [];

            foreach ($dayMeals as $mealIndex => $defaultIngredients) {
                $defaultSelection = $this->normalizeMealSelection($defaultIngredients);

                if (!array_key_exists($mealIndex, $selectedDayMeals)) {
                    continue;
                }

                $selectedIngredients = $this->normalizeMealSelection($selectedDayMeals[$mealIndex]);

                if ($selectedIngredients === $defaultSelection) {
                    continue;
                }

                $customPlan[$dayIndex][$mealIndex] = $selectedIngredients;
            }

            if (isset($customPlan[$dayIndex]) && empty($customPlan[$dayIndex])) {
                unset($customPlan[$dayIndex]);
            }
        }

        $assignment->custom_plan = !empty($customPlan) ? $customPlan : null;
        $assignment->save();

        $message = __('message.custom_diet_meal_updated');

        return response()->json([
            'status' => true,
            'type' => 'diet',
            'event' => 'norefresh',
            'message' => $message,
        ]);
    }

    protected function normalizePlanInput($plan, bool $strict = false): array
    {
        if (!is_array($plan)) {
            if ($strict && $plan !== null && $plan !== '') {
                throw new InvalidArgumentException('Invalid plan structure.');
            }

            return [];
        }

        try {
            $normalized = MealPlan::normalizePlan($plan, $strict, true);
        } catch (InvalidArgumentException $exception) {
            if ($strict) {
                throw $exception;
            }

            return [];
        }

        ksort($normalized);

        foreach ($normalized as $dayKey => $dayMeals) {
            if (!is_array($dayMeals)) {
                unset($normalized[$dayKey]);
                continue;
            }

            ksort($dayMeals);
            $normalized[$dayKey] = $dayMeals;
        }

        return $normalized;
    }

    protected function normalizeMealSelection($value, bool $strict = false): array
    {
        return MealPlan::normalizeMeal($value, $strict);
    }

    public function getAssignWorkoutList(Request $request)
    {
        $user_id = request('user_id');
        $data = Workout::myWorkout($user_id)->orderBy('id', 'desc')->get();
        $view = view('users.assign-workout-list',compact('user_id', 'data'))->render();
        return response()->json([ 'data' => $view, 'status' => true ]);
    }

    public function recommendProductForm(Request $request)
    {
        $user_id = request('user_id');
        $view = view('users.recommend_product', compact('user_id'))->render();

        return response()->json(['data' => $view, 'status' => true]);
    }

    public function recommendProductSave(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $productIds = collect($validated['product_ids'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if (empty($productIds)) {
            return response()->json([
                'status' => false,
                'event' => 'validation',
                'message' => __('message.request_required', ['name' => __('message.product')]),
            ]);
        }

        $user = User::findOrFail($validated['user_id']);
        $user->recommendedProducts()->syncWithoutDetaching($productIds);

        $message = __('message.recommendproduct');

        return response()->json([
            'status' => true,
            'type' => 'product',
            'event' => 'norefresh',
            'message' => $message,
        ]);
    }

    public function getRecommendProductList(Request $request)
    {
        $user_id = request('user_id');
        $recommendations = UserProductRecommendation::with('product')
            ->where('user_id', $user_id)
            ->orderByDesc('id')
            ->get();

        $view = view('users.recommend-product-list', compact('user_id', 'recommendations'))->render();

        return response()->json(['data' => $view, 'status' => true]);
    }

    public function assignDietDestroy(Request $request)
    {
        $assigndiet = AssignDiet::where('user_id', $request->user_id )->where('diet_id', $request->diet_id )->first();

        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.assigndiet')]);
        if($assigndiet != '') {
            $assigndiet->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.assigndiet')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message, 'type' => 'diet', 'event' => 'norefresh']);
        }

        return redirect()->back()->with($status,$message);
    }

    public function assignWorkoutForm(Request $request)
    {
        $user_id = request('user_id');
        $view = view('users.assign_workout',compact('user_id'))->render();
        return response()->json(['data' =>  $view, 'status'=> true]);
    }
    public function assignWorkoutSave(Request $request)
    {
        $data = $request->all();
        unset($data['_token']);
        AssignWorkout::updateOrCreate([ 'user_id' => request('user_id'), 'workout_id' => request('workout_id') ]);
        
        $message = __('message.assignworkout');

        return response()->json(['status' => true,  'type' => 'workout', 'event' => 'norefresh', 'message' => $message]);
    }

    public function assignWorkoutDestroy(Request $request)
    {
        $assignworkout = AssignWorkout::where('user_id', $request->user_id )->where('workout_id', $request->workout_id )->first();

        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.assignworkout')]);
        if($assignworkout != '') {
            $assignworkout->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.assignworkout')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message, 'type' => 'workout', 'event' => 'norefresh']);
        }

        return redirect()->back()->with($status,$message);
    }

    public function recommendProductDestroy(Request $request)
    {
        $recommendation = UserProductRecommendation::where('user_id', $request->user_id)
            ->where('product_id', $request->product_id)
            ->first();

        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.recommendproduct')]);

        if ($recommendation) {
            $recommendation->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.recommendproduct')]);
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'type' => 'product',
                'event' => 'norefresh',
            ]);
        }

        return redirect()->back()->with($status, $message);
    }

    public function fetchUserGraphData($type, $unit, $dateValue, $user_id)
    {
  
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $dataArray = [];
        $dateArray = [];

        switch ($dateValue) {
            case 'month':
                $data = UserGraph::whereYear('date', $currentYear)->whereMonth('date', $currentMonth);
                break;
            case 'week':
                $data = UserGraph::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'year':
                $data = UserGraph::whereYear('date', $currentYear);
                break;
            default:
                $data = UserGraph::query();
                break;
        }

        $userWeightValues = $data->where('user_id', $user_id)->where('type', $type)->where('unit', $unit)->orderBy('date', 'asc')->get(['value', 'date']);

        foreach ($userWeightValues as $record) {
            $dataArray[] = $record->value;
            $dateArray[] = $record->date;
        }

        return [
            'data' => $dataArray,
            'category' => $dateArray
        ];
    }

    public function fetchUserGraph(Request $request)
    {
        if (!$request->ajax()) {
            abort(403, 'Unauthorized action.');
        }

        $unit = $request->unit;
        $type = $request->type;
        $dateValue = $request->dateValue;
        $user_id = $request->id;

        $graphData = $this->fetchUserGraphData($type, $unit, $dateValue, $user_id);
        $data = $graphData['data'];
        $category = $graphData['category'];

        return response()->json([
            'data' => $data,
            'category' => $category
        ]);
    }
}
