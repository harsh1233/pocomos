<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Functions;
use Closure;
use Illuminate\Http\Request;
use App\Models\Orkestra\OrkestraGroup;
use App\Models\Orkestra\OrkestraUserGroup;

class CheckRole
{
    use Functions;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $roles = '')
    {
        if (!$roles) {
            return response()->json(["error"=>"Access Forbidden"], 403);
        }

        /**Roles management */
        $tempRoles = array();
        $newRoles = array();
        $roles = explode("|", $roles);
        foreach ($roles as $value) {
            $tempRoles[] = config('roles.'.$value);
        }
        foreach ($tempRoles as $value) {
            if (is_array($value)) {
                $newRoles = array_merge($newRoles, $value);
            } else {
                continue;
            }
        }
        $newRoles = array_merge($newRoles, $roles);
        $newRoles = array_unique($newRoles);
        /**Roles management end */

        //Logged in user id
        $officeUser = auth()->user()->pocomos_company_office_user ?? null;
        $office = (auth()->user()->pocomos_company_office_user ? auth()->user()->pocomos_company_office_user->company_details : null);

        /**Get loggend in user roles */
        $allRoles = $this->getUserAllRoles();

        /**Checking the logged in user has access to current office */
        $hasAccess = $this->userHasAccessToOffice($officeUser, $office);

        $status = false;
        if ($hasAccess) {
            foreach ($newRoles as $role) {
                if (in_array($role, $allRoles)) {
                    $status = true;
                    break;
                }
            }
        }
        if (!$status) {
            return response()->json(["error"=>"Access Forbidden"], 403);
        }

        return $next($request);
    }
}
