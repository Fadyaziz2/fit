<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class AuthHelper {
    public static function authSession(){
        if (Auth::check()) {
            return Auth::user()->fresh();
        }

        if (Session::has('auth_user')) {
            return Session::get('auth_user');
        }

        return new \App\Models\User;
    }

    public static function checkMenuRoleAndPermission($menu)
    {
        if (Auth::check()) {
            if ($menu->data('role') == null && auth()->user()->hasRole('admin')) {
                return true;
            }

            if($menu->data('permission') == null && $menu->data('role') == null) {
                return true;
            }

            if($menu->data('role') != null) {
                if(auth()->user()->hasAnyRole(explode(',', $menu->data('role')))) {
                    return true;
                }
            }

            if($menu->data('permission') != null) {
                $permissions = is_array($menu->data('permission'))
                    ? $menu->data('permission')
                    : [$menu->data('permission')];

                foreach ($permissions as $permission) {
                    if (\userHasPermissionIncludingParents($permission)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public static function checkRolePermission($role,$permission){
        try{
            if($role->hasPermissionTo($permission)){
                return true;
            }
            return false;
        }catch (Exception $e){
            return false;
        }
    }

    public static function demoUserPermission(){
        if(Auth::user()->hasRole('demo_admin')){
            return true;
        }else{
            return false;
        }
    }
}
