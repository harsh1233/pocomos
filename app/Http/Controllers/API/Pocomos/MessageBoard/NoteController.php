<?php

namespace App\Http\Controllers\API\Pocomos\MessageBoard;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosCompanyOfficeUserNote;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DateTime;
use App\Models\Pocomos\PocomosBlogPost;

class NoteController extends Controller
{
    use Functions;

    /**
     * API for list of Note
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'assigned_to' => 'required|exists:orkestra_users,id',
            'assigned_by' => 'nullable|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->assigned_to)->where('office_id', $request->office_id)->first();
        $pocomos_office_user_notes = PocomosCompanyOfficeUserNote::where('office_user_id', $find_assigned_by_to->id)->join('pocomos_notes', 'pocomos_office_user_notes.note_id', '=', 'pocomos_notes.id')->join('orkestra_users', 'pocomos_notes.user_id', '=', 'orkestra_users.id')->orderBy('pocomos_notes.date_created', 'desc');
        // $PocomosNote = PocomosNote::orderBy('id', 'desc')
        //     ->get();

        if ($request->search) {
            $search = $request->search;
            $pocomos_office_user_notes->where(function ($query) use ($search) {
                $query->where('summary', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%');
                // ->orWhere('summary', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $pocomos_office_user_notes->count();
        $pocomos_office_user_notes->skip($perPage * ($page - 1))->take($perPage);

        $pocomos_office_user_notes = $pocomos_office_user_notes->get();

        return $this->sendResponse(true, 'List', [
            'pocomos_office_user_notes' => $pocomos_office_user_notes,
            'count' => $count,
        ]);
    }

    /**
     * API for create of Note
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'assigned_by' => 'required|exists:orkestra_users,id',
            'assigned_to' => 'required|exists:orkestra_users,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'summary' => 'required',
            'body' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input = [];
        $input['user_id'] = $request->assigned_by;
        $input['summary'] = $request->summary;
        $input['body'] = $request->body;
        $input['active'] = $request->active;
        $input['interaction_type'] = 'Other';
        $addNote = PocomosNote::create($input);
        $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->assigned_to)->where('office_id', $request->office_id)->first();
        $pocomos_office_user_create = [];
        $pocomos_office_user_create['office_user_id'] = $find_assigned_by_to->id;
        $pocomos_office_user_create['note_id'] = $addNote->id;
        $pocomos_office_user_notes = PocomosCompanyOfficeUserNote::create($pocomos_office_user_create);
        return $this->sendResponse(true, 'Note added successfully', $pocomos_office_user_notes);
    }

    /**
     * API for Note.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosNote = PocomosNote::find($id);
        if (!$PocomosNote) {
            return $this->sendResponse(false, 'Unable to find the note.');
        }

        $PocomosCompanyOfficeUserNote = PocomosCompanyOfficeUserNote::where('note_id', $id)->delete();
        $PocomosNote->delete();

        return $this->sendResponse(true, 'Note deleted successfully.');
    }

    /**
     * API for list of Blog Post
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function releaseNew(Request $request)
    {
        $datetime = new DateTime('tomorrow');
        $datetime = $datetime->format('Y-m-d H:i:s');
        $PocomosBlogPost = PocomosBlogPost::orderBy('date_posted', 'desc')->where('active', 1)->where('date_posted', '<', $datetime)->first();
        return $this->sendResponse(true, 'List of Blog Post.', $PocomosBlogPost);
    }
}
