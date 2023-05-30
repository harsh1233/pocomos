<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use DB;
use PDF;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosContract;
use Illuminate\Support\Facades\Session;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Pocomos\PocomosPestAgreement;
use Illuminate\Support\Facades\DB as FacadesdDB;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosCustomAgreementTemplate;
use App\Models\Pocomos\PocomosPestContractServiceType;

class AgreementController extends Controller
{
    use Functions;

    /**
     * API for list of Agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'status' => 'nullable|in:active,inactive,all',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $sql = "SELECT *, sa.id as 'pest_agreements_id'
        FROM pocomos_pest_agreements AS sa
       LEFT  JOIN pocomos_agreements AS ca ON ca.id = sa.agreement_id
        WHERE ca.office_id = '$request->office_id'";

        if ($request->status == 'active') {
            $sql .= " AND sa.enabled = 1";
        } elseif ($request->status == 'inactive') {
            $sql .= " AND sa.enabled = 0";
        } elseif (!$request->status) {
            $sql .= " AND sa.enabled = 1";
        }

        if ($request->search) {
            $search = $request->search;
            $sql .= " AND ( ca.name LIKE '%$search%'
                    OR ca.description LIKE '%$search%'
                    ) ";
        }

        $sql .= " ORDER BY ca.position ASC";

        $sqlNew = $sql;
        $allData = DB::select(DB::raw($sqlNew));
        $allIds = array_column($allData, 'id');
        
        $count = count(DB::select(DB::raw($sql)));
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        
        $sql .= " LIMIT $perPage offset $page";
        
        $agreement_details = DB::select(DB::raw($sql));

        foreach ($agreement_details as $status) {
            if ($status->default_service_type) {
                $status->service_type_data = PocomosPestContractServiceType::where('id', $status->default_service_type)->select('name', 'id')->get();
            }

            if ($status->custom_agreement_id) {
                $status->custom_agreement_data = PocomosCustomAgreementTemplate::where('id', $status->custom_agreement_id)->select('name', 'id')->get();
            }

            $status->service_frequencies_data = [];
            if (unserialize($status->service_frequencies)) {
                $status->service_frequencies_data = unserialize($status->service_frequencies);
            }

            $status->billing_frequencies_data = [];
            if (unserialize($status->billing_frequencies)) {
                $status->billing_frequencies_data = unserialize($status->billing_frequencies);
            }

            $status->exceptions_data = [];
            if (unserialize($status->exceptions)) {
                $status->exceptions_data = unserialize($status->exceptions);
            }

            $status->autopay_terms_data = [];
            if (unserialize($status->autopay_terms)) {
                $status->autopay_terms_data = unserialize($status->autopay_terms);
            }

            $status->contract_terms_data = [];
            if (unserialize($status->contract_terms)) {
                $status->contract_terms_data = unserialize($status->contract_terms);
            }
        };

        $data = [
            'records' => $agreement_details,
            'count' => $count,
            'all_ids' => $allIds
        ];

        return $this->sendResponse(true, 'List of Agreements.', $data);
    }

    public function listNew(Request $request)
    {
        $isSalesPerson = Session::get(config('constants.PREVIOUS_LOGGEDIN_USER'));

        $sql1 = '';
        if($isSalesPerson){
            $sql1 = ' AND ca.hideSalesRepo = 0';
        }

        $sql = "SELECT sa.*
        FROM pocomos_agreements AS sa
        WHERE sa.office_id = '$request->office_id'";

        $sql = "SELECT ca.*, sa.*, ca.id as 'id', cst.name as 'default_service_type_name'
        FROM pocomos_pest_agreements AS ca
        LEFT JOIN pocomos_agreements AS sa ON ca.agreement_id = sa.id
        LEFT JOIN pocomos_pest_contract_service_types cst ON sa.default_service_type = cst.id
        WHERE sa.office_id = '$request->office_id'".$sql1;

        if (isset($request->status)) {
            $sql .= " AND ca.enabled = " . $request->status;
        }

        $sql .= " ORDER BY sa.id DESC";

        $agreement_details = DB::select(DB::raw($sql));

        $data = [
            'records' => $agreement_details,
            'count' => count($agreement_details)
        ];

        return $this->sendResponse(true, 'List of Agreements.', $data);
    }

    /**
     * API for create of agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'enabled' => 'nullable|boolean',
            'auto_renew' => 'nullable|boolean',
            'auto_renew_lock' => 'nullable|boolean',
            'auto_renew_initial' => 'nullable|boolean',
            'initial_job_lock' => 'nullable|boolean',
            'delay_welcome_email' => 'nullable|boolean',
            'bill_immediately' => 'nullable|boolean',
            'specifyNumberOfJobs' => 'nullable|boolean',
            'variable_length' => 'nullable|boolean',
            'auto_renew_installments' => 'nullable|boolean',
            'auto_renew_installments_lock' => 'nullable|boolean',
            'default_service_type' =>  'nullable|exists:pocomos_pest_contract_service_types,id',
            'length' => 'nullable',
            'max_jobs' => 'nullable',
            'one_month_followup' => 'nullable|boolean',
            'enableBillingFrequencies' => 'nullable|boolean',
            'billing_frequencies' => 'array',
            'service_frequencies' => 'array',
            'specify_exception' => 'nullable|boolean',
            'exceptions' => 'array',
            'default_agreement' => 'nullable|boolean',
            'allow_dates_in_the_past' => 'nullable|boolean',
            'allow_addendum' => 'nullable|boolean',
            'custom_agreement_id' =>  'nullable|exists:pocomos_custom_agreement_templates,id',
            'signature_agreement_text' => 'nullable',
            'enable_default_price' => 'nullable|boolean',
            'regular_initial_price' => 'nullable',
            'initial_price' => 'nullable',
            'recurring_price' => 'nullable',
            'installment_default_price' => 'nullable',
            'installment_default_number_payments' => 'nullable',
            'installment_default_frequency' => 'nullable',
            'monthly_default_normal_initial' => 'nullable',
            'monthly_default_price' => 'nullable',
            'monthly_default_initial_price' => 'nullable',
            'due_at_signup_default_price' => 'nullable',
            'two_payment_default_first_payment_price' => 'nullable',
            'two_payment_default_second_payment_price' => 'nullable',
            'contract_terms' => 'array',
            'autopay_terms' => 'array',
            'enable_new_pdf_layout' => 'nullable',
            'initial_duration' => 'nullable',
            'regular_duration' => 'nullable',
            'agreement_body' => 'nullable',
            'invoice_intro' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosAgreement::query();

        $position = (($position = (clone ($query))->orderBy('position', 'desc')->first()) ? $position->position + 1 : 1);

        $agreement_details = $request->only('office_id', 'name', 'description', 'auto_renew', 'auto_renew_lock', 'auto_renew_initial', 'initial_job_lock', 'bill_immediately', 'specifyNumberOfJobs', 'enableBillingFrequencies', 'variable_length', 'auto_renew_installments', 'auto_renew_installments_lock', 'default_service_type', 'length', 'custom_agreement_id', 'signature_agreement_text', 'enable_default_price', 'initial_price', 'regular_initial_price', 'recurring_price', 'installment_default_price', 'installment_default_number_payments', 'installment_default_frequency', 'monthly_default_normal_initial', 'monthly_default_price', 'monthly_default_initial_price', 'due_at_signup_default_price', 'two_payment_default_first_payment_price', 'two_payment_default_second_payment_price', 'enable_new_pdf_layout', 'agreement_body', 'invoice_intro') + ['active' => true, 'position' => $position];

        $pest_agreement_details = $request->only('enabled', 'delay_welcome_email', 'max_jobs', 'one_month_followup', 'specify_exception', 'default_agreement', 'allow_dates_in_the_past', 'allow_addendum', 'initial_duration', 'regular_duration');

        $agreement_details['billing_frequencies'] =  serialize($request->input('billing_frequencies'));

        $agreement_details['autopay_terms'] =   serialize($request->input('autopay_terms'));

        $agreement_details['contract_terms'] = serialize($request->input('contract_terms'));

        $PocomosAgreement =   (clone ($query))->create($agreement_details);

        $pest_agreement_details['service_frequencies'] =  serialize($request->input('service_frequencies'));

        $pest_agreement_details['exceptions'] =  serialize($request->input('exceptions'));

        if ($PocomosAgreement) {
            $pest_agreement_details['agreement_id'] = $PocomosAgreement->id;
            $pest_agreement_details['active'] = 1;
        }

        $pestagreementdetails =  PocomosPestAgreement::create($pest_agreement_details);

        if (isset($request->default_agreement) && $request->default_agreement == 1) {
            $PocomosAgreement = PocomosPestAgreement::where('id', '!=', $pestagreementdetails->id)->update([
                'default_agreement' => 0
            ]);
        }

        return $this->sendResponse(true, 'The agreement has been created successfully.', $PocomosAgreement);
    }


    /**
     * API for create of agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'agreement_id' => 'required|exists:pocomos_agreements,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'enabled' => 'nullable|boolean',
            'hideSalesRepo' => 'nullable|boolean',
            'default_agreement' => 'nullable|boolean',
            'allow_online_booking' => 'nullable|boolean',
            'allow_pronexis_booking' => 'nullable|boolean',
            'allow_dates_in_the_past' => 'nullable|boolean',
            'allow_addendum' => 'nullable|boolean',
            'custom_agreement_id' =>  'nullable|exists:pocomos_custom_agreement_templates,id',
            'description' => 'nullable',
            'auto_renew' => 'nullable|boolean',
            'auto_renew_lock' => 'nullable|boolean',
            'auto_renew_initial' => 'nullable|boolean',
            'initial_job_lock' => 'nullable|boolean',
            'delay_welcome_email' => 'nullable|boolean',
            'bill_immediately' => 'nullable|boolean',
            'specifyNumberOfJobs' => 'nullable|boolean',
            'variable_length' => 'nullable|boolean',
            'auto_renew_installments' => 'nullable|boolean',
            'auto_renew_installments_lock' => 'nullable|boolean',
            'default_service_type' =>  'nullable|exists:pocomos_pest_contract_service_types,id',
            'length' => 'nullable',
            'max_jobs' => 'nullable',
            'one_month_followup' => 'nullable|boolean',
            'enableBillingFrequencies' => 'nullable|boolean',
            'billing_frequencies' => 'array',
            'service_frequencies' => 'array',
            'specify_exception' => 'nullable|boolean',
            'exceptions' => 'array',
            'signature_agreement_text' => 'nullable',
            'enable_default_price' => 'nullable|boolean',
            'regular_initial_price' => 'nullable',
            'initial_price' => 'nullable',
            'recurring_price' => 'nullable',
            'installment_default_price' => 'nullable',
            'installment_default_number_payments' => 'nullable',
            'installment_default_frequency' => 'nullable',
            'monthly_default_normal_initial' => 'nullable',
            'monthly_default_price' => 'nullable',
            'monthly_default_initial_price' => 'nullable',
            'due_at_signup_default_price' => 'nullable',
            'two_payment_default_first_payment_price' => 'nullable',
            'two_payment_default_second_payment_price' => 'nullable',
            'contract_terms' => 'array',
            'autopay_terms' => 'array',
            'enable_new_pdf_layout' => 'nullable',
            'initial_duration' => 'nullable',
            'regular_duration' => 'nullable',
            'agreement_body' => 'nullable',
            'invoice_intro' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAgreement = PocomosAgreement::where('office_id', $request->office_id)->where('id', $request->agreement_id)->first();

        if (!$PocomosAgreement) {
            return $this->sendResponse(false, 'Agreement not found.');
        }

        $agreement_details = $request->only('office_id', 'name', 'description', 'auto_renew', 'auto_renew_lock', 'auto_renew_initial', 'initial_job_lock', 'bill_immediately', 'specifyNumberOfJobs', 'enableBillingFrequencies', 'variable_length', 'auto_renew_installments', 'auto_renew_installments_lock', 'default_service_type', 'length', 'custom_agreement_id', 'signature_agreement_text', 'enable_default_price', 'initial_price', 'regular_initial_price', 'recurring_price', 'installment_default_price', 'installment_default_number_payments', 'installment_default_frequency', 'monthly_default_normal_initial', 'monthly_default_price', 'monthly_default_initial_price', 'due_at_signup_default_price', 'two_payment_default_first_payment_price', 'two_payment_default_second_payment_price', 'enable_new_pdf_layout', 'agreement_body', 'invoice_intro');

        $pest_agreement_details = $request->only('enabled', 'hideSalesRepo', 'allow_online_booking', 'allow_pronexis_booking', 'delay_welcome_email', 'max_jobs', 'one_month_followup', 'specify_exception', 'default_agreement', 'allow_dates_in_the_past', 'allow_addendum', 'initial_duration', 'regular_duration');

        $agreement_details['billing_frequencies'] =  serialize($request->input('billing_frequencies'));

        $agreement_details['autopay_terms'] =   serialize($request->input('autopay_terms'));

        $agreement_details['contract_terms'] = serialize($request->input('contract_terms'));

        $PocomosAgreement->update($agreement_details);

        $pest_agreement_details['service_frequencies'] =  serialize($request->input('service_frequencies'));

        $pest_agreement_details['exceptions'] =  serialize($request->input('exceptions'));

        if ($PocomosAgreement) {
            $pestagreementdetails = PocomosPestAgreement::where('agreement_id', $request->agreement_id)->first();
        }

        $pestagreementdetails->update($pest_agreement_details);

        if (isset($request->default_agreement) && $request->default_agreement == 1) {
            $PocomosAgreement = PocomosPestAgreement::where('id', '!=', $pestagreementdetails->id)->update([
                'default_agreement' => 0
            ]);
        }

        return $this->sendResponse(true, 'The agreement has been updated successfully.', $PocomosAgreement);
    }

    /**Duplicate agreement */
    public function duplicateAgreement(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'agreement_id' => 'required|exists:pocomos_agreements,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office = $request->office_id;
        $agreement = $request->agreement_id;

        $pest_agreement = DB::select(DB::raw("SELECT ppa.*
            FROM pocomos_pest_agreements AS ppa
            JOIN pocomos_agreements AS pa ON ppa.agreement_id = pa.id
            WHERE pa.office_id = $office AND pa.id = $agreement"));

        if (!$pest_agreement) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find the Agreement.']));
        }

        $oldEntity = PocomosPestAgreement::findOrFail($pest_agreement[0]->id);
        $oldAgreement = $oldEntity->agreement_detail;

        $newEntity = $oldEntity->toArray();
        unset($newEntity['id'], $newEntity['agreement_detail']);

        $newAgreement = $oldAgreement->toArray();
        unset($newAgreement['id']);
        $newAgreement['name'] = $oldAgreement->name . '-duplicate';
        $newAgreement['default_service_type'] = $oldAgreement->default_service_type;
        $newAgreement = PocomosAgreement::create($newAgreement);

        $newEntity['agreement_id'] = $newAgreement->id;
        $newEntity = PocomosPestAgreement::create($newEntity);

        return $this->sendResponse(true, __('strings.create', ['name' => 'Agreement']), $newEntity->id);
    }

    /**
     * Creates a fake customer and sends the agreement to the email provided.
     */
    public function sendTestEmail(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'agreement_id' => 'required|exists:pocomos_pest_agreements,id',
            'emails.*' => 'email',
            'service_frequency' => 'nullable',
            'service_type' => 'exists:pocomos_pest_contract_service_types,id',
            'tax_code' => 'exists:pocomos_tax_codes,id',
            'billing_frequency' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        FacadesdDB::beginTransaction();
        try {
            $office = $request->office_id;
            $agreement = $request->agreement_id;

            $pest_agreement = DB::select(DB::raw("SELECT ppa.*
                FROM pocomos_pest_agreements AS ppa
                JOIN pocomos_agreements AS pa ON ppa.agreement_id = pa.id
                WHERE pa.office_id = $office AND ppa.id = $agreement"));

            if (!$pest_agreement) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find the Agreement.']));
            }

            $pest_agreement = PocomosPestAgreement::findOrFail($pest_agreement[0]->id);

            $salesProfile = $this->createFakeCustomer($pest_agreement, $request->emails);

            $contractModel = $this->createFakeContracts($salesProfile, $pest_agreement);
            $contractModel['serviceFrequency'] = $request->service_frequency;
            $contractModel['serviceType'] = $request->service_type;
            $contractModel['taxCode'] = $request->tax_code;

            if ($pest_agreement->agreement_detail->billing_frequencies && count(unserialize($pest_agreement->agreement_detail->billing_frequencies))) {
                $contractModel['billingFrequency'] = serialize($request->billing_frequency);
            }

            $pestContract = $this->transformToContract($contractModel);
            
            if ($pestContract->contract_details) {
                $pestContract->contract_details->profile_id = $salesProfile->id;
                $pestContract->contract_details->save();
            }

            //Lets make some Fake jobs for the Fake contract for the Fake customer. I hope I won't be paid in Fake Money.
            // $jobs = $factory->createJobsForContract($pestContract, new \DateTime());

            $this->generateWelcomeEmailNew($pestContract);

            FacadesdDB::commit();
            $status = true;
            $message = __('strings.sucess', ['name' => 'Send test email process completed']);
        } catch (\Exception $e) {
            FacadesdDB::rollback();
            $status = false;
            $message = $e->getMessage();
        }
        return $this->sendResponse($status, $message);
    }

    /**
     * API for reorder of agreement
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function reorder(Request $request)
    {
        $v = validator($request->all(), [
            'all_ids.*' => 'required|exists:pocomos_agreements,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $allIds = $request->all_ids;

        $i = 1;
        foreach ($allIds as $value) {
            DB::select(DB::raw("UPDATE pocomos_agreements SET position = $i WHERE id = $value"));
            $i++;
        }

        return $this->sendResponse(true, 'Agreement reordered successfully.');
    }

    /**Get agreement details */
    public function getAgreement($id)
    {
        $sql = "SELECT *, sa.id as 'pest_agreements_id'
        FROM pocomos_pest_agreements AS sa
       LEFT  JOIN pocomos_agreements AS ca ON ca.id = sa.agreement_id
        WHERE ca.id = '$id'";

        $sql .= " ORDER BY ca.id DESC";

        $agreement_details = DB::select(DB::raw($sql));

        foreach ($agreement_details as $status) {
            if ($status->default_service_type) {
                $status->service_type_data = PocomosPestContractServiceType::where('id', $status->default_service_type)->select('name', 'id')->get();
            }

            if ($status->custom_agreement_id) {
                $status->custom_agreement_data = PocomosCustomAgreementTemplate::where('id', $status->custom_agreement_id)->select('name', 'id')->get();
            }

            $status->service_frequencies_data = [];
            if (unserialize($status->service_frequencies)) {
                $status->service_frequencies_data = unserialize($status->service_frequencies);
            }

            $status->billing_frequencies_data = [];
            if (unserialize($status->billing_frequencies)) {
                $status->billing_frequencies_data = unserialize($status->billing_frequencies);
            }

            $status->exceptions_data = [];
            if (unserialize($status->exceptions)) {
                $status->exceptions_data = unserialize($status->exceptions);
            }

            $status->autopay_terms_data = [];
            if (unserialize($status->autopay_terms)) {
                $status->autopay_terms_data = unserialize($status->autopay_terms);
            }

            $status->contract_terms_data = [];
            if (unserialize($status->contract_terms)) {
                $status->contract_terms_data = unserialize($status->contract_terms);
            }
        };

        return $this->sendResponse(true, __('strings.details', ['name' => 'Agreement']), $agreement_details);
    }

    /**
     * API for Test new agreement PDF layout
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function testNewAgreementLayoutAction($agreement_id, $office_id)
    {
        $PocomosAgreement = PocomosAgreement::where('office_id', $office_id)->where('id', $agreement_id)->first();

        if (!$PocomosAgreement) {
            return $this->sendResponse(false, 'Unable to find the Agreement.');
        }

        $jobs = PocomosContract::select(
            'pocomos_contracts.id'
        )
            ->join('pocomos_customer_sales_profiles as pcsp', 'pocomos_contracts.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->where('pcsp.office_id', $office_id)
            ->where('pocomos_contracts.agreement_id', $agreement_id);

        $jobs = $jobs->get();

        if (!count($jobs)) {
            return $this->sendResponse(false, 'You need a customer with this agreement to test. You can create a test customer for this.');
        }

        $pestContract = PocomosPestContract::where('contract_id', $jobs[0]['id'])->first();

        $salesContract = $pestContract->contract_details;
        $profile = $salesContract->profile_details;
        $agreement = $salesContract->agreement_detail;
        $office = $profile->office_details;
        $customer = $profile->customer_details;

        $template = $this->agreementGenerator(array(
            'office' => $office,
            'customer' => $customer,
            'agreement' => $agreement,
            'contract' => $salesContract,
            'pestContract' => $pestContract,
            'profile' => $profile
        ), true);

        $pdf = PDF::loadView('pdf.dynamic_render', compact('template'));

        return $pdf->download('test-agreement-new-layout' . $agreement_id . '.pdf');
    }
}
