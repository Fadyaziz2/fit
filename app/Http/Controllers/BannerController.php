<?php

namespace App\Http\Controllers;

use App\DataTables\BannerDataTable;
use App\Helpers\AuthHelper;
use App\Http\Requests\BannerRequest;
use App\Models\Banner;

class BannerController extends Controller
{
    public function index(BannerDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.banner')]);
        $authUser = AuthHelper::authSession();

        if (!$authUser->can('banner-list')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $assets = ['data-table'];
        $headerAction = $authUser->can('banner-add')
            ? '<a href="' . route('banner.create') . '" class="btn btn-sm btn-primary" role="button">' . __('message.add_form_title', ['form' => __('message.banner')]) . '</a>'
            : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'authUser', 'assets', 'headerAction'));
    }

    public function create()
    {
        if (!auth()->user()->can('banner-add')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.banner')]);
        return view('banner.form', compact('pageTitle'));
    }

    public function store(BannerRequest $request)
    {
        if (!auth()->user()->can('banner-add')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = $request->only(['title', 'subtitle', 'button_text', 'redirect_url', 'display_order', 'status']);
        $banner = Banner::create($data);

        storeMediaFile($banner, $request->banner_image, 'banner_image');

        return redirect()->route('banner.index')->withSuccess(__('message.save_form', ['form' => __('message.banner')]));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('banner-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = Banner::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.banner')]);

        return view('banner.form', compact('data', 'id', 'pageTitle'));
    }

    public function update(BannerRequest $request, $id)
    {
        if (!auth()->user()->can('banner-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $banner = Banner::findOrFail($id);
        $data = $request->only(['title', 'subtitle', 'button_text', 'redirect_url', 'display_order', 'status']);
        $banner->fill($data)->update();

        if ($request->hasFile('banner_image')) {
            $banner->clearMediaCollection('banner_image');
            $banner->addMediaFromRequest('banner_image')->toMediaCollection('banner_image');
        }

        if (auth()->check()) {
            return redirect()->route('banner.index')->withSuccess(__('message.update_form', ['form' => __('message.banner')]));
        }

        return redirect()->back()->withSuccess(__('message.update_form', ['form' => __('message.banner')]));
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('banner-delete')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $banner = Banner::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.banner')]);

        if ($banner) {
            $banner->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.banner')]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }
}
