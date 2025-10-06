<?php

namespace App\Http\Controllers;

use App\DataTables\ExclusiveOfferDataTable;
use App\Helpers\AuthHelper;
use App\Http\Requests\ExclusiveOfferRequest;
use App\Models\ExclusiveOffer;
use Illuminate\Support\Carbon;

class ExclusiveOfferController extends Controller
{
    public function index(ExclusiveOfferDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.exclusive_offer')]);
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['exclusive-offer-list', 'exclusive-offer'])) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $assets = ['data-table'];
        $headerAction = $authUser->hasAnyPermission(['exclusive-offer-add', 'exclusive-offer'])
            ? '<a href="' . route('exclusive-offer.create') . '" class="btn btn-sm btn-primary" role="button">' . __('message.add_form_title', ['form' => __('message.exclusive_offer')]) . '</a>'
            : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'authUser', 'assets', 'headerAction'));
    }

    public function create()
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['exclusive-offer-add', 'exclusive-offer']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.exclusive_offer')]);
        return view('exclusive-offer.form', compact('pageTitle'));
    }

    public function store(ExclusiveOfferRequest $request)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['exclusive-offer-add', 'exclusive-offer']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        if ($request->status === 'active' && ExclusiveOffer::active()->exists()) {
            return redirect()->back()->withInput()->withErrors(__('message.exclusive_offer_single_active'));
        }

        $data = $request->only(['title', 'description', 'button_text', 'button_url', 'status']);
        $data['activated_at'] = $request->status === 'active' ? Carbon::now() : null;

        $offer = ExclusiveOffer::create($data);
        storeMediaFile($offer, $request->offer_image, 'exclusive_offer_image');

        return redirect()->route('exclusive-offer.index')->withSuccess(__('message.save_form', ['form' => __('message.exclusive_offer')]));
    }

    public function edit($id)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['exclusive-offer-edit', 'exclusive-offer']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = ExclusiveOffer::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.exclusive_offer')]);

        return view('exclusive-offer.form', compact('data', 'id', 'pageTitle'));
    }

    public function update(ExclusiveOfferRequest $request, $id)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['exclusive-offer-edit', 'exclusive-offer']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $offer = ExclusiveOffer::findOrFail($id);

        if ($request->status === 'active' && ExclusiveOffer::active()->where('id', '!=', $offer->id)->exists()) {
            return redirect()->back()->withInput()->withErrors(__('message.exclusive_offer_single_active'));
        }

        $data = $request->only(['title', 'description', 'button_text', 'button_url', 'status']);
        if ($request->status === 'active') {
            $data['activated_at'] = $offer->status !== 'active' ? Carbon::now() : $offer->activated_at;
        } else {
            $data['activated_at'] = null;
        }

        $offer->fill($data)->save();

        if ($request->hasFile('offer_image')) {
            storeMediaFile($offer, $request->offer_image, 'exclusive_offer_image');
        }

        return redirect()->route('exclusive-offer.index')->withSuccess(__('message.update_form', ['form' => __('message.exclusive_offer')]));
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['exclusive-offer-delete', 'exclusive-offer']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $offer = ExclusiveOffer::findOrFail($id);

        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.exclusive_offer')]);

        if ($offer) {
            $offer->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.exclusive_offer')]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }
}
