<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTermsAndCondition;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Support\Facades\Storage;
use DB;

class TermsAndConditionsController extends Controller
{
    use Functions;

    /**
     * API for list of Terms and Conditions
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        // $PocomosTermsAndCondition = DB::table('orkestra_files as sa')->leftJoin('pocomos_terms_and_conditions as of', 'sa.id', '=', 'of.file_id')->where('of.active', 1)->select('of.*', 'sa.path', 'sa.filename')->get();

        $termsAndConditions = PocomosTermsAndCondition::where('active', true)->with('ork_file');

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $termsAndConditions->count();
        $termsAndConditions->skip($perPage * ($page - 1))->take($perPage);

        $termsAndConditions = $termsAndConditions->get();

        return $this->sendResponse(true, 'List of Terms and Conditions.', [
            'terms_conditions' => $termsAndConditions,
            'count' => $count,
        ]);
    }

    /**
     * API for details of Terms and Conditions
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosTermsAndCondition = PocomosTermsAndCondition::find($id);
        if (!$PocomosTermsAndCondition) {
            return $this->sendResponse(false, 'Terms and Conditions Not Found');
        }
        return $this->sendResponse(true, 'Terms and Conditions details.', $PocomosTermsAndCondition);
    }

    /**
     * API for create of Terms and Conditions
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'note' => 'required',
            'enabled' => 'required|boolean',
            'file' => 'required|mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('note', 'enabled');
        $input_details['active'] =  1;

        // File Details

        if ($request->file('file')) {
            $file = $request->file('file');

            //store your file into database
            $file_details['filename'] = $file->getClientOriginalName();
            $file_details['mime_type'] = $file->getMimeType();
            $file_details['file_size'] = $file->getSize();
            $file_details['active'] = 1;
            $file_details['md5_hash'] =  md5_file($file->getRealPath());

            $url = "Product" . "/" . $file_details['filename'];
            Storage::disk('s3')->put($url, file_get_contents($file));
            $file_details['path'] = Storage::disk('s3')->url($url);

            $OrkestraFile =  OrkestraFile::create($file_details);
            $input_details['file_id'] = $OrkestraFile->id;
        }

        $PocomosTermsAndCondition =  PocomosTermsAndCondition::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Terms and Conditions created successfully.', $PocomosTermsAndCondition);
    }

    /**
     * API for update of Terms and Conditions
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'note' => 'required',
            'enabled' => 'required|boolean',
            'file' => 'required|mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480',
            'terms_condition_id' => 'required|exists:pocomos_terms_and_conditions,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTermsAndCondition = PocomosTermsAndCondition::find($request->terms_condition_id);

        if (!$PocomosTermsAndCondition) {
            return $this->sendResponse(false, 'Terms and Conditions not found.');
        }

        $input_details = $request->only('note', 'enabled');

        // File Details
        if ($request->file('file')) {
            $OrkestraFile = OrkestraFile::find($PocomosTermsAndCondition->file_id);

            $file = $request->file('file');

            //store your file into database
            $file_details['filename'] = $file->getClientOriginalName();
            $file_details['mime_type'] = $file->getMimeType();
            $file_details['file_size'] = $file->getSize();
            $file_details['active'] = 1;
            $file_details['md5_hash'] =  md5_file($file->getRealPath());

            $url = "Product" . "/" . $file_details['filename'];
            Storage::disk('s3')->put($url, file_get_contents($file));
            $file_details['path'] = Storage::disk('s3')->url($url);

            if ($OrkestraFile) {
                $OrkestraFile->update($file_details);
            } else {
                $Configuration =  OrkestraFile::create($file_details);
                $input_details['file_id'] =  $Configuration->id;
            }
        }

        $PocomosTermsAndCondition->update($input_details);

        return $this->sendResponse(true, 'Terms and Conditions updated successfully.', $PocomosTermsAndCondition);
    }

    /* API for changeStatus of Terms and Conditions */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'terms_condition_id' => 'required|exists:pocomos_terms_and_conditions,id',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosTermsAndCondition = PocomosTermsAndCondition::find($request->terms_condition_id);

        if (!$PocomosTermsAndCondition) {
            return $this->sendResponse(false, 'Unable to find Emergency News');
        }

        $PocomosTermsAndCondition->update([
            'active' => $request->enabled,
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosTermsAndCondition);
    }
}
