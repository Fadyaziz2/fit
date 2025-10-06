<?php

namespace App\Http\Controllers;

use App\DataTables\SuccessStoryDataTable;
use App\Helpers\AuthHelper;
use App\Http\Requests\SuccessStoryRequest;
use App\Models\SuccessStory;

class SuccessStoryController extends Controller
{
    public function index(SuccessStoryDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.successstory')]);
        $authUser = AuthHelper::authSession();

        if (!$authUser->hasRole('admin') && !$authUser->hasAnyPermission(['successstory-list', 'successstory'])) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $assets = ['data-table'];
        $headerAction = $authUser->hasAnyPermission(['successstory-add', 'successstory'])
            ? '<a href="' . route('successstory.create') . '" class="btn btn-sm btn-primary" role="button">' . __('message.add_form_title', ['form' => __('message.successstory')]) . '</a>'
            : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'authUser', 'assets', 'headerAction'));
    }

    public function create()
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['successstory-add', 'successstory']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.successstory')]);
        return view('successstory.form', compact('pageTitle'));
    }

    public function store(SuccessStoryRequest $request)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['successstory-add', 'successstory']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = $request->only(['title', 'description', 'display_order', 'status']);
        $story = SuccessStory::create($data);

        storeMediaFile($story, $request->before_image, 'success_story_before_image');
        storeMediaFile($story, $request->after_image, 'success_story_after_image');

        return redirect()->route('successstory.index')->withSuccess(__('message.save_form', ['form' => __('message.successstory')]));
    }

    public function edit($id)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['successstory-edit', 'successstory']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = SuccessStory::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.successstory')]);

        return view('successstory.form', compact('data', 'id', 'pageTitle'));
    }

    public function update(SuccessStoryRequest $request, $id)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['successstory-edit', 'successstory']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $story = SuccessStory::findOrFail($id);
        $data = $request->only(['title', 'description', 'display_order', 'status']);
        $story->fill($data)->update();

        if ($request->hasFile('before_image')) {
            storeMediaFile($story, $request->before_image, 'success_story_before_image');
        }

        if ($request->hasFile('after_image')) {
            storeMediaFile($story, $request->after_image, 'success_story_after_image');
        }

        return redirect()->route('successstory.index')->withSuccess(__('message.update_form', ['form' => __('message.successstory')]));
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user || (!$user->hasRole('admin') && !$user->hasAnyPermission(['successstory-delete', 'successstory']))) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $story = SuccessStory::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.successstory')]);

        if ($story) {
            $story->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.successstory')]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }
}
