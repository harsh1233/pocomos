<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTeam;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosMembership;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Orkestra\OrkestraUser;
use DB;

class TeamsController extends Controller
{
    use Functions;

    // Team listing with members
    public function list($id)
    {
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $teamsByOffices = PocomosCompanyOffice::with([
            'teams' => function ($q) {
                $q->whereActive(1);
            },
            'teams.member_details.ork_user_details'
        ])
            ->whereId($id)->orWhere('parent_id', $id)->get(['id', 'name']);

        // $list_team = PocomosTeam::where('office_id',$id)->with('member_details.ork_user_details')->get();

        // if(!$list_team){
        //     return $this->sendResponse(false, 'Team not found.');
        // }
        return $this->sendResponse(true, 'Team listing with members.', $teamsByOffices);
    }


    // Add new teams
    public function addTeam(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'name' => 'required',
            'color' => 'required',
            'active' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $add_team = PocomosTeam::create($request->all());
        return $this->sendResponse(true, 'Team created successfully.', $add_team);
    }

    public function getMembers(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $ids            = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereActive(true)->pluck('id');

        $addedIds = PocomosMembership::whereActive(1)->pluck('salesperson_id')->toArray();

        $salesPeople = PocomosSalesPeople::with('office_user_details.user_details')
                            ->whereIn('user_id', $ids)->whereNotIn('id', $addedIds)
                            ->whereActive(true)->get();

        /* $userIds         =  $PocomosSalesPeople->pluck('user_id');
        $salesPeopleIds  =  $PocomosSalesPeople->pluck('id');

        // $salesPeopleIds  = PocomosSalesPeople::whereIn('user_id', $ids)->whereNotIn('id', $addedIds)
        //                                             ->whereActive(true)->pluck('id');

        $OfficeUserIds  = PocomosCompanyOfficeUser::whereIn('id', $userIds)->pluck('user_id');
     return   $salesPeople    = OrkestraUser::whereIn('id', $OfficeUserIds)->whereActive(true)->get();

        // foreach($salesPeople as $r){
            $i=0;
            foreach($salesPeopleIds as $q){
                if(isset($salesPeople[$i])){
                    $salesPeople[$i]['salespeople_id'] = $q;
                }
                // return $salesPeople;
                $i++;
            }
        // } */

        return $this->sendResponse(true, 'Salesperson list', $salesPeople);
    }

    // Add new member in team
    public function addMember(Request $request)
    {
        $v = validator($request->all(), [
            'sales_person_ids' => 'required|array|unique:pocomos_memberships,salesperson_id',
            'team_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_team = PocomosTeam::where('id', $request->team_id)->first();
        if (!$find_team) {
            return $this->sendResponse(false, 'Team not found.');
        }
        foreach ($request->sales_person_ids as $sales_person_id) {
            $input = [];
            $company_office_user_profiles_id = PocomosCompanyOfficeUserProfile::where('user_id', $sales_person_id)->pluck('id')->first();
            $find_sales_person_id = PocomosSalespersonProfile::where('office_user_profile_id', $company_office_user_profiles_id)->pluck('id')->first();
            $input['salesperson_id'] = $sales_person_id;
            $input['salesperson_profile_id'] = $find_sales_person_id;
            $input['team_id'] = $request->team_id;
            $input['active'] = '1';
            $add_member = PocomosMembership::create($input);
        }
        return $this->sendResponse(true, 'Team member added successfully.', $find_team);
    }



    // Delete member
    public function removeMember($id)
    {
        $find_member = PocomosMembership::where('id', $id)->delete();
        if (!$find_member) {
            return $this->sendResponse(false, 'Team member not found.');
        }
        return $this->sendResponse(true, 'Team member deleted successfully.');
    }

    // Delete team
    public function removeTeam($id)
    {
        PocomosMembership::whereTeamId($id)->delete();

        $find_team = PocomosTeam::whereId($id)->update(['active' => 0]);
        if (!$find_team) {
            return $this->sendResponse(false, 'Team not found.');
        }
        return $this->sendResponse(true, 'Team deleted successfully.');
    }

    // Edit Team
    public function editTeam(Request $request)
    {
        $v = validator($request->all(), [
            'team_id' => 'required',
            'name' => 'required',
            'color' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $find_team = PocomosTeam::where('id', $request->team_id)->first();
        if (!$find_team) {
            return $this->sendResponse(false, 'Team not found.');
        }
        $find_team = $find_team->update($request->only('name', 'color'));
        return $this->sendResponse(true, 'Team member edited successfully.', $find_team);
    }

    // Team listing with members
    public function teamsList(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_ids = PocomosCompanyOffice::whereId($request->office_id)->orWhere('parent_id', $request->office_id)->pluck('id')->toArray();
        $teams = PocomosTeam::with('member_details.ork_user_details')->whereIn('office_id', $office_ids)->get();
        return $this->sendResponse(true, 'Team listing with members.', $teams);
    }
}
