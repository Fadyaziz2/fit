<?php

namespace App\Http\Controllers;

use App\DataTables\IngredientDataTable;
use App\Helpers\AuthHelper;
use App\Http\Requests\IngredientRequest;
use App\Models\Ingredient;

class IngredientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IngredientDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.ingredient')]);
        $auth_user = AuthHelper::authSession();

        if (!$auth_user->can('ingredient-list')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $assets = ['data-table'];
        $headerAction = $auth_user->can('ingredient-add') ? '<a href="' . route('ingredient.create') . '" class="btn btn-sm btn-primary" role="button">' . __('message.add_form_title', ['form' => __('message.ingredient')]) . '</a>' : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user', 'assets', 'headerAction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->can('ingredient-add')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.ingredient')]);

        return view('ingredient.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(IngredientRequest $request)
    {
        if (!auth()->user()->can('ingredient-add')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        Ingredient::create($request->validated());

        return redirect()->route('ingredient.index')->withSuccess(__('message.save_form', ['form' => __('message.ingredient')]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (!auth()->user()->can('ingredient-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $data = Ingredient::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.ingredient')]);

        return view('ingredient.form', compact('data', 'id', 'pageTitle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(IngredientRequest $request, $id)
    {
        if (!auth()->user()->can('ingredient-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $ingredient = Ingredient::findOrFail($id);
        $ingredient->fill($request->validated())->update();

        return redirect()->route('ingredient.index')->withSuccess(__('message.update_form', ['form' => __('message.ingredient')]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('ingredient-delete')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $ingredient = Ingredient::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.ingredient')]);

        if ($ingredient) {
            $ingredient->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.ingredient')]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }
}
