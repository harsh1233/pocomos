<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosCustomer;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosPestpacExportCustomer;

class PestpacExportCustomerController extends Controller
{
    use Functions;

    /**
     * API for list of the PestPac Export Customer
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list($officeid)
    {
        $PocomosPestpacExportCustomer = PocomosPestpacExportCustomer::where('office_id', $officeid)->where('active', 1)->orderBy('id', 'desc')->get();

        return $this->sendResponse(true, 'List of the PestPac Export Customer.', $PocomosPestpacExportCustomer);
    }

    /**
     * API for details of the PestPac Export Customer
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosPestpacExportCustomer = PocomosPestpacExportCustomer::find($id);
        if (!$PocomosPestpacExportCustomer) {
            return $this->sendResponse(false, 'the PestPac Export Customer Not Found');
        }
        return $this->sendResponse(true, 'the PestPac Export Customer details.', $PocomosPestpacExportCustomer);
    }

    /**
     * Re-enable customer for pestpac export
     */
    public function changePestpacCustomerStatus(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'pac_pest_export_id' => 'required|required|exists:pocomos_pestpac_export_customers,id',
            'status' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $office_id = $request->office_id;
        $status = $request->status ?? config('constants.PENDING');
        if (!$this->isGranted('ROLE_OWNER')) {
            $office_id = Session::get(config('constants.ACTIVE_OFFICE_ID')) ?? $office_id;
        }

        $pestPacExportCustomer = PocomosPestpacExportCustomer::where('office_id', $office_id)->where('id', $request->pac_pest_export_id)->firstOrFail();

        if (!in_array($status, array('Pending', 'Success', 'Failed', 'Paused', 'Cancelled'))) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find '.$status.' Pestpac Export Customer status.']));
        }

        $pestPacExportCustomer->status = $status;
        $pestPacExportCustomer->save();
        return $this->sendResponse(true, __('strings.update', ['name' => 'Customer status']));
    }

    /**
     * Export customer to pestpac
     */
    public function tryExporting(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'pac_pest_export_id' => 'required|required|exists:pocomos_pestpac_export_customers,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;
        $exportCustId = $request->pac_pest_export_id;

        // $pestpacHelper =  $this->get('pocomos.pest.helper.pestpac.initializer');
        $exportCust = $this->getPestpacCustomerForExport($exportCustId, $office_id, $this->isGranted('ROLE_OWNER'));

        if (!$exportCust) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the PestPac Export Customer.']));
        }
        $exportCust = (array)$exportCust[0];
        $exportCust = PocomosPestpacExportCustomer::findOrFail($exportCust['id']);
        $pestPacConfig = $this->getPestpacConfigurationForExportCustomer($exportCust);

        $exportCust->errors = '';
        $exportCust->status = config('constants.PENDING');

        $pestpacHelper =  $this->get('pocomos.pest.helper.pestpac.initializer');
        // $requestMakingHelper = $pestpacHelper->getRequestMakingHelper($pestPacConfig);
        $this->createEveryThing($exportCust, $pestPacConfig);
        $errors = $exportCust->errors;
        if ($errors) {
            $status = 400;
            $message = $errors;
        } else {
            $status = 200;
            $message = __('strings.sucess', ['name' => 'Customer exported']);
        }

        return $this->sendResponse($status, $message);
    }

    /**
     * Update service setup/order data
     */
    public function updateServiceOrder(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'pac_pest_export_id' => 'required|required|exists:pocomos_pestpac_export_customers,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office_id = $request->office_id;
        $exportCustId = $request->pac_pest_export_id;

        $exportCust = $this->getPestpacCustomerForExport($exportCustId, $office_id, $this->isGranted('ROLE_OWNER'));

        if (!$exportCust) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the PestPac Export Customer.']));
        }

        $exportCust = (array)$exportCust[0];
        $exportCust = PocomosPestpacExportCustomer::findOrFail($exportCust['id']);
        $pestPacConfig = $this->getPestpacConfigurationForExportCustomer($exportCust);

        $errors = '';

        // update service order
        $serviceOrderResult = $this->serviceOrderUpdate($exportCust);

        $errors = '';
        if ($serviceOrderResult['error']) {
            $errors .= $serviceOrderResult['URI'].' Got this: '.$serviceOrderResult['errorMessage'] ."<br/>";
        }
        // update service order
        $serviceSetupResult = $this->serviceSetupUpdate($exportCust);
        $errors = '';
        if ($serviceSetupResult['error']) {
            $errors .= $serviceSetupResult['URI'].' Got this: '.$serviceSetupResult['errorMessage'] ."<br/>";
        }

        if (strlen($errors) > 0) {
            // dd($errors);
            return $this->sendResponse(false, $errors);
        } else {
            return $this->sendResponse(true, __('strings.update', ['name' => 'Customer Order data']));
        }
    }
}
