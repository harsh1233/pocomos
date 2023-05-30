<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosVoiceFormLetter;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use Illuminate\Support\Facades\Storage;

class VoiceFormLetterController extends Controller
{
    use Functions;

    /**
     * API for list of Voice Template
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosVoiceFormLetter = PocomosVoiceFormLetter::where('office_id', $request->office_id)->where('active', 1)->with('file_detail');

        if ($request->search) {
            $search = $request->search;
            $PocomosVoiceFormLetter->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('message_order', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $PocomosVoiceFormLetter->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $PocomosVoiceFormLetter->skip($perPage * ($page - 1))->take($perPage);
        }
        $PocomosVoiceFormLetter = $PocomosVoiceFormLetter->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'List', [
            'Voice_Template' => $PocomosVoiceFormLetter,
            'count' => $count,
        ]);
    }

    /**
     * API for get Voice Template
     .
     *
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosVoiceFormLetter = PocomosVoiceFormLetter::with('file_detail')->where('active', 1)->find($id);
        if (!$PocomosVoiceFormLetter) {
            return $this->sendResponse(false, 'Voice Template Not Found');
        }
        return $this->sendResponse(true, 'Voice Template details.', $PocomosVoiceFormLetter);
    }

    /**
     * API for create Voice Template
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'title' => 'required',
            'message' => 'nullable',
            'description' => 'required',
            'type' => 'nullable|in:Select Type,Play Audio Only,Read Text Only,Audio & Text Both',
            'message_order' => 'nullable|in:Select Order,Play audio then read message,Read message then play audio',
            'confirm_job' => 'nullable|boolean',
            'require_job' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->all();

        if ($request->file('audiofile')) {
            $file = $request->file('audiofile');

            //store your file into database
            $file_details['filename'] = $file->getClientOriginalName();
            $file_details['mime_type'] = $file->getMimeType();
            $file_details['file_size'] = $file->getSize();
            $file_details['active'] = 1;
            $file_details['md5_hash'] =  md5_file($file->getRealPath());

            $url = "Voice" . "/" . $file_details['filename'];
            Storage::disk('s3')->put($url, file_get_contents($file));
            $file_details['path'] = Storage::disk('s3')->url($url);

            $OrkestraFile =  OrkestraFile::create($file_details);
            $input_details['file_id'] = $OrkestraFile->id;
        }

        $input_details['active'] = 1;

        $PocomosVoiceFormLetter =  PocomosVoiceFormLetter::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, 'Voice Template created successfully.', $PocomosVoiceFormLetter);
    }

    /**
     * API for update Voice Template
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'voiceformLetter_id' => 'required|exists:pocomos_voice_form_letters,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'title' => 'required',
            'message' => 'nullable',
            'description' => 'required',
            'type' => 'nullable|in:Select Type,Play Audio Only,Read Text Only,Audio & Text Both',
            'message_order' => 'nullable|in:Select Order,Play audio then read message,Read message then play audio',
            'confirm_job' => 'nullable|boolean',
            'require_job' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosVoiceFormLetter = PocomosVoiceFormLetter::where('office_id', $request->office_id)->where('id', $request->voiceformLetter_id)->first();

        if (!$PocomosVoiceFormLetter) {
            return $this->sendResponse(false, 'Voice Template not found.');
        }

        $input_details = $request->all();

        if ($request->file('audiofile')) {
            $OrkestraFile = OrkestraFile::find($PocomosVoiceFormLetter->file_id);

            $file = $request->file('audiofile');

            //store your file into database
            $file_details['filename'] = $file->getClientOriginalName();
            $file_details['mime_type'] = $file->getMimeType();
            $file_details['file_size'] = $file->getSize();
            $file_details['active'] = 1;
            $file_details['md5_hash'] =  md5_file($file->getRealPath());

            $url = "Voice" . "/" . $file_details['filename'];
            Storage::disk('s3')->put($url, file_get_contents($file));
            $file_details['path'] = Storage::disk('s3')->url($url);

            if ($OrkestraFile) {
                $OrkestraFile->update($file_details);
            } else {
                $Configuration =  OrkestraFile::create($file_details);
                $input_details['file_id'] =  $Configuration->id;
            }
        }

        $PocomosVoiceFormLetter->update($input_details);

        return $this->sendResponse(true, 'Voice Template updated successfully.', $PocomosVoiceFormLetter);
    }

    /**
     * API for delete Voice Template
     .
     *
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosVoiceFormLetter = PocomosVoiceFormLetter::find($id);
        if (!$PocomosVoiceFormLetter) {
            return $this->sendResponse(false, 'Voice Template not found.');
        }

        $PocomosVoiceFormLetter->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Voice Template deleted successfully.');
    }
}
