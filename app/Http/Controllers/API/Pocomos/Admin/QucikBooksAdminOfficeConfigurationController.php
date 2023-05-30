<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pocomos\PocomosOfficeQuickbooksAdminsetting;

class QucikBooksAdminOfficeConfigurationController extends Controller
{
    use Functions;
    /**
     * API for Quickbooksadminofficeconfiguration setting createandedit
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createUpdate(Request $request)
    {
        $v = validator($request->all(), [
            'enabled' => 'nullable',
            'desktop_version_enabled' => 'nullable',
            'online_version_enabled' => 'nullable',
            'sync_to_qb_enabled' => 'nullable',
            'sync_from_qb_enabled' => 'nullable',
            'sync_customers_enabled' => 'nullable',
            'sync_invoices_enabled' => 'nullable',
            'sync_payments_enabled' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $booksAdminConfiguration = PocomosOfficeQuickbooksAdminsetting::where('office_id', $request->office_id)->first();
        // dd(count($booksAdminConfiguration));
        if ($booksAdminConfiguration) {
            $updateConfiguration = $booksAdminConfiguration->update($request->all());
            $message = 'Quick books admin configuration updated successfully.';
            $data = $booksAdminConfiguration;
        } else {
            $input = [];
            $input = $request->all();
            $input['office_id'] = $request->office_id;
            $createConfiguration = PocomosOfficeQuickbooksAdminsetting::create($input);
            $message = 'Quick books admin configuration added successfully.';
            $data = $createConfiguration;
        }
        return $this->sendResponse(true, $message, $data);
    }

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booksAdminConfiguration = PocomosOfficeQuickbooksAdminsetting::where('office_id', $request->office_id)->first();

        return $this->sendResponse(true, 'List of quick books admin office configurations', $booksAdminConfiguration);
    }
}
