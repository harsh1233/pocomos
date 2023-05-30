<?php

namespace App\Http\Controllers\API\Pocomos\Customer;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Jobs\CustomerStateJob;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosContract;
use DB;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosSubCustomer;

class SubCustomerController extends Controller
{
    use Functions;

    /**
     * API for Updates the current contract's billing customr
   .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateResponsibleAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'responsibleCustomer' => 'required|exists:pocomos_customers,id',
            'responsibleContract' => 'required|exists:pocomos_contracts,id',
            'getCurrentContract' => 'required|exists:pocomos_contracts,id',
        ]);

        // find customer from id
        $customer = $this->findCustomerByIdAndOffice($request->customer_id, $request->office_id);
        if (!$customer) {
            throw new \Exception('Unable to find the Customer.');
        }

        $pest_contarct = PocomosPestContract::where('contract_id', $request->getCurrentContract)->first();
        $pestContract = $pest_contarct->id;

        $responsibleContract = null;
        if ($request->responsibleCustomer != $request->customer_id) {
            $pest_contarct = PocomosPestContract::where('contract_id', $request->responsibleContract)->first();
            $responsibleContract = $pest_contarct->id;
        }

        $this->updateResponsibleForBilling($pestContract, $responsibleContract);

        if ($responsibleContract) {
            $args['ids'] = array($customer->id);
            CustomerStateJob::dispatch($args);
        }

        return $this->sendResponse(true, 'The responsible billing party has been updated.');
    }

    public function getResponsibleAction(Request $request)
    {
        $v = validator($request->all(), [
            'customer_id' => 'required|exists:pocomos_customers,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'getCurrentContract' => 'required|exists:pocomos_contracts,id',
        ]);

        // find customer from id
        $customer = $this->findCustomerByIdAndOffice($request->customer_id, $request->office_id);
        if (!$customer) {
            throw new \Exception('Unable to find the Customer.');
        }

        $pest_contarct = PocomosPestContract::where('contract_id', $request->getCurrentContract)->first();


        if ($pest_contarct->parent_contract_id == null) {
            $result['child'] = true;
        } else {

            $parentContracts = DB::select(DB::raw("SELECT pa.name , pco.contract_id as 'contract_id' , sco.status as 'status', st.name AS 'service_type_name'
                    FROM pocomos_pest_contracts as pco
                    JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
                    JOIN pocomos_agreements AS pa ON  pa.id =sco.agreement_id
                    JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
                    JOIN pocomos_customers as pc ON pc.id = p.customer_id
                    LEFT JOIN pocomos_pest_contract_service_types st ON st.id  = pco.service_type_id
                    WHERE pco.id= " . $pest_contarct->parent_contract_id . ""));

            $result = $parentContracts;
        }

        return $this->sendResponse(true, 'result', $result);
    }

    public function getContractChoiceList(Request $request)
    {
        $v = validator($request->all(), [
            'getCurrentContract' => 'required|exists:pocomos_contracts,id',
        ]);

        $pestContract = PocomosPestContract::where('contract_id', $request->getCurrentContract)->first();
        $PocomosContract = PocomosContract::where('id', $pestContract->contract_id)->firstorfail();
        $sale_profile = PocomosCustomerSalesProfile::where('id', $PocomosContract->profile_id)->first();
        $childCustomer = PocomosCustomer::where('id', $sale_profile->customer_id)->first();

        $subCustomer = PocomosSubCustomer::whereChildId($childCustomer->id)->firstOrFail();
        if ($subCustomer && $subCustomer->parent_id !== null) {
            $parentCustomer = $subCustomer->getParentNew();
        }

        $parentPestContracts = DB::select(DB::raw("SELECT pa.name , pco.contract_id as 'contract_id' , sco.status as 'status', st.name AS 'service_type_name'
                    FROM pocomos_pest_contracts as pco
                    JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
                    JOIN pocomos_agreements AS pa ON  pa.id =sco.agreement_id
                    JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
                    JOIN pocomos_customers as pc ON pc.id = p.customer_id
                    LEFT JOIN pocomos_pest_contract_service_types st ON st.id  = pco.service_type_id
                    WHERE pc.id= " . $parentCustomer->id . ""));

        return $this->sendResponse(true, 'parentContracts', $parentPestContracts);
    }
}
