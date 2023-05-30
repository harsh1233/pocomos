<?php

namespace App\Http\Controllers\API\Pocomos\Vtp;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosVtpVideos;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosReportsOfficeState;
use App\Models\Pocomos\PocomosVtpWatchedVideo;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosCompanyOfficeUser;

class VideosController extends Controller
{
    use Functions;

    /**
     * API for list of Videos
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $mainOfficeId = $pocomosCompanyOffice->parent_id ?? $request->office_id;

        $reportsOfficeState = PocomosReportsOfficeState::join('pocomos_company_offices as pco', 'pocomos_reports_office_states.office_id', 'pco.id')
                    ->join('pocomos_addresses as pa', 'pco.contact_address_id', 'pa.id')
                    ->join('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
                    ->where('pco.active', true)
                    ->where('pco.parent_id', $request->office_id)
                    ->orderBy('pco.id')
                    ->get();

        // return $reportsOfficeState;

        $officeIds[] = $mainOfficeId;

        foreach ($reportsOfficeState as $office) {
            $officeIds[] =  $office->office_id;
        }

        // return $officeIds;

        $PocomosVtpVideos = PocomosVtpVideos::whereActive(true)->whereIn('office_id', $officeIds)->orderBy('order');

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page    = $request->page;
            $perPage = $request->perPage;
            $PocomosVtpVideos->skip($perPage * ($page - 1))->take($perPage);
        }

        $PocomosVtpVideos = $PocomosVtpVideos->get();

        return $this->sendResponse(true, 'List of Vtp Videos.', $PocomosVtpVideos);
    }

    /**
     * API for create of video
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'                 => 'required',
            'name'                      => 'required',
            'description'               => 'required',
            'url'                       => 'required',
            'technician_or_salesperson' => 'in:Salesperson,Technician'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $query = PocomosVtpVideos::query();

        $order = (($order = (clone($query))->whereActive(true)->whereOfficeId($request->office_id)->orderBy('order', 'desc')->first()) ? $order->order + 1 : 1);

        $input = $request->only('office_id', 'name', 'description', 'url', 'technician_or_salesperson')+['active' => true, 'order' => $order];

        $PocomosVtpVideo =  (clone($query))->create($input);

        return $this->sendResponse(true, 'Video created successfully.', $PocomosVtpVideo);
    }

    /**
     * API for details of Video
     .
     *
     * @param  \Interger  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosVtpVideo = PocomosVtpVideos::find($id);
        if (!$PocomosVtpVideo) {
            return $this->sendResponse(false, 'Video Not Found');
        }
        return $this->sendResponse(true, 'Video details.', $PocomosVtpVideo);
    }

    /**
     * API for edit of video
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function edit(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id'                 => 'required',
            'name'                      => 'required',
            'description'               => 'required',
            'url'                       => 'required',
            'technician_or_salesperson' => 'in:Salesperson,Technician'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosVtpVideo = PocomosVtpVideos::find($id);
        if (!$PocomosVtpVideo) {
            return $this->sendResponse(false, 'Video Not Found');
        }

        $PocomosVtpVideo->office_id                 = $request->office_id;
        $PocomosVtpVideo->name                      = $request->name;
        $PocomosVtpVideo->description               = $request->description;
        $PocomosVtpVideo->url                       = $request->url;
        $PocomosVtpVideo->technician_or_salesperson = $request->technician_or_salesperson;

        $PocomosVtpVideo->save();

        return $this->sendResponse(true, 'Video updated successfully.');
    }

    /**
     * API for delete of video
     .
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosVtpVideo = PocomosVtpVideos::find($id);
        if (!$PocomosVtpVideo) {
            return $this->sendResponse(false, 'Video Not Found');
        }
        $PocomosVtpVideo->active = false;
        $PocomosVtpVideo->save();

        $pos = 1;
        $videos = PocomosVtpVideos::whereActive(true)->orderBy('order')->get();
        foreach ($videos as $value) {
            $value->order = $pos;
            $value->save();
            $pos = $pos + 1;
        }

        return $this->sendResponse(true, 'Video deleted successfully');
    }

    /**
     * API for reorder of video
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function reorder(Request $request, $id)
    {
        $v = validator($request->all(), [
            'pos' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosVtpVideo = PocomosVtpVideos::find($id);
        if (!$PocomosVtpVideo) {
            return $this->sendResponse(false, 'Video Not Found');
        }

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $PocomosVtpVideo->order;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosVtpVideos::where('active', true)->orderBy('order')->get();
            foreach ($videos as $value) {
                $detail = PocomosVtpVideos::find($value->id);
                if ($value->id == $id) {
                    $position = $newPosition;
                } else {
                    $position = $detail->order;
                    if ($movedDown) {
                        if ($position > $originalPosition && $position <= $newPosition) {
                            $position--;
                        }
                    } elseif ($position <= $originalPosition && $position >= $newPosition) {
                        $position++;
                    }
                }
                $detail->order = $position;
                $detail->save();
            }
        }

        return $this->sendResponse(true, 'Video reordered successfully.');
    }

    // For training videos (Get current user's least unwatched video)
    public function watchAction(request $request, $id = null)
    {
        $v = validator($request->all(), [
            'user_id'   => 'required|exists:orkestra_users,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if ($id) {
            $video = PocomosVtpVideos::whereId($id)->first();
            if (!$video) {
                return $this->sendResponse(false, 'Unable to locate Video.');
            }
        } else {
            // Get current user's least unwatched video
            $officeId =  PocomosCompanyOfficeUser::whereUserId($request->user_id)->first()->office_id;

            $salesPersonProfile = PocomosCompanyOfficeUserProfile::with('salesPersonProfile')->whereUserId($request->user_id)->first();
            $salesPersonProfileID = $salesPersonProfile ? $salesPersonProfile->salesPersonProfile->id : null;

            $nextOrder = DB::select(DB::raw('SELECT MIN(v.order) o FROM pocomos_vtp_videos v
                        WHERE v.id NOT IN (
                            SELECT v2.id FROM pocomos_vtp_videos v2
                            JOIN pocomos_vtp_watched_videos wv ON v2.id = wv.video_id
                            JOIN pocomos_salesperson_profiles sp ON sp.id = wv.profile_id AND sp.id = '.$salesPersonProfileID.'
                        )
                        AND v.active = true
                        AND v.office_id = '.$officeId));

            $order = $nextOrder[0]->o;
            $video = PocomosVtpVideos::whereOfficeId($officeId)->whereActive(true)->whereOrder($order)->first();
        }

        // For video type
        $type = null;
        if (str_contains($video->url, 'vimeo')) {
            $type = 'vimeo';
        } elseif (str_contains($video->url, 'youtube') || str_contains($video->url, 'youtu.be')) {
            $type = 'youtube';
        } else {
            return $this->sendResponse(false, 'Unknown video type');
        }

        // For video code
        if ($type == 'youtube') {
            $parts = parse_url($video->url);
            $vars = array();
            parse_str($parts['query'], $vars);
            $code = $vars['v'];
        } elseif ($type == 'vimeo') {
            $parts = parse_url($video->url);
            $code = preg_replace('/[^0-9]/', '', $parts['path']);
        } else {
            return $this->sendResponse(false, 'Unknown video type');
        }
        return $this->sendResponse(true, 'Video', [
            'video' => $video,
            'code'  => $code,
            'type'  => $type
        ]);
    }

    public function watchedVideos(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $salesPersonProfile = PocomosCompanyOfficeUserProfile::with('salesPersonProfile')->whereUserId($request->user_id)->firstOrFail();

        $salesPersonProfileID = $salesPersonProfile->toArray()['sales_person_profile'] ? $salesPersonProfile->toArray()['sales_person_profile']['id'] : null;

        $videoIds = PocomosVtpWatchedVideo::whereProfileId($salesPersonProfileID)->pluck('video_id');

        $pocomosVtpVideos = PocomosVtpVideos::whereIn('id', $videoIds)->whereActive(true);

        if ($request->search) {
            $search = '%'.$request->search.'%';

            $pocomosVtpVideos->where(function ($pocomosVtpVideos) use ($search) {
                $pocomosVtpVideos->where('name', 'like', $search)
                 ->orWhere('description', 'like', $search);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $pocomosVtpVideos->count();
        $pocomosVtpVideos->skip($perPage * ($page - 1))->take($perPage);

        $pocomosVtpVideos = $pocomosVtpVideos->get();

        return $this->sendResponse(true, 'Watched Videos', [
            'videos' => $pocomosVtpVideos,
            'count' => $count,
        ]);
    }

    public function videoWatched(request $request, $id)
    {
        $v = validator($request->all(), [
            'user_id'   => 'required',
            'office_id'   => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $salesPersonProfile = PocomosCompanyOfficeUserProfile::with('salesPersonProfile')->whereUserId($request->user_id)->firstOrFail();
        // return $salesPersonProfile;
        $salesPersonProfileID = $salesPersonProfile->toArray()['sales_person_profile'] ? $salesPersonProfile->toArray()['sales_person_profile']['id'] : null;

        // $officeId =  PocomosCompanyOfficeUser::whereUserId($request->user_id)->firstOrFail()->office_id;

        $video = PocomosVtpVideos::whereId($id)->whereOfficeId($request->office_id)->whereActive(true)->first();

        try {
            PocomosVtpWatchedVideo::create([
                'profile_id' => $salesPersonProfileID,
                'video_id'   => $video->id,
            ]);
        } catch(\Exception $e) {
            return $this->sendResponse(true, 'Video already added to watched videos or Video does not exist');
        }
        return $this->sendResponse(true, 'Video added to watched videos successfully.');
    }
}
