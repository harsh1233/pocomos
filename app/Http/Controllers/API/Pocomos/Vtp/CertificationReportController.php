<?php

namespace App\Http\Controllers\API\Pocomos\Vtp;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\PocomosVtpVideos;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class CertificationReportController extends Controller
{
    use Functions;

    /**
     * API for list of Certification Reports
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        // $PocomosSalespersonProfile = PocomosSalespersonProfile::with('certificateLevel:id,name')->whereActive(true)->get();

        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $videos = PocomosVtpVideos::whereOfficeId($request->office_id)->whereActive(true)->orderBy('order');

        /**For search functionality*/
        if ($request->search) {
            $videos->where('name', 'like', '%' . $request->search . '%');
        }

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page    = $request->page;
            $perPage = $request->perPage;
            $videos->skip($perPage * ($page - 1))->take($perPage);
        }

        $videos = $videos->get();

        $sql = 'SELECT DISTINCT sp.id,
                u.first_name,
                u.last_name,
                cl.name AS cert_level,';

        $i = 1;
        foreach ($videos as $video) {
            $sql .= '(CASE WHEN EXISTS (SELECT 1 FROM pocomos_vtp_watched_videos v1 WHERE v1.profile_id = sp.id AND v1.video_id = '.$video->id.') THEN 1 ELSE 0 END) AS video' . $i . ',';
            $i++;
        }

        $sql = rtrim($sql, ',');

        $sql .= ' FROM pocomos_salesperson_profiles sp
                JOIN pocomos_company_office_user_profiles oup ON sp.office_user_profile_id = oup.id
                JOIN pocomos_company_office_users ou ON ou.profile_id = oup.id AND ou.office_id = '.$request->office_id.'
                LEFT JOIN pocomos_vtp_certification_levels cl ON sp.certification_level_id = cl.id
                JOIN orkestra_users u ON oup.user_id = u.id';

        if ($request->search) {
            $search = '"%'.$request->search.'%"';
            $sql .= " where (
                CONCAT(u.first_name,' ',u.last_name) LIKE $search 
                OR cl.name LIKE $search)";
        }

        /**For pagination */
        $count = count(\DB::select($sql));

        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $sql .= " LIMIT $perPage offset $page";

        $data = \DB::select($sql);

        // return $data;
        foreach ($data as $datum) {
            $i = 1;
            $datum->total = 0;
            while (true) {
                $videoI='video'.$i;
                if (!isset($datum->$videoI)) {
                    break;
                }
                $datum->total += $datum->$videoI;
                $i++;
            }
        }

        return $this->sendResponse(true, 'List of VTP Certification Reports.', [
            // 'videos' => $videos,
            'data'   => $data,
            'count'   => $count
        ]);
    }


    public function get(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosSalespersonProfile = PocomosSalespersonProfile::with('certificateLevel')->whereId($id)->first();
        if (!$PocomosSalespersonProfile) {
            return $this->sendResponse(false, 'Certification Report Not Found');
        }

        return $this->sendResponse(true, 'Certificate Report', $PocomosSalespersonProfile);
    }

    /**
     * API for update of Certification Report
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'certification_level_id' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosSalespersonProfile = PocomosSalespersonProfile::whereId($id)->first();
        if (!$PocomosSalespersonProfile) {
            return $this->sendResponse(false, 'Certification Report Not Found');
        }

        $PocomosSalespersonProfile->certification_level_id = $request->certification_level_id;
        $PocomosSalespersonProfile->save();

        return $this->sendResponse(true, 'Profile updated successfully');
    }
}
