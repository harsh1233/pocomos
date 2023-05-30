<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use Hash;
use DateTime;
use Carbon\Carbon;
use GuzzleHttp\Client;
use ZendPdf\PdfDocument;
use Illuminate\Support\Str;
use App\Jobs\ResendEmailJob;
use GuzzleHttp\Psr7\Request;
use App\Jobs\TaxRecalculationJob;
use App\Models\Pocomos\PocomosJob;
use App\Models\Pocomos\PocomosTag;
use App\Models\Pocomos\PocomosArea;
use App\Models\Pocomos\PocomosLead;
use App\Models\Pocomos\PocomosNote;
use App\Models\Pocomos\PocomosPest;
use App\Models\Pocomos\PocomosTeam;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\Pocomos\PocomosAlert;
use App\Models\Pocomos\PocomosEmail;
use App\Models\Pocomos\PocomosRoute;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Orkestra\OrkestraFile;
use App\Models\Orkestra\OrkestraUser;
use App\Models\Pocomos\PocomosCounty;
use Illuminate\Support\Facades\Cache;
use App\Mail\RemoteCompletionCustomer;
use App\Models\Orkestra\OrkestraGroup;
use App\Models\Pocomos\PocomosAddress;
use App\Models\Pocomos\PocomosInvoice;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosVehicle;
use App\Models\Orkestra\OrkestraResult;
use App\Models\Pocomos\PocomosContract;
use App\Models\Pocomos\PocomosCustomer;
use App\Models\Pocomos\PocomosLeadNote;
use App\Models\Pocomos\PocomosRecruits;
use App\Models\Pocomos\PocomosSchedule;
use App\Models\Pocomos\PocomosSmsUsage;
use App\Models\Pocomos\PocomosTimezone;
use Illuminate\Database\PDO\Connection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\Orkestra\OrkestraAccount;
use App\Models\Pocomos\PocomosAgreement;
use App\Models\Pocomos\PocomosLeadQuote;
use App\Models\Pocomos\PocomosRecruiter;
use App\Models\Pocomos\PocomosSalesArea;
use Twilio\Rest\Client as TwillioClient;
use App\Models\Pocomos\PocomosFormLetter;
use App\Models\Pocomos\PocomosJobService;
use App\Models\Pocomos\PocomosLeadAction;
use App\Models\Pocomos\PocomosMembership;
use App\Models\Pocomos\PocomosRouteSlots;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Orkestra\OrkestraUserGroup;
use App\Models\Pocomos\PocomosCustomField;
use App\Models\Pocomos\PocomosDistributor;
use App\Models\Pocomos\PocomosImportBatch;
use App\Models\Pocomos\PocomosOfficeAlert;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosPhoneNumber;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\PocomosSalesStatus;
use App\Models\Pocomos\PocomosSubCustomer;
use GuzzleHttp\Exception\RequestException;
use App\Models\Orkestra\OrkestraCredential;
use App\Models\Pocomos\PocomosCustomerNote;
use App\Models\Pocomos\PocomosEmailMessage;
use App\Models\Pocomos\PocomosFormVariable;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosLeadsAccount;
use App\Models\Pocomos\PocomosPestContract;
use App\Models\Orkestra\OrkestraTransaction;
use App\Models\Pocomos\PocomosCustomerAlert;
use App\Models\Pocomos\PocomosCustomersNote;
use App\Models\Pocomos\PocomosCustomerState;
use App\Models\Pocomos\PocomosMarketingType;
use App\Models\Pocomos\PocomosMissionConfig;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosPestAgreement;
use App\Models\Pocomos\PocomosPestpacConfig;
use App\Models\Pocomos\PocomosSmsFormLetter;
use App\Models\Pocomos\PocomosStandardPrice;
use App\Models\Pocomos\PocomosCustomersPhone;
use App\Models\Pocomos\PocomosImportCustomer;
use App\Models\Pocomos\PocomosInvoicePayment;
use App\Models\Pocomos\PocomosPestpacSetting;
use App\Models\Orkestra\OrkestraCountryRegion;
use App\Models\Pocomos\PocomosAcsJobEventsTag;
use App\Models\Pocomos\PocomosAcsNotification;
use App\Models\Pocomos\PocomosUserTransaction;
use App\Models\Pocomos\PocomosBestfitThreshold;
use App\Models\Pocomos\PocomosCustomersAccount;
use App\Models\Pocomos\PocomosEmailTypeSetting;
use App\Models\Pocomos\PocomosRecruitingRegion;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestContractsPest;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosEmailsAttachedFile;
use App\Models\Pocomos\PocomosInvoiceTransaction;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\PocomosReportsOfficeState;
use App\Models\Pocomos\PocomosSalespersonProfile;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Models\Pocomos\PocomosNotificationSetting;
use App\Models\Pocomos\Recruitement\PocomosRegion;
use Symfony\Component\HttpKernel\Profiler\Profile;
use App\Models\Pocomos\PocomosCustomerSalesProfile;
use App\Models\Pocomos\PocomosPestContractsInvoice;
use App\Models\Pocomos\PocomosReportsContractState;
use App\Models\Pocomos\PocomosAcsJobEventsAgreement;
use App\Models\Pocomos\PocomosAcsJobEventsException;
use App\Models\Pocomos\PocomosInvoiceInvoicePayment;
use App\Models\Pocomos\PocomosMissionExportContract;
use App\Models\Pocomos\PocomosPestpacExportCustomer;
use App\Models\Pocomos\PocomosPestQuickbooksSetting;
use App\Models\Pocomos\PocomosSalesAreaPivotManager;
use App\Models\Pocomos\PocomosCustomersWorkorderNote;
use App\Models\Pocomos\PocomosAcsJobEventsServiceType;
use App\Models\Pocomos\PocomosPestContractServiceType;
use App\Models\Pocomos\PocomosCompanyOfficeUserProfile;
use App\Models\Pocomos\PocomosCustomFieldConfiguration;
use App\Models\Pocomos\PocomosSalestrackerOfficeSetting;
use App\Models\Pocomos\PocomosCustomersNotifyMobilePhone;
use App\Models\Pocomos\PocomosPestContractsSpecialtyPest;
use App\Models\Pocomos\Recruitement\PocomosRecruitStatus;
use App\Models\Pocomos\PocomosRecruitingOfficeConfiguration;
use App\Models\Pocomos\Recruitement\PocomosRecruitAgreement;
use App\Models\Pocomos\PocomosRecruitCustomFieldConfiguration;
use App\Models\Pocomos\PocomosPestOfficeDefaultChemsheetSettings;

trait Functions
{
    // send json response
    public function sendResponse($status, $message, $data = null)
    {
        if ($status) {
            return response()->json(['message' => $message, 'data' => $data], 200);
        } else {
            return response()->json(['error' => $message], 400);
        }
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /* function is used for create to do for users*/

    public function createToDo($request, $type, $name, $dept = null)
    {
        $find_assigned_by_id = PocomosCompanyOfficeUser::where('user_id', $request->assignd_by)->where('office_id', $request->office_id)->first();

        if ($dept == "Customer") {
            $find_assigned_by_to = PocomosCustomerSalesProfile::where('customer_id', $request->assign_to)->where('office_id', $request->office_id)->first();
        } else {
            $find_assigned_by_to = PocomosCompanyOfficeUser::where('user_id', $request->assign_to)->where('office_id', $request->office_id)->first();
        }

        $input['name'] = $name;
        $input['description'] = $request->description;
        $input['priority'] = $request->priority ?? 'Normal';
        $input['status'] = 'Posted';
        $input['type'] = $type;
        $input['date_due'] = $request->dateDue;
        $input['active'] = 1;
        $input['notified'] = 0;
        $alert = PocomosAlert::create($input);

        $pocomos_office_alert = [];
        $pocomos_office_alert['alert_id'] = $alert->id;
        $pocomos_office_alert['active'] = '1';

        if ($dept == 'Customer') {
            $pocomos_office_alert['assigned_by_id'] = $find_assigned_by_id->id ?? null;
            $pocomos_office_alert['assigned_to_id'] = $find_assigned_by_to->id ?? null;
            $pocomos_office_alert_create = PocomosCustomerAlert::create($pocomos_office_alert);
        } else {
            $pocomos_office_alert['assigned_by_user_id'] = $find_assigned_by_id->id ?? null;
            $pocomos_office_alert['assigned_to_user_id'] = $find_assigned_by_to->id ?? null;
            $pocomos_office_alert_create = PocomosOfficeAlert::create($pocomos_office_alert);
        }

        return $alert;
    }
    // Email template
    public function emailTemplate($body)
    {
        $mailBody = "";
        $variables = ['{{ lead_first_name }}', '{{ lead_address }}', '{{ lead_contact }}', '{{ lead_name }}', '{{ lead_email }}'];
        $values = [' Kishan ', ' kishanf@zignuts.com ', ' 12345678 ', ' Jamnagar ', ' Faldu Kishan '];
        // foreach($variables as $variable){
        //     $contains = Str::contains($body, $variable);
        //     if($contains == true){
        //         $str_variable = "{{ ".$variable." }}";
        //         $mailBody .= str_replace($str_variable, ' First Value', $body);

        //     }
        // }
        $mailBody = str_replace($variables, $values, $body);
        return $mailBody;
    }

    /**Uplaod file */
    public function uploadFile($file, $file_description = null, $show_to_customer = null)
    {
        //store file into document folder
        $detail['path'] = $file->store('public/files');

        //store your file into database
        $detail['filename'] = $file->getClientOriginalName();
        $detail['mime_type'] = $file->getMimeType();
        $detail['file_size'] = $file->getSize();
        $detail['active'] = 1;
        $detail['md5_hash'] =  md5_file($file->getRealPath());
        $file =  OrkestraFile::create($detail);

        if ($file) {
            return $file->id;
        } else {
            return 0;
        }
    }

    /**Uplaod file on S3 */
    public function uploadFileOnS3($folder, $file, $user_id = null)
    {
        //store your file into database
        $detail['filename'] = $file->getClientOriginalName();
        $detail['mime_type'] = $file->getMimeType();
        $detail['file_size'] = $file->getSize();
        $detail['active'] = 1;
        $detail['md5_hash'] =  md5_file($file->getRealPath());
        $detail['user_id'] = $user_id;

        $image = explode('.', $detail['filename']);
        $url = $folder . "/" . preg_replace('/[^A-Za-z0-9\-]/', '', $image[0]) . '_' . strtotime(date('Y-m-d H:i:s')) . '.' . $image[1];
        Storage::disk('s3')->put($url, file_get_contents($file));
        $detail['path'] = Storage::disk('s3')->url($url);

        $file =  OrkestraFile::create($detail);
        if ($file) {
            return $file->id;
        } else {
            return 0;
        }
    }

    /**Generate agreement with dynamic details */
    public function generateAgreement($agreement_body, $rec_id, $office_id)
    {
        $data = DB::select(DB::raw("SELECT pr.id, pr.first_name, pr.last_name, pr.legal_name, pr.date_of_birth, pr.email, pn.number as 'primary_phone', an.number as 'alt_phone', ro.name as 'office_name', CONCAT(ud.first_name, ' ' , ud.last_name ) as 'recruiter_name', rr.name as 'region_name', CONCAT(ppn.street, ' ,', ppn.city, ' ,', ppn.postal_code ,'.') as 'current_addres', CONCAT(pan.street, ' ,', pan.city, ' ,', pan.postal_code ,'.') as 'primary_addres', ra.name as 'agreement_name', rc.date_start, rc.date_end, rc.status, rc.date_created, rc.addendum, anr.summary, roc.office_id, pr.desired_username
        FROM pocomos_recruits AS pr
        LEFT JOIN pocomos_addresses AS ppn ON pr.current_address_id = ppn.id
        LEFT JOIN pocomos_addresses AS pan ON pr.primary_address_id = pan.id
        LEFT JOIN pocomos_phone_numbers AS pn ON ppn.phone_id = pn.id
        LEFT JOIN pocomos_phone_numbers AS an ON pan.phone_id = an.id
        LEFT JOIN pocomos_recruiting_offices AS ro ON pr.recruiting_office_id = ro.id
        LEFT JOIN pocomos_recruiting_office_configurations AS roc ON ro.office_configuration_id = roc.id
        LEFT JOIN pocomos_recruiters AS ru ON pr.recruiter_id = ru.id
        LEFT JOIN pocomos_company_office_users AS ou ON ru.user_id = ou.id
        LEFT JOIN orkestra_users AS ud ON ou.user_id = ud.id
        LEFT JOIN pocomos_recruiting_region AS rr ON pr.recruiting_region_id = rr.id
        LEFT JOIN pocomos_recruit_contracts AS rc ON pr.recruit_contract_id = rc.id
        LEFT JOIN pocomos_recruit_agreements AS ra ON rc.agreement_id = ra.id
        LEFT JOIN pocomos_recruit_notes AS rn ON pr.id = rn.recruit_id
        LEFT JOIN pocomos_notes AS anr ON rn.note_id = anr.id
        LEFT JOIN pocomos_company_offices AS co ON roc.office_id = co.id
        WHERE (roc.office_id = '$office_id' OR co.parent_id = '$office_id') AND pr.active = 1 AND pr.id = $rec_id
        GROUP BY pr.id
        ORDER BY pr.id ASC"));

        $office_logo = $this->getOfficeLogo($office_id);
        $recruitment_res = $this->getRecruitmentContractSigns($rec_id);

        $recruit_beginning_date = $data[0]->date_start ? date('Y-m-d', strtotime($data[0]->date_start)) : 'N/A';
        $recruit_end_date = $data[0]->date_end ? date('Y-m-d', strtotime($data[0]->date_end)) : 'N/A';
        $recruit_current_address = $data[0]->current_addres ?? 'N/A';
        $recruit_permanent_address = $data[0]->current_addres ?? 'N/A';
        $recruiter_name = $data[0]->recruiter_name ?? 'N/A';
        $recruit_first_name = $data[0]->first_name ?? 'N/A';
        $recruit_last_name = $data[0]->last_name ?? 'N/A';
        $recruit_phone = $data[0]->primary_phone ?? 'N/A';
        $recruiter_signature = $recruitment_res ? $recruitment_res['recruiter_signature_sign'] : '';
        $recruit_initials = $recruitment_res ? $recruitment_res['initials_sign'] : '';
        $recruit_signature = $recruitment_res ? $recruitment_res['signature'] : '';

        $variables = ['{{company_logo}}', '{{recruit_beginning_date}}', '{{recruit_current_address}}', '{{recruit_end_date}}', '{{recruiter_name}}', '{{recruiter_signature}}', '{{recruit_first_name}}', '{{recruit_initials}}', '{{recruit_last_name}}', '{{recruit_permanent_address}}', '{{recruit_phone}}', '{{recruit_signature}}', '{{custom_fields}}'];

        $values = [$office_logo, $recruit_beginning_date, $recruit_current_address, $recruit_end_date, $recruiter_name, $recruiter_signature, $recruit_first_name, $recruit_initials, $recruit_last_name, $recruit_permanent_address, $recruit_phone, $recruit_signature];

        return str_replace($variables, $values, $agreement_body);
    }

    /**Get office logo */
    public function getOfficeLogo($id)
    {
        $office =  PocomosCompanyOffice::findOrFail($id);

        $data = DB::select(DB::raw("SELECT ro.path
        FROM pocomos_company_offices AS po
        JOIN orkestra_files AS ro ON po.logo_file_id = ro.id
        WHERE (po.id = '$id' OR po.parent_id = '$id')"));

        if (!$data) {
            return null;
        }

        return '<img height="100px" width="200px" src="' . storage_path('app') . '/' . $data[0]->path . '">';
    }

    /**Get recruitment contract details */
    public function getRecruitmentContractSigns($id)
    {
        $data = DB::select(DB::raw("SELECT rsf.path as 'signature', isf.path as 'initials_sign', rrsf.path as 'recruiter_signature_sign'
        FROM pocomos_recruits AS pr
        JOIN pocomos_recruit_contracts AS prc ON pr.recruit_contract_id = prc.id
        JOIN orkestra_files AS rsf ON prc.signature_id = rsf.id
        JOIN orkestra_files AS isf ON prc.initials_id = isf.id
        JOIN orkestra_files AS rrsf ON prc.recruiter_signature_id = rrsf.id
        WHERE pr.id = '$id'"));

        if (!$data) {
            return null;
        }

        $res = [
            'signature' => '<img height="100px" width="200px" src="' . storage_path('app') . '/' . $data['0']->signature . '">',
            'initials_sign' => '<img height="100px" width="200px" src="' . storage_path('app') . '/' . $data['0']->initials_sign . '">',
            'recruiter_signature_sign' => '<img height="100px" width="200px" src="' . storage_path('app') . '/' . $data['0']->recruiter_signature_sign . '">',
        ];

        return $res;
    }

    public function getReasonCountsBySalesPeople(
        $startDate,
        $endDate,
        $officeIds = array(),
        $teams = array(),
        $salespeople = array(),
        $page,
        $perPage,
        $search
    ) {
        $query = 'SELECT s.id, CONCAT(u.first_name," ",u.last_name) AS salesperson,
                    o.list_name AS office_name,
                    r.name AS reason,
                    COUNT(r.id) AS total_leads
                FROM pocomos_lead_not_interested_reasons AS r
                JOIN pocomos_leads AS l ON r.id = l.not_interested_reason_id
                JOIN pocomos_lead_quotes AS q ON l.quote_id = q.id
                JOIN pocomos_salespeople AS s ON q.salesperson_id = s.id
                LEFT JOIN pocomos_memberships AS m ON s.id = m.salesperson_id
                JOIN pocomos_company_office_users AS ou ON s.user_id = ou.id
                JOIN pocomos_company_offices AS o ON ou.office_id = o.id
                JOIN orkestra_users AS u ON ou.user_id = u.id
                WHERE l.status = "Not Interested"
                AND l.date_created BETWEEN "' . $startDate . '" AND "' . $endDate . '" ';

        if ($officeIds) {
            $officeIds = implode(',', $officeIds);
            $query .= ' AND o.id IN (' . $officeIds . ')';
        }

        if ($teams) {
            $teams = implode(',', $teams);
            $query .= ' AND m.team_id IN (' . $teams . ')';
        }

        if ($salespeople) {
            $salespeople = implode(',', $salespeople);
            $query .= ' AND s.id IN (' . $salespeople . ')';
        }

        if ($search) {
            $search = '"%' . $search . '%"';
            $query .= " AND (CONCAT(u.first_name,' ',u.last_name) LIKE $search
                OR o.list_name LIKE $search
                )";
        }

        $query .= ' GROUP BY s.id, r.id';

        /**For pagination */
        $count = count(DB::select(DB::raw($query)));

        $paginateDetails = $this->getPaginationDetails($page, $perPage, true);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $query .= " LIMIT $perPage offset $page";
        $data = DB::select(DB::raw($query));

        return $data;
    }

    public function generateSalesPeopleLeadsArray($leads, $salespeople, $type)
    {
        $leads = array();
        foreach ($salespeople as $sid => $salesperson) {
            $salesperson = (array)$salesperson;
            // return $salesperson;

            // foreach ($salesperson as $reason) {
            //     return $salesperson;

            $leads[$sid]['salesperson'] = $salesperson['salesperson'];
            $leads[$sid]['officeName'] = $salesperson['office_name'];
            $leads[$sid][$type . 's'][$salesperson[$type]] = $salesperson['total_leads'];
            // $leads[$sid][$type . 's'][$salesperson['$']type] = $salesperson['total_leads'];
            $leads[$sid][$type . 's']['name'] = $salesperson[$type];
            $leads[$sid][$type . 's']['val'] = $salesperson['total_leads'];
            // }

            if ($type == 'knock') {
                $talks = 0;
                $sales = 0;
                $leads[$sid]['valuePerDoor'] = $salesperson['value_per_door'];

                if (isset($leads[$sid][$type . 's']['talk'])) {
                    $talks = $leads[$sid][$type . 's']['talk'];
                }

                if (isset($leads[$sid][$type . 's']['sale'])) {
                    $sales = $leads[$sid][$type . 's']['sale'];
                }

                if ($talks === 0) {
                    $leads[$sid]['closingPercentage'] = 0;
                } else {
                    $closingPercentage = ($sales / $talks) * 100;
                    $leads[$sid]['closingPercentage'] = round($closingPercentage, 2);
                }
            }
        }

        return $leads;
    }

    public function getKnockCountsBySalesPeople($startDate, $endDate, $officeIds = array(), $teams = array(), $salespeople = array())
    {
        $sql = 'SELECT s.id, CONCAT(u.first_name," ",u.last_name) AS salesperson,
                    o.list_name AS office_name,
                    a.type AS knock,
                    COUNT(a.id) AS total_leads,
                    ss.value_per_door
                FROM pocomos_lead_actions AS a
                JOIN pocomos_leads AS l ON a.lead_id = l.id
                JOIN pocomos_lead_quotes AS q ON l.quote_id = q.id
                JOIN pocomos_salespeople AS s ON q.salesperson_id = s.id
                JOIN pocomos_reports_salesperson_states AS ss ON ss.salesperson_id = s.id
                LEFT JOIN pocomos_memberships AS m ON s.id = m.salesperson_id
                JOIN pocomos_company_office_users AS ou ON s.user_id = ou.id
                JOIN pocomos_company_offices AS o ON ou.office_id = o.id
                JOIN orkestra_users AS u ON ou.user_id = u.id
                WHERE l.date_created BETWEEN "'.$startDate.'" AND "'.$endDate.'"';

        if ($officeIds) {
            $officeIds = implode(',', $officeIds);
            $sql .= ' AND o.id IN (' . $officeIds . ')';
        }

        if ($teams) {
            $teams = implode(',', $teams);
            $sql .= ' AND m.team_id IN (' . $teams . ')';
        }

        if ($salespeople) {
            $salespeople = implode(',', $salespeople);
            $sql .= ' AND s.id IN (' . $salespeople . ')';
        }

        $sql .= ' GROUP BY s.id, a.type';

        $knocks = DB::select(DB::raw($sql));

        return $knocks;
    }

    public function createEmptyTransformedArray()
    {
        return array(
            'sales' => 0,
            'leads' => 0,
            'talks' => 0,
            'knocks' => 0,
        );
    }

    public function transformDatum(array $array, array $datum)
    {
        return $array[$datum['type'] . 's'] += $datum['count'];
    }

    public function performAdditionalCalculations(array $array)
    {
        $array['knocks-sales'] = $array['sales'] <= 0 ? 0 : round($array['knocks'] / $array['sales'], 1);
        $array['knocks-leads'] = $array['leads'] <= 0 ? 0 : round($array['knocks'] / $array['leads'], 1);
        $array['knocks-talks'] = $array['talks'] <= 0 ? 0 : round($array['knocks'] / $array['talks'], 1);
        $array['talks-sales'] = $array['sales'] <= 0 ? 0 : round($array['talks'] / $array['sales'], 1);
        $array['talks-leads'] = $array['leads'] <= 0 ? 0 : round($array['talks'] / $array['leads'], 1);
        $array['leads-sales'] = $array['sales'] <= 0 ? 0 : round($array['leads'] / $array['sales'], 1);

        return $array;
    }

    /**Check customer is parent or not */
    public function is_cutomer_parent($id)
    {
        return PocomosSubCustomer::where('parent_id', $id)->count() ? true : false;
    }

    /**Check customer is child or not */
    public function is_cutomer_child($id)
    {
        return PocomosSubCustomer::where('child_id', $id)->count() ? true : false;
    }

    /**Check customer has multiple contracts or not */
    public function is_cutomer_multiple_contracts($id)
    {
        $profile = PocomosCustomerSalesProfile::where('customer_id', $id)->first();
        if (!$profile) {
            return false;
        }
        return PocomosContract::where('status', '!=', config('constants.CANCELLED'))->whereProfileId($profile->id)->count() > 1 ? true : false;
    }

    /**Convert customer details result to csv formate */
    public function convert_csv_formate_customer_data($heading, $data, $columns)
    {
        $res = array();
        $res[] = $heading;

        foreach ($data as $value) {
            $row = array();

            $row[] = $value->customer_name ?? '';
            $row[] = $value->office_name ?? '';
            $row[] = $value->office_fax ?? '';
            $row[] = $value->customer_email ?? '';
            $row[] = $value->company_name ?? '';
            $row[] = $value->billing_name ?? '';
            $row[] = $value->secondary_emails ?? '';
            $row[] = $value->street ?? '';
            $row[] = $value->city ?? '';
            $row[] = $value->billing_street ?? '';
            $row[] = $value->billing_postal ?? '';
            $row[] = $value->sales_status ?? '';
            $row[] = $value->contract_start_date ?? '';
            $row[] = $value->salesperson ?? '';
            $row[] = $value->map_code ?? '';
            $row[] = $value->service_type ?? '';
            $row[] = $value->autopay ? 'Yes' : 'No';
            $row[] = $value->date_created ?? '';
            $row[] = $value->initial_price ?? '';
            $row[] = $value->recurring_price ?? '';
            $row[] = $value->last_service_date ?? '';
            $row[] = $value->balance ?? '';
            $row[] = $value->first_name ?? '';
            $row[] = $value->last_name ?? '';
            $row[] = $value->account_type ?? '';
            $row[] = $value->next_service_date ?? '';

            $res[] = $row;
        }
        return $res;
    }

    // adding invoice price
    public function addPrice($request, $invoice)
    {
        $credentials = PocomosOfficeSetting::where('office_id', $request->office_id)->first();
        // if (!$credentials || !$credentials->points_credentials_id) {
        //     throw new \Exception(__('strings.message', ['message' => 'The OfficeConfiguration has no associated Points Credentials']));
        // }

        $transaction_data = [];

        $transaction_data['account_id'] = $request->account_id;
        $transaction_data['credentials_id'] =  '11';
        $transaction_data['amount'] = $request->amount;
        $transaction_data['type'] = 'sale';
        $transaction_data['network'] = $request->method;
        $transaction_data['status'] = 'Approved';
        $transaction_data['active'] = '1';
        $transaction_create = OrkestraTransaction::create($transaction_data);

        $user_transaction = [];
        $user_transaction['invoice_id'] = $invoice->id;
        $user_transaction['transaction_id'] = $transaction_create->id;
        $user_transaction['active'] = true;
        $user_transaction['memo'] = '';
        $user_transaction['type'] = $request->description;
        $user_transaction['user_id'] = $request->user;
        $userTransaction = PocomosUserTransaction::create($user_transaction);

        return $user_transaction;
    }
    /**Update customer details */
    public function deactivateCustomer($customer_id, $status_reason = null, $deactivate_children = false)
    {
        $customer = PocomosCustomer::findOrFail($customer_id);
        $customer->status = config('constants.INACTIVE');
        $customer->date_deactivated = date('Y-m-d H:i:s');
        $customer->status_reason_id = $status_reason;
        $customer->save();

        $sales_profile = PocomosCustomerSalesProfile::where('customer_id', $customer_id)->first();
        $sales_profile->autopay = false;
        $sales_profile->save();

        if ($deactivate_children) {
            $sub_customers = PocomosSubCustomer::where('parent_id', $customer_id)->get();

            foreach ($sub_customers as $val) {
                $this->deactivateCustomer($val->child_id, $status_reason);
            }
        }

        $desc = '';
        if (auth()->user()) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> deactivated";
        } else {
            $desc .= 'The system deactivated ';
        }

        if (isset($customer)) {
            $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . '</a>.';
        } else {
            $desc .= ' a customer account.';
        }

        $profileId = 'null';
        if ($sales_profile) {
            $profileId = $sales_profile->id;
        }

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Customer Deactivated", ' . auth()->user()->pocomos_company_office_user->id . ',
                        ' . $profileId . ', "' . $desc . '", "", "' . date('Y-m-d H:i:s') . '")';

        DB::select(DB::raw($sql));

        return true;
    }

    /**Cancel contract details */
    public function cancelContract($contract_id, $status_reason = null, $sales_status = null)
    {
        $salesContract = PocomosContract::with('profile_details.customer')->findOrFail($contract_id);
        $pestContract = PocomosPestContract::where('contract_id', $contract_id)->firstOrFail();

        if ($salesContract->status != config('constants.ACTIVE')) {
            throw new \Exception(__('strings.message', ['message' => 'A Contract must be Active before it can be cancelled.']));
        }

        $salesContract->status = config('constants.CANCELLED');
        $salesContract->date_cancelled = date('Y-m-d H:i:s');
        $salesContract->status_reason_id = $status_reason;

        if ($sales_status) {
            $salesContract->sales_status_id = $sales_status;
        }
        $salesContract->save();

        foreach ($pestContract->jobs_details as $job) {
            if (in_array($job->status, array(config('constants.PENDING'), config('constants.RESCHEDULED')))) {
                $invoice = $job->invoice_detail ?? array();
                $this->cancelJob($job, $invoice);
            } elseif ($job->invoice && ($job->invoice->status != config('constants.PAID'))) {
                try {
                    $this->cancelInvoice($job->invoice);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        $pocomos_invoices = PocomosInvoice::where('contract_id', $contract_id)->get();

        foreach ($pocomos_invoices as $val) {
            $val->status = config('constants.CANCELLED');
            $val->balance = 0;
            $val->save();
        }

        if (isset($salesContract->profile_details->customer)) {
            $customer = $salesContract->profile_details->customer;
        }

        $profileId = 'null';
        if (isset($customer->sales_profile)) {
            $profileId = $customer->sales_profile->id;
        }

        $desc = '';
        if (auth()->user()) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> cancelled";
        } else {
            $desc .= 'The system cancelled ';
        }

        if (isset($customer)) {
            $desc .= " a contract belonging to <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a>.";
        } else {
            $desc .= ' a customer\'s contract.';
        }

        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Contract Cancelled", ' . auth()->user()->pocomos_company_office_user->id . ',
                        ' . $profileId . ', "' . $desc . '", "", "' . date('Y-m-d H:i:s') . '")';

        DB::select(DB::raw($sql));

        return true;
    }

    /**Get future mics incvoices */
    public function findFutureMiscInvoices($contract_id, $includePaid = false)
    {
        $invoiceStatus = array(config('constants.CANCELLED'));
        if (!$includePaid) {
            $invoiceStatus[] = config('constants.PAID');
        }
        $currentDate = date('Y-m-d H:i:s');

        $invoices = PocomosInvoice::where('contract_id', $contract_id)
            ->where('date_due', '>=', $currentDate)
            ->whereIn('status', $invoiceStatus)
            ->get();

        return $invoices;
    }

    /**Get future invoices */
    public function findFutureInvoices($contract_id, $includePaid = false)
    {
        $invoiceStatus = array(config('constants.CANCELLED'));
        if (!$includePaid) {
            $invoiceStatus[] = config('constants.PAID');
        }
        $currentDate = date('Y-m-d H:i:s');

        $jobStatus = array(
            config('constants.COMPLETE'),
            config('constants.CANCELLED')
        );
        $jobStatusStr = $this->convertArrayInStrings($jobStatus);
        $invoiceStatusStr = $this->convertArrayInStrings($invoiceStatus);

        $invoices = DB::select(DB::raw("SELECT i.*
        FROM pocomos_invoices AS i
        JOIN pocomos_jobs AS j ON i.id = j.invoice_id
        JOIN pocomos_pest_contracts AS pc ON j.contract_id = pc.id
        WHERE i.date_due >= '$currentDate' AND j.type = '" . config('constants.REGULAR') . "' AND j.status IN ($jobStatusStr) AND i.status IN ($invoiceStatusStr) "));

        return $invoices;
    }

    /**
     * Update invoice recurring price
     *
     * @param $contract_id
     * @param $invoice
     * @param $recurringPrice
     *
     * @throws \Orkestra\Transactor\Exception\TransactorException
     */
    public function updateInvoiceRecurringPrice($contract_id, $invoice, $recurring_price)
    {
        $due = $invoice->amount_due;
        if (abs($due - $recurring_price) < 0.01) {
            return;
        }

        $item = PocomosInvoiceItems::where('invoice_id', $invoice->id)->first();
        $this->updateInvoiceItem($item, $recurring_price);

        // Increase; Move from Paid status
        // if ($invoice->isPaid() && $due <= $recurring_price) {
        //     // $invoice->setStatus(new InvoiceStatus(InvoiceStatus::NOT_SENT));
        // }
        return true;
    }

    /**
     * @param InvoiceItem $item
     * @param $newItemPrice
     * @param float null $oldItemPrice
     * @param PocomosTaxCode|null $oldItemTaxCode
     */
    public function updateInvoiceItem($item, $newItemPrice, $oldItemPrice = null, $oldItemTaxCode = null)
    {
        $officeId = Session::get('current_office_id') ?? null;

        if (in_array($item->type, array(config('constants.DISCOUNT'), config('constants.CREDIT')), false)) {
            $item->price = -abs($item->price);
            $newItemPrice = -abs($newItemPrice);
        }
        $invoice = PocomosInvoice::findOrFail($item->invoice_id);

        if ($oldItemPrice === null && $oldItemTaxCode === null) {
            $oldTotalPrice = $item->price + ($item->price * $invoice->sales_tax);
            $oldItemPrice = $item->price;
        } else {
            $oldTotalPrice = $oldItemPrice + ($oldItemPrice * $oldItemTaxCode->tax_rate);
        }

        $taxCode = PocomosTaxCode::findOrFail($invoice->tax_code_id);
        $item->sales_tax = $taxCode->tax_rate;
        $item->tax_code_id = $invoice->tax_code_id;
        $itemPriceDiff = round(($newItemPrice) - ($oldItemPrice), 2);
        $newSalesTax = $taxCode->tax_rate;
        $newTotalPrice = ($newItemPrice + ($newItemPrice * $newSalesTax));
        $diff = round(($newTotalPrice) - ($oldTotalPrice), 2);

        if (abs($diff) > 0.005) {
            $newAmountDue = $invoice->amount_due + ($itemPriceDiff);
            $newBalance = $invoice->balance + ($diff);
            $invoice->amount_due = (float)$newAmountDue;
            $invoice->balance = $newBalance;
            if ($invoice->balance < 0) {
                // Now office id is static defined
                $user = OrkestraUser::where('id', auth()->user()->id)->first();
                $balanceDiff = abs(0 - $invoice->balance);
                $profile = $invoice->contract->profile_details;
                $this->addCredit($profile, $balanceDiff, $user);
                $this->addItem('Credit applied for overpayment', $balanceDiff, true, $invoice);
            }

            $item->price = $newItemPrice;
        }
        $item->save();
        $invoice->Save();

        // return true;
        $this->distributeInvoicePayments($invoice);
    }

    /**
     * @param string $description
     * @param float $price
     * @param bool $adjustment
     * @return bool|PocomosInvoiceItems
     */
    public function addItem($description, $price, $adjustment = false, $invoice)
    {
        //ALL ADDITEMS NEED OT BE RETHOUGHT. OTherwise it's fckd
        $taxCode = PocomosTaxCode::findOrFail($invoice->tax_code_id);

        $item['tax_code_id'] = $invoice->tax_code_id;
        $item['sales_tax'] = $taxCode->tax_rate;
        $item['type'] = '';
        $item['description'] = $description;
        $item['price'] = $price;
        $item['active'] = true;
        // $item['invoice_id'] = $invoice_id;
        //Todo: Remove this. Also need to figure out where adjustments are added
        if ($adjustment) {
            $item['type'] = config('constants.INTERNAL_ADJUSTMENT');
        }

        $item = PocomosInvoiceItems::create($item);

        $this->addInvoiceItemSwitch($item, $invoice);

        return $item;
    }

    public function addDiscountItem($description, $price, $discount = false, $invoice)
    {
        $invoice_item['tax_code_id'] = $invoice->tax_code_id;
        $invoice_item['sales_tax'] = $invoice->tax_code->tax_rate;
        $invoice_item['description'] = $description;
        $invoice_item['price'] =  $price;

        if ($discount) {
            $invoice_item['type'] = 'Discount';
        }

        $invoice_item['invoice_id'] = $invoice->id;
        $invoice_item['active'] = 1;
        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $this->addInvoiceItemSwitch($invoice_item, $invoice);
    }

    /**Adds an existing InvoiceItem to this Invoice.*/

    public function addInvoiceItemSwitch($item, $invoice)
    {
        $item->sales_tax = $invoice->tax_code->tax_rate;
        $item->tax_code_id = $invoice->tax_code->id;
        $item->invoice_id = $invoice->id;

        switch ($item->type) {
                //            Not important for removal
            case 'Adjustment':
                $invoice->balance   += round($item->price + ($item->price * $item->sales_tax), 2);
                break;
                //                cause for the issue with balance. Credit should be substracted from the balance. That's it.1
            case 'Credit':
                $invoice->balance   += round($item->price + ($item->price * $item->sales_tax), 2);
                break;
            case 'Discount':
                $invoice->amount_due += $item->price;
                $invoice->balance   += round($item->price + ($item->price * $item->sales_tax), 2);
                break;
            default:
                $invoice->amount_due += $item->price;
                $invoice->balance   += round($item->price + ($item->price * $item->sales_tax), 2);
                break;
        }

        $invoice->save();

        return $item;
    }

    public function updateServiceType($contract, $serviceType)
    {
        $originalType = $contract->service_type_id;

        $contract->service_type_id = $serviceType;

        $jobs = PocomosJob::where('contract_id', $contract->id)->get();

        foreach ($jobs as $job) {
            if (!in_array($job->status, array(config('constants.PENDING'), config('constants.RESCHEDULED')))) {
                return true;
            }

            // update first service on a job that has the same ServiceType
            $services = PocomosJobService::where('job_id', $job->id)->get();
            foreach ($services as $service) {
                if ($service->service_type_id == $originalType) {
                    $service->service_type_id = $serviceType;
                    $service->save();
                }
            }

            // update first invoiceItem on a job's invoice that has a description matching the serviceType
            $oldDescription = sprintf('%s %s Service', $job->type, $originalType);
            $newDescription = sprintf('%s %s Service', $job->type, $serviceType);

            $invoiceItems = $job->invoice->invoice_items;
            foreach ($invoiceItems as $item) {
                if ($item->description == $oldDescription) {
                    $item->description = $newDescription;
                    $item->save();
                }
            }

            $contract->save();
        }

        return true;
    }

    public function addCredit($profile, $amount, $user = null, $description = null)
    {
        return $this->processCreditTransaction($profile, $amount, config('constants.CREDIT'), $user, $description);
    }

    public function removeCredit($profile, $amount, $user = null, $description = null)
    {
        return $this->processCreditTransaction($profile, $amount, config('constants.SALE'), $user, $description);
    }

    /**
     * @param PocomosCustomerSalesProfile $profile
     * @param $amount
     * @param $transactionType
     * @param OrkestraUser $user
     * @param $description
     * @return PocomosUserTransaction
     */
    public function processCreditTransaction($profile, $amount, $transactionType, $user = null, $description = null)
    {
        if (!$profile->points_account_id) {
            throw new \Exception(__('strings.message', ['message' => 'The given CustomerSalesProfile has no associated PointsAccount']));
        }

        $configuration = $profile->office_id;

        if (!$configuration) {
            throw new \Exception(__('strings.message', ['message' => 'The Office has no associated OfficeConfiguration']));
        }

        $credentials = PocomosOfficeSetting::where('office_id', $configuration)->first();
        if (!$credentials || !$credentials->points_credentials_id) {
            throw new \Exception(__('strings.message', ['message' => 'The OfficeConfiguration has no associated Points Credentials']));
        }

        $transaction['amount'] = ($amount * 100);
        $transaction['network'] = config('constants.POINTS');
        $transaction['type'] = $transactionType;
        $transaction['account_id'] = $profile->points_account_id;
        $transaction['credentials_id'] = $credentials->points_credentials_id;
        $transaction['description'] = $description;
        $transaction['status'] = 'Approved';
        $transaction['active'] = true;
        $transaction['date_created'] = date("Y-m-d");
        $transaction = OrkestraTransaction::create($transaction);

        $user_transaction['transaction_id'] = $transaction->id;
        $user_transaction['active'] = true;
        $user_transaction['memo'] = '';
        $user_transaction['type'] = $description ?? '';
        if ($user !== null) {
            $user_transaction['user_id'] = $user->id;
        }
        $userTransaction = PocomosUserTransaction::create($user_transaction);

        $customerState = PocomosCustomerState::where('customer_id', $profile->customer_id)->first();
        $account = $profile->points_account;

        if($transactionType == config('constants.CREDIT')){
            $customerState->balance_credit = $customerState->balance_credit + $amount;
        
            $account->balance = $account->balance + ($amount * 100);
        }else{
            if(($customerState->balance_credit - $amount) >= 0){
                $customerState->balance_credit = $customerState->balance_credit - $amount;
            }else{
                $customerState->balance_credit = 0;
            }

            if(($account->balance - ($amount * 100)) >= 0){
                $account->balance = $account->balance - ($amount * 100);
            }else{
                $account->balance = 0;
            }
        }
        
        $account->save();
        $customerState->save();

        return $userTransaction;
    }

    /**
     * Send form letter base email to customers
     * @param array $customerIds
     * @param PocomosFormLetter $letter
     */
    public function sendFormLetterFromCustomers($customerIds, $letter, $office_id)
    {
        $count = 0;
        $result = array();

        $office = PocomosCompanyOffice::findOrFail($office_id);
        $office_email = unserialize($office->email);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($office_id)->whereUserId(auth()->user()->id)->first();

        if (isset($office_email[0])) {
            $from = $office_email[0];
        } else {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $cus_ids = PocomosCustomerSalesProfile::where('office_id', $office_id)->pluck('customer_id')->toArray();
        $cus_ids = array_intersect($cus_ids, $customerIds);
        $customers = PocomosCustomer::with('sales_profile.office_details')->whereIn('id', $cus_ids)->get();
        $formLetter = PocomosFormLetter::findOrFail($letter);

        /** @var PocomosCustomer $customer */
        foreach ($customers as $customer) {
            $customerEmail = $customer->email;
            if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $agreement_body = $this->sendFormLetter($letter, $customer);
                $profile = $customer->sales_profile;
                Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customerEmail, $from) {
                    $message->from($from);
                    $message->to($customerEmail);
                    $message->subject($formLetter['subject']);
                });

                $email_input['office_id'] = $office->id;
                $email_input['office_user_id'] = $officeUser->id;
                $email_input['customer_sales_profile_id'] = $profile->id;
                $email_input['type'] = 'Welcome Email';
                $email_input['body'] = $agreement_body;
                $email_input['subject'] = $formLetter['subject'];
                $email_input['reply_to'] = $from;
                $email_input['reply_to_name'] = $office->name ?? '';
                $email_input['sender'] = $from;
                $email_input['sender_name'] = $office->name ?? '';
                $email_input['active'] = true;
                $email = PocomosEmail::create($email_input);

                $input['email_id'] = $email->id;
                $input['recipient'] = $customer->email;
                $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
                $input['date_status_changed'] = date('Y-m-d H:i:s');
                $input['status'] = 'Delivered';
                $input['external_id'] = '';
                $input['active'] = true;
                $input['office_user_id'] = $officeUser->id;
                PocomosEmailMessage::create($input);
            }
        }

        return true;
    }

    /**
     * Send jobs base send form letter email to customers
     * @param array $jobIds
     * @param $letter
     */
    public function sendFormLetterFromJobIds(array $jobIds, $letter, $office_id)
    {
        // dd($jobIds);
        $office = PocomosCompanyOffice::findOrFail($office_id);
        $formLetter = PocomosFormLetter::findOrFail($letter);
        // dd(11);
        $jobIds = $this->convertArrayInStrings($jobIds);
        $from = $this->getOfficeEmail($office_id);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($office_id)->whereUserId(auth()->user()->id)->first();

        $jobs = DB::select(DB::raw("SELECT j.*
            FROM pocomos_jobs AS j
            JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id
            JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
            JOIN pocomos_customer_sales_profiles AS p ON c.profile_id = p.id
            JOIN pocomos_agreements AS a ON c.agreement_id = a.id
            WHERE p.office_id = $office_id AND j.id IN($jobIds)"));

        foreach ($jobs as $job) {
            $job = PocomosJob::findOrFail($job->id);
            $profile = $job->contract->contract_details->profile_details;
            $customer = $job->contract->contract_details->profile_details->customer_details;
            // dd(88);
            $agreement_body = $this->sendFormLetter($letter, $customer, $job);

            Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customer, $from) {
                $message->from($from);
                $message->to($customer->email);
                $message->subject($formLetter['subject']);
            });

            $email_input['office_id'] = $office->id;
            $email_input['office_user_id'] = $officeUser->id;
            $email_input['customer_sales_profile_id'] = $profile->id;
            $email_input['type'] = $formLetter['title'];
            $email_input['body'] = $agreement_body;
            $email_input['subject'] = $formLetter['subject'];
            $email_input['reply_to'] = $from;
            $email_input['reply_to_name'] = $office->name ?? '';
            $email_input['sender'] = $from;
            $email_input['sender_name'] = $office->name ?? '';
            $email_input['active'] = true;
            $email = PocomosEmail::create($email_input);

            $input['email_id'] = $email->id;
            $input['recipient'] = $customer->email;
            $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
            $input['date_status_changed'] = date('Y-m-d H:i:s');
            $input['status'] = 'Delivered';
            $input['external_id'] = '';
            $input['active'] = true;
            $input['office_user_id'] = $officeUser->id;
            PocomosEmailMessage::create($input);

            if ($job && $formLetter->confirm_job) {
                $this->confirmJob($job);
            }
        }

        return true;
    }

    public function sendFormLetterFromInvoiceIds($invoiceIds, $formLetterId)
    {
        $formLetter = PocomosFormLetter::find($formLetterId);

        $customers = $this->getCustomersByInvoiceIdsAndOffice($invoiceIds, $formLetter->office_id);

        $officeId = auth()->user()->pocomos_company_office_user->office_id;

        $office = PocomosCompanyOffice::findOrFail($officeId);

        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId(auth()->user()->id)->first();

        $from = $this->getOfficeEmail($officeId);
        // $from = 'vinitm@zignuts.com';

        foreach ($customers as $customer) {
            // return $customer->email;
            $agreement_body = $this->sendFormLetter($formLetterId, $customer);

            Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customer, $from) {
                $message->from($from);
                $message->to($customer->email);
                $message->subject($formLetter['subject']);
            });

            $email_input['office_id'] = $officeId;
            $email_input['office_user_id'] = $officeUser->id;
            $email_input['customer_sales_profile_id'] = $customer->sales_profile->id;
            $email_input['type'] = $formLetter['title'];
            $email_input['body'] = $agreement_body;
            $email_input['subject'] = $formLetter['subject'];
            $email_input['reply_to'] = $from;
            $email_input['reply_to_name'] = $office->name ?? '';
            $email_input['sender'] = $from;
            $email_input['sender_name'] = $office->name ?? '';
            $email_input['active'] = true;
            $email = PocomosEmail::create($email_input);

            $input['email_id'] = $email->id;
            $input['recipient'] = $customer->email;
            $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
            $input['date_status_changed'] = date('Y-m-d H:i:s');
            $input['status'] = 'Delivered';
            $input['external_id'] = '';
            $input['active'] = true;
            $input['office_user_id'] = $officeUser->id;
            PocomosEmailMessage::create($input);
            // foreach ($emailResult->getEmails() as $email) {
            //     $result->addEntity($email);
            //     $result->meta->count++;
            // }
        }

        // return $result;
    }

    public function getCustomersByInvoiceIdsAndOffice($invoiceIds, $officeId)
    {
        return  PocomosCustomer::select('*', 'pocomos_customers.id', 'pocomos_customers.email')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pocomos_customers.id', 'pcsp.customer_id')
            ->join('pocomos_company_offices as pco', 'pcsp.office_id', 'pco.id')
            ->join('pocomos_contracts as pc', 'pcsp.id', 'pc.profile_id')
            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_invoices as pi', 'pc.id', 'pi.contract_id')
            ->where('pco.id', $officeId)
            ->whereIn('pi.id', $invoiceIds)
            ->get();
    }


    public function sendSmsFormLetterFromJobIds($jobIds, $letter)
    {
        // dd($jobIds);
        $jobs = PocomosJob::with('contract', 'route_detail')
            ->select('*', 'pcsp.customer_id', 'pocomos_jobs.contract_id')
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
            ->where('pcsp.office_id', $letter->office_id)
            ->whereIn('pocomos_jobs.id', $jobIds)
            ->get();

        foreach ($jobs as $job) {
            $customerId = $job->customer_id;
            $contractId = $job->contract_id;
            // $customer = $job->getContract()->getContract()->getProfile()->getCustomer();

            $customer = PocomosCustomer::with('contact_address', 'sales_profile')->findOrFail($customerId);
            $pestContract = PocomosPestContract::with('service_type_details')->find($contractId);

            $sentCount = $this->sendSmsFormLetter($letter, $customer, $pestContract, $job);
            // $result->meta->count += $sentCount;

            if ($job && $letter->confirm_job == 1 && $sentCount > 0) {
                $this->confirmJob($job);
            }
        }

        return $sentCount;
    }


    public function sendSmsFormLetterFromInvoiceIds($invoiceIds, $letter)
    {

        $invoices = $this->findByIdsAndOffice_invoiceRepo($invoiceIds, $letter->office_id);

        foreach ($invoices as $invoice) {
            $customer = $invoice->contract->profile_details->customer;

            $sentCount = $this->sendSmsFormLetter($letter, $customer, $invoice->contract->pest_contract_details);
        }
        // return $result;
    }


    public function findByIdsAndOffice_invoiceRepo($invoiceIds, $officeId)
    {
        // dd($invoiceIds);
        return  PocomosInvoice::select('*', 'pocomos_invoices.id')->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->where('pag.office_id', $officeId)
            ->whereIn('pocomos_invoices.id', $invoiceIds)
            ->get();
    }

    /**
     * Send Lead form letter
     *

     */
    public function sendLeadFormLetter($formLetter, $lead, $job = null)
    {
        $job = isset($job) ? $job : null;
        /** @var PocomosContract|null $contract */
        $contract = $job ? $job->contract : null;

        $letter = PocomosFormLetter::findOrFail($formLetter);

        $body = $this->renderleadDynamicTemplate($letter->body, null, $lead, $contract, $job);
        return $body;
    }

    /**

     */
    public function renderleadDynamicTemplate($template, $params = null, $lead = null, $pcc = null, $job = null, $pdf = false)
    {
        if (!$params) {
            $params = $this->getLeadCustomDynamicParameters($lead, $pcc, $job, $pdf);
        }

        if ($pdf) {
            $uploadDir = storage_path('app') . '/public' . config('constants.CKEDITOR_UPLOAD_DIR');
            $template = preg_replace('/(?<=(?:src\\="))[^"]+\\/file\\/\\d+\\/view\\?(\\w+.[^"]+)/', $uploadDir . '/\\1', $template);
        }

        $matches = array();
        preg_match_all('/\{\{\s*?([\w_]+)\s*?\}\}/', $template, $matches, PREG_OFFSET_CAPTURE);

        foreach (array_reverse($matches[0], true) as $matchIndex => $match) {
            list($word, $pos) = $match;
            $param = $matches[1][$matchIndex][0];

            $value = isset($params[$param]) ? $params[$param] : '';

            $template = substr_replace($template, $value, $pos, strlen($word));
        }

        return $template;
    }

    public function getLeadCustomDynamicParameters($lead, $pestContract = null, $job = null, $pdf = false)
    {
        $defaultParameters = $this->getLeadDynamicParameters($lead, $pestContract, $job, $pdf);

        $customParameters = array();

        return array_merge($defaultParameters, $customParameters);
    }

    public function getLeadDynamicParameters($lead, $pestContract = null, $job = null, $pdf = false)
    {
        $customFieldsParameters = array();

        return array_merge(array(
            //Default Things
            'lead_first_name' => $lead->first_name ?? '',
            'lead_last_name' => $lead->last_name ?? '',
            'lead_name' => $lead->first_name  . " " .  $lead->last_name ?? '',
            'lead_id' => $lead->id ?? '',
            'lead_email' => $lead->email ?? '',
            'lead_phone_number' =>  $lead->contact_address->primaryPhone->number,
            'lead_address' => $lead->contact_address ? $lead->contact_address->suite . ', ' . $lead->contact_address->street . ', ' . $lead->contact_address->city : '',

        ), $customFieldsParameters);
    }

    /**
     * Send form letter
     *
     * @param PocomosFormLetter $formLetter
     * @param PocomosCustomer $customer
     * @param PocomosJob|null $job
     */
    public function sendFormLetter($formLetter, $customer, $job = null)
    {
        $job = isset($job) ? $job : null;
        $contract = null;

        if ($job) {
            if ($job['contract_id'] != null) {
                $contract = PocomosPestContract::where('id', $job['contract_id'])->firstOrFail();
            }
        }
        $letter = PocomosFormLetter::findOrFail($formLetter);

        $body = $this->renderDynamicTemplate($letter->body, null, $customer, $contract, $job);
        return $body;
    }

    /**
     * @param string $template
     * @param null $params
     * @param PocomosCustomer|PocomosCustomerSalesProfile $customer
     * @param PocomosContract $pcc
     * @param PocomosJob $job
     * @param bool $pdf
     * @return string
     */
    public function renderDynamicTemplate($template, $params = null, $customer = null, $pcc = null, $job = null, $pdf = false)
    {
        if (!$params) {
            $params = $this->getCustomDynamicParameters($customer, $pcc, $job, $pdf);
        }

        if ($pdf) {
            $uploadDir = storage_path('app') . '/public' . config('constants.CKEDITOR_UPLOAD_DIR');
            $template = preg_replace('/(?<=(?:src\\="))[^"]+\\/file\\/\\d+\\/view\\?(\\w+.[^"]+)/', $uploadDir . '/\\1', $template);
        }

        $matches = array();
        preg_match_all('/\{\{\s*?([\w_]+)\s*?\}\}/', $template, $matches, PREG_OFFSET_CAPTURE);

        foreach (array_reverse($matches[0], true) as $matchIndex => $match) {
            list($word, $pos) = $match;
            $param = $matches[1][$matchIndex][0];

            $value = isset($params[$param]) ? $params[$param] : '';

            $template = substr_replace($template, $value, $pos, strlen($word));
        }

        return $template;
    }

    /**
     * @param PocomosCustomer|PocomosCustomerSalesProfile $customer
     * @param PocomosPestContract $pestContract
     * @param PocomosJob $job Optional job context
     * @param bool $pdf
     * @return array
     */
    public function getCustomDynamicParameters($customer, $pestContract = null, $job = null, $pdf = false)
    {
        $defaultParameters = $this->getDynamicParameters($customer, $pestContract, $job, $pdf);
        $profile = null;
        if ($customer) {
            $profile = $customer->sales_profile;
        }

        if ($job) {
            $job = PocomosJob::findOrFail($job['id']);
            $office = ($job->contract->contract_details->agreement_details ? $job->contract->contract_details->agreement_details->office_details : $profile->office_details);
        } else {
            $office = $profile->office_details;
        }

        if (!$pestContract && $job) {
            $pestContract = $job->contract;
        }

        //bEcAuSe It'S cUrReNt YeAr
        $currentYear = date('Y-m-d');
        $nextYear = date('Y-m-d', strtotime('+1 year'));

        $customParameters = array(
            'insight1_billing_calendar' => $this->getInsightCalendar($pestContract),
            'insight1_billing_calendar_french' => $this->getInsightCalendar($pestContract, 'French'),
            'insight1_address' => $this->getInsightAddress($office),
            'insight1_pests' => $this->getInsightPests($pestContract, $office),
            'insight1_special_pests' => $this->getInsightSpecialtyPests($pestContract, $office),
            'insight1_contract_length' => $this->getInsightContractLength($pestContract, $office, 'English'),
            'insight1_contract_length_french' => $this->getInsightContractLength($pestContract, $office, "French"),
            'streched_customer_signature' => $this->getStretchedCustomerSignature($pestContract),
            'customer_signature_base64' => $this->getStretchedCustomerSignature($pestContract),
            'billing_address_or_service' => $this->getBillingOrServiceAddress($customer),
            'streched_customer_autopay_signature' => $this->getStretchedCustomerAutopaySignature($pestContract, $profile),
            'terminix_calendar' => $this->getTerminixCalendar($pestContract),
            'terminix_calendar_monthly_quarterly' => $this->getTerminixCalendarMonthly($pestContract),
            'terminix_special_pests' => $this->getTerminixSpecialtyPests($pestContract, $office),
            'terminix_regular_pests' => $this->getTerminixRegularPests($pestContract, $office),
            'terminix_payment_method' => $this->getTerminixPaymentMethod($customer),
            'terminix_autopay' => $this->getTerminixAutopay($customer),
            'contract_jobs_this_year' => $this->getContractRegularJobCountForYear($pestContract, $currentYear),
            'contract_jobs_next_year' => $this->getContractRegularJobCountForYear($pestContract, $nextYear),
            'contract_earliest_job_month' => $this->getContractEarliestMonth($pestContract),
            'contract_latest_job_month' => $this->getContractLatestMonth($pestContract),
            'contract_unique_month_count' => $this->getContractUniqueMonthCount($pestContract),
            'contract_regular_job_total_cost' => $this->getContractRegularJobTotalCost($pestContract),
            'terminix_recurring_yearly_for_mosquitos' => $this->getRecurringYearlyForMosquitosTerminix($pestContract),
            'prodefense_billing_calendar' => $this->getProDefenseCalendar($pestContract),
        );
        return array_merge($defaultParameters, $customParameters);
    }

    public function getDynamicParameters($customer, $pestContract = null, $job = null, $pdf = false)
    {
        $profile = null;
        if ($customer) {
            $profile = $customer->sales_profile;
            $customer = $customer;
            $customer_id = $customer->id;
        }

        if ($customer) {
            $customerState = $customer->state_details;
        } else {
            $customerState = null;
        }

        if (!$profile) {
            $profile = $customer->sales_profile;
        }

        if ($job) {
            if (!isset($job['id'])) {
                $job = (array) $job;
            }
            $job = PocomosJob::findOrFail($job['id']);
            $office = ($job->contract->contract_details->agreement_details ? $job->contract->contract_details->agreement_details->office_details : $profile->office_details);
        } else {
            $office = $profile->office_details ?? array();
        }

        if (!$pestContract && $job) {
            $pestContract = $job->contract;
        } elseif (!$pestContract && $customer->id) {
            $sales_profile = PocomosCustomerSalesProfile::where('customer_id', $customer_id)->first();
            $contract_details = null;

            if ($sales_profile) {
                $contract_details = PocomosContract::where('profile_id', $sales_profile->id)->first();
            } else {
                $pestContract = null;
            }
            if ($contract_details) {
                $pestContract = PocomosPestContract::where('contract_id', $contract_details->id)->first();
            } else {
                $pestContract = null;
            }
        }
        $customFieldsParameters = $this->getCustomFields($office, $pestContract);
        $linkData = $this->getVerifyEmailLink($customer);
        $tech = $this->getTechnician($job);

        return array_merge(array(
            //Default Things
            'company_logo' => "<img src='" . $office->logo->full_path . "' height='100px' width='100px'>",
            'office_address' => ($office->coontact_address ? $office->coontact_address->suite : '') . ', ' . ($office->coontact_address ? $office->coontact_address->street : '') . ', ' . ($office->coontact_address ? $office->coontact_address->city : ''),
            'office_phone' => $this->getOfficePhone($office),
            'company_name' => $office->name ?? '',
            'company_address' => ($office->coontact_address ? $office->coontact_address->suite : '') . ', ' . ($office->coontact_address ? $office->coontact_address->street : '') . ', ' . ($office->coontact_address ? $office->coontact_address->city : ''),
            'company_phone' => $this->getOfficePhone($office),
            'company_email' => $office->email ?? '',
            'customer_portal_link' => $office->customer_portal_link ?? '',

            'contract_initial_service_window' => $this->getInitialServiceWindow($office, $pestContract),
            'service_window' => $this->getServiceWindow($office, $job),
            'service_calendar' => $this->getServiceCalender($pestContract),

            'billing_calendar' => $this->getBillingCalendar($pestContract),

            'verify_email' => $linkData['html_link'] ?? "",
            'invoice_numbers' => $this->getInvoiceNumbers($customer),
            'scheduled_services' => $this->getScheduledServices($customer),
            'customer_first_name' => $customer->first_name,
            'customer_company_name' => $customer->company_name,
            'customer_last_name' => $customer->last_name,
            'customer_name' => $this->getCustomerName($customer),
            'customer_id' => $customer->external_account_id,
            'customer_service_address' => $customer->contact_address ? $customer->contact_address->suite . ', ' . $customer->contact_address->street . ', ' . $customer->contact_address->city : '',
            'customer_billing_address' => $customer->billing_address ? $customer->billing_address->suite . ', ' . $customer->billing_address->street . ', ' . $customer->billing_address->city : '',
            'customer_email' => $customer->email,
            'customer_phone' => $customer->contact_address->primaryPhone->number ?? null,

            'next_service' => $this->getDateWithFormat(($customer->state_details ? $customer->state_details->next_service_date : ''), $pdf),
            'customer_last_service_date' => $this->getLastServiceDate($customer, $pdf),
            // TODO: Why does this get called before customer creation is flushed? We should use the Report Helper here
            //Because such is life.
            'balance' => $this->formatCurrency($customer->state_details ? $customer->state_details->balance_overall : 0.00),
            'credit' => $this->formatCurrency($customer->state_details ? $customer->state_details->balance_credit : 0.00),
            'technician' => ($tech ? ($tech->user_detail ? ($tech->user_detail->user_details_name ? $tech->user_detail->user_details_name->first_name . ' ' . $tech->user_detail->user_details_name->last_name : '') : '') : ''),
            'technician_bio' => $this->getTechnicianBio($job),
            'technician_photo' => $this->getTechnicianPhoto($job, $pdf),
            'service_date' => $this->getServiceDate($job, $pdf),
            'service_time' => $this->getServiceTime($job),
            'service_frequency' => $pestContract->service_frequency ?? '',
            'service_type' => $pestContract->service_type_details ? $pestContract->service_type_details->name : '',
            'service_address' => $customer->contact_address ? $customer->contact_address->suite . ', ' . $customer->contact_address->street . ', ' . $customer->contact_address->city : '',
            'service_city' => $customer->contact_address ? $customer->contact_address->city : '',
            'service_state' => $customer->contact_address ? ($customer->contact_address->region ? $customer->contact_address->region->name : '') : '',
            'service_zip' =>  $customer->contact_address ? $customer->contact_address->postal_code : '',
            'customer_signature' => '<img height="100px" width="200px" src="' . $this->getSignature(($pestContract->contract_details ? ($pestContract->contract_details->signature_details ? $pestContract->contract_details->signature_details->path : '') : ''), $pdf) . '">',
            'auto_pay_signature' => '<img height="100px" width="200px" src="' . $this->getSignature(($pestContract->contract_details ? ($pestContract->contract_details->signature_details ? $pestContract->contract_details->signature_details->path : '') : ''), $pdf) . '">',
            'auto_pay_checkbox' => $this->getAutoPayCheckbox($pestContract),
            'auto_pay_yesno' => ($pestContract->contract_details ? ($pestContract->contract_details->auto_renew ? 'Yes' : 'No') : 'No'),
            'agreement_price_info' => $this->getAgreementPriceInfoNew($pestContract),
            //It's broken by the way.
            'selected_pests' => $this->getSelectedPestsNew($pestContract, $office),
            'regular_pests_list' => $this->getListOfRegularPestsNew($pestContract, $office),

            'agreement_type' => $pestContract->contract_details->agreement_details->name ?? '',
            'agreement_length' => $this->getAgreementLength($pestContract),
            'services_count' => count(($pestContract->jobs_details ? $pestContract->jobs_details->toArray() : array() ?? array())),

            'total_contract_value' => $this->getContractValue($pestContract),
            'salesperson_signature' => $this->getSalespersonSignature($pestContract, $pdf),
            'stretched_salesperson_signature' => $this->getSalespersonSignatureStretched($pestContract, $pdf),

            'contract_start_date' => $this->getDateWithFormat($pestContract->contract_details->date_start, $pdf),
            'contract_start_month_year' => $this->getYearWithFormat($pestContract->contract_details->date_start),
            'contract_end_month_year' => $this->getYearWithFormat($pestContract->contract_details->date_end),
            'contract_end_date' => $this->getDateWithFormat($pestContract->contract_details->date_end, $pdf),
            'contract_month_length' => $this->getContractLengthInMonths($pestContract),

            'customer_cc_last_four' => $this->getCustomerCCLastFour($customer),
            'customer_bank_last_four' => $this->getCustomerBankLastFour($customer),
            'customer_cc_expiry_date' => $this->getCustomerCCExpirationDate($customer),

            'get_signature_date' => $this->getSignatureDate($pestContract, $pdf),

            'contract_regular_jobs_count' => $this->getContractRegularJobCount($pestContract),

            'customer_billing_name' => $pestContract->billing_name,
            'salesperson_name' => $this->getSalespersonName($pestContract),


            'contract_initial_job_date' => $this->getContractInitialJobDate($pestContract),
            'contract_initial_job_time' => $this->getContractInitialJobTime($pestContract),
            'contract_initial_job_note' => $this->getContractInitialJobNote($pestContract),

            //Normal prices
            'contract_initial_price' => $this->getContractInitialPrice($pestContract),
            'contract_recurring_price' => $this->getContractRecurringPrice($pestContract),
            'contract_regular_initial_price' => $this->getContractRegularInitialPrice($pestContract),
            'contract_initial_discount' => $this->getContractInitialDiscount($pestContract),
            'contract_recurring_discount' => $this->getContractRecurringDiscount($pestContract),
            'contract_value_pre_creation' => $this->getContractValuePreCreation($pestContract),

            //Just the tax
            'contract_initial_price_tax' => $this->getInitialPriceTax($pestContract),
            'contract_recurring_price_tax' => $this->getRecurringPriceTax($pestContract),
            'contract_total_contract_value_tax' => $this->getContractValuePreCreationTax($pestContract),

            //Prices with Tax
            'contract_recurring_price_with_tax' => $this->getContractRecurringPriceWithTax($pestContract),
            'contract_initial_price_with_tax' => $this->getContractInitialPriceWithTax($pestContract),
            'contract_value_pre_creation_with_tax' => $this->getContractValuePreCreationWithTax($pestContract),
            'contract_value_pre_creation_first_year' => $this->getContractValuePreCreationFirstYear($pestContract),
            'contract_value_pre_creation_after_first_year' => $this->getContractValuePreCreationAfterFirstYear($pestContract),
            'contract_value_second_year' => $this->getContractValuePreCreationSecondYear($pestContract),
            'contract_value_pre_creation_first_year_just_tax' => $this->getContractValuePreCreationFirstYearJustTax($pestContract),
            'monthly_contract_value_pre_creation_first_year' => $this->getMonthlyContractValuePreCreationFirstYear($pestContract),
            'contract_addendum' => $this->getContractAddendum($pestContract),
            'contract_yearly_recurring' => $this->getContractYearlyRecurring($pestContract),
            'payment_method' => ($customer->sales_profile->autopay_account ? $customer->sales_profile->autopay_account->type : ''),
            'billing_calendar_new' => $this->getBillingCalendarNew($pestContract),
            'email_payment_link' => $this->getCustomerPublicPaymentLink($customer),
            'sms_payment_link' => $this->getCustomerPublicPaymentLinkSms($customer),
            'prodefense_billing_calendar' => $this->getProDefenseBillingCalendar($pestContract),
            'prodefense_new_billing_calendar' => $this->getProDefenseNewBillingCalendar($pestContract)
        ), $customFieldsParameters);
    }

    public function getInvoiceNumbers($customer)
    {
        $data = DB::select(DB::raw('SELECT GROUP_CONCAT(i.id ORDER BY i.id ASC SEPARATOR ", ") as invoice_numbers FROM   pocomos_customers c
        JOIN pocomos_customer_sales_profiles csp on c.id = csp.customer_id
        JOIN pocomos_contracts co on csp.id = co.profile_id
        JOIN pocomos_invoices i on co.id = i.contract_id
        WHERE c.id = ' . $customer->id . '
        AND i.status IN ("Due", "Past due", "Collections", "In collections")
        AND c.status <> "Cancelled"'));

        $invoiceNumbers = $data[0]->invoice_numbers;
        $pos = strrpos($invoiceNumbers, ',');

        if ($pos !== false) {
            $invoiceNumbers = substr_replace($invoiceNumbers, ' and', $pos, 1);
        }

        return $invoiceNumbers;
    }

    public function getDateWithFormat($date = null, $pdf)
    {
        $format = $pdf ? 'd-M-Y' : 'm-d-Y';

        if ($date !== null && $date !== null) {
            return date($format, strtotime($date));
        }

        return '';
    }

    public function formatCurrency($currency)
    {
        return sprintf('$%s', number_format($currency, 2));
    }

    public function getSignature($signature = null, $pdf)
    {
        $src = $signature;
        $isFileExist = false;
        if ($signature) {
            if (file_exists(env('ASSET_URL') . '' . $signature)) {
                $src = env('ASSET_URL') . '' . $signature;
                $isFileExist = true;
            }
            if (!$pdf && $isFileExist) {
                $src = 'data:image/' . pathinfo($src, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($src));
            }
            return $src;
        }

        return '';
    }

    public function getAgreementPriceInfo($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $overview = $this->getPricingOverview($pcc);

        return $overview;
    }

    public function getPricingOverview($pest_contract_details)
    {
        $result = array();

        $result['frequency'] = $pest_contract_details->contract_details->billing_frequency;
        $result['salesTax'] = $pest_contract_details->contract_details->sales_tax;
        $result['regularInitialPrice'] = $pest_contract_details->regular_initial_price;
        $result['recurringDiscount'] = $pest_contract_details->recurring_discount;
        $result['handleDiscountTypes'] = $pest_contract_details->contract_details->discount_types;

        $pestDiscountTypes = $pest_contract_details->contract_details->discount_types;
        $discountAmount = $amountCalculated = 0;

        foreach ($pestDiscountTypes as $discountType) {
            if ($discountType && $discountType->discount && $discountType->type == "percent") {
                switch ($pest_contract_details->contract_details->billing_frequency) {
                    case 'Per service':
                        $recurringPrice = $pest_contract_details->initial_price;
                        break;
                    case 'Monthly':
                        $recurringPrice = $pest_contract_details->initial_price;
                        break;
                    case 'Initial monthly':
                        $recurringPrice = $pest_contract_details->initial_price;
                        break;
                    case 'Due at signup':
                        $recurringPrice = $pest_contract_details->initial_price;
                        break;
                    case 'Installments':
                        $recurringPrice = $pest_contract_details->initial_price;
                        break;
                    case 'Two payments':
                        $recurringPrice = $pest_contract_details->initial_price;
                        break;
                    default:
                        $recurringPrice = $pest_contract_details->recurring_price;
                        break;
                }
                $rate = $discountType->amount / 100;
                $amountCalculated = $recurringPrice * $rate;
            } else {
                $amountCalculated = $discountType->amount;
            }
            $discountAmount += $amountCalculated;
        }

        if ($pest_contract_details->contract_details->billing_frequency == "Monthly") {
            $result['initialDiscount'] = abs($discountAmount);
            $result['initialPrice']  = 0;
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
        } elseif ($pest_contract_details->contract_details->billing_frequency == "Initial monthly") {
            $result['initialDiscount'] = abs($discountAmount + $pest_contract_details->initial_discount);
            $result['initialPrice'] = $pest_contract_details->initial_price - $discountAmount;
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
            $result['recurringDiscount'] = abs($discountAmount);
        } elseif ($pest_contract_details->contract_details->billing_frequency == "Per service") {
            $result['initialDiscount'] = abs($discountAmount + $pest_contract_details->initial_discount);
            $result['initialPrice'] = $pest_contract_details->initial_price;
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
            $result['recurringDiscount'] = abs($discountAmount);
        } elseif ($pest_contract_details->contract_details->billing_frequency == "Due at signup") {
            $result['initialDiscount'] = abs($discountAmount + $pest_contract_details->initial_discount);
            $result['initialPrice'] = $pest_contract_details->initial_price;
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
            $result['recurringDiscount'] = abs($discountAmount);
        } elseif ($pest_contract_details->contract_details->billing_frequency == "Two payments") {
            $result['initialDiscount'] = abs($discountAmount + $pest_contract_details->initial_discount);
            $result['initialPrice'] = $pest_contract_details->initial_price;
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
            $result['recurringDiscount'] = abs($discountAmount);
        } elseif ($pest_contract_details->contract_details->billing_frequency == "Installments") {
            $result['initialDiscount'] = abs($discountAmount);
            $result['initialPrice'] = abs($pest_contract_details->initial_price);
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
            $result['recurringDiscount'] = abs($discountAmount);
            $result['numberOfPayments'] = $pest_contract_details->contract_details->number_of_payments;
        } else {
            $result['initialDiscount'] = abs($discountAmount);
            $result['initialPrice'] = $pest_contract_details->initial_price;
            $result['recurringPrice'] = $pest_contract_details->recurring_price;
        }

        return $result;
    }

    public function getSelectedPests($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }

        $allPests = PocomosPest::where('office_id', $office->id)->where('active', 1)->pluck('name')->toArray();

        $pests_ids = PocomosPestContractsPest::where('contract_id', $pcc->id)->get('pest_id')->toArray();
        $contractPests = PocomosPest::whereIn('id', $pests_ids)->pluck('name')->toArray();

        $special_pests_ids = PocomosPestContractsSpecialtyPest::where('contract_id', $pcc->id)->get('pest_id')->toArray();
        $contractSpecialty = PocomosPest::whereIn('id', $special_pests_ids)->pluck('name')->toArray();

        $selected = array_merge($contractPests, $contractSpecialty);

        return $selected;
    }

    public function getListOfRegularPests($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }

        $pests_ids = PocomosPestContractsPest::where('contract_id', $pcc->id)->get('pest_id')->toArray();
        $contractPests = PocomosPest::whereIn('id', $pests_ids)->pluck('name')->toArray();

        $contractPests = $this->convertArrayInStrings($contractPests);

        return $contractPests;
    }

    public function getContractValue($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $contractState = PocomosReportsContractState::where('contract_id', $pcc->id)->first();

        if (!$contractState) {
            return '';
        }

        return $contractState;
    }

    public function getYearWithFormat($date = null)
    {
        $format = 'F-Y';

        if ($date !== null && $date !== null) {
            return date($format, strtotime($date));
        }

        return '';
    }

    public function getContractLengthInMonths($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $dateEnd = new DateTime($pcc->contract_details->date_end);
        $dateStart = new DateTime($pcc->contract_details->date_start);

        $diffce = $dateStart->diff($dateEnd);

        return ($diffce->m + ($diffce->y * 12));
    }

    public function getCustomerCCLastFour($customer)
    {
        $lastFour = '';
        $account = $this->getAccountByType($customer, 'CardAccount');
        if ($account) {
            $lastFour = $account->last_four;
        }
        return $lastFour;
    }

    public function getCustomerBankLastFour($customer)
    {
        $lastFour = '';
        $account = $this->getAccountByType($customer, 'BankAccount');
        if ($account) {
            $lastFour = $account->last_four;
        }
        return $lastFour;
    }

    public function getAccountByType($customer, $type)
    {
        $sales_profile = $customer->sales_profile;

        $accounts = PocomosCustomersAccount::where('profile_id', $sales_profile->id)->get();

        $accountToBeReturned = false;

        foreach ($accounts as $account) {
            $OrkestraAccount = OrkestraAccount::where('id', ($account->account_id))->first();

            if (!($OrkestraAccount->active == 1)) {
                continue;
            }

            if ($OrkestraAccount->type === $type) {
                $accountToBeReturned = $OrkestraAccount;
                break;
            }
        }
        return $accountToBeReturned;
    }

    public function getSalespersonName($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $salesPerson = $pcc->contract_details->profile_details;
        if (!$salesPerson) {
            return '';
        }
        $profile = null;
        if ($salesPerson->sales_people) {
            $profile = $salesPerson->sales_people->office_user_details;
        }
        if (!$profile) {
            return '';
        }

        return $profile->first_name . ' ' . $profile->last_name;
    }

    public function getPriceBaseTax($price, $taxRate, $withTax = false)
    {
        if (!$price || !$taxRate) {
            return '';
        }
        $res = '';
        if ($withTax) {
            $res = $price + ($price * $taxRate);
        } else {
            $res = $price * $taxRate;
        }
        return $res;
    }

    public function getContractYearlyRecurring($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $multiplier = 1;

        switch ($pcc->service_frequency) {
            case config('constants.ANNUALLY'):
                $multiplier = 12;
                break;
            case config('constants.SEMI_ANNUALLY'):
            case config('constants.HEXA_WEEKLY'):
                $multiplier = 6;
                break;

            case config('constants.QUARTERLY'):
            case config('constants.TRI_WEEKLY'):
                $multiplier = 3;
                break;

            case config('constants.BI_MONTHLY'):
            case config('constants.BI_WEEKLY'):
                //case config('constants.TWICE_PER_MONTH')::TWICE_PER_MONTH:
                $multiplier = 2;
                break;

            case config('constants.MONTHLY'):
            case config('constants.WEEKLY'):
            default:
                $multiplier = 1;
        }

        $yearlyMultiplier =  12 / $multiplier;

        return $pcc->recurring_price * $yearlyMultiplier;
    }

    public function processPayment($invoice_id, $generalValues, $payment, $user = false, $description = null)
    {
        FacadesDB::beginTransaction();
        try {
            // dd(11);
            $payment = (object)$payment;
            $generalValues = (object)$generalValues;

            $this->updateBillingInformation($invoice_id, $payment->account_id);

            $configuration = $generalValues->office_id;

            $credentials = PocomosOfficeSetting::where('office_id', $configuration)->first();
            if (!$credentials) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to locate office configuration']));
            }

            $result = array();
            $date = new DateTime(date("Y-m-d H:i:s"));

            if ($payment->method !== "points") {
                $lastTransaction = PocomosInvoiceTransaction::where('invoice_id', $invoice_id)->pluck('transaction_id')->toArray();
                $transaction = array();
                if ($lastTransaction) {
                    foreach ($lastTransaction as $val) {
                        $end_time = $date->modify(sprintf('-%s minutes', 3));
                        $transaction = OrkestraTransaction::where('id', $val)->where('account_id', $payment->account_id)->where('date_created', '>', $end_time)->get();
                    }
                }

                if (count($transaction) > 0) {
                    throw new \Exception(__('strings.message', ['message' => 'A card can only be charged once per minute, per invoice.']));
                }
            }

            $credentials_id = $this->getCredentials($credentials, $payment->method);

            if ($payment->method == 'points') {
                $payment->amount = round($payment->amount, 2) * 100;
            }
            $accountid = OrkestraAccount::findOrFail($payment->account_id);
            // dd(11);
            $customer = PocomosCustomer::findOrFail($generalValues->customer_id);

            if ($payment->method == 'card' || $payment->method == 'ach') {

                $accountType = 'C'; //Bank checking account (ACH)
                if ($payment->method == 'card') {
                    $accountType = 'R'; //Payment Card (debit or credit)
                }

                $accountAccessory = $accountid->ach_routing_number; //Bank routing number
                if ($payment->method == 'card') {
                    $year = explode('20', $accountid->card_exp_year);
                    $year = $year[1] ?? '';
                    $accountAccessory = $accountid->card_exp_month . '' . $year; //Card expity date, month
                }

                $data = [
                    'requestType' => 'sale',
                    'amount' => $payment->amount,
                    'accountType' => $accountType,
                    'accountNumber' => $accountid->account_number,
                    'accountAccessory' => $accountAccessory,
                    'csc' =>  $accountid->card_cvv,
                    'holderType' => 'O', //Type of a payment card or bank account holder. Set value to O
                    'holderName' =>  $accountid->name,
                    'street' => $customer->contact_address->street ?? '',
                    'city' => $customer->contact_address->city ?? '',
                    'state' => $customer->contact_address->region->name ?? '',
                    'zipCode' => $customer->contact_address->postal_code ?? '',
                    'countryCode' => $customer->contact_address->region->country_detail->code ?? '',
                    'phone' => $customer->contact_address->primaryPhone->number ?? '',
                    'email' => $customer->email ?? '',
                    'transactionIndustryType' => 'RE',
                    'transactionCategoryType' => 'B', //Bill payment.
                    'transactionModeType' => 'N', //For card not present.
                ];

                // dd(11);
                $result = $this->createCharge($data, $credentials_id);

                $result = array_reduce(
                    explode('&', $result),
                    function ($carry, $kvp) {
                        list($key, $value) = explode('=', $kvp);
                        $carry[trim($key)] = trim($value);
                        return $carry;
                    },
                    []
                );
            }

            $invoice = PocomosInvoice::findOrFail($invoice_id);

            $OrkestraTransaction['parent_id'] = null;
            $OrkestraTransaction['account_id'] = $payment->account_id;
            $OrkestraTransaction['credentials_id'] = $credentials_id;
            $OrkestraTransaction['amount'] =  $payment->amount;
            $OrkestraTransaction['network'] = $payment->method;
            $OrkestraTransaction['type'] = 'Sale';
            if (count($result) && $result['responseType'] == 'exception') {
                $OrkestraTransaction['status'] = 'Error';
            } else {
                $OrkestraTransaction['status'] = 'Approved';
            }
            $OrkestraTransaction['active'] = true;
            $OrkestraTransaction['description'] = $payment->description ?? null;
            $OrkestraTransaction['referenceNumber'] = $payment->referenceNumber ?? '';
            $OrkestraTransaction = OrkestraTransaction::create($OrkestraTransaction);

            $OrkestraResult['transaction_id'] = $OrkestraTransaction->id;
            $OrkestraResult['external_id'] = '';
            $OrkestraResult['message'] = $result['responseMessage'] ?? '';
            $OrkestraResult['data'] =  serialize($result);
            if (count($result) && $result['responseType'] == 'exception') {
                $OrkestraResult['status'] = 'Error';
            } else {
                $OrkestraResult['status'] = 'Approved';
            }
            $OrkestraResult['transacted'] = true;
            $OrkestraResult['date_transacted'] =   date('Y-m-d H:i:s');
            $OrkestraResult['transactor'] = 'pocomos.zift.card';
            $OrkestraResult['active'] = true;
            $OrkestraResult = OrkestraResult::create($OrkestraResult);

            $user_transaction['invoice_id'] = $invoice_id;
            $user_transaction['transaction_id'] = $OrkestraTransaction->id;
            if ($user) {
                $user_transaction['user_id'] = $user;
            }
            if ($invoice->status == 'Past due') {
                $user_transaction['past_due'] = 1;
            }
            $user_transaction['active'] = true;
            $user_transaction['memo'] = '';
            $user_transaction['type'] = 'Invoice';
            $userTransaction = PocomosUserTransaction::create($user_transaction);

            $invoice_transaction['invoice_id'] = $invoice_id;
            $invoice_transaction['transaction_id'] = $OrkestraTransaction->id;
            $invoicetransaction = PocomosInvoiceTransaction::create($invoice_transaction);

            $input['balance'] = $invoice->balance - $payment->amount;
            $invoice->update($input);

            $this->distributeInvoicePayments($invoice);

            $status = array("Approved", "Processed", "Pending");

            if ($result && (!in_array($OrkestraTransaction->status, $status))) {
                $msg = "Some payments could not be processed, Error : " . $result['responseMessage'];
                $msg = str_replace('+', ' ', $msg);
                throw new \Exception(__('strings.message', ['message' => $msg]));
            }

            FacadesDB::commit();
        } catch (\Exception $e) {
            FacadesDB::rollback();

            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $OrkestraTransaction;
    }

    public function processPayment_salesPaymentHelper($payment, $customerSalesProfile)
    {
        $office = $customerSalesProfile->office_details;

        $configuration = PocomosOfficeSetting::where('office_id', $office->id)->first();
        if (!$configuration) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate office configuration']));
        }

        $now = new \DateTime();

        if ($payment['method'] !== 'points') {
            $end_time = $now->modify(sprintf('-%s seconds', 35));

            // dd($end_time);

            $lastTransaction = OrkestraTransaction::whereAccountId($payment['account_id'])
                ->where('date_created', '>', $end_time)
                ->orderByDesc('id')
                ->first();

            if ($lastTransaction) {
                throw new \Exception("An Account can only be charged once per minute.");
            }
        }

        $credentialId = $this->getCredentials($configuration, $payment['method']);

        // dd($credentialId);

        if ($payment['method'] == 'points') {
            $payment['transaction']->update(['amount' => round($payment['transaction']->amount), 2 * 100]);
        }

        if (empty($credentialId)) {
            throw new \Exception(__('strings.message', ['message' => 'No suitable payment transactor configured for processing ' . $payment['method'] . ' payments']));
        }

        $payment['transaction']->update(['credentials_id' => $credentialId]);


        $this->transact($payment, $credentialId);

        return $payment['transaction'];
    }

    public function transact($payment, $credentialId)
    {

        // dd($payment['account']->account_number);

        if ($payment['method'] == 'card' || $payment['method'] == 'ach') {
            // dd(11);
            $creds = OrkestraCredential::findOrFail($credentialId);

            $exceptions = unserialize($creds->credentials);

            $url = config('constants.ZIFT_SANDBOX_URL');

            $data = [
                'userName' => $exceptions['username'],
                'password' => $exceptions['password'],
                'accountId' => $exceptions['account_id'] ?? '',
                'requestType' => 'sale',
                'amount' => $payment['amount'],
                'accountType' => 'R',
                'transactionIndustryType' => 'RE',
                'accountNumber' => $payment['account']->account_number,
                'accountAccessory' => $payment['account']->ach_routing_number,
                'csc' =>  $payment['account']->card_cvv,
                //'holderType'=>'P',
                'holderName' =>  $payment['account']->name,
                'street' => $payment['customer']->contact_address->street ?? '',
                'city' => $payment['customer']->contact_address->city ?? '',
                'state' => $payment['customer']->contact_address->region->name ?? '',
                'zipCode' => $payment['customer']->contact_address->postal_code ?? '',
                'countryCode' => $payment['customer']->contact_address->region->country_detail->code ?? '',
                'phone' => $payment['customer']->contact_address->primaryPhone->number ?? '',
                'email' => $payment['customer']->email ?? '',
            ];

            // $result = $this->createCharge($data, $credentialId);

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                ),
            );
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
    
            // dd($result);
    
            return $result;
        }
    }

    /**Processes a refund */
    public function processRefund($invoice_id, $transaction_id, $user, $addAmountBack = false, $office_id)
    {
        $transaction = OrkestraTransaction::findOrFail($transaction_id);

        if ($transaction->type == 'Refund') {
            throw new \Exception(__('strings.message', ['message' => 'The transaction has already been refunded.']));
        }

        $OrkestraResult = OrkestraResult::where('transaction_id', $transaction_id)->first();
        if (!$OrkestraResult) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find transaction']));
        }

        $exceptions = unserialize($OrkestraResult->data);

        $data = [
            'requestType' => 'refund',
            'amount' => $exceptions['amount'] ?? $transaction->amount,
            'transactionId' => $exceptions['transactionId'] ?? $transaction->id,
            'accountId' => $exceptions['accountId'] ?? $transaction['account_id']
        ];

        $credentials = PocomosOfficeSetting::where('office_id', $office_id)->first();
        if (!$credentials) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate office configuration']));
        }

        $credentials_id = $this->getCredentials($credentials, $transaction->network);

        $result = $this->createRefund($data, $credentials_id);

        $result = array_reduce(
            explode('&', $result),
            function ($carry, $kvp) {
                list($key, $value) = explode('=', $kvp);
                $carry[trim($key)] = trim($value);
                return $carry;
            },
            []
        );

        if (count($result) && isset($result['failureMessage'])) {
            $msg = "There was a issue with this refund, Error : " . $result['failureMessage'];
            $msg = str_replace('+', ' ', $msg);
            throw new \Exception(__('strings.message', ['message' => $msg]));
        }

        $OrkestraTransaction['parent_id'] = $transaction_id;
        $OrkestraTransaction['account_id'] =  $transaction->account_id;
        $OrkestraTransaction['credentials_id'] = $transaction->credentials_id;
        $OrkestraTransaction['amount'] =  $transaction->amount;
        $OrkestraTransaction['network'] = $transaction->network;
        $OrkestraTransaction['type'] = 'Refund';
        if ($result['responseType'] == 'exception') {
            $OrkestraTransaction['status'] = 'Error';
        } else {
            $OrkestraTransaction['status'] = 'Approved';
        }
        $OrkestraTransaction['active'] = true;
        $OrkestraTransaction['description'] =  null;
        $OrkestraTransaction = OrkestraTransaction::create($OrkestraTransaction);

        $OrkestrResult['transaction_id'] = $OrkestraTransaction->id;
        $OrkestrResult['external_id'] = '';
        $OrkestrResult['message'] = $result['responseMessage'];
        $OrkestrResult['data'] =  serialize($result);
        if ($result['responseType'] == 'exception') {
            $OrkestrResult['status'] = 'Error';
        } else {
            $OrkestrResult['status'] = 'Approved';
        }
        $OrkestrResult['transacted'] = true;
        $OrkestrResult['date_transacted'] =   date('Y-m-d H:i:s');
        $OrkestrResult['transactor'] = 'pocomos.zift.card';
        $OrkestrResult['active'] = true;
        $OrkestrResult = OrkestraResult::create($OrkestrResult);

        $user_transaction['invoice_id'] = $invoice_id;
        $user_transaction['transaction_id'] = $OrkestraTransaction->id;
        $user_transaction['active'] = true;
        $user_transaction['memo'] = '';
        $user_transaction['type'] = 'Invoice';
        $userTransaction = PocomosUserTransaction::create($user_transaction);

        $invoice_transaction['invoice_id'] = $invoice_id;
        $invoice_transaction['transaction_id'] = $OrkestraTransaction->id;
        $invoicetransaction = PocomosInvoiceTransaction::create($invoice_transaction);

        // if ($transaction->network == 'POINTS') {
        //     $transaction->amount = round($transaction->amount, 2) * 100;
        // }

        if ($addAmountBack) {
            $amount = $transaction->amount;

            if ($transaction->network == 'Points') {
                $amount = round($amount / 100, 2);
                //$input['balance'] = $amount;
            }

            $PocomosInvoice = PocomosInvoice::findOrFail($invoice_id);
            $input['balance'] = $transaction->amount + $PocomosInvoice->balance;
            $PocomosInvoice->update($input);

            if ($amount <= 0) {
                throw new \Exception(__('strings.message', ['message' => 'Value of amount must be greater than zero.']));
            }

            $tax =  $PocomosInvoice->sales_tax;
            $preTaxAmount = $amount / (1 + $tax);

            /**If pretax amount is more than the amount_due then it means That it's a multi invoice refund (!) */

            if ($preTaxAmount > $PocomosInvoice->amount_due) {
                $preTaxAmount = $PocomosInvoice->amount_due;
            }

            $amount -= $preTaxAmount;

            if ($amount < 0) {
                $preTaxAmount = (0 - $amount);
            }

            $PocomosInvoiceItems = PocomosInvoiceItems::where('invoice_id', $invoice_id)->orderBy('id', 'desc')->first();
            $invoice_item = [];
            $invoice_item['tax_code_id'] = $PocomosInvoiceItems->tax_code_id;
            $invoice_item['invoice_id'] = $invoice_id;
            $invoice_item['description'] = 'Refund Adjustment';
            $invoice_item['price'] = $preTaxAmount;
            $invoice_item['active'] = true;
            $invoice_item['sales_tax'] =  $PocomosInvoiceItems->sales_tax;
            $invoice_item['type'] = 'Adjustment';
            $invoice_item = PocomosInvoiceItems::create($invoice_item);
        }

        $PocomosInvoice = PocomosInvoice::findOrFail($invoice_id);
        $this->distributeInvoicePayments($PocomosInvoice);

        return $result;
    }

    /**Processes a failed payment */
    public function processFailedPayment($invoice_id, $transaction_id)
    {
        $invoice = OrkestraTransaction::findOrFail($transaction_id);

        if ($invoice->type == 'Refund') {
            throw new \Exception(__('strings.message', ['message' => 'The transaction has already been refunded']));
        }

        $input['status'] = 'Cancelled';
        $invoice->update($input);

        $amount =  $invoice->amount;

        if ($invoice->network == 'Points') {
            $amount = round($amount / 100, 2);
        }

        $PocomosInvoice = PocomosInvoice::findOrFail($invoice_id);
        $tax =  $PocomosInvoice->sales_tax;
        $preTaxAmount = $amount / (1 + $tax);

        $invoice_item = [];
        $invoice_item['tax_code_id'] = $PocomosInvoice->tax_code_id;
        $invoice_item['invoice_id'] = $invoice_id;
        $invoice_item['description'] = 'Cancelled Payment Adjustment';
        $invoice_item['price'] = $preTaxAmount;
        $invoice_item['active'] = '1';
        $invoice_item['sales_tax'] =  $PocomosInvoice->sales_tax;
        $invoice_item['type'] = 'Adjustment';

        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $this->distributeInvoicePayments($PocomosInvoice);

        return $invoice;
    }

    public function getCredentials($configuration, $type)
    {
        switch ($type) {
            case "card":
                $generator = $configuration->card_credentials_id;
                break;
            case "ach":
                $generator = $configuration->ach_credentials_id;
                break;
            case "check":
                $generator = $configuration->check_credentials_id;
                break;
            case "points":
                $generator = $configuration->points_credentials_id;
                break;
            case "processed_outside":
                $generator = $configuration->external_credentials_id;
                break;
            case "cash":
                $generator = $configuration->cash_credentials_id;
                break;
            default:
                throw new \Exception(__('strings.message', ['message' => 'Invalid network type']));
        }

        if (empty($generator)) {
            throw new \Exception(__('strings.message', ['message' => 'No suitable payment transactor configured for processing payments']));
        }

        return $generator;
    }

    public function updateBillingInformation($invoiceId, $accountId)
    {
        $billingAddress = PocomosInvoice::with('contract.profile_details.customer_details.billing_address.region.country_detail')->whereId($invoiceId)->firstOrFail()->contract->profile_details->customer_details->billing_address;
        $customer = PocomosInvoice::findOrFail($invoiceId)->contract->profile_details->customer_details;

        $updateDetails = [
            'address' => $billingAddress->street . ' ' . $billingAddress->suite,
            'city' => $billingAddress->city,
            'region' => $billingAddress->region->code ?? '',
            'country' => $billingAddress->region->country_detail->code ?? '',
            'postal_code' => $billingAddress->postal_code,
        ];

        $isDependant = $this->isDependentChild($customer->id);

        if ($isDependant) {
            $isDependant = (array)$isDependant;
            $isDependant = PocomosSubCustomer::findOrFail($isDependant['id']);
            $updateDetails['name'] = $isDependant->getParent();
        } else {
            $updateDetails['name'] = ($customer->first_name ?? "") . ' ' . ($customer->last_name ?? "");
        }

        OrkestraAccount::whereId($accountId)->update($updateDetails);
    }

    /**Processes a payment */

    public function processPayments($payment, $profile)
    {
        $payment = (object)$payment;

        // $configuration = $generalValues->office_id;

        $credentials = PocomosOfficeSetting::where('office_id', $profile->office_id)->first();
        if (!$credentials) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate office configuration']));
        }

        $now =  new DateTime(date("Y-m-d H:i:s"));

        if ($payment->method !== 'points') {
            $end_time = $now->modify(sprintf('-%s seconds', 35));
            $transaction = OrkestraTransaction::where('account_id', $payment->account_id)->where('date_created', '>', $end_time)->get();

            if (count($transaction) > 0) {
                throw new \Exception(__('strings.message', ['message' => 'An Account can only be charged once per minute.']));
            }
        }

        $credentials_id = $this->getCredentials($credentials, $payment->method);

        if (empty($credentials_id)) {
            throw new \Exception(__('strings.message', ['message' => 'No suitable payment transactor configured for processing payments.']));
        }

        if ($payment->method == 'points') {
            $payment->amount = round($payment->amount, 2) * 100;
        }

        $accountid = OrkestraAccount::findOrFail($payment->account_id);
        $customer = PocomosCustomer::findOrFail($payment->customer_id);

        $data = [
            'requestType' => 'sale',
            'amount' => $payment->amount,
            'accountType' => 'R',
            'transactionIndustryType' => 'RE',
            'accountNumber' => $accountid->account_number,
            'accountAccessory' => $accountid->ach_routing_number,
            'csc' =>  $accountid->card_cvv,
            //'holderType'=>'P',
            'holderName' =>  $accountid->name,
            'street' => $customer->contact_address->street ?? '',
            'city' => $customer->contact_address->city ?? '',
            'state' => $customer->contact_address->region->name ?? '',
            'zipCode' => $customer->contact_address->postal_code ?? '',
            'countryCode' => $customer->contact_address->region->country_detail->code ?? '',
            'phone' => $customer->contact_address->primaryPhone->number ?? '',
            'email' => $customer->email ?? '',
            //'customerAccountCode' => '0000000001',
            //'transactionCode' => '0000000001',
        ];

        $result = $this->createCharge($data, $credentials_id);

        $result = array_reduce(
            explode('&', $result),
            function ($carry, $kvp) {
                list($key, $value) = explode('=', $kvp);
                $carry[trim($key)] = trim($value);
                return $carry;
            },
            []
        );

        return $result;
    }


    public function applyTransaction($profile, $transaction, $IIPayment, $user = null, $payment)
    {
        $credentials = PocomosOfficeSetting::where('office_id', $profile->office_id)->first();
        $credentials_id = $this->getCredentials($credentials, $payment['method']);

        $OrkestraTransaction['parent_id'] = null;
        $OrkestraTransaction['account_id'] = $payment['account_id'];
        $OrkestraTransaction['credentials_id'] = $credentials_id;
        $OrkestraTransaction['amount'] =  $payment['amount'];
        $OrkestraTransaction['network'] =  $payment['method'];
        $OrkestraTransaction['type'] = 'Sale';
        if (count($transaction) && isset($transaction['responseType']) && $transaction['responseType'] == 'exception') {
            $OrkestraTransaction['status'] = 'Error';
        } else {
            $OrkestraTransaction['status'] = 'Approved';
        }
        $OrkestraTransaction['active'] = true;
        $OrkestraTransaction['referenceNumber'] = $payment['amount'];
        $transaction = OrkestraTransaction::create($OrkestraTransaction);

        $transAmount = $transaction->amount;

        if ($transaction->network == 'points') {
            $transAmount = (int) round($transAmount, 2) * 100;
        }

        $processedAmount = $balanceRemaining = (int)$transAmount;

        $approved = $transaction->status == 'Approved';

        $invoices = $IIPayment->invoice;

        while ($balanceRemaining > 0) {
            $invoiceBalance = (int)(round($invoices['balance'], 2) * 100);

            if ($invoiceBalance > $balanceRemaining) {
                $invoiceBalance = $balanceRemaining;
            }

            $balanceRemaining -= $invoiceBalance;

            $it = PocomosInvoiceTransaction::whereInvoiceId($invoices['id'])->whereTransactionId($transaction->id)->first();
            if (!$it) {
                $invoice_transaction['invoice_id'] = $invoices['id'];
                $invoice_transaction['transaction_id'] = $transaction->id;
                $invoicetransaction = PocomosInvoiceTransaction::create($invoice_transaction);
            }

            if ($approved) {
                $invoices->update(['balance' => $invoices->balance - round($invoiceBalance / 100, 2)]);

                $iiPayments = PocomosInvoiceInvoicePayment::with('payment')->whereInvoiceId($invoices->id)->get();

                foreach ($iiPayments as $otherPayment) {
                    if ($otherPayment->payment_id === $IIPayment->payment_id) {
                        continue;
                    }

                    $otherPayment->payment->update(['amount_in_cents' => $otherPayment->payment->amount_in_cents - $invoiceBalance]);

                    $this->updatePaymentStatus($otherPayment->payment);
                }
            }
        }

        if ($approved && $balanceRemaining > 0) {
            $this->addCredit($profile, round($balanceRemaining / 100, 2), $user);
        }

        if ($approved) {
            $IIPayment->payment->update([
                'amount_in_cents' => $IIPayment->payment->amount_in_cents - $processedAmount
            ]);
        }

        $this->updatePaymentStatus($IIPayment->payment);
    }

    public function updatePaymentStatus($payment)
    {
        if ($payment->amount_in_cents <= 0) {
            $payment->update(['status' => 'Paid']);
        } else {
            $payment->update(['status' => 'Unpaid']);
        }
    }

    public function getFirstYearContract($contract_id)
    {
        $sql = 'SELECT SUM(i.amount_due) AS firstYearValue,i.id
                FROM pocomos_invoices AS i
                JOIN pocomos_contracts AS c ON i.contract_id = c.id
                WHERE c.id = ' . $contract_id . ' AND i.active = 1 AND i.status != "Cancelled"
                AND i.date_due <= DATE_ADD(c.date_start , INTERVAL 1 YEAR)
                AND i.date_created <= DATE_ADD(c.date_created, INTERVAL 2 DAY)
                GROUP BY c.id';

        $result = DB::select(DB::raw($sql));

        return isset($result[0]->firstYearValue) ? $result[0]->firstYearValue : 0.00;
    }

    /**update-contract-first-year-value */
    public function updateFirstYearContractValue($contract_id, $pest_contract)
    {
        $contractPrice = DB::select(DB::raw('SELECT SUM(i.amount_due) AS firstYearValue
                FROM pocomos_invoices AS i
                JOIN pocomos_contracts AS c ON i.contract_id = c.id
                WHERE c.id = "$contract_id" AND i.active = 1 AND i.status != "Cancelled"
                AND i.date_due <= DATE_ADD(c.date_start , INTERVAL 1 YEAR)
                AND i.date_created <= DATE_ADD(c.date_created, INTERVAL 2 DAY)
                GROUP BY c.id'));

        if ($contractPrice) {
            $input['first_year_contract_value'] = $contractPrice[0]->firstYearValue;
            $pest_contract->update($input);
        }

        return $pest_contract;
    }

    /**
     * Exports the given collection of Jobs.
     */

    public function getBestFit($beginSearch, $office_id, $duration, $newAddress, $limit = 3)
    {
        $office = PocomosCompanyOffice::with('contact')->findOrFail($office_id);

        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $office_id)->firstOrFail();

        if (!$duration) {
            $duration = $pestOfficeConfig->regular_duration;
        }

        //TODO account for days where the office is closed

        $endSearch = $pestOfficeConfig->best_fit_range;
        $endSearch = date('Y-m-d H:i:s', strtotime($beginSearch . '+ ' . $endSearch . ' days'));

        // dd($endSearch);
        // dd($beginSearch.$endSearch);


        $routes = PocomosRoute::where('pocomos_routes.office_id', $office_id)
            ->whereBetween('pocomos_routes.date_scheduled', [$beginSearch, $endSearch])
            ->where('pocomos_routes.active', 1)
            ->orderBy('pocomos_routes.date_scheduled')
            ->get();

        // dd($routes);

        $data = array();

        foreach ($routes as $route) {
            // return $route;
            // $route = $result;

            // $availableSlots = $this->getAvailableTimeSlots($route);

            // return \DateTime $time;

            $availableSlots = array_map(function ($slot) use ($route) {
                return $q = ['route_id' => $route->id, 'time' => $slot];
            }, $this->getAvailableTimeSlots($route));

            // return $availableSlots;

            $addresses = array();
            $segments = array();

            // dd(99);

            $originAddress = $this->getOriginAddress($route);

            $addresses[] = $originAddress;
            foreach ($availableSlots as $availableSlot) {
                $before = $this->getRequestAddress($this->getPreviousJobSlot($route, $availableSlot));

                // dd(112);
                $after = $this->getRequestAddress($this->getNextJobSlot($route, $availableSlot));

                if ($before) {
                    $addresses[] = $before;
                }

                if ($after) {
                    $addresses[] = $after;
                }

                $segments[] = ['slot' => $availableSlot, 'originAddress' => $before, 'afterAddress' => $after];
            }

            // return $segments;

            // $segments = array_unique($segments);

            // return 11;

            ob_start();
            $this->seedDistancesBetween(array_unique(array_merge($addresses, array($newAddress, $office->contact))));

            // dd(11);

            foreach ($segments as $segment) {
                $timeTo = isset($this->getDistanceBetween($segment['originAddress'], $newAddress)->duration) ?? null;
                $timeFrom = $original = 0;

                if ($segment['afterAddress']) {
                    $timeFrom = $this->getDistanceBetween($newAddress, $segment['afterAddress'])->duration;
                    $original = $segment['afterAddress'] ? $this->getDistanceBetween($segment['originAddress'], $segment['afterAddress'])->duration : 0;
                }

                if ($timeTo == PHP_INT_MAX || $timeFrom == PHP_INT_MAX || $original == PHP_INT_MAX) {
                    continue; //skip results with unknown distances
                }

                $key = (int)ceil($timeTo + $timeFrom - $original);
                if (!isset($data[$key])) {
                    // return 11;
                    $data[$key] = array();
                }

                $data[$key][] = $segment;
            }
            ob_end_clean();
        }

        // ksort($data);

        $result = [];

        // return $data;

        foreach ($data as $delta => $segments) {
            $q = 0;
            foreach ($segments as $segment) {
                if ($limit <= 0) {
                    break 2;
                }
                $slot = $segment['slot'];
                // dd($slot);
                $result[] = $slot;
                $result[$q]['delta'] = $delta;
                $limit--;

                $q++;
            }
        }
        // return 11;
        return $result;
    }

    public function getDistanceBetween($addressFrom, $addressTo)
    {
        return $addressFrom;
        return $this->get($addressFrom, $addressTo);
        // dd($addressFrom);
    }

    public function seedDistancesBetween($addresses, $limit = 50)
    {
        // dd(88);
        $groups = array();

        do {
            $groups[] = array_splice($addresses, 0, (count($addresses) < $limit ? count($addresses) : $limit));
        } while (count($addresses) > 0);

        foreach ($groups as $group) {
            $this->seed($group, $groups);
        }
    }

    private function seed($addressGroup, $groups)
    {
        foreach ($groups as $group) {
            if ($this->groupsAreKnown($addressGroup, $group)) {
                continue;
            }

            // $this->seedDistancesBetween($addressGroup, $group);
        }
    }

    private function groupsAreKnown($addressGroup, $otherGroup)
    {
        foreach ($addressGroup as $address) {
            foreach ($otherGroup as $otherAddress) {
                if (!($address !== $otherAddress)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getOriginAddress($route)
    {
        $tech = $route->technician_detail;
        if ($tech && $tech->routing_address) {
            // dd(88);
            return $tech->routing_address;
        }

        $office = $route->office_detail;
        if ($office->routing_address) {
            return $office->routing_address;
        }

        return $office->contact;
        // return 88;
    }

    public function getRequestAddress($slot = null)
    {
        if ($slot) {
            $slotJob = PocomosJob::whereSlotId($slot['id'])->first();

            if (!$slot || !$slotJob) {
                return null;
            }

            return $slotJob->contract->contract_details->profile_details->customer->contact_address;
        }
    }

    public function getPreviousJobSlot($route, $slot)
    {
        $prevSlot = $this->getSlotBefore($slot, $route);
        // dd($prevSlot);

        if ($prevSlot) {

            $slotJob = PocomosJob::whereSlotId($prevSlot['id'])->first();

            if (!$prevSlot || ($prevSlot['type'] == 'Regular' && $slotJob)) {
                return $prevSlot;
            }
            // dd(1);
        }

        // return $this->getPreviousJobSlot($route, $prevSlot);
    }

    public function getNextJobSlot($route, $slot)
    {
        $nextSlot = $this->getSlotAfter($slot, $route);

        if (!$nextSlot || ($nextSlot->type == 'Regular' && $nextSlot->job_detail)) {
            return $nextSlot;
        }

        return $this->getNextJobSlot($route, $nextSlot);
    }

    public function getSlotAfter($slot, $route)
    {
        if (isset($slot->route_id) && $slot->route_id !== $route->id) {
            throw new \Exception(__('strings.message', ['message' => 'The slot does not belong to this route']));
        }

        foreach ($route->slots as $routeSlot) {
            if ($routeSlot->time_begin > $slot) {
                return $routeSlot;
            }
        }

        return null;
    }

    public function getSlotBefore($slot, $route)
    {
        if (isset($slot->route_id) && $slot->route_id !== $route->id) {
            throw new \Exception(__('strings.message', ['message' => 'The slot does not belong to this route']));
        }

        // $slots = $route->slots->toArray();
        // dd($slot);
        foreach (array_reverse($route->slots->toArray()) as $routeSlot) {
            if ($routeSlot['time_begin'] < $slot) {
                return $routeSlot;
            }
        }

        // dd(11);

        return null;
    }

    /**Queue customer for mission export */
    public function queueForExport($office, $customer, $contract)
    {
        $missionConfiguration = PocomosMissionConfig::where('office_id', $office)->where('active', 1)->where('enabled', 1)->first();

        if (!$missionConfiguration) {
            return $this->sendResponse(false, 'Mission Export is not enabled for this office.');
        }

        $exportContract = PocomosMissionExportContract::where('office_id', $office)->where('customer_id', $customer)->where('pest_contract_id', $contract->id)->first();

        if ($exportContract) {
            return $this->sendResponse(false, 'Mission Export is not enabled for this office.');
        }

        $exportContract = [];
        $exportContract['customer_id'] = $customer;
        $exportContract['pest_contract_id'] = $contract->id;
        $exportContract['office_id'] =  $office;
        $exportContract['test_env'] = $missionConfiguration->test_env;
        $exportContract['status'] = 'Success';

        $exportContract = PocomosMissionExportContract::create($exportContract);

        return $exportContract;
    }

    /** Queue customer for pestpac export */
    public function queueForPPExport($office, $customer, $contract)
    {
        $ppExportCust = PocomosPestpacExportCustomer::where('office_id', $office)->where('customer_id', $customer)->where('pest_contract_id', $contract->id)->first();

        if ($ppExportCust) {
            return $this->sendResponse(false, 'Pestpac Export is not enabled for this office.');
        }

        $ppExportCust = [];
        $ppExportCust['customer_id'] = $customer;
        $ppExportCust['pest_contract_id'] = $contract->id;
        $ppExportCust['office_id'] =  $office;
        $ppExportCust = PocomosPestpacExportCustomer::create($ppExportCust);

        return $ppExportCust;
    }

    /**
     * Resend emails using ResendEmailJob
     *
     * @param PocomosCustomerSalesProfile $profile
     * @param PocomosCompanyOfficeUser $officeUser
     * @param array $formData
     */
    public function resendEmails($profile, $officeUser, $formData, $customer_id = null)
    {
        $args = array_merge(array(
            'cspId' => $profile->id,
            'officeUserId' => $officeUser->id,
            'alertReceivingUsers' => array($officeUser->id),
            'customer_id' => $customer_id
        ), $formData);
        ResendEmailJob::dispatch($args);
    }

    /**
     * Returns the email verification hash for a given customer
     *
     * @param PocomosCustomer $customer
     *
     * @return string
     */
    public function getEmailVerificationHash($customer)
    {
        return md5($customer->gid . $customer->emailemail);
    }

    /**
     * Gets the path where contract PDFs are stored
     *
     *
     * @return string
     */
    public function getContractPath($contract)
    {
        return $this->ensureDirectoryExists(config('constants.INTERNAL_PATH') . DIRECTORY_SEPARATOR . 'contracts');
    }

    /**
     * @return string
     */
    public function getContractFilename($contract)
    {
        if (!$contract->id) {
            return $this->getContractPath($contract) . DIRECTORY_SEPARATOR . $this->generateFilename('pdf');
        }

        return $this->getContractPath($contract) . DIRECTORY_SEPARATOR . $contract->id . '.pdf';
    }

    /**
     * Attempts to create the given directory, throwing an exception if unable to
     *
     * @param string $path
     *
     * @return string $path
     */
    public function ensureDirectoryExists($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \Exception(sprintf('Could not create directory "%s"', $path));
            }
        }

        return $path;
    }

    /**
     * @param $extension
     * @return string
     */
    public function generateFilename($extension)
    {
        return md5(uniqid(uniqid('', true), true)) . '.' . $extension;
    }

    public function agreementGenerator($parameters = array(), $is_html = false)
    {
        $agreementTemplate = $this->renderDynamicTemplate($parameters['agreement']['agreement_body'], null, $parameters['customer'], $parameters['pestContract'], null, true);

        if ($is_html) {
            return $agreementTemplate;
        }
        $rendered = view('emails.dynamic_email_render', ['agreement_body' => $agreementTemplate])->render();

        return $rendered;
    }

    //findCustomerByIdAndOffice
    public function findCustomerByIdAndOffice($customer_id, $office_id)
    {
        $res = DB::select(DB::raw("SELECT pc.*
        FROM pocomos_customers AS pc
        JOIN pocomos_customer_sales_profiles AS csp ON pc.id = csp.customer_id
        WHERE pc.id = '$customer_id' AND csp.office_id = '$office_id' AND pc.active = 1"));

        return $res[0] ?? array();
    }

    public function findContractByCustomer($contractid, $customer_id)
    {
        $res = DB::select(DB::raw("SELECT ppc.*
        FROM pocomos_pest_contracts AS ppc
        JOIN pocomos_contracts AS pc ON ppc.contract_id = pc.id
        JOIN pocomos_customer_sales_profiles AS pcsp ON pc.profile_id = pcsp.id
        WHERE pc.id = $contractid AND pcsp.customer_id = $customer_id"));

        return $res[0] ?? array();
    }

    public function findAgreementByIdAndOffice($pest_agreement_id, $office_id)
    {
        $res = DB::select(DB::raw("SELECT ppa.*
        FROM pocomos_pest_agreements AS ppa
        JOIN pocomos_agreements AS pa ON ppa.agreement_id = pa.id
        WHERE ppa.id = '$pest_agreement_id' AND pa.office_id = '$office_id' AND ppa.active = 1"));

        if (count($res)) {
            $res = PocomosPestAgreement::findOrFail($res[0]->id);
        } else {
            $res = array();
        }
        return $res;
    }

    /**
     * @param $offices
     * @param $user
     * @param $inputs
     * @param int $start
     * @param null $limit
     * @return array
     */
    public function getSearchResults($offices, $user, $data, $start = 0, $limit = null, $search)
    {
        $office_ids = $this->convertArrayInStrings($offices);
        $select_query = '';
        $join_query = '';
        $where_query = '';
        $limit_query = '';
        $like_query = '';
        $group_by_query = ' GROUP BY pc.profile_id ';

        $select_query = "SELECT pcs.id, pcs.account_type , pcs.first_name, pcs.last_name, ppn.number as 'phone_number',
         pcs.email, pcs.email, pcad.postal_code as 'current_postal', pcs.status as 'status',
         pcs.date_created as 'date_signed_up', pcsd.last_service_date, pcsd.next_service_date ,
         ppc.id as pest_cont_id, pcad.street, pcad.suite, pcad.city, pcad.postal_code,
         pcas.code, pcas.name as region_name
         FROM pocomos_pest_contracts as ppc";

        $join_query = " LEFT JOIN pocomos_contracts as pc ON ppc.contract_id = pc.id
        LEFT JOIN pocomos_jobs as pj ON pc.id = pj.contract_id
        LEFT JOIN pocomos_agreements as pa ON pc.agreement_id = pa.id
        LEFT JOIN pocomos_customer_sales_profiles as pcsp ON pc.profile_id = pcsp.id
        LEFT JOIN pocomos_customers as pcs ON pcsp.customer_id = pcs.id
        LEFT JOIN pocomos_addresses as pcad ON pcs.contact_address_id = pcad.id
        LEFT JOIN pocomos_addresses as pbad ON pcs.billing_address_id = pbad.id
        LEFT JOIN orkestra_accounts as oa ON pcsp.external_account_id = oa.id
        LEFT JOIN pocomos_invoices as pid ON pj.invoice_id = pid.id
        LEFT JOIN orkestra_countries_regions as pcas ON pcad.region_id = pcas.id
        LEFT JOIN orkestra_countries_regions as pbas ON pbad.region_id = pbas.id
        LEFT JOIN pocomos_sales_status AS pss ON pcs.status = pss.id
        LEFT JOIN pocomos_customer_state AS pcsd ON pcs.id = pcsd.customer_id
        LEFT JOIN pocomos_phone_numbers AS ppn ON pcad.phone_id = ppn.id
        LEFT JOIN pocomos_pest_contracts_tags AS ppct ON ppc.id = ppct.contract_id
        ";

        $where_query = " WHERE pcsp.office_id IN ($office_ids)";

        //CONSTANTS BASE MANAGE IS TEMPRORY BECAUSE NOW NOT IMPLETEMENTED LOGIN WILL UPDATE IN FEATURE ONCE LOGIN WILL DONE
        if (config('constants.ROLE_TECH_RESTRICTED')) {
            $join_query .= " LEFT JOIN pocomos_technicians as pt ON ppc.technician_id = pt.id JOIN pocomos_company_office_users as pcou ON pt.user_id = pcou.id JOIN orkestra_users as ou ON pcou.user_id = ou.id";

            if ($data['user_id']) {
                $where_query .= " AND ou.id = " . $data['user_id'] . "";
            }
            $data['preferredTech'] = null;
        }

        if ($limit) {
            $limit_query .= ' LIMIT ' . $limit;
        }

        if ($start >= 0) {
            $limit_query .= ' OFFSET ' . $start;
        }

        $contractStatus = array(config('constants.ACTIVE'), config('constants.COMPLETE'));
        if (isset($data['include_cancelled_contracts']) && $data['include_cancelled_contracts']) {
            $contractStatus[] = config('constants.CANCELLED');
        }

        // return $contractStatus;

        $val = $this->convertArrayInStrings($contractStatus);
        $where_query .= " AND (pc.status IN ($val))";

        if (isset($data['contract_status'])) {
            // return 77;
            $contract_status = $data['contract_status'];
            $where_query .= " AND pc.signed = $contract_status";
        }
        if (isset($data['external_account_id']) && $data['external_account_id']) {
            $external_account_id = $data['external_account_id'];
            $where_query .= " AND pcs.external_account_id = $external_account_id";
        }
        if (isset($data['first_name']) && $data['first_name']) {
            $first_name = $data['first_name'];
            $where_query .= " AND pcs.first_name LIKE '%$first_name%'";
        }
        if (isset($data['last_name']) && $data['last_name']) {
            $last_name = $data['last_name'];
            $where_query .= " AND pcs.last_name LIKE '%$last_name%'";
        }
        if (isset($data['company_name']) && $data['company_name']) {
            $company_name = $data['company_name'];
            $where_query .= ' AND pcs.company_name LIKE "%' . $company_name . '%"';
        }
        if (isset($data['account_type']) && $data['account_type']) {
            $val = $this->convertArrayInStrings($data['account_type']);
            $where_query .= " AND pcs.account_type IN ($val)";
        }
        if (isset($data['phone']) && $data['phone']) {
            $val = preg_replace('/[^0-9]/', '', $data['phone']);
            $where_query .= " AND ppn.number LIKE '%$val%'";
        }
        if (isset($data['email_address']) && $data['email_address']) {
            $email_address = $data['email_address'];
            $where_query .= " AND pcs.email LIKE '%$email_address%' OR pcs.secondary_emails LIKE '%$email_address%'";
        }
        if (isset($data['street_address']) && $data['street_address']) {
            $street_address = $data['street_address'];
            $where_query .= " AND pcad.street LIKE '%$street_address%' OR pbad.street LIKE '%$street_address%'";
        }
        if (isset($data['city']) && $data['city']) {
            $city = $data['city'];
            $where_query .= " AND pcad.city LIKE '%$city%' OR pbad.city LIKE '%$city%'";
        }

        if (isset($data['state']) && $data['state']) {
            $state = $data['state'];
            // dd($state);
            $where_query .= " AND pcas.id = $state OR pbas.id = $state";
        }

        if (isset($data['zip']) && $data['zip']) {
            $zip = $data['zip'];
            $where_query .= " AND pcad.postal_code LIKE '%$zip%' OR pbad.postal_code LIKE '%$zip%'";
        }

        if (isset($data['customer_status']) && $data['customer_status']) {
            $val = $this->convertArrayInStrings($data['customer_status']);
            $where_query .= " AND pcs.status IN ($val)";
        }

        if (isset($data['last_modified'])) {
            $val = $this->convertArrayInStrings($data['last_modified']);
            $where_query .= " AND pcs.modified_by_id IN ($val)";
        }

        if (isset($data['signup_date_start']) && $data['signup_date_start']) {
            $start = $data['signup_date_start'];
            $where_query .= " AND pc.date_created >= '$start' ";
        }

        if (isset($data['signup_date_end']) && $data['signup_date_end']) {
            $end = $data['signup_date_end'];
            // $end = new DateTime($data['signup_date_end']);
            // $end = $end->modify('+ 23 hours, 59 minutes, 59 seconds')->format('Y-m-d H:i:s');
            $where_query .= " AND pc.date_created <= '$end' ";
        }
        if (isset($data['sales_person']) && count($data['sales_person'])) {
            $val = $this->convertArrayInStrings($data['sales_person']);
            $where_query .= " AND pc.salesperson_id IN ($val)";
        }
        if (isset($data['agreement']) && count($data['agreement'])) {
            $val = $this->convertArrayInStrings($data['agreement']);
            $where_query .= " AND pa.id IN ($val)";
        }
        if (isset($data['service_type']) && count($data['service_type'])) {
            $val = $this->convertArrayInStrings($data['service_type']);
            $where_query .= " AND ppc.service_type_id IN ($val)";
        }

        if (isset($data['no_preferred_week_day']) && $data['no_preferred_week_day']) {
            $where_query .= " AND ppc.week_of_the_month IS NULL OR ppc.week_of_the_month = ''";
            $where_query .= " AND ppc.day_of_the_week IS NULL OR ppc.day_of_the_week = ''";
        } else {
            if (isset($data['recurring_week']) && $data['recurring_week']) {
                $recurring_week = $data['recurring_week'];
                $where_query .= " AND ppc.week_of_the_month = '$recurring_week'";
            }
            if (isset($data['recurring_day']) && $data['recurring_day']) {
                $recurring_day = $data['recurring_day'];
                $where_query .= " AND ppc.day_of_the_week = '$recurring_day'";
            }
        }

        if (isset($data['initial_fees']) && $data['initial_fees'] > 0) {
            $initial_fees = $data['initial_fees'];
            $where_query .= " AND ppc.initial_price = $initial_fees";
        }
        if (isset($data['recurring_fees']) && $data['recurring_fees'] > 0) {
            $recurring_fees = $data['recurring_fees'];
            $where_query .= " AND ppc.recurring_price = $recurring_fees";
        }

        if (isset($data['service_frequency']) && count($data['service_frequency'])) {
            $val = $this->convertArrayInStrings($data['service_frequency']);
            $where_query .= " AND ppc.service_frequency IN ($val)";
        }
        if (isset($data['billing_status']) && count($data['billing_status'])) {
            $val = $this->convertArrayInStrings($data['billing_status']);
            $where_query .= " AND pid.status IN ($val)";
        }

        if (isset($data['invoice_id']) && $data['invoice_id']) {
            $invoice_id = $data['invoice_id'];
            $where_query .= " AND pid.id = $invoice_id ";
        }
        if (isset($data['autopay']) && $data['autopay'] !== null) {
            $autopay = $data['autopay'];
            $where_query .= " AND pcsp.autopay = $autopay ";
        }
        if (isset($data['autorenew']) && $data['autorenew'] !== null) {
            $auto_renew = $data['autorenew'];
            $where_query .= " AND pc.auto_renew = $auto_renew ";
        }
        if (isset($data['service_start']) && $data['service_start']) {
            $service_start = $data['service_start'];
            $where_query .= " AND pj.date_scheduled >= '$service_start' ";
        }
        if (isset($data['service_end']) && $data['service_end']) {
            $service_end = $data['service_end'];
            $where_query .= " AND pj.date_scheduled <= '$service_end' ";
        }
        if ((isset($data['service_start']) && $data['service_start']) || (isset($data['service_end']) && $data['service_end'])) {
            $job_status = array(config('constants.CANCELLED'));
            $val = $this->convertArrayInStrings($job_status);
            $where_query .= " AND pj.status NOT IN ($val)";
        }

        if (isset($data['initial_service_end']) && $data['initial_service_end']) {
            $initial_status = config('constants.INITIAL');
            $initial_service_end = $data['initial_service_end'];
            $where_query .= " AND pj.date_scheduled <= $initial_service_end  AND pj.type = '$initial_status'";
        }
        if (isset($data['technician']) && $data['technician']) {
            $technician = $data['technician'];
            $where_query .= " AND pj.technician_id = $technician";
        }

        if (isset($data['preferred_tech']) && $data['preferred_tech']) {
            $preferred_tech = $data['preferred_tech'];

            if ($preferred_tech === 'unassigned') {
                $where_query .= " AND ppc.technician_id IS NULL";
            } else {
                // return $preferred_tech;
                $where_query .= " AND ppc.technician_id = $preferred_tech";
            }
        }

        if (isset($data['job_status']) && $data['job_status']) {
            $val = $data['job_status'];
            $where_query .= " AND pj.status = '$val' ";
        }

        if (isset($data['job_type']) && $data['job_type']) {
            $val = $this->convertArrayInStrings($data['job_type']);
            $where_query .= " AND pj.type IN ($val)";
        }

        if (isset($data['invoice_status']) && $data['invoice_status']) {
            $val = $this->convertArrayInStrings($data['invoice_status']);
            $where_query .= " AND pid.status IN ($val)";
        }

        if (isset($data['sales_status']) && count($data['sales_status'])) {
            $sales_status = $data['sales_status'];
            $sales_status_details = PocomosSalesStatus::whereIn('id', $sales_status)->get();

            foreach ($sales_status_details as $salesStatus) {
                if ($salesStatus->default_status) {
                    $where_query .= " AND pc.sales_status_id is null";
                    break;
                }
            }
            $val = $this->convertArrayInStrings($sales_status);
            $where_query .= " AND pc.sales_status_id IN ($val)";
        }

        if (isset($data['found_by_type']) && count($data['found_by_type'])) {
            $val = $this->convertArrayInStrings($data['found_by_type']);
            $where_query .= " AND pc.found_by_type_id IN ($val)";
        }

        if (isset($data['billing_frequency']) && $data['billing_frequency']) {
            $billing_frequency = $data['billing_frequency'];
            $where_query .= " AND pc.billing_frequency = '$billing_frequency'";
        }

        if (isset($data['custom_fields']) && count($data['custom_fields'])) {
            // return 11;
            foreach ($data['custom_fields'] as $index => $customFormField) {
                // return $customFormField;
                $custom_field = PocomosCustomField::where('custom_field_configuration_id', $index)->first();

                if (!$custom_field) {
                    continue;
                }

                $label = PocomosCustomFieldConfiguration::where('id', $custom_field->custom_field_configuration_id)->first()->label;
                $alias = 'cfc' . $index;
                $fromAlias = 'cf' . $index;

                $join_query .= " LEFT JOIN pocomos_custom_fields as $fromAlias ON ppc.id = $fromAlias.pest_control_contract_id
                LEFT JOIN pocomos_custom_field_configuration as $alias ON $fromAlias.custom_field_configuration_id = $alias.id";
                $where_query .= " AND $fromAlias.pest_control_contract_id = ppc.id AND $alias.label = '$label' AND $fromAlias.value LIKE '%$customFormField%'";
            }
        }

        if (isset($data['tags'])) {
            $tags = $data['tags'];
            if ($tags) {
                if (isset($data['is_tag_checked']) && $data['is_tag_checked']) {
                    $tags = $this->convertArrayInStrings($tags);

                    $where_query .= " AND ppct.tag_id IN ($tags)";
                } else {
                    // dd(11);
                    // $officeIds = [$office_id];
                    $officeIds = $office_ids;
                    // dd($officeIds);
                    $excludeIds = $this->findContractsByPest($officeIds, 0, $tags);

                    if (count($excludeIds)) {
                        $val = $this->convertArrayInStrings($excludeIds);
                        $where_query .= " AND ppc.id NOT IN ($val)";
                    }
                }
            }
        }

        if (isset($data['pest']) && count($data['pest'])) {
            $val = $this->convertArrayInStrings($data['pest']);
            $join_query .= " LEFT JOIN pocomos_pest_contracts_pests as ppcp ON ppc.id = ppcp.contract_id ";
            $where_query .= " AND ppcp.pest_id IN ($val)";
        }

        if (isset($data['specialty_pest']) && count($data['specialty_pest'])) {
            $val = $this->convertArrayInStrings($data['specialty_pest']);
            $join_query .= " LEFT JOIN pocomos_pest_contracts_pests as ppcs ON ppc.id = ppcs.contract_id ";
            $where_query .= " AND ppcs.pest_id IN ($val)";
        }

        if (isset($data['county']) && count($data['county'])) {
            $val = $this->convertArrayInStrings($data['county']);
            $where_query .= " AND ppc.county_id IN ($val)";
        }

        if (isset($data['tax_codes']) && count($data['tax_codes'])) {
            if (array_key_exists('tax_codes', $data) && count($data['tax_codes'])) {
                $val = $this->convertArrayInStrings($data['tax_codes']);
                $where_query .= " AND pc.tax_code_id IN ($val)";
            }
        }
        if ($search) {
            $search = $search;

            $like_query .= " AND (pcs.first_name LIKE '%$search%' OR pcs.last_name LIKE '%$search%' OR ppn.number LIKE '%$search%' OR pcs.email LIKE '%$search%' OR pcad.postal_code LIKE '%$search%' OR pss.name LIKE '%$search%' OR pcs.date_created LIKE '%$search%' OR pcsd.last_service_date LIKE '%$search%' OR pcsd.next_service_date LIKE '%$search%') ";
        }
        $countRes = DB::select(DB::raw($select_query . '' . $join_query . '' . $where_query . '' . $like_query . '' . $group_by_query));
        // dd(11);
        $customerIds = array_map(function ($item) {
            return $item->id;
        }, $countRes);

        $count = count($countRes);

        $merged_query = $select_query . '' . $join_query . '' . $where_query . '' . $like_query . '' . $group_by_query . '' . $limit_query;

        $res = DB::select(DB::raw($merged_query));

        return array('res' => $res, 'count' => $count, 'customer_ids' => $customerIds);
    }

    public function findContractsByPest($officeIds, $pestType = false, $tags = false)
    {
        $join_query = "SELECT pco.id
        FROM pocomos_pest_contracts as pco
        JOIN pocomos_contracts as sco ON pco.contract_id = sco.id
        JOIN pocomos_customer_sales_profiles as p ON sco.profile_id = p.id";

        $where_query = " WHERE p.office_id IN ($officeIds)";

        if ($pestType) {
            $join_query .= " JOIN pocomos_pest_contracts_pests as ps ON pco.id = sco.contract_id ";
            $where_query .= " AND ps.type = $pestType";
        }

        if ($tags) {
            $val = $this->convertArrayInStrings($tags);
            // dd($val);
            $join_query .= " JOIN pocomos_pest_contracts_tags as t ON pco.id = t.contract_id";
            $where_query .= " AND t.tag_id IN ($val)";
        }
        $merged_query = $join_query . '' . $where_query;
        $update_data = DB::select(DB::raw($merged_query));

        $ids = array();
        foreach ($update_data as $val) {
            $ids[] = $val->id;
        }
        return $ids;
    }

    public function convertArrayInStrings($val = array())
    {
        return "'" . implode("', '", $val) . "'";
    }

    public function findOneBySalespersonMembership($office_user_id)
    {
        $sales_people = PocomosSalesPeople::whereUserId($office_user_id)->firstOrFail();

        $membership = DB::select(DB::raw("SELECT pm.*
        FROM pocomos_salespeople as sp
        JOIN pocomos_memberships as pm ON sp.id = pm.salesperson_id
        WHERE pm.salesperson_id = $sales_people->id"));

        return $membership;
    }

    /**
     * @param PocomosSalesPeople $user
     * @param PocomosCompanyOffice $office
     * @param PocomosTeam $team
     * @return array
     * @throws \Exception
     */
    public function getUserAssignedSalesAreas($user, $office, $team = null)
    {
        $office_user_id = $user->office_user_details->user_id;
        $team_id = $team ? $team->id : null;

        $sql = 'SELECT psa.area_borders as area_borders, psa.color as color, psa.blocked as blocked, psa.id as id
                FROM pocomos_sales_area psa
                LEFT JOIN pocomos_sales_area_pivot_manager psapm ON psa.id = psapm.sales_area_id
                LEFT JOIN pocomos_sales_area_pivot_salesperson psaps ON psa.id = psaps.sales_area_id
                LEFT JOIN pocomos_sales_area_pivot_teams psapt ON psa.id = psapt.sales_area_id
                WHERE (psapm.office_user_id = ' . $office_user_id . ' OR psaps.salesperson_id = ' . $user->id;

        if ($team_id) {
            $sql .= ' OR psapt.team_id = ' . $team_id . ' ';
        }
        // OR
        // $sql .= ' psa.blocked = ' . true . ')
        // AND (psa.active = ' . true . ' AND psa.office_id = ' . $office->id . ' AND psa.enabled = ' . true . ')
        // GROUP BY psa.id';

        $sql .= ' )
        AND psa.office_id = ' . $office->id . '
        GROUP BY psa.id';

        $res = DB::select(DB::raw($sql));
        return $res;
    }

    /**
     * @param PocomosCompanyOfficeUser $officeUser
     * @param PocomosSalesArea  $salesArea
     * @return bool
     */
    public function canSalesAreaEdit($office_user, $sales_area)
    {
        $sales_area_manageres = PocomosSalesAreaPivotManager::where('sales_area_id', $sales_area->id)->where('office_user_id', $office_user->id)->get();

        //CONSTANTS BASE MANAGE IS TEMPRORY BECAUSE NOW NOT IMPLETEMENTED LOGIN WILL UPDATE IN FEATURE ONCE LOGIN WILL DONE
        if (count($sales_area_manageres) || config('constants.ROLE_OWNER')) {
            return true;
        }
        return false;
    }


    /**We don't want to reculculate invoices with payments, because that's gonna introduce a whole slew of issues */
    public function canBeRecalculated($invoice_id, $invoice_data)
    {
        $item = PocomosInvoiceItems::where('invoice_id', $invoice_id)->get();

        foreach ($item as $val) {
            if ($val->type == 'Adjustment') {
                return false;
            }
        }

        $payments = PocomosInvoiceInvoicePayment::where('invoice_id', $invoice_id)->get();

        foreach ($payments as $payment) {
            $payment_invoice = PocomosInvoicePayment::findOrFail($payment->payment_id);

            if ($payment_invoice->status == 'Paid') {
                return false;
            }
        }

        return true;
    }

    /**update Invoice Tax */
    public function updateInvoiceTax($invoice_id, $contract, $invoice_data, $addLineItem =  true)
    {
        $taxCode = PocomosTaxCode::findOrFail($contract->tax_code_id);

        $inv['tax_code_id'] = $taxCode->id;
        $inv['sales_tax'] = $taxCode->tax_rate;
        $totalPrice  = 0.00;
        $totalCreditAmount = 0.00;

        $invoicetransaction = PocomosInvoiceTransaction::where('invoice_id', $invoice_id)->get();
        $count = 0;

        foreach ($invoicetransaction as $transaction) {
            $transaction = OrkestraTransaction::findOrFail($transaction->transaction_id);

            $status = $transaction->status;

            if ($status == 'Error' || $status == 'Declined') {
                continue;
            } elseif ($status == 'Approved') {
                $count++;
            }
        }

        if ($count > 0) {
            return $this->sendResponse(false, 'Can not recalculate due to payments on the invoice.');
        }

        $item = PocomosInvoiceItems::where('invoice_id', $invoice_id)->get();

        foreach ($item as $val) {
            if ($val->type == 'Adjustment') {
                continue;
            }

            $this->updateInvoiceItem($val, $val->price);

            if ($val->type == 'Credit') {
                $totalCreditAmount += $val->price;
            } else {
                $totalPrice +=  $val->price;
            }
        }

        $inv['amount_due'] = $invoice_data->amount_due;
        $inv['status'] = $invoice_data->status;
        $inv['active'] = $invoice_data->active;

        $difference = $totalPrice - $invoice_data->amount_due;
        if ($addLineItem === true && abs($difference) > 0.001) {
            $this->addItem('Price adjustment', $difference, true, $invoice_data);
        } else {
            $inv['amount_due'] = (float)$totalPrice;
        }

        $inv['balance'] = ($totalPrice * (1 + $taxCode->tax_rate)) - abs($totalCreditAmount);
        $inv['date_due'] =  $invoice_data->date_due;

        $PocomosInvoice = PocomosInvoice::create($inv);
        return $PocomosInvoice;
    }

    public function updateInvoiceTaxNew($invoice, $addLineItem =  true)
    {
        $newTaxCode = $invoice->contract->tax_details;
        $invoice->tax_code_id = $newTaxCode->id;
        $invoice->sales_tax = $newTaxCode->tax_rate;
        $totalPrice  = 0.00;
        $totalCreditAmount = 0.00;

        $transactions = $invoice->transactions_details;
        $count = 0;

        foreach ($transactions as $transaction) {
            // $transaction = OrkestraTransaction::findOrFail($transaction->transaction_id);

            $status = $transaction->transactions->status;

            if ($status == 'Error' || $status == 'Declined') {
                continue;
            } elseif ($status == 'Approved') {
                $count++;
            }
        }

        if ($count > 0) {
            return $this->sendResponse(false, 'Can not recalculate due to payments on the invoice.');
        }

        // $item = PocomosInvoiceItems::where('invoice_id', $invoice_id)->get();

        $items = $invoice->invoice_items;

        foreach ($items as $item) {
            if ($item->type == 'Adjustment') {
                continue;
            }

            $this->updateInvoiceItem($item, $item->price);

            if ($item->type == 'Credit') {
                $totalCreditAmount += $item->price;
            } else {
                $totalPrice +=  $item->price;
            }
        }

        // setAmountDue
        $difference = $totalPrice - $invoice->amount_due;

        if ($addLineItem === true && abs($difference) > 0.001) {
            $this->addItem('Price adjustment', $difference, true, $invoice);
        } else {
            $inv['amount_due'] = (float)$totalPrice;
            $invoice->amount_due = $newTaxCode->id;
        }

        $invoice->balance = $totalPrice * (1 + $newTaxCode->tax_rate) - abs($totalCreditAmount);

        $invoice->save();

        // return $PocomosInvoice;
    }


    public function applySingleDiscount($discountType, $newDescription)
    {
        $discountAmount = 0;
        $result = $dAmount = $dType = array();
        $contract = $discountType->contract->pest_contract_details;
        $discountAmount = $discountType->amount;
        $type = $discountType->type;

        // $dType['amount'] = $discountAmount;
        // $dType['type'] = $type;
        // $dType['discount'] = $discountType->discount_id;
        // $dType['description'] = $newDescription;

        if ($contract->contract_details->billing_frequency === 'Per service') {
            $jobs = $contract->jobs_details;
            $result = $this->handleSingleDiscountTypeForJobInvoice($contract, $discountType, $discountAmount, $jobs);
        } else {
            $jobs = $contract->misc_invoices;
            $result = $this->handleSingleDiscountTypeForMiscInvoice($contract, $discountType, $discountAmount, $jobs);
        }
        return $result;
    }


    public function handleSingleDiscountTypeForJobInvoice($pestContract, $discountType, $discountAmount, $jobs)
    {
        foreach ($jobs as $job) {
            if ($job->invoice->status == 'Not sent') {
                if ($discountType->type === 'percent') {
                    $price = $discountType->amount;
                    $amountDue = $job->invoice->invoice_items[0]->price;
                    $discount = -abs(round($amountDue, 2) * round($price / 100, 2));
                } else {
                    $discount = -abs($discountType->amount);
                }
                if ($discountType->discount_id) {
                    $discountTypeName = $discountType->discount->name;
                } else {
                    $discountTypeName = "Default";
                }

                $type = $discountType->type;
                if ($type == "static") {
                    $description = sprintf('%s', $discountType->description);
                } else {
                    $description = sprintf('%s', $discountType->description);
                }
                $invoice = $job->invoice;
                $this->addDiscountItem($description, $discount, true, $invoice);
            }
        }
        return $pestContract;
    }


    public function handleSingleDiscountTypeForMiscInvoice($pestContract, $discountType, $discountAmount, $invoices)
    {
        $invoices = $pestContract->misc_invoices;
        $billingFrequency = $pestContract->contract->billing_frequency;
        $numberOfPayments = $pestContract->contract->number_of_payments;

        foreach ($invoices as $invoice) {
            if ($invoice->invoice->status == 'Not sent') {
                if ($discountType->type === 'percent') {
                    if ($billingFrequency == "Installments") {
                        $price = $discountType->amount;
                        $amountDue = $invoice->invoice->invoice_items[0]->price;

                        $totalDiscount = abs(round($amountDue * $numberOfPayments, 2) * round($price / 100, 2));
                        $discount = -abs($totalDiscount / $numberOfPayments);
                    } else {
                        $price = $discountType->amount;
                        $amountDue = $invoice->invoice->invoice_items[0]->price;
                        $discount = -abs(round($amountDue, 2) * round($price / 100, 2));
                    }
                } else {
                    if ($billingFrequency == "Installments") {
                        $discount = -abs($discountType->amount / $numberOfPayments);
                    } else {
                        $discount = -abs($discountType->amount);
                    }
                }
                $type = $discountType->type;
                if ($discountType->discount_id) {
                    $discountTypeName = $discountType->discount->name;
                } else {
                    $discountTypeName = "Default";
                }
                if ($type == "static") {
                    $description = sprintf('%s', $discountType->description);
                } else {
                    $description = sprintf('%s', $discountType->description);
                }
                $this->addDiscountItem($description, $discount, true, $invoice->invoice);
            }
        }
        return $pestContract;
    }

    /**
     * @param PocomosSalesArea $salesArea
     * @return array
     */
    public function getRevenueBySalesArea($sales_area)
    {
        $count_customers = 0;
        $count_leads = 0;
        $count_not_interested = 0;
        $area_value = 0;
        $area_value_last_year = 0;

        /** @var PocomosLead $lead */
        foreach ($sales_area->lead_details as $lead) {
            switch ($lead->status) {
                case config('constants.LEAD'):
                    $count_leads++;
                    break;
                case config('constants.NOT_INTERESTED'):
                    $count_not_interested++;
                    break;
                default:
                    break;
            }
        }
        /** @var PocomosCustomer $customer */
        foreach ($sales_area->customer_details as $customer) {
            $count_customers++;
            foreach ($customer->sales_profile->contract_details as $contract) {
                $date1 = new DateTime($contract->date_start);
                $date2 = new DateTime(date("Y-m-d H:i:s"));
                $interval = $date1->diff($date2);
                if ($interval->y < 2) {
                    $area_value_last_year += $contract->original_value;
                }
                $area_value += $contract->original_value;
            }
        }
        $array = [
            'Customers' => $count_customers,
            'Leads' => $count_leads,
            'NotInterested' => $count_not_interested,
            'AreaValue' => $area_value,
            'lastYear' => $area_value_last_year
        ];

        return $array;
    }

    /**
     * Returns office geo location
     * @return array
     */
    public function getGeocodeByOffice($office_id)
    {
        $office = PocomosCompanyOffice::findOrFail($office_id);

        $address = $office->coontact_address;
        if ($address) {
            $location = ['lat' => (float)$address->latitude, 'lng' => (float)$address->longitude];

            return $location;
        }
        return ['lat' => 39.8097343, 'lng' => -98.5556199];
    }

    /**
     *  Deletes a Invoice entity.
     */
    public function cancelInvoice($invoice)
    {
        $input['status'] = 'Cancelled';
        $input['balance'] = 0;
        $result =  $invoice->update($input);
        $this->distributeInvoicePayments($invoice);
        return true;
    }

    /**
     * api for cancel Job
     * @return array
     */
    public function cancelJob($job, $invoice)
    {
        $input['status'] = 'Cancelled';
        $input['date_cancelled'] = new DateTime(date("Y-m-d H:i:s"));
        $input['slot_id'] = null;
        $result =  $job->update($input);

        if (!(abs($invoice->balance - $invoice->amount_due)) > (0.001) && !($invoice->status = "Cancelled")) {
            $this->cancelInvoice($invoice);
        }

        return $result;
    }

    public function applyCredit($customerSalesProfile, $officeUser)
    {
        $pccs = PocomosPestContract::join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
                ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
                ->join('pocomos_agreements as pa', 'pc.agreement_id', 'pa.id')
                ->where('pcsp.id', $customerSalesProfile->id)
                ->get();

        // dd($pccs);

        $invoices = array();
        foreach ($pccs as $pcc) {
            foreach ($pcc->jobs_details as $job) {
                // dd($pcc->jobs_details);

                if ($job->status != 'Cancelled') {
                    $invoices[] = $job->invoice;
                }
            }

            $invoices = array_merge($invoices, $pcc->misc_invoices->toArray());
        }

        // dd($invoices);

        $invoices = array_filter($invoices, function ($invoice) {
            $status = $invoice->status;

            return !($status == 'Cancelled' || $status == 'Paid');
        });

        usort($invoices, function ($a, $b) {
            $aDate = $a->date_due->getTimestamp();
            $bDate = $b->date_due->getTimestamp();
            if ($aDate === $bDate) {
                return 0;
            }

            return ($aDate > $bDate ? 1 : -1);
        });

        $pointsAccount = $customerSalesProfile->points_account;
        $totalPayment = round($pointsAccount->balance / 100,2);

        foreach ($invoices as $invoice) {
            if ($totalPayment == 0) {
                break;
            }

            if ($invoice->balance <= 0.001) {
                continue;
            }

            $payment['account'] = $pointsAccount;
            $payment['method'] = 'Points';
            $payment['description'] = '';

            if ($invoice->balance >= $totalPayment) {
                $payment['amount'] = $totalPayment;
                $totalPayment = 0;
            } else {
                $payment['amount'] = $invoice->balance;
                $totalPayment = $totalPayment - $invoice->balance;
            }

            $generalValues['customer_id'] = $customerSalesProfile->customer_details->id ?? null;
            $generalValues['office_id'] = $customerSalesProfile->office_id;

            $transaction = $this->processPayment($invoice->id, $generalValues, $payment, $officeUser->user_details->id);

            if ($transaction->status != 'Approved') {
                throw new \Exception(__('strings.message', ['message' => 'Credit application failed.']));
            }
        }

    }

    /**Office user base get sales areas details */
    public function getSalesAreasByOfficeUser($user)
    {
        $data = array();
        if (!config('constants.ROLE_OWNER')) {
            return $sales_areas = PocomosSalesArea::whereOfficeId($user->office_id)->get();
        }
        $sales_person = PocomosSalesPeople::whereUserId($user->id)->first();
        $sales_person_profile = PocomosSalespersonProfile::where('office_user_profile_id', $user->profile_id)->first();
        $office = PocomosCompanyOffice::findOrFail($user->office_id);

        $memberships = $this->findOneBySalespersonMembership($user->id);
        if ($sales_person) {
            if (isset($memberships[0]) && $memberships[0]->team_id) {
                $team = PocomosTeam::findOrFail($memberships[0]->team_id);
                $data = $this->getUserAssignedSalesAreas($sales_person, $office, $team);
            } else {
                $data = $this->getUserAssignedSalesAreas($sales_person, $office);
            }
        }
        return $data;
    }

    /**
     * Send form letter
     *
     * @param PocomosSmsFormLetter $letter
     * @param PocomosCustomer $customer
     * @param PocomosPestContract|null $contract
     * @param PocomosJob|null $job
     * @return int
     */
    public function sendSmsFormLetter($letter, $customer, $pestContract = null, $job = null)
    {
        $parameters = $this->getDynamicParameters($customer, $pestContract, $job);

        $profile = PocomosCustomerSalesProfile::where('customer_id', $customer->id)->firstOrFail();
        $message = $letter->message ?? null;
        $message = $this->parseMessageVariables($message, $parameters);

        return $this->sendMessageToProfile($profile, $message);
    }

    /**
     * Parse message variables
     *
     * @param string $message
     * @param array $parameters
     * @return string
     */
    public function parseMessageVariables($message, $parameters)
    {
        foreach ($parameters as $name => $value) {
            $name = sprintf('{{ %s }}', $name);

            $message = str_replace($name, $value, $message);
        }
        return $message;
    }

    /**
     * Sends the given message to all "notifyMobilePhones" on a PocomosCustomerSalesProfile
     *
     * @param  PocomosCustomerSalesProfile $profile
     * @param  string $message
     * @return int The number of messages sent
     */
    public function sendMessageToProfile($profile, $message)
    {
        // dd($profile);

        $phones_data = PocomosCustomersPhone::with(['phone' => function ($q) {
            $q->whereActive(true)->whereType(config('constants.MOBILE'));
        }])->where('profile_id', $profile->id)->get()->toArray();

        // dd($phones_data);

        $count = 0;
        /** @var PocomosPhoneNumber $phone */
        foreach ($phones_data as $phone) {
            // dd($phone);
            $phone = (object)$phone;
            if ($phone->phone) {
                // dd(77);
                $phone->phone;
                $phoneDetail = PocomosPhoneNumber::findOrFail($phone->phone['id']);

                // dd($phoneDetail);

                $number = filter_var($phoneDetail->number, FILTER_SANITIZE_NUMBER_INT);
                if (strlen($number) === 10 || strlen($number) === 9) {
                    $phoneDetail->number = $number;
                    $phoneDetail->save();
                } else {
                    continue;
                }
                try {
                    // dd($message);

                    $this->sendMessage($profile->office_details, $phoneDetail, $message);
                    $count++;
                } catch (\Exception $e) {
                    // dd($e);
                    Log::info("Message send Phone : " . $phoneDetail->number . ' Error : ' . json_encode($e->getMessage()));
                }
            }
        }

        return $count;
    }

    /**
     * @param PocomosCompanyOffice $office
     * @param PocomosPhoneNumber $recipient
     * @param string $message
     * @param PocomosCompanyOfficeUser|null $officeUser
     */
    public function sendMessage($office, $recipient, $message, $officeUser = null, $seen = false)
    {
        // dd($recipient->number);
        $config = PocomosOfficeSetting::where('office_id', $office->id)->first();

        $sender = $config ? $config->sender_phone_details : null;

        if (empty($message)) {
            $message = 'No Text';
        }
        // dd($sender);

        if ($sender === null) {
            // dd(11);
            throw new \Exception(__('strings.two_params_office_message', ['param1' => $office->id, 'param2' => $office->name]));
        }

        $sms = null;
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken  = env('TWILIO_AUTH_TOKEN');
        // dd($authToken);

        $client = new TwillioClient($accountSid, $authToken);


        $input['office_id'] = $office->id ?? null;
        $input['phone_id'] = $recipient->id;
        $input['message_part'] = $message;
        $input['sender_phone_id'] = $sender->id;
        // dd($officeUser);
        $input['office_user_id'] = $officeUser ? $officeUser->id : null;
        $input['inbound'] = 0;
        $input['answered'] = 0;
        $input['seen'] = $seen;
        $input['active'] = 1;
        $sms = PocomosSmsUsage::create($input);

        $this->markMessagesAsRead($recipient, /* answered */ true);

        try {
            // Use the client to do fun stuff like send text messages!
            $message = $client->messages->create(
                // the number you'd like to send the message to
                $recipient->number,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    // 'from' => config('constants.TWILLIO_NUMBER'),
                    // 'from' => '+14094074706',
                    'from' => $sender->number,
                    // the body of the text message you'd like to send
                    'body' => $message
                )
            );
        } catch (\Exception $e) {
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        return $sms;
    }

    public function markMessagesAsRead($phone, $answered = false)
    {
        // dd($phone->id);

        $sql = 'UPDATE `pocomos_sms_usage`
                SET seen = 1';

        if ($answered) {
            $sql .= ' , answered = 1';
        }
        $sql .= ' WHERE (sender_phone_id = ' . $phone->id . ' OR phone_id = ' . $phone->id . ')';

        DB::select(DB::raw(($sql)));
        return true;
    }

    public function getLastContactedPhone($phoneNumber, $officeId = null)
    {
        $sql = 'SELECT sms.phone_id
                 FROM `pocomos_sms_usage` AS sms
                 JOIN pocomos_phone_numbers AS ph ON ph.id = sms.phone_id
                     WHERE ph.number = ' . $phoneNumber . '';

        if ($officeId) {
            $sql .= ' AND sms.office_id = ' . $officeId . '';
        }
        $sql .= ' ORDER BY sms.date_created DESC LIMIT 1';

        return DB::select(DB::raw(($sql)));
    }

    public function getOfficeByPhoneId($phone)
    {
        $sql = 'SELECT csp.office_id FROM pocomos_customer_sales_profiles AS csp
                JOIN pocomos_customers_phones AS cp ON csp.id = cp.profile_id
                WHERE phone_id = ' . $phone->id . '';

        $officeId = DB::select(DB::raw(($sql)));

        // dd($officeId[0]->office_id);
        // dd($phone->id);

        if (!$officeId) {
            $sql = 'SELECT ou.office_id, pcp.id as qqq FROM pocomos_leads as pl
                    JOIN pocomos_lead_quotes q ON pl.quote_id = q.id
                    JOIN pocomos_salespeople ps ON ps.id = q.salesperson_id
                    JOIN pocomos_company_office_users ou ON ou.id = ps.user_id
                    JOIN pocomos_addresses pca on pca.id = pl.contact_address_id
                    JOIN pocomos_addresses pba on pba.id = pl.billing_address_id
                    JOIN pocomos_phone_numbers pcp ON pcp.id = pba.phone_id OR pcp.id = pca.phone_id
                    WHERE pcp.number = ' . $phone->number . '
                ';

            $officeId = DB::select(DB::raw(($sql)));

            // dd($officeId);
            if (!$officeId) {
                throw new \Exception(__('strings.message', ['message' => 'Unable to find Office entity for customer phone ' . $phone->number . '.']));
            }
        }

        $officeId = $officeId[0]->office_id;

        return PocomosCompanyOffice::findOrFail($officeId);
    }

    public function getOfficeBySenderPhone($phone)
    {
        return PocomosCompanyOffice::join('pocomos_office_settings as pos', 'pocomos_company_offices.id', 'pos.office_id')
            ->join('pocomos_phone_numbers as ppn', 'pos.sender_phone_id', 'ppn.id')
            ->where('ppn.id', $phone->id)
            ->first();
    }

    public function createMessage_officeSmsController($office, $to, $messageBody, $from = null, $inbound = true, $fromNumber = false)
    {
        $input['office_id'] = $office->id ?? null;
        $input['phone_id'] = $to->id;
        $input['message_part'] = $messageBody;
        $input['inbound'] = $inbound;

        if ($from) {
            $input['sender_phone_id'] = $from->id;
        }

        $sms = PocomosSmsUsage::create($input);

        if ($fromNumber) {
            $this->sentReceiveSmsNotification($office, $fromNumber, $messageBody, $from);
        }
    }

    public function sentReceiveSmsNotification($office, $senderNumber, $messageBody, $fromPhone = null)
    {
        $title = 'Inbound SMS';
        $message = 'You have received an inbound SMS from ';
        $senderReference = '';

        if ($fromPhone) {
            $customer = $this->getCustomerByPhone($office, $fromPhone);

            // if ($customer) {
            //     $customerUrl = $this->generateUrl('customer_show', array('id' => $customer->getId()), true);
            //     $senderReference .= '<a href="' . $customerUrl . '">' . $customer . '</a>';
            // }
        }

        $message .= $senderReference;

        $message .= ' ' . $senderNumber . ' saying <strong>"' . $messageBody . '".</strong><br/>';

        if (strlen($senderReference) == 0) {
            $message .= '<br/>This number is not assigned to a customer, please respond to this message outside of Pocomos.';
        }

        $officeUsers = $this->findActiveEmployeesByOffice($office, ['ROLE_RECEIVE_INBOUND_SMS']);

        $officeConfigurationSettings = PocomosPestOfficeSetting::whereOfficeId($office->id)->first();

        $isInboundSmsEnable = $officeConfigurationSettings->send_inbound_sms_email;

        file_put_contents('officeUsers.txt', "");

        foreach ($officeUsers as $officeUser) {
            file_put_contents('officeUsers.txt', json_encode($officeUser->user_details->username), FILE_APPEND);

            try {
                $alert = $this->createAssignedAlert($officeUser, $officeUser, $title, $message, 'Normal');
            } catch (\Exception $e) {
                // $this->get('logger')->error($e->getMessage());
            }

            if ($isInboundSmsEnable) {
                // send email
                try {
                    $emailResult = $this->createBuilder_emailFactory(
                        'Inbound SMS email Type',
                        array(
                            'body' => $message,
                            'officeUser' => $officeUser,
                        )
                    );
                } catch (\Exception $e) {
                    // $this->get('logger')->error($e->getMessage());
                }
            }
        }
    }

    public function createBuilder_emailFactory($type, $options)
    {
        $this->buildEmail($type, $options);
    }

    public function buildEmail($type, $options)
    {
        $officeUser = $options['officeUser'];
        $body = $options['body'];
        $subject = 'New Inbound SMS';

        $emailInput['office_id'] = $officeUser->office_id;
        $emailInput['office_user_id'] = $officeUser->id;
        $emailInput['type'] = $type;
        $emailInput['body'] = $body;
        $emailInput['subject'] = $subject;
        $emailInput['reply_to'] = '';
        $emailInput['reply_to_name'] = '';
        $emailInput['sender'] = '';
        $emailInput['sender_name'] = '';
        $emailInput['active'] = true;
        PocomosEmail::create($emailInput);

        Mail::send('emails.dynamic_email_render', ['agreement_body' => $body], function ($message) use ($subject, $officeUser) {
            $message->from(auth()->user()->email);
            $message->to($officeUser->user_details->email);
            $message->subject($subject);
        });
    }

    public function getCustomerByPhone($office, $phone)
    {
        $sql = "SELECT c.id FROM pocomos_phone_numbers AS p
                JOIN pocomos_customers_phones AS cp ON p.id = cp.phone_id
                JOIN pocomos_customer_sales_profiles AS csp ON cp.profile_id = csp.id
                JOIN pocomos_customers AS c ON csp.customer_id = c.id
                WHERE p.id = " . $phone->id . " AND csp.office_id = " . $office->id . "";

        $customerId = DB::select(DB::raw(($sql)));

        if ($customerId) {
            $customerId = $customerId[0]->id;
            $officeId = $office->id;
            return $this->findOneByIdAndOffice_customerRepo($customerId, $officeId);
        }

        return null;
    }

    public function findOneByIdAndOffice_customerRepo($id, $officeId)
    {
        $dql = 'SELECT c.*, csp.id as profile_id
            FROM pocomos_customer_sales_profiles as csp,
            pocomos_customers c
              JOIN pocomos_addresses pca on pca.id = c.contact_address_id
            WHERE csp.customer_id = c.id
            AND c.id = ' . $id . '
            AND csp.office_id = ' . $officeId . '
            LIMIT 1
            ';

        return DB::select(DB::raw(($dql)))[0] ?? null;
    }

    public function findAllByCustomerPCCRepo($custId, $statuses = array())
    {
        $qb = PocomosPestContract::select('*', 'pocomos_pest_contracts.id')
            ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->where('pcsp.customer_id', $custId)
            ->orderBy('pc.status');

        if (count($statuses)) {
            $qb->whereIn('pc.status', $statuses);
        }

        return $qb->get();
    }

    public function findAllServicesForContract($pestContractId)
    {
        return $this->createFindContractServicesQueryBuilder($pestContractId)
            ->whereIn('pocomos_jobs.status', ['Pending', 'Re-scheduled', 'Complete'])
            ->orderBy('pocomos_jobs.date_scheduled', 'DESC')
            ->groupBy('pocomos_jobs.date_scheduled')
            ->get();
    }

    public function findOrderedByCustomer($custId, $order = 'ASC', $limit = null)
    {
        $queryBuilder = PocomosCustomersNote::join('pocomos_customers as pc', 'pocomos_customers_notes.customer_id', 'pc.id')
            ->join('pocomos_notes as pn', 'pocomos_customers_notes.note_id', 'pn.id')
            ->where('pc.id', $custId)
            ->where('pn.active', 1)
            ->orderBy('pn.favorite', $order)
            ->orderBy('pn.date_created', $order);

        if (is_int($limit)) {
            $queryBuilder->take($limit);
        }

        return $queryBuilder->get();
    }

    public function findActiveEmployeesByOffice($office, $roles)
    {
        // return $office->id;
        return PocomosCompanyOfficeUser::select('*', 'pocomos_company_office_users.id')
            ->join('orkestra_users as ou', 'pocomos_company_office_users.user_id', 'ou.id')
            ->join('orkestra_user_groups as oug', 'ou.id', 'oug.user_id')
            ->join('orkestra_groups as og', 'oug.group_id', 'og.id')
            ->where('og.role', '!=', 'ROLE_CUSTOMER')
            ->where('pocomos_company_office_users.office_id', $office->id)
            ->where('pocomos_company_office_users.active', 1)
            ->where('ou.active', 1)
            ->whereIn('og.role', $roles)
            ->groupBy('pocomos_company_office_users.id')
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();
    }

    public function findAllActiveByOffice_techRepo($officeId)
    {
        return PocomosTechnician::join('pocomos_company_office_users as pcou', 'pocomos_technicians.user_id', 'pcou.id')
            ->join('orkestra_users as ou', 'pcou.user_id', 'ou.id')
            ->where('pcou.office_id', $officeId)
            ->where('pocomos_technicians.active', 1)
            ->where('pcou.active', 1)
            ->where('ou.active', 1)
            ->orderBy('ou.first_name')
            ->orderBy('ou.last_name')
            ->get();
    }

    /* public function createJobFromModel($jobModel)
    {
        // dd($jobModel['route']);
        if ($jobModel['route']) {
            $entity = $this->createJob($jobModel->contract, $jobModel->type, $jobModel->amountDue, $jobModel->dateScheduled, null);
            $this->slotHelper->assignJobToRoute($entity, $jobModel->route, $jobModel->timeScheduled);
        } else {
            $entity = $this->createJob($jobModel->contract, $jobModel->type, $jobModel->amountDue, $jobModel->dateScheduled, $jobModel->timeScheduled);
        }

        foreach ($jobModel->targetedPests as $pest) {
            $entity->addPest($pest);
        }

        if ($jobModel->color) {
            $entity->setColor($jobModel->color);
        }

        if ($jobModel->note) {
            $entity->setNote($jobModel->note);
        }

        if ($jobModel->treatmentNote) {
            $entity->setTreatmentNote($jobModel->treatmentNote);
        }

        return $entity;
    } */

    public function hasACHCredentials($officeId)
    {
        $achCredentials = OrkestraCredential::join('pocomos_office_settings as pos', 'orkestra_credentials.id', 'pos.ach_credentials_id')
            ->where('pos.office_id', $officeId)
            ->first();

        return $achCredentials;
    }

    public function doGenerateServiceHistoryGenerator($parameters, $options = null)
    {
        $pdf = PDF::loadView('pdf.blank');
        $pdf = $pdf->download('blank_' . strtotime('now') . '.pdf');

        $pestContract = $parameters['contract'];

        $jobs = $this->findCompletedServicesForContract($pestContract->id)->get();

        // $jobs = PocomosJob::has('invoice')->take(3)->get();

        // dd($jobs);

        foreach ($jobs as $job) {

            $invoice = $job->invoice;

            $pdf = $this->createBuilder_PdfFactory('Invoice', $invoice);
        }

        return $pdf;
    }

    public function findCompletedServicesForContract($pestContractId)
    {
        return $this->createFindContractServicesQueryBuilder($pestContractId)
            ->orderBy('pocomos_jobs.date_completed', 'desc')
            ->whereIn('pocomos_jobs.status', ['Complete', 'Cancelled'])
            // ->get()
        ;
    }

    public function createFindContractServicesQueryBuilder($pestContractId)
    {
        return PocomosJob::select(
            '*',
            'pocomos_jobs.id as job_id',
            'pocomos_jobs.type',
            'pocomos_jobs.status',
            'pocomos_jobs.slot_id',
            'pocomos_jobs.date_scheduled',
            'pi.id as invoice_id',
            'pi.status as invoice_status',
            'ou.first_name as tech_fname',
            'ou.last_name as tech_lname'
        )
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->leftjoin('pocomos_route_slots as prs', 'pocomos_jobs.slot_id', 'prs.id')

            // added
            // ->leftJoin('pocomos_routes as pr', 'prs.route_id', 'pr.id')
            ->leftJoin('pocomos_technicians as pt', 'pocomos_jobs.technician_id', 'pt.id')
            ->leftJoin('pocomos_company_office_users as pcou', 'pt.user_id', 'pcou.id')
            ->leftJoin('orkestra_users as ou', 'pcou.user_id', 'ou.id')

            ->where('ppc.id', $pestContractId);
    }

    public function findScheduledServicesForContract($pestContractId)
    {
        return $this->createFindContractServicesQueryBuilder($pestContractId)
            ->whereIn('pocomos_jobs.status', ['Pending', 'Re-scheduled'])
            ->orderBy('pocomos_jobs.date_scheduled');
    }

    /**Get service schedule details */
    public function getServiceSchedule($data)
    {
        $service_schedule = '<div id="service-schedule"><ul class="table-list clearfix">';
        $schedule = array();
        $contract_type_id = $data['service_information']['contract_type_id'];

        $pest_agreement = PocomosPestAgreement::whereId($contract_type_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }

        $current = date('Y-m-d', strtotime($data['service_information']['scheduling_information']['initial_date'] ?? date('Y-m-d')));
        $current_month = date('F', strtotime($current));

        $agreement_length = $agreement['length'];
        $end = date('Y-m-d', strtotime("+$agreement_length month", strtotime($current)));

        if ($exceptions && in_array($current_month, $exceptions)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $c = 1;
        $exception_css = '';

        while ($current <= $end) {
            $current_month = date('F', strtotime($current));

            if ($exceptions && in_array($current_month, $exceptions)) {
                $exception_css = 'box-disabled';
            } else {
                $exception_css = '';
            }

            if ($c == 1) {
                $text = 'X';
            } else {
                $text = '';
            }

            $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
            <span class="list-value">' . $text . '</span></li>';
            $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
            $c = $c + 1;
        }
        $service_schedule .= '</ul></div>';
        return $service_schedule;
    }

    /**Get billing schedule details */
    public function getBillingSchedule($data)
    {
        $billing_schedule = '<div id="billing-schedule"><ul class="table-list clearfix">';
        $schedule = array();

        $contract_type_id = $data['service_information']['contract_type_id'];

        $pest_agreement = PocomosPestAgreement::whereId($contract_type_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }

        $initial_price = $data['service_information']['pricing_information']['initial_price'] ?? 0;
        $current = date('Y-m-d', strtotime($data['service_information']['contract_start_date'] ?? date('Y-m-d')));
        $initial_date = date('Y-m-d', strtotime($data['service_information']['scheduling_information']['initial_date'] ?? date('Y-m-d')));

        $agreement_length = $agreement['length'];
        $end = date('Y-m-d', strtotime("+$agreement_length month", strtotime($current)));

        $c = 1;
        $initial_month = date('m', strtotime($initial_date));
        $current_month = date('m', strtotime($current));

        if (!$pest_agreement['allow_dates_in_the_past'] && ($initial_month < $current_month)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        if ($exceptions && in_array($current_month, $exceptions)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        while ($current <= $end) {
            $current_month = date('m', strtotime($current));

            $current_month_str = date('F', strtotime($current));

            if ($exceptions && in_array($current_month_str, $exceptions)) {
                $exception_css = 'box-disabled';
            } else {
                $exception_css = '';
            }

            if ($initial_month == $current_month) {
                $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
            } else {
                $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0.00);
            }

            $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
            <span class="list-value">' . $initial_amount . '</span></li>';
            $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
            $c = $c + 1;
        }
        $billing_schedule .= '</ul></div>';
        return $billing_schedule;
    }

    public function logDiagnostic($exception, $contract, $invoice = null)
    {
        $error_data = [
            "message" => $exception->getMessage(),
            "line" => $exception->getLine(),
            'customer' => (string)$contract->profile_details->customer_details->first_name ?? 'n/a' . ' ' . (string)$contract->profile_details->customer_details->last_name ?? 'n/a',
            'office' => (string)$contract->profile_details->office_details->name ?? 'n/a',
            'contract' => $contract->id ?? 'n/a',
            'invoice' => $invoice ? $invoice->id : 'n/a',
        ];

        Log::info("Error Data : " . json_encode($error_data));
    }

    public function doHandleContract($contract)
    {
        $profile = $contract->profile_details;
        $account = $profile->autopay_account;

        try {
            $payable = $this->isContractPayable($contract);
        } catch (\Exception $e) {
            $this->logDiagnostic($e, $contract);

            $payable = false;
        }

        if (!$payable) {
            return true;
        }

        foreach ($this->getContarctInvoices($contract) as $invoice) {
            try {
                $payable = $this->isInvoicePayable($invoice);
            } catch (\Exception $e) {
                $this->logDiagnostic($e, $contract, $invoice);

                $payable = false;
            }

            if (!$payable) {
                continue;
            }

            $pointsAccount = $invoice->contract->profile_details->points_account;
            $officeUser = PocomosCompanyOfficeUser::whereOfficeId($profile->office_id)->whereUserId(auth()->user()->id)->firstOrFail();
            $generalValues['customer_id'] = $profile->customer_details->id ?? null;
            $generalValues['office_id'] = $profile->office_id;

            if ($pointsAccount->balance > 0) {
                $pointsByDollar = $pointsAccount->balance / 100;

                $payment['amount'] = $pointsByDollar;
                $payment['account'] = $pointsAccount;
                $payment['method'] = 'POINTS';
                $payment['description'] = '';

                try {
                    $transaction = $this->processPayment($invoice->id, $generalValues, $payment, $officeUser->id);
                    $transaction = array();
                    $this->logTransactionResult($transaction, $invoice);
                } catch (\Exception $e) {
                    $this->logDiagnostic($e, $contract, $invoice);
                }
            }

            // Ensure the Account Credit transaction did not pay off the invoice
            if (!$invoice->status == config('constants.PAID')) {
                if ($account->type == 'BankAccount') {
                    $type = 'ach';
                } else {
                    $type = 'card';
                }
                $payment['amount'] = $invoice->balance;
                $payment['account'] = $account;
                $payment['method'] = $account->type;
                $payment['description'] = $type;

                try {
                    $transaction = $this->processPayment($invoice->id, $generalValues, $payment, $officeUser->id);
                    $transaction = array();
                    $this->logTransactionResult($transaction, $invoice);
                } catch (\Exception $e) {
                    $this->logDiagnostic($e, $contract, $invoice);
                }
            }
        }

        return true;
    }

    /**
     * Returns true if the given Contract should be handled by this helper
     *
     * @return bool
     */
    public function isContractPayable($contract)
    {
        if (config('constants.ACTIVE') != $contract->status) {
            throw new \Exception(__('strings.message', ['message' => 'Contract must have Active status.']));
        }

        $profile = $contract->profile_details;
        $customer = $profile->customer_details;

        if (config('constants.ACTIVE') != $customer->status) {
            throw new \Exception(__('strings.message', ['message' => 'Customer must have Active status.']));
        }

        if ($profile->autopay != true) {
            throw new \Exception(__('strings.message', ['message' => 'Customer must have autopay enabled.']));
        }

        if ($profile->autopay_account == null) {
            throw new \Exception(__('strings.message', ['message' => 'Customer must have autopay account configured.']));
        }

        $account = $profile->autopay_account;

        if ($account->type == config('constants.CARD_ACCOUNT')) {
            $hasExpDate = ($account->card_exp_year && $account->card_exp_month);
            //Some imported tokens don't have a exp date. So this hack let's them through, because let's face it - they work.
            //Worst case is that the transactor will throw a catchable error.
            //But it shouldn't because for tokenized accs - it doesn't need the exp date.
            if (!$hasExpDate && $account->account_token !== null) {
                return true;
            }
            if (
                $hasExpDate &&
                ((date('Y', strtotime($account->card_exp_year)) == date('Y') &&
                    date('m', strtotime($account->card_exp_month)) < date('m'))
                    || (date('Y', strtotime($account->card_exp_year)) < date('Y')))
            ) {
                throw new \Exception(__('strings.message', ['message' => 'Card on file is expired.']));
            }
        }

        return true;
    }

    public function getContarctInvoices($contract)
    {
        $invoices = PocomosInvoice::where('contract_id', $contract->id)->get();
        return $invoices;
    }

    public function isInvoicePayable($invoice)
    {
        if ($invoice->balance <= 0) {
            throw new \Exception(__('strings.message', ['message' => 'Invoice has a zero balance.']));
        }

        if (!in_array($invoice->status, array(
            config('constants.SENT'),
            config('constants.DUE'),
            config('constants.PAST_DUE'),
            config('constants.COLLECTIONS'),
            config('constants.IN_COLLECTIONS')
        ))) {
            throw new \Exception(__('strings.message', ['message' => 'Invoice is not considered due.']));
        }

        $contract = $invoice->contract;
        //We don't need to check if the job is completed on contracts that are billed monthly
        if (!($contract->billing_frequency === config('constants.MONTHLY'))) {
            $job = $this->getJob($invoice);
            if ($job && !$job->status == config('constants.COMPLETE')) {
                throw new \Exception(__('strings.message', ['message' => 'Invoice is associated with an incomplete job.']));
            }
        }

        $transactions = PocomosInvoiceTransaction::with('transactions', 'user_transactions')->where('invoice_id', $invoice->id)->get()->toArray();

        usort($transactions, function ($a, $b) {
            return $a->date_created > $b->date_created;
        });
        /** @var InvoiceTransaction[] $transactions */
        $transactions = array_slice($transactions, -2);
        $declined = array();
        foreach ($transactions as $it) {
            $transaction = $it['transactions'];
            $user_transactions = $it['user_transactions'];

            if (
                !$user_transactions['user_id']
                && $transaction['type'] == config('constants.SALE')
                && $transaction['status'] == config('constants.DECLINED')
            ) {
                $declined[] = $transaction;
            }
        }

        $limit = 2;
        $account = $invoice->contract->profile_details->autopay_account;
        if ($account) {
            $limit = 1;
        }

        if (count($declined) >= $limit) {
            throw new \Exception(__('strings.message', ['message' => 'Invoice has too many declined transactions associated.']));
        }

        $lastTransaction = PocomosUserTransaction::where('invoice_id', $invoice->id)->latest('date_created')->first();

        if ($lastTransaction) {
            $oldDate = new DateTime($lastTransaction->date_created);

            if ($lastTransaction && $oldDate->diff(new DateTime())->days < 2) {
                throw new \Exception(__('strings.message', ['message' => 'Invoice was attempted too recently.']));
            }
        }
        return true;
    }

    public function getJob($invoice)
    {
        $update_data = DB::select(DB::raw("SELECT j.*, i.*
            FROM pocomos_jobs AS j
            JOIN pocomos_invoices AS i ON j.invoice_id = i.id
            WHERE i.id = '$invoice->id'"));

        return $update_data;
    }

    /**
     * @param $transaction
     * @param $invoice
     */
    public function logTransactionResult($transaction, $invoice)
    {
        $data = [
            "message" => ($transaction['status'] == config('constants.APPROVED') ? 'Successfully processed transaction' : 'Failed to process transaction'),
            'customer' => (string)$invoice->contract->profile_details->customer_details->first_name ?? 'n/a' . ' ' . (string)$invoice->contract->profile_details->customer_details->last_name ?? 'n/a',
            'office' => (string)$invoice->contract->profile_details->office_details->name ?? 'n/a',
            'contract' => $invoice->contract->id ?? 'n/a',
            'invoice' => $invoice ? $invoice->id : 'n/a',
        ];

        Log::info("Log Data : " . json_encode($data));
    }

    public function createContractJob($contract, $type, $price, $dateScheduled, $timeScheduled = null, $note = null, $treatmentNote = null)
    {
        $job = new PocomosJob();
        $job->date_scheduled = $dateScheduled;
        $job->original_date_scheduled = $dateScheduled;
        $job->contract_id = $contract->id;
        $job->type = $type;
        $job->active = true;
        $job->technician_note = '';
        $job->weather = '';
        $job->color = '';
        $job->commission_type = '';
        $job->commission_value = 0.0;

        // $job->status = new JobStatus(JobStatus::PENDING,JobStatus::RESCHEDULED);
        $job->status = config('constants.PENDING');

        $job->note = '';
        if ($note) {
            $job->note = $note;
        }

        $job->treatmentNote = '';
        if ($treatmentNote) {
            $job->treatmentNote = $treatmentNote;
        }

        $job->technician_id = $contract->technician_id ?? null;
        $job->save();

        $service = new PocomosJobService();
        $service->service_type_id = $contract->service_type_id;
        $service->job_id = $job->id;
        $service->active = true;
        $service->save();

        $this->generateInvoice($contract, $job, $price);

        if ($timeScheduled !== null) {
            $job->time_scheduled = $timeScheduled;
            $job->save();
            $this->assignJobBasedOnSchedule($job);
        }

        return $job;
    }

    public function generateInvoice($pestContract, $job, $priceOverride = null)
    {
        $parentContract = $pestContract->parent_contract;
        $contract = $pestContract->contract_details;

        if ($parentContract && $parentContract->contract_details->tax_details === $pestContract->contract_details->tax_details) {
            $invoiceContract = $parentContract->contract_details;
        } else {
            $invoiceContract = $pestContract->contract_details;
        }

        $price = ($priceOverride === null ? ($job->type == config('constants.INITIAL')
            ? $pestContract->initial_price
            : $pestContract->recurring_price) : $priceOverride);

        $invoice = new PocomosInvoice();
        $invoice->tax_code_id = $contract->tax_code_id;
        $invoice->sales_tax = $contract->sales_tax;
        $invoice->contract_id = $contract->id;
        $invoice->date_due = $job->date_scheduled;
        $invoice->amount_due = 0.0;
        $invoice->balance = 0.0;
        $invoice->active = true;
        $invoice->status = config('constants.NOT_SENT');
        $invoice->save();

        $payment = new PocomosInvoicePayment();
        $payment->date_scheduled = $invoice->date_due;
        $payment->amount_in_cents = round($invoice->balance, 2) * 100;
        $payment->status = config('constants.UNPAID');
        $payment->active = true;

        $payment->active = true;

        if (!config('constants.PAID') === $invoice->status && !config('constants.CANCELLED') === $invoice->status) {
            $payment->status = config('constants.PAID');
        }
        $payment->save();

        PocomosInvoiceInvoicePayment::create(['invoice_id' => $invoice->id, 'payment_id' => $payment->id]);

        $invoice_payments = PocomosInvoiceInvoicePayment::where('invoice_id', $invoice->id)->get();

        if (count($invoice_payments) > 0) {
            return PocomosInvoiceInvoicePayment::where('invoice_id', $invoice->id)->first();
        }

        $description = sprintf('%s %s Service', $job->type, $pestContract->service_type_details->name ?? '');

        $invoice_item = [];
        $invoice_item['tax_code_id'] = $contract->tax_code_id;
        $invoice_item['invoice_id'] = $invoice->id;
        $invoice_item['description'] = $description;
        $invoice_item['price'] = $price;
        $invoice_item['active'] = '1';
        $invoice_item['sales_tax'] =  $contract->sales_tax;
        $invoice_item['type'] = 'Type';
        $invoice_item = PocomosInvoiceItems::create($invoice_item);

        $job->invoice_id = $invoice->id;
        $job->save();

        $this->distributeInvoicePayments($invoice);
        return true;
    }

    public function distributeInvoicePayments($invoice)
    {
        $invoices = array();
        $payments = PocomosInvoiceInvoicePayment::where('invoice_id', $invoice->id)->get();
        foreach ($payments as $payment) {
            $payment_invoice = PocomosInvoice::findOrFail($payment->invoice_id);

            // foreach ($payment->getInvoices() as $invoice_detail) {
            if (!in_array($payment_invoice, $invoices, true)) {
                $invoices[] = $payment_invoice;
            }
            // }
        }

        $balance = 0;
        foreach ($invoices as $invoice_detail) {
            $balance += $invoice_detail->balance;
        }

        $paymentAmount = round($balance / (count($payments) ?: 1), 2);
        foreach ($payments as $payment) {
            if ($payment->status == config('constants.PAID')) {
                continue;
            }
            $payment->amount = $paymentAmount;
            $balance -= $paymentAmount;
        }

        if ($balance > 0) {
            $newPayment = new PocomosInvoicePayment();
            $newPayment->amount_in_cents = round($balance, 2) * 100;
            $newPayment->date_scheduled = $invoices[0]->date_due;
            $newPayment->status = config('constants.UNPAID');
            $newPayment->active = true;
            $newPayment->save();
            $payments[] = $newPayment;

            PocomosInvoiceInvoicePayment::create(['invoice_id' => $invoice->id, 'payment_id' => $newPayment->id]);
        }

        return $payments;
    }

    public function assignJobBasedOnSchedule($job)
    {
        // dd(11);
        $dateScheduled = $job->date_scheduled;
        $timeScheduled = $job->time_scheduled;

        $office = $job->contract->contract_details->agreement_details->office_details;

        if (!$timeScheduled) {
            // Assign to first empty slot?
            throw new \Exception(__('strings.message', ['message' => 'Unable to assign slot. A suitable route was unable to be located or created.']));
        }

        $select_sql = "SELECT r.*
        FROM pocomos_routes AS r";

        $where_sql = " WHERE r.date_scheduled = '$dateScheduled'
        AND r.office_id = '$office->id' ";

        $from_sql = " ";

        if ($tech = $job->contract->technician_details) {
            $technician_id = $tech->id;
            $where_sql .= " AND r.technician_id = '.$technician_id.'";
        }
        $merge_sql = $select_sql . '' . $where_sql . '' . $from_sql;

        $routes = DB::select(DB::raw($merge_sql));

        $slot = null;
        while ($slot === null) {
            if (count($routes) > 0) {
                $route = array_shift($routes);
            } else {
                // if ($this->routeFactory === null) {
                //     throw new \Exception(__('strings.message', ['message' => 'Unable to assign slot. A suitable route was unable to be located or created.']));
                // }
                $route = $this->createRoute_routeFactory($office->id, $dateScheduled, $technician_id = null, 'SlotHelper:263');

                /* $route = new PocomosRoute();
                $route->name = 'Route';
                $route->office_id = $office->id;
                $route->created_by = 'SlotHelper:263';
                $route->date_scheduled = $dateScheduled;
                if ($tech !== null) {
                    $route->technician_id = $tech->id;
                }
                $route->active = true;
                $route->locked = false;
                $route->save();

                $schedule = $this->getEffectiveSchedule($office, $dateScheduled);

                if ($schedule->lunch_duration > 0) {
                    $this->assignLunchSlot($route);
                } */
            }

            try {
                $slot = $this->assignJobToRoute($job, $route, $timeScheduled);
            } catch (\Exception $e) {
                // dd(11);
                if (!isset($route->id)) {
                    throw new \Exception(__('strings.message', ['message' => 'Unable to assign slot. A suitable route was unable to be located or created.']));
                }

                continue;
            }
        }
        return $slot;
    }

    public function generateContractAgreement(array $parameters)
    {
        $office = $parameters['office'];
        $parameters['config'] = $office->office_configuration;

        $customer = $parameters['customer'];
        $pestContract = $parameters['pestContract'];
        $agreement = $parameters['agreement'];

        $profile = isset($parameters['profile']) ? $parameters['profile'] : null;

        $agreementTemplate = $this->renderDynamicTemplate($parameters['config']->welcome_letter, null, $profile ?: $customer, $pestContract, null, true);

        // $rendered = view('emails.dynamic_email_render', array('agreement_body' => $configTemplate));

        if (is_null($agreement->custom_agreement_id)) {
            $agreementBody = $agreement->agreement_body;
        } else {
            $agreementBody = $agreement->custom_agreement_template->agreement_body;
        }
        $agreementTemplate = $this->renderDynamicTemplate($agreementBody, null, $profile ?: $customer, $pestContract, null, true);

        // $rendered = view('emails.dynamic_email_render', array('agreement_body' => $agreementTemplate));

        return $agreementTemplate;
    }

    public function getLifetimeRevenue($pestContract)
    {
        $lifetimeRevenue = 0.00;
        $amount = 0.00;

        $invoices = $this->getInvoicesReportHelper($pestContract);
        // dd($invoices->pluck('id'));

        /* $allTransactions = [];
        foreach ($invoices as $invoice) {
            $invoiceTransactions = PocomosInvoiceTransaction::where('invoice_id', $invoice->id)->first()->toArray();
            $allTransactions = array_merge($allTransactions, $invoiceTransactions);
        }
        $allTransactions = array_unique($allTransactions); */

        $allTransactions = PocomosInvoiceTransaction::whereIn('invoice_id', $invoices->pluck('id'))->get();

        foreach ($allTransactions as $transaction) {
            if ('Approved' != $transaction->transactions->status) {
                continue;
            }
            if ($transaction->transactions->type == 'Sale') {
                $amount = $transaction->transactions->amount;

                if ($transaction->transactions->account_detail->type == 'Points') {
                    $amount /= 100;
                }

                $lifetimeRevenue += $amount;
            }
        }

        // dd($invoices);

        /* $invoices = PocomosJob::where('contract_id', $pestContract->id)->pluck('invoice_id')->toArray();
        $allTransactions = [];

        if ($invoices) {
            foreach ($invoices as $inv) {
                $transactions = PocomosInvoiceTransaction::where('invoice_id', $inv->id)->pluck('transaction_id')->toArray();

                if ($transactions) {
                    foreach ($transactions as $inv) {
                        $transaction = OrkestraTransaction::where('id', $inv->id)->first();
                        if ($transaction->type == 'Sale') {
                            $amount = $transaction->amount;
                        }
                        $account = OrkestraAccount::where('id', $transaction->account_id)->first();
                        if ($account) {
                            if ($account->type == 'Points') {
                                $amount /= 100;
                            }
                        // }

                        $lifetimeRevenue += $amount;
                    }
                }
            }
        } */

        return $lifetimeRevenue;
    }

    public function generateWelcomeEmail($pcc, $officeUserId)
    {
        /** @var PocomosPestContract $pestContract */
        $pestContract = $pcc;
        $salesContract = $pestContract->contract_details;
        $profile = $salesContract->profile_details;
        $office = $profile->office_details;
        $customer = $profile->customer_details;
        $config = PocomosPestOfficeSetting::where('office_id', $office->id)->first();
        $initialJob = PocomosJob::where('contract_id', $pestContract->id)->where('type', 'Initial')->first();
        // if(!$initialJob){
        //     $job = $this->getFirstJob($pestContract->id);dd("if");
        //     if($job){
        //         $initialJob = $job;
        //     }dd("else");
        // }
        $body = $this->renderDynamicTemplate($config->welcome_letter, null, $customer, $pestContract, $initialJob);

        $subject = 'Welcome to ' . $office->name;

        $emails = unserialize($office->email);
        $from = $emails[0] ?? '';
        $customerEmail = $customer->email;

        $customer = $profile->customer_details;
        $agreement = $salesContract->agreement_detail;
        // $path = $this->getContractFilename($salesContract);

        $path =  "contract/" . preg_replace('/[^A-Za-z0-9\-]/', '', $salesContract->id) . '.pdf';

        // $pdf = PDF::loadView('emails.dynamic_email_render', compact('agreement_body'));

        // if (!file_exists($path)) {
        $template = $this->agreementGenerator(array(
            'office' => $office,
            'customer' => $customer,
            'agreement' => $agreement,
            'contract' => $salesContract,
            'pestContract' => $pestContract,
            'profile' => $profile
        ), true);
        $pdf = PDF::loadView('pdf.dynamic_render', compact('template'));

        Storage::disk('s3')->put($path, $pdf->output(), 'public');
        $path = Storage::disk('s3')->url($path);
        // }

        $customerName = $customer->first_name . ' ' . $customer->last_name;
        $filename = $customerName . '_' . ($salesContract->signature_details ? 'signed_agreement.pdf' : 'agreement.pdf');

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUserId;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = 'Welcome Email';
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUserId;
        PocomosEmailMessage::create($input);

        $file_details = $this->getFileInfo($path);
        //store file into document folder
        $file_input['path'] = $path;
        //store your file into database
        $file_input['filename'] = $filename;
        $file_input['mime_type'] = 'application/pdf';
        $file_input['file_size'] = $file_details['fileSize'] ?? 0;
        $file_input['active'] = true;
        $file_input['md5_hash'] =  '';
        $file =  OrkestraFile::create($file_input);

        PocomosEmailsAttachedFile::create(['email_id' => $email->id, 'file_id' => $file->id]);

        Mail::send('emails.dynamic_email_render', ['agreement_body' => $body], function ($message) use ($subject, $customerEmail, $from, $filename, $path) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
            $message->attachData(file_get_contents($path), $filename);
        });
    }

    /**Get file details */
    public function getFileInfo($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $data = curl_exec($ch);
        $fileSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'fileExists' => (int) $httpResponseCode == 200,
            'fileSize' => (int) $fileSize
        ];
    }

    public function getFirstJob($contract_id)
    {
        $jobs = PocomosJob::where('contract_id', $contract_id)->get()->toArray();
        usort($jobs, function ($a, $b) {
            return $a->date_scheduled > $b->date_scheduled;
        });
        return reset($jobs);
    }

    public function notifyRemoteCompletion($office, $customer, $notifyEmail = false)
    {
        // $description = sprintf('Customer <a href="%s">%s</a> has remotely completed an agreement', $this->generateUrl('customer_show', array('id' => $customer->id), true), $customer);

        $officeUsers = PocomosCompanyOfficeUser::where('office_id', $office->id)->get();
        //NEED TO DEVELOP AFTER LOGIN MODULE DEVELOPMENT
        // $officeUsers = $em->getRepository(OfficeUser::class)->findActiveEmployeesByOffice(
        // /* office */
        //     $office,
        //     /* roles */
        //     array(
        //         'ROLE_OWNER',
        //         'ROLE_BRANCH_MANAGER',
        //         'ROLE_SECRETARY'
        //     ),
        //     /* limit */
        //     30
        // );

        // foreach ($officeUsers as $officeUser) {
        //     $alert = $factory->createAssignedAlert($officeUser, $officeUser, 'Remote Completion', $description, new AlertPriority(AlertPriority::NORMAL));
        //     $em->persist($alert);

        //     if ($notifyEmail) {
        //         $emailResult = $this->get('pocomos.sales.factory.email')->createBuilder(
        //             EmailType::ASSIGNED_ALERT(),
        //             array('alert' => $alert)
        //         )->getResult();

        //         $emailResult->persistAll($em);
        //     }
        // }
    }

    /**
     * api for cancel Job
     * @return array
     */
    public function getOutstandingBalance($contract_id)
    {
        $outstandingBalance = 0.00;

        $pocomos_invoices = PocomosInvoice::where('contract_id', $contract_id)->where('status', '=', 'Cancelled')->orWhere('status', '=', 'Paid')->get();

        foreach ($pocomos_invoices as $val) {
            $outstandingBalance += $val->balance;
        }

        return $outstandingBalance;
    }

    public function getOutstandingBalance_customer($customer_id)
    {
        $sql = '
            SELECT id, IFNULL(outstanding, 0) AS outstanding, (credit + (due_total - balance_total)) AS credit
            FROM (
                SELECT cu.id,
                    SUM((
                      SELECT SUM(i.balance)
                      FROM pocomos_invoices i
                      LEFT JOIN pocomos_jobs j ON j.invoice_id = i.id
                      WHERE i.contract_id = sco.id
                        AND (i.status IN ("Due","Past due","Collections", "In collections"))
                        AND CASE
                            WHEN j.id IS NULL THEN 1
                            WHEN j.status <> "Cancelled" THEN 1
                            ELSE 0
                        END = 1
                    )) as outstanding,
                    IFNULL((a.balance/100), 0) as credit,
                    IFNULL((
                      SELECT SUM(ROUND(it.price * (1 + it.sales_tax) * 100) / 100)
                      FROM pocomos_invoice_items it
                      JOIN pocomos_invoices i ON it.invoice_id = i.id
                      LEFT JOIN pocomos_jobs j ON j.invoice_id = i.id
                      WHERE i.contract_id = sco.id
                        AND it.type <> \'Adjustment\'
                        AND (i.status IN ("Not sent","Sent") )
                        AND CASE
                            WHEN j.id IS NULL THEN 1
                            WHEN j.status <> "Cancelled" THEN 1
                            ELSE 0
                        END = 1
                    ), 0) as due_total,
                    IFNULL((
                      SELECT SUM(i.balance)
                      FROM pocomos_invoices i
                      LEFT JOIN pocomos_jobs j ON j.invoice_id = i.id
                      WHERE i.contract_id = sco.id
                        AND (i.status IN ("Not sent","Sent") )
                        AND CASE
                            WHEN j.id IS NULL THEN 1
                            WHEN j.status <> "Cancelled" THEN 1
                            ELSE 0
                        END = 1
                    ), 0) AS balance_total
                FROM pocomos_customers cu
                    JOIN pocomos_customer_sales_profiles csp on csp.customer_id = cu.id
                    JOIN orkestra_accounts a on csp.points_account_id = a.id
                    JOIN pocomos_contracts sco on (sco.profile_id = csp.id AND sco.status <> "Cancelled")
                WHERE cu.id  = ' . $customer_id . '
                GROUP BY cu.id
            ) t
        ';

        $results = DB::select(DB::raw($sql));


        $outstanding = 0;
        foreach ($results as $result) {
            $outstanding += $result->outstanding - $result->credit;
        }

        return $outstanding;
    }


    public function findAllBillableContractsByLocation($parent, $location, $includeChildren = false)
    {
        // dd($location);
        if ($includeChildren) {
            // dd(11);
            $contracts = DB::select(DB::raw("SELECT sco.id
                    FROM pocomos_pest_contracts as pco
                    JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
                    JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
                    JOIN pocomos_customers as pc ON pc.id = p.customer_id
                    WHERE p.customer_id = " . $location . " "));
        } else {
            // dd(11);

            $parentContracts = DB::select(DB::raw("SELECT sco.id
                    FROM pocomos_pest_contracts as pco
                    JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
                    JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
                    JOIN pocomos_customers as pc ON pc.id = p.customer_id
                    WHERE p.customer_id= " . $parent . " "));

            foreach ($parentContracts as $key => $value) {
                $w[] = $value->id;
            }

            $parentContracts = $this->convertArrayInStrings($w);

            $contracts = DB::select(DB::raw("SELECT sco.id
        FROM pocomos_pest_contracts as pco
        JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
        JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
        JOIN pocomos_customers as pc ON pc.id = p.customer_id
        WHERE p.customer_id = " . $location . " AND  pco.parent_contract_id  IN ($parentContracts)  "));
        }

        return $contracts;
    }

    public function findAllBillableContractsByCustomer($customerId, $includeChildren = false)
    {
        $contracts = DB::select(DB::raw('SELECT sco.id
        FROM pocomos_pest_contracts as pco
        JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
        JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
        JOIN pocomos_customers as pc ON pc.id = p.customer_id
        WHERE p.customer_id= ' . $customerId . ' '));


        if ($includeChildren) {
            $subs = PocomosSubCustomer::where('parent_id', $customerId)->pluck('child_id')->toArray();
            foreach ($subs as $sub) {
                $subcontract = DB::select(DB::raw('SELECT sco.id
        FROM pocomos_pest_contracts as pco
        JOIN pocomos_contracts as sco ON sco.id = pco.contract_id
        JOIN pocomos_customer_sales_profiles as p ON p.id = sco.profile_id
        JOIN pocomos_customers as pc ON pc.id = p.customer_id
        WHERE p.customer_id= ("$sub") '));

                $contracts = array_merge($contracts, $subcontract);
            }

            return $contracts;
        }

        $childContracts = DB::select(DB::raw('SELECT pco.id
        FROM pocomos_pest_contracts as pco
        WHERE  pco.parent_contract_id IN ("$parentContracts")   '));

        return array_merge($contracts, $childContracts);
    }

    public function getInvoiceHistoryJobs($startDate, $endDate, $pestControlContracts = array(), $minAmount = null, $maxAmount = null, $invoiceStatus = null)
    {
        /*
        $join_query = "SELECT *
        FROM pocomos_jobs as ppc
        JOIN pocomos_pest_contracts as pc ON  pc.id= ppc.contract_id
        JOIN pocomos_invoices as pj ON  pj.id= ppc.invoice_id
        WHERE  ppc.status = 'Complete' AND pj.date_due  >= '$startDate' AND pj.date_due  <= '$endDate' AND pc.active = 1";

        if ($pestControlContracts) {
            $val = $this->convertArrayInStrings($pestControlContracts);
            $join_query .= " AND pc.id IN  ($val)";
        }

        if ($minAmount > 0) {
            $join_query .= " AND (pj.amount_due + (pj.sales_tax * pj.amount_due))  >= '$minAmount'";
        }

        if ($maxAmount > 0) {
            $join_query .= " AND (pj.amount_due + (pj.sales_tax * pj.amount_due))  <= '$maxAmount'";
        }
        if ($invoiceStatus) {
            $join_query .= " AND pj.status = '$invoiceStatus'";
        }

        $res = DB::select(DB::raw($join_query));

        return $res;
        */

        $jobs = PocomosJob::select(
            '*',
            'pocomos_jobs.date_scheduled as job_date_scheduled',
            'pocomos_jobs.type as job_type',
            'pocomos_jobs.id as job_id',
            'pi.status as invoice_status',
            'pi.date_due as invoice_date_due',
            'pi.balance as invoice_balance',
            'pag.name as agreement_name',
            'pocomos_jobs.contract_id as contract_id_yn',
            'ppc.id as qqq'
        )
            ->join('pocomos_pest_contracts as ppc', 'pocomos_jobs.contract_id', 'ppc.id')
            ->join('pocomos_invoices as pi', 'pocomos_jobs.invoice_id', 'pi.id')
            ->join('pocomos_contracts as pc', 'ppc.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftJoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')

            ->where('ppc.active', 1)
            ->whereIn('ppc.id', $pestControlContracts)
            ->where('pocomos_jobs.status', 'Complete')
            ->whereBetween('pi.date_due', [$startDate, $endDate]);

        // dd($pestControlContracts);

        if ($minAmount > 0) {
            $jobs->where(DB::raw('(pi.amount_due + (pi.sales_tax * pi.amount_due))'), '>=', $minAmount);
        }

        if ($maxAmount > 0) {
            $jobs->where(DB::raw('(pi.amount_due + (pi.sales_tax * pi.amount_due))'), '<=', $maxAmount);
        }

        if ($invoiceStatus) {
            $jobs->where('pi.status', $invoiceStatus);
        }

        return $jobs;

        /*
        invoice_id
        invoice_date_due
        first/last name
        street
        job_type
        amount_due
        invoice_balance
        invoice_status
        agreement_name
        contract_id_yn
        */
    }

    public function getInvoiceHistoryInvoices($startDate, $endDate, $pestControlContracts = array(), $minAmount = null, $maxAmount = null, $invoiceStatus = null)
    {
        /*
        $qb_query = "SELECT *
        FROM pocomos_invoices as ppc
        JOIN pocomos_contracts as pc ON ppc.contract_id = pc.id
        JOIN pocomos_pest_contracts as pcc ON pcc.contract_id = pc.id
        WHERE  pc.active = 1 AND  pcc.active = 1  AND pc.status = 'Active'
        AND ppc.date_due  >= '$startDate' AND ppc.date_due <= '$endDate'
        ";

        // if ($pestControlContracts) {
        //     $val = $this->convertArrayInStrings($pestControlContracts);
        //     $qb_query .= " AND pc.id IN  ($val)";
        // }

        if ($minAmount > 0) {
            $qb_query .= " AND (ppc.amount_due + (ppc.sales_tax * ppc.amount_due))  >= '$minAmount'";
        }

        if ($maxAmount > 0) {
            $qb_query .= " AND (ppc.amount_due + (ppc.sales_tax * ppc.amount_due))  <= '$maxAmount'";
        }
        if ($invoiceStatus) {
            $qb_query .= " AND ppc.status = '$invoiceStatus'";
        }

        $res = DB::select(DB::raw($qb_query));

        return $res;
        */
        $invIds = PocomosInvoice::join('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')->pluck('invoice_id')->toArray();

        $query = PocomosInvoice::select(
            '*',
            'pocomos_invoices.id as invoice_id',
            'pocomos_invoices.date_due as invoice_date_due',
            'pocomos_invoices.status as invoice_status',
            'pocomos_invoices.balance as invoice_balance',
            'pag.name as agreement_name'
        )
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftjoin('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->join('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')

            ->whereNotIn('pocomos_invoices.id', $invIds)
            ->where('pc.active', 1)
            ->where('pc.status', 'Active')
            ->where('ppc.active', 1)
            ->whereIn('ppc.id', $pestControlContracts)
            ->whereBetween('pocomos_invoices.date_due', [$startDate, $endDate]);

        if ($minAmount > 0) {
            $query->where(DB::raw('(pocomos_invoices.amount_due + (pocomos_invoices.sales_tax * pocomos_invoices.amount_due))'), '>=', $minAmount);
        }

        if ($maxAmount > 0) {
            $query->where(DB::raw('(pocomos_invoices.amount_due + (pocomos_invoices.sales_tax * pocomos_invoices.amount_due))'), '<=', $maxAmount);
        }

        if ($invoiceStatus) {
            $query->where('pocomos_invoices.status', $invoiceStatus);
        }

        return $query;
    }

    /**outstandingBalance of customer*/
    public function outstandingBalance($outstandingBalance)
    {
        // outstanding is the sum of balances on all due or worse invoices
        // credit is account credit + sum of prepayments (total amount due - balance) on all not due invoices
        $cancelledStatus = config('constants.CANCELLED');
        $outstandingStatuses = $this->convertArrayInStrings(array(config('constants.DUE'), config('constants.PAST_DUE'), config('constants.COLLECTIONS'), config('constants.IN_COLLECTIONS')));
        $jobStatus = config('constants.CANCELLED');

        $sql = '
            SELECT id, IFNULL(outstanding, 0) AS outstanding, (credit) AS credit
            FROM (
                SELECT cu.id,
                    SUM((
                      SELECT SUM(i.balance)
                      FROM pocomos_invoices i
                      LEFT JOIN pocomos_jobs j ON j.invoice_id = i.id
                      WHERE i.contract_id = sco.id
                        AND (i.status IN (' . $outstandingStatuses . '))
                        AND CASE
                            WHEN j.id IS NULL THEN 1
                            WHEN j.status <> "' . $jobStatus . '" THEN 1
                            ELSE 0
                        END = 1
                    )) as outstanding,
                    IFNULL((a.balance/100), 0) as credit
                FROM pocomos_customers cu
                    JOIN pocomos_customer_sales_profiles csp on csp.customer_id = cu.id
                    JOIN orkestra_accounts a on csp.points_account_id = a.id
                    JOIN pocomos_contracts sco on (sco.profile_id = csp.id AND sco.status <> "' . $cancelledStatus . '")
                WHERE cu.id = (' . $outstandingBalance . ')
                GROUP BY cu.id
            ) t
        ';

        $results = DB::select(DB::raw($sql));

        $mapped = array();
        foreach ($results as $result) {
            $result->overall = $result->outstanding - $result->credit;
            $mapped[$result->id] = $result;
        }

        return $mapped;
    }

    public function getAgreementBody($contract_id, $customer_id)
    {
        $cus_detail = PocomosCustomer::with(['contact_address.primaryPhone', 'contact_address.altPhone', 'billing_address.primaryPhone', 'billing_address.altPhone', 'sales_profile.points_account', 'sales_profile.autopay_account', 'sales_profile.external_account', 'sales_profile.sales_people.office_user_details.user_details', 'sales_profile.sales_people.office_user_details.profile_details', 'sales_profile.sales_people.office_user_details.company_details', 'sales_profile.contract_details' => function ($q) use ($contract_id) {
            $q->whereId($contract_id);
        }, 'sales_profile.contract_details.agreement_details', 'sales_profile.contract_details.tax_details', 'sales_profile.contract_details.pest_contract_details', 'sales_profile.contract_details.pest_contract_details.pest_agreement_details', 'sales_profile.contract_details.pest_contract_details.service_type_details', 'sales_profile.contract_details.pest_contract_details.contract_tags', 'sales_profile.contract_details.pest_contract_details.contract_tags.tag_details', 'notes_details.note', 'sales_profile.contract_details.pest_contract_details.targeted_pests.pest', 'sales_profile.contract_details.signature_details', 'state_details'])->findOrFail($customer_id);

        $contract_details = $cus_detail->sales_profile['contract_details'][0] ?? array();

        $contract_type_id = $contract_details['agreement_id'] ?? null;
        $contract_id = $contract_details['id'] ?? null;
        $contract_start_date = date('Y-m-d', strtotime($contract_details['date_start']));
        $service_type_id = $contract_details['pest_contract_details']['service_type_id'];
        $tax_code_id = $contract_details['tax_code_id'] ?? null;
        $technician_id = $contract_details['pest_contract_details']['technician_id'] ?? null;
        $service_address = $cus_detail->contact_address ?? array();
        $billing_information = $cus_detail->billing_address ?? array();
        $pricing_information = $contract_details['agreement_details'];
        $signature_path = $contract_details['signature_details']['path'] ?? '';

        $agreement = PocomosAgreement::findOrFail($contract_type_id);
        $service_type_res = PocomosPestContractServiceType::findOrFail($service_type_id);
        $tax_code = PocomosTaxCode::findOrFail($tax_code_id);
        $addendum = $contract_details['pest_contract_details']['addendum'];
        $pest_contract_id = $contract_details['pest_contract_details']['id'];

        $pest_contarct = PocomosPestContract::where('contract_id', $contract_id)->first();
        $contract = PocomosContract::findOrFail($pest_contarct->contract_id);
        $pest_agreement = PocomosPestAgreement::findOrFail($pest_contarct->agreement_id);

        $pests_ids = PocomosPestContractsPest::where('contract_id', $pest_contract_id)->pluck('pest_id')->toArray();
        $pests_name = PocomosPest::whereIn('id', array_merge($pests_ids))->pluck('name')->toArray();

        try {
            $agreement_body = $agreement->agreement_body;

            $variables = PocomosFormVariable::where('enabled', true)->where('active', true)->get();

            $res = DB::select(DB::raw("SELECT ofd.path as 'technician_photo', oud.first_name as 'technician_name', pco.fax as 'office_fax', pco.customer_portal_link, cld.path as 'company_logo', CONCAT(pad.suite, ', ' , pad.street, ', ' , pad.city, ', ' , pad.postal_code) as 'office_address', CONCAT(tad.suite, ', ' , tad.street, ', ' , tad.city, ', ' , tad.postal_code) as 'technician_address'
            FROM pocomos_technicians AS pt
            JOIN pocomos_company_office_users AS cou ON pt.user_id = cou.id
            JOIN pocomos_company_office_user_profiles AS oup ON cou.profile_id = oup.id
            JOIN orkestra_files AS ofd ON oup.photo_id = ofd.id
            JOIN orkestra_users AS oud ON oup.user_id = oud.id
            JOIN pocomos_company_offices AS pco ON cou.office_id = pco.id
            JOIN orkestra_files AS cld ON pco.logo_file_id = cld.id
            JOIN pocomos_addresses AS pad ON pco.contact_address_id = pad.id
            JOIN pocomos_addresses AS tad ON pt.routing_address_id = tad.id
            WHERE pt.id = '$technician_id' AND pt.active = 1"));

            $res = $res[0] ?? array();

            $technician = $res->technician_name ?? '';
            $service_type = $service_type_res->name ?? '';
            $office_address = $res->office_address ?? '';
            $office_phone = $res->office_fax ?? '';
            $service_addr = $service_address['suite'] . ', ' . $service_address['street'] . ', ' . $service_address['city'] . ', ' . $service_address['state'] . ', ' . $service_address['postal'];
            $company_logo = $res->company_logo ?? '';
            $billing_address = $billing_information['suite'] . ', ' . $billing_information['street'] . ', ' . $billing_information['city'] . ', ' . $billing_information['state'] . ', ' . $billing_information['postal'];
            $selected_pests = implode(', ', $pests_name);
            $agreement_length = $agreement['length'] ?? 'N/A';
            $technician_photo = $res->technician_photo ?? '';
            $technician_bio = $res->technician_address ?? '';
            $initial_price_tax = $tax_code->tax_rate ?? '';
            $contract_value_tax = $tax_code->tax_rate ?? '';
            $initial_price_with_tax = $pricing_information['initial_price'] - ($pricing_information['initial_price'] * $tax_code->tax_rate / 100);
            $customer_portal_link = $res->customer_portal_link ?? '';

            foreach ($variables as $var) {
                if (@unserialize($var['type'])) {
                    $types_res = unserialize($var['type']);
                    if ($types_res !== false) {
                        if (in_array('Pest Agreement', $types_res)) {
                            $variable_name = $var['variable_name'] ?? null;

                            if (strpos($agreement_body, $variable_name) !== false) {
                                if ($variable_name === 'customer_name') {
                                    $value = $service_address['first_name'] . ' ' . $service_address['last_name'];
                                } elseif ($variable_name === 'service_address') {
                                    $value = $service_addr;
                                } elseif ($variable_name === 'customer_service_address') {
                                    $value = $service_addr;
                                } elseif ($variable_name === 'service_city') {
                                    $value = $service_address['city'] ?? '';
                                } elseif ($variable_name === 'service_state') {
                                    $value = $service_address['state'] ?? '';
                                } elseif ($variable_name === 'service_zip') {
                                    $value = $service_address['postal'] ?? '';
                                } elseif ($variable_name === 'customer_phone') {
                                    $value = $service_address['phone'] ?? '';
                                } elseif ($variable_name === 'customer_email') {
                                    $value = $service_address['email'] ?? '';
                                } elseif ($variable_name === 'contract_start_date') {
                                    $value = $contract_start_date;
                                } elseif ($variable_name === 'customer_signature' && $signature_path) {
                                    $value = '<img height="100px" width="200px" src="' . storage_path('app') . '/' . $signature_path . '">';
                                } elseif ($variable_name === 'salesperson_signature') {
                                    // $value = '<img height="100px" width="200px" src="'.storage_path('app'). '/' . $signature_path.'">';
                                } elseif ($variable_name === 'balance') {
                                    $value = 0.00;
                                } elseif ($variable_name === 'credit') {
                                    $value = 0.00;
                                } elseif ($variable_name === 'invoice_numbers') {
                                    $value = '';
                                } elseif ($variable_name === 'technician') {
                                    $value = $technician;
                                } elseif ($variable_name === 'service_date') {
                                    $value = $scheduling_information['initial_date'] ?? '';
                                } elseif ($variable_name === 'service_time') {
                                    $value = '';
                                } elseif ($variable_name === 'service_frequency') {
                                    $value = implode(', ', unserialize($pest_agreement['service_frequency']));
                                } elseif ($variable_name === 'service_type') {
                                    $value = $service_type ?? '';
                                } elseif ($variable_name === 'office_address') {
                                    $value = $office_address ?? '';
                                } elseif ($variable_name === 'office_phone') {
                                    $value = $office_phone;
                                } elseif ($variable_name === 'service_address') {
                                    $value = $service_addr;
                                } elseif ($variable_name === 'company_logo') {
                                    $value = $company_logo ?? '';
                                } elseif ($variable_name === 'customer_last_name') {
                                    $value = $service_address['last_name'];
                                } elseif ($variable_name === 'customer_service_address') {
                                    $value = $service_address;
                                } elseif ($variable_name === 'customer_billing_address') {
                                    $value = $billing_address;
                                } elseif ($variable_name === 'agreement_price_info') {
                                    $value = $pricing_information['initial_price'] ?? 0.00;
                                } elseif ($variable_name === 'auto_pay_checkbox') {
                                    $value = $billing_information['is_enroll_auto_pay'] ? 'Autopay' : 'No Autopay';
                                } elseif ($variable_name === 'selected_pests') {
                                    $value = $selected_pests;
                                } elseif ($variable_name === 'agreement_length') {
                                    $value = $agreement_length;
                                } elseif ($variable_name === 'total_contract_value') {
                                    $value = $pricing_information['initial_price'] ?? 0.00;
                                } elseif ($variable_name === 'customer_company_name') {
                                    $value = $service_address['company_name'] ?? '';
                                } elseif ($variable_name === 'next_service') {
                                    $value = '';
                                } elseif ($variable_name === 'contract_addendum') {
                                    $value = $addendum ?? '';
                                } elseif ($variable_name === 'customer_portal_link') {
                                    $value = $customer_portal_link;
                                } elseif ($variable_name === 'contract_recurring_price') {
                                    $value = $pricing_information['recurring_price'];
                                } elseif ($variable_name === 'technician_photo') {
                                    $value = $technician_photo;
                                } elseif ($variable_name === 'technician_bio') {
                                    $value = $technician_bio;
                                } elseif ($variable_name === 'contract_initial_price') {
                                    $value = $pricing_information['initial_price'];
                                } elseif ($variable_name === 'contract_total_contract_value_tax') {
                                    $value = $contract_value_tax;
                                } elseif ($variable_name === 'contract_initial_price_with_tax') {
                                    $value = $initial_price_with_tax;
                                } elseif ($variable_name === 'contract_initial_discount') {
                                    $value = $pricing_information['initial_discount'];
                                } elseif ($variable_name === 'contract_initial_price_tax') {
                                    $value = $initial_price_tax;
                                } elseif ($variable_name === 'customer_last_service_date') {
                                    $value = '';
                                } elseif ($variable_name === 'contract_recurring_discount') {
                                    $value = $pricing_information['discount_per_job'] ?? 0;
                                } else {
                                    $value = '';
                                }
                                $agreement_body = str_replace('{{ ' . $variable_name . ' }}', $value, $agreement_body);
                            }
                        }
                    }
                }
            }
            return $agreement_body;
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
    }

    /**Checking the logged in user hase access to given role */
    public function isGranted($roles)
    {
        if (auth()->user()) {
            if (!is_array($roles)) {
                $roles = array($roles);
            }
            //Logged in user id
            $user_id = auth()->user()->id;

            //Get user base active groups
            $groups_ids = OrkestraUserGroup::where('user_id', $user_id)->pluck('group_id')->toArray();
            $user_roles = OrkestraGroup::whereIn('id', $groups_ids)->pluck('role')->toArray();

            foreach ($user_roles as $val) {
                if (in_array($val, $roles)) {
                    return true;
                }
            }
        }
        return false;
    }

    /*Checking the logged in user has able to access current office or not */
    public function userHasAccessToOffice($officeUser, $office)
    {
        // Administrator is always allowed
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // If the OfficeUser is an owner or sales admin
        if (
            $this->isGranted('ROLE_OWNER')
            || $this->isGranted('ROLE_SALES_ADMIN')
        ) {
            // ... and belongs to the office's children, or its parents
            $officeUserOffice = $officeUser->company_details;
            // $office->getChildOffices->contains($officeUserOffice) ||
            if (
                $office->getParentdOffices->id === $officeUserOffice->id
            ) {
                return true;
            }
        }

        // If the OfficeUser is an owner or salesperson or technician
        if (
            $this->isGranted('ROLE_OWNER')
            || $this->isGranted('ROLE_SALESPERSON')
            || $this->isGranted('ROLE_TECHNICIAN')
            || $this->isGranted('ROLE_SECRETARY')
            || $this->isGranted('ROLE_BRANCH_MANAGER')
            || $this->isGranted('ROLE_COLLECTIONS')
        ) {
            // ... and belongs to the office
            if ($office->id === $officeUser->company_details->id) {
                return true;
            }

            $profile = $officeUser->profile_details;

            // ... and any of the OfficeUser's siblings belong to the office
            foreach ($profile->pocomosuserprofiles as $sibling) {
                if ($sibling->id === $officeUser->id) {
                    continue;
                }

                if ($office->id === $sibling->company_details->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Switches the current office
     *
     * @param $office_id
     */
    public function switchOffice($office_id)
    {
        $office = PocomosCompanyOffice::findOrFail($office_id);
        $parent_id = null;
        if ($office->parent_id) {
            $parent_id = $office->parent_id;
        }
        $office_setting = PocomosOfficeSetting::whereOfficeId($office_id)->firstOrFail();
        $office_user = PocomosCompanyOfficeUser::whereUserId(auth()->user()->id)->firstOrFail();

        $this->requiresAccessToOffice($office_id);

        $user = auth()->user();
        $data[config('constants.ACTIVE_OFFICE_ID')] = $office_id ?? 'N/A';
        $data[config('constants.ACTIVE_OFFICE_USER_ID')] = $office_user->id ?? 'N/A';
        $data[config('constants.ACTIVE_THEME_KEY')] = $office_setting->theme ?? 'N/A';

        Session::put($data);
        Session::save();
        return true;
    }

    /**
     * Ensures the current user has access to the given office
     */
    public function requiresAccessToOffice($office_id)
    {
        $office = PocomosCompanyOffice::findOrFail($office_id);
        $officeUser = PocomosCompanyOfficeUser::where('user_id', auth()->user()->id)->firstOrFail();

        if (!$this->userHasAccessToOffice($officeUser, $office)) {
            throw new \Exception(__('strings.message', ['message' => 'User not have access.']));
        }
    }

    /**Set impersonate user details */
    public function setImpersonating($user_id)
    {
        $user = OrkestraUser::findOrFail(auth()->user()->id);
        Session::put(config('constants.PREVIOUS_LOGGEDIN_USER'), auth()->user()->id);
        // dd(auth()->user()->pocomos_company_office_user->office_id);

        Session::put(config('constants.PREVIOUS_USER_OFFICE_ID'), auth()->user()->pocomos_company_office_user->office_id);
        Session::save();
        //Delete all old tokens
        $user->tokens()->delete();
        $impersonateUser = OrkestraUser::findOrFail($user_id);
        //Create new token
        return $impersonateUser->createToken('MyAuthApp')->plainTextToken;
    }

    /**Set impersonate user details */
    public function leaveImpersonatMode()
    {
        $user = OrkestraUser::findOrFail(auth()->user()->id);
        $pre_user_id = Session::get(config('constants.PREVIOUS_LOGGEDIN_USER'));
        //Delete all old tokens
        $user->tokens()->delete();
        $user = OrkestraUser::findOrFail($pre_user_id);
        //Create new token
        Session::forget(config('constants.PREVIOUS_LOGGEDIN_USER'));
        return $user->createToken('MyAuthApp')->plainTextToken;
    }

    public function createBuilder_PdfFactory($type, $params)
    {
        // dd(config('constants.PDF_TYPES'));

        // dd(88);

        if (!in_array($type, config('constants.PDF_TYPES'))) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to locate an PdfType for ' . $type . '']));
        }

        // dd($type);

        if ($type == 'Invoice') {
            $pdf = $this->getInvoiceBasePdf($params);
            $pdf = $pdf->download('billing_summary_' . strtotime('now') . '.pdf');
        } elseif ($type == 'Billing Summary') {
            $pdf = $this->buildPdf_billingSummary($params);
        }

        return $pdf;
    }

    /**Get invoice base pdf content */
    public function buildPdf_billingSummary($params)
    {
        // dd($params['contract']->contract);

        $getContract = PocomosContract::first();
        // dd($contract);
        $contract = $params['contract']->contract ?? $getContract;  //not pest contract
        $serviceContract = $contract->pest_contract_details;
        // dd(11);

        $paid = $params['paid'];
        $profile = $contract->profile_details;
        $office = $profile->office_details;

        // if($office){
        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $office->id)->firstOrFail();

        // dd($paid);

        if ($invoiceConfig->use_legacy_layout) {
            // Use the old generator
            $params = array(
                'contract' => $contract,
                'paid' => $paid,
            );

            // return $this->legacyServiceHistorySummaryGenerator($params);
        }
        // }


        // dd($contract);

        // if(isset($contract->invoices)){
        $invoices = $contract->invoices->filter(function ($invoice) use ($paid) {
            if ($invoice->status == 'Not sent') {
                return false;
            }

            if ($invoice->status == 'Cancelled') {
                return false;
            }

            $refVal = $invoice->status == 'Paid';
            if ($paid !== 'Paid') {
                return !$refVal;
            }

            return $refVal;
        });
        // }

        // dd($invoices);

        // $agreement = PocomosAgreement::first();
        // $serviceCustomer = PocomosCustomer::first();

        // if($contract){
        $jobs = $contract->pest_contract_details->jobs_details;

        $job = $this->getLastJob_pestControlContract($jobs);

        $serviceCustomer = $this->getCustomer_Job($job);

        $lifetimeRevenue = $this->getLifetimeRevenue($contract->pest_contract_details);

        $outstandingBalance = $this->getOutstandingBalance_customer($serviceCustomer->id);

        $agreement = $contract->agreement_details;
        $publicPaymentLink = '';

        $agreement = $contract->agreement_details;
        // }

        // if($profile){
        $billingCustomer = $profile->customer;
        // }


        $invoiceIntro = $this->renderDynamicTemplate($agreement->invoice_intro, null, $serviceCustomer, $serviceContract, null, true);
        // dd(11);
        $parameters = array(
            'serviceCustomer' => $serviceCustomer ?? null,
            'billingCustomer' => $billingCustomer ?? null,
            'invoices' => $invoices ?? null,
            'lifetime_revenue' => $lifetimeRevenue ?? null,
            'office' => $office ?? null,
            'invoiceConfig' => $invoiceConfig ?? null,
            'paid_type' =>  $paid,
            'outstanding' => $outstandingBalance ?? null,
            'contract' => $contract,
            'invoiceIntro' => $invoiceIntro,
            'portalLink' => $publicPaymentLink ?? ''
        );

        // dd($parameters['billingCustomer']->state_details->balance_credit);

        $pdf = PDF::loadView('pdf.BillingSummary.index', compact('parameters'));

        // dd(11);


        return $pdf->download('billing_summary_' . strtotime('now') . '.pdf');
    }

    public function findOneByIdAndCustomerPCCRepository($ppcId, $custId)
    {
        return  PocomosPestContract::select('*', 'pocomos_pest_contracts.id')
            ->join('pocomos_contracts as pc', 'pocomos_pest_contracts.contract_id', 'pc.id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->where('pcsp.customer_id', $custId)
            ->where('pocomos_pest_contracts.id', $ppcId)
            ->first();
    }

    public function getLastJob_pestControlContract($jobs)
    {
        $jobs = $jobs->toArray();

        usort($jobs, function ($a, $b) {
            return $a['date_scheduled'] > $b['date_scheduled'];
        });
        return end($jobs);
    }

    public function getCustomer_Job($job)
    {
        // dd($job);
        $job = PocomosJob::findorfail($job['id']);

        if (!is_null($job->pest_contract->contract)) {
            return $job->pest_contract->contract->profile_details->customer;
        }
        if (!is_null($job->termite_inspection)) {
            return $job->termite_inspection->customer;
        }
    }

    // doGenerate
    public function legacyServiceHistorySummaryGenerator($params)
    {
        // dd(11);
        $contract = $params['contract'];    //pest contract
        $paid = $params['paid'];

        //findOnlyCompletedServicesForContract
        $jobs = $this->createFindContractServicesQueryBuilder($contract->id)
            ->orderBy('pocomos_jobs.date_completed', 'desc')
            ->where('pocomos_jobs.status', 'Complete')
            ->get();

        //findMiscInvoicesByContract, createMiscInvoiceQueryBuilder
        // $invIds = PocomosInvoice::join('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')->pluck('invoice_id')->toArray();
        $query = PocomosInvoice::select(
            '*',
            'pocomos_invoices.id',
            'pocomos_invoices.status as invoice_status',
            'pocomos_invoices.balance as invoice_balance',
            'pcu.email',
            'pc.id as contract_id'
        )
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            // ->whereNotIn('pocomos_invoices.id', $invIds)
            ->where('pc.id', $contract->contract_id);

        if ($paid !== null) {
            $paidExpr = $paid == 'paid' ? $query->where('pocomos_invoices.status', 'Paid') : $query->where('pocomos_invoices.status', '!=', 'Paid');
        }

        $miscInvoices = $query->get();

        $invoices = array();
        $validJobs = array();

        // dd($jobs);

        foreach ($jobs as $job) {
            if (isset($job->invoice)) {

                $invoice = $job->invoice;
                // dd($invoice);

                if ($invoice->status == 'Not sent') {
                    continue;
                }
                // dd(11);

                if ($invoice->status == 'Cancelled') {
                    continue;
                }

                if ($paid == 'unpaid') {
                    if ($invoice->status != 'Paid') {
                        continue;
                    }
                } else {
                    if ($invoice->status == 'Paid') {
                        continue;
                    }
                }

                $validJobs[] = $job;

                $pdf = $this->createBuilder_PdfFactory('Invoice', $invoice);

                $invoices[] = $pdf;
            }
        }

        foreach ($miscInvoices as $miscInvoice) {
            $pdf = $this->createBuilder_PdfFactory('Invoice', $miscInvoice);

            $invoices[] = $pdf;
        }

        $summaryParameters = array(
            'validJobs' => $validJobs,
            'miscInvoices' => $miscInvoices,
            'pest_contract' => $contract,
            'agreement' => $contract->contract->agreement_details ?? null,
            'office' => $contract->contract->agreement_details->office_details ?? null,
            'customer' => $contract->contract->profile_details->customer ?? null,
            'contract' => $contract->contract,
        );

        dd(66);

        return $finalPdf = $this->createBuilder_PdfFactory('Billing Summary', $params);

        // return $finalPdf = $this->fileContentBaseUploadS3(config('constants.INVOICES'), $finalPdf);
    }

    /**Get invoice base pdf content */
    //buildPdf - InvoiceType.php
    public function getInvoiceBasePdf($invoice)
    {
        // dd($invoice);
        $invoice = PocomosInvoice::findOrFail($invoice->id);
        $profile = $invoice->contract->profile_details;
        $contract = $invoice->contract;
        $office = $profile->office_details;
        $customer = $invoice->contract->profile_details->customer_details;
        $customerPhone = $customer->sales_profile->phone_numbers;
        $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $office->id)->firstOrFail();
        $customerState = PocomosCustomerState::where('customer_id', $customer->id)->firstOrFail();
        $outstandingBalance = $customerState->balance_outstanding ?? 00;
        $taxCode = explode('-', $contract->pest_contract_details->contract_details->tax_details->code);
        if ($invoiceConfig->use_legacy_layout) {
            // Use the old generator
            $params = array(
                'office' => $office,
                'customer' => $contract->profile_details->customer_details,
                'agreement' => $contract->agreement_details,
                'pest_contract' => $contract->pest_contract_details,
                'invoice' => $invoice,
                'job' => array()
            );
            if ($job = $invoice->job) {
                $params['job'] = $job;


                // dd(33);
                return $this->legacyInvoiceGeneratorGenerate($params);
            }
            // dd(44);
            return $this->legacyMiscInvoiceGenerator($params);
        }

        // dd(1111);

        $products = array(); // We'll only load these if we need them.

        $job = $invoice->job;
        $serviceCustomer = $billingCustomer = $profile->customer_details;
        $serviceContract = $billingContract = $contract->pest_contract_details;

        $technicianSignature = false;
        if ($job !== null) {
            $serviceCustomer = $job->contract->contract_details->profile_details->customer_details;
            $serviceContract = $job->contract;

            if (!(config('constants.COMPLETE') === $job->status)) {
                $products = DB::select(DB::raw("SELECT p.*
                FROM pocomos_pest_products AS p
                WHERE p.active = true AND p.enabled = true AND p.office_id = $office->id ANd p.shows_on_invoices = true
                ORDER BY p.id ASC"));
            }

            $technicianSignature = DB::select(DB::raw("SELECT s.path
            FROM pocomos_technicians AS t
            JOIN pocomos_routes AS r ON r.technician_id = t.id
            JOIN pocomos_route_slots AS sl ON r.id = sl.route_id
            JOIN pocomos_jobs AS j ON sl.id = j.slot_id
            JOIN pocomos_company_office_users AS ou ON t.user_id = ou.id
            JOIN pocomos_company_office_user_profiles AS p ON ou.profile_id = p.id
            JOIN orkestra_files AS s ON p.signature_id = s.id
            WHERE j.id = $job->id AND r.office_id = $office->id"));
        }

        if (is_array($products)) {
            $products = array_slice($products, 0, 7);
        }

        $pestConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();

        $hash = 0;
        if ($profile->office_user_detail === null) {
            // $hash = $this->container->get('orkestra.application.helper.hashed_entity')->create($billingCustomer, new \DateTime('+24 hours'));
            // $this->entityManager->persist($hash);
            // $this->entityManager->flush();
        }

        $lastJob = $this->getLastJob($serviceContract);

        $autoPayAccount = $profile->autopay_account;
        $currentYear = date("Y");
        $currentMonth = date("m");
        $autoPayAccountExpired = false;

        if ($autoPayAccount) {
            if ($autoPayAccount && date('Y', strtotime($autoPayAccount->card_exp_year)) <= $currentYear && date('m', strtotime($autoPayAccount->card_exp_month)) <= $currentMonth) {
                $autoPayAccountExpired = true;
            }
        }

        $agreement = $contract->agreement_details;
        $invoiceIntro = $this->renderDynamicTemplate($agreement->invoice_intro, null, $serviceCustomer, $serviceContract, null, true);
        // dd($invoiceIntro);
        // $this->dynamicParameterHelper->getCustomerProfilePaymentLink($serviceCustomer)
        $publicPaymentLink = '';
        $technician = null;

        if ($job) {
            $technician = DB::select(DB::raw("SELECT t.*
            FROM pocomos_technicians AS t
            JOIN pocomos_routes AS r ON r.technician_id = t.id
            JOIN pocomos_route_slots AS sl ON r.id = sl.route_id
            JOIN pocomos_jobs AS j ON sl.id = j.slot_id
            WHERE j.id = $job->id AND r.office_id = $office->id"));
        }

        $technicianPhotoSrc = null;
        if ($technician) {
            $technician = PocomosTechnician::findOrFail($technician->id);
            $photo = $technician->user_detail->profile_details->photo_details;
            if ($photo) {
                try {
                    // $base64 = base64_encode(file_get_contents($photo->path));
                    // $technicianPhotoSrc = "data:{$photo->getMimeType()};base64,$base64";
                    $technicianPhotoSrc = $photo->path;
                } catch (\Exception $e) {
                    //We do nothing. This is purely for this class not to crash on dev
                    //Never has been a problem on prod.
                }
            }
        }

        $areas = PocomosSalesArea::where('office_id', $office->id)->get();
        // $areas = $this->entityManager->getRepository(Area::class)->findBy(array('showOnInvoice' => true, 'office' => $office));

        $select_query = "SELECT cf.* FROM pocomos_custom_fields AS cf ";
        $join_query = "JOIN pocomos_custom_field_configuration AS cfc ON cfc.id = cf.custom_field_configuration_id
        JOIN pocomos_pest_office_settings AS oc ON cfc.office_configuration_id = oc.id ";
        $where_query = "WHERE cfc.active = true AND cfc.show_on_precompleted_invoice = true AND oc.office_id = $office->id ";
        $group_query = " GROUP BY cfc.id";

        if ($contract && $contract->id) {
            $where_query .= " ANd cf.pest_control_contract_id = $contract->id ";
        }

        $merge_sql = $select_query . '' . $join_query . '' . $where_query . '' . $group_query;
        // dd(11);
        $customFields = DB::select(DB::raw($merge_sql));

        $parameters = array(
            'serviceCustomer' => $serviceCustomer,
            'serviceContract' => $serviceContract,
            'billingCustomer' => $billingCustomer,
            'billingContract' => $billingContract,
            'invoice' => $invoice,
            'job' => $job,
            'lastJob' => $lastJob,
            'phoneNumbers' => $customerPhone,
            'office' => $office,
            'invoiceConfig' => $invoiceConfig,
            'products' => $products,
            'pestConfig' => $pestConfig,
            'outstanding' => $outstandingBalance,
            'technicianSignature' => $technicianSignature,
            'hash' => $hash,
            'autoPayAccountExpired' => $autoPayAccountExpired,
            'invoiceIntro' => $invoiceIntro,
            'technician' => $technician,
            'technician_photo_src' => $technicianPhotoSrc,
            'taxCode' => $taxCode[0],
            'areas' => $areas,
            'customFields' => $customFields,
            'portalLink' => $publicPaymentLink
        );

        // $pdf = PDF::loadView('pdf.Invoice.index', compact('parameters'));
        $pdf = PDF::loadView('pdf.invoice', compact('parameters'));

        return $pdf;
    }

    /**Get multiple invoice base pdf content */
    public function getMutipleInvoiceBasePdf($invoices)
    {
        $i = 0;
        $parameters['invoice'] = [];
        foreach ($invoices as $oneinvoice) {
            $invoice = PocomosInvoice::findOrFail($oneinvoice);
            $profile = $invoice->contract->profile_details;
            $contract = $invoice->contract;
            $office = $profile->office_details;
            $customer = $invoice->contract->profile_details->customer_details;
            $customerPhone = $customer->sales_profile->phone_numbers;
            $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $office->id)->firstOrFail();
            $customerState = PocomosCustomerState::where('customer_id', $customer->id)->firstOrFail();
            $outstandingBalance = $customerState->balance_outstanding ?? 00;
            $taxCode = explode('-', $contract->pest_contract_details->contract_details->tax_details->code);
            if ($invoiceConfig->use_legacy_layout) {
                // Use the old generator
                $params = array(
                    'office' => $office,
                    'customer' => $contract->profile_details->customer_details,
                    'agreement' => $contract->agreement_details,
                    'pest_contract' => $contract->pest_contract_details,
                    'invoice' => $invoice,
                );
                if ($job = $invoice->job) {
                    $params['job'] = $job;

                    return $this->legacyInvoiceGeneratorGenerate($params);
                }

                return $this->legacyMiscInvoiceGenerator($params);
            }

            $products = array(); // We'll only load these if we need them.

            $job = $invoice->job;
            $serviceCustomer = $billingCustomer = $profile->customer_details;
            $serviceContract = $billingContract = $contract->pest_contract_details;

            $technicianSignature = false;
            if ($job !== null) {
                $serviceCustomer = $job->contract->contract_details->profile_details->customer_details;
                $serviceContract = $job->contract;

                // return $office->id;

                if (!(config('constants.COMPLETE') === $job->status)) {
                    $products = DB::select(DB::raw("SELECT p.*
                FROM pocomos_pest_products AS p
                WHERE p.active = true AND p.enabled = true AND p.office_id = $office->id ANd p.shows_on_invoices = true
                ORDER BY p.id ASC"));
                }


                $technicianSignature = DB::select(DB::raw("SELECT s.path
            FROM pocomos_technicians AS t
            JOIN pocomos_routes AS r ON r.technician_id = t.id
            JOIN pocomos_route_slots AS sl ON r.id = sl.route_id
            JOIN pocomos_jobs AS j ON sl.id = j.slot_id
            JOIN pocomos_company_office_users AS ou ON t.user_id = ou.id
            JOIN pocomos_company_office_user_profiles AS p ON ou.profile_id = p.id
            JOIN orkestra_files AS s ON p.signature_id = s.id
            WHERE j.id = $job->id AND r.office_id = $office->id"));
            }


            if (is_array($products)) {
                $products = array_slice($products, 0, 7);
            }

            $pestConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();

            $hash = 0;
            if ($profile->office_user_detail === null) {
                // $hash = $this->container->get('orkestra.application.helper.hashed_entity')->create($billingCustomer, new \DateTime('+24 hours'));
                // $this->entityManager->persist($hash);
                // $this->entityManager->flush();
            }

            $lastJob = $this->getLastJob($serviceContract);

            $autoPayAccount = $profile->autopay_account;
            $currentYear = date("Y");
            $currentMonth = date("m");
            $autoPayAccountExpired = false;

            if ($autoPayAccount) {
                if ($autoPayAccount && date('Y', strtotime($autoPayAccount->card_exp_year)) <= $currentYear && date('m', strtotime($autoPayAccount->card_exp_month)) <= $currentMonth) {
                    $autoPayAccountExpired = true;
                }
            }


            $agreement = $contract->agreement_details;
            $invoiceIntro = $this->renderDynamicTemplate($agreement->invoice_intro, null, $serviceCustomer, $serviceContract, null, true);
            // $this->dynamicParameterHelper->getCustomerProfilePaymentLink($serviceCustomer)
            $publicPaymentLink = '';
            $technician = null;

            if ($job) {
                $technician = DB::select(DB::raw("SELECT t.*
            FROM pocomos_technicians AS t
            JOIN pocomos_routes AS r ON r.technician_id = t.id
            JOIN pocomos_route_slots AS sl ON r.id = sl.route_id
            JOIN pocomos_jobs AS j ON sl.id = j.slot_id
            WHERE j.id = $job->id AND r.office_id = $office->id"));
            }
            // dd($technician[0]->id);

            $technicianPhotoSrc = null;
            if ($technician) {
                $technician = PocomosTechnician::findOrFail($technician[0]->id);
                $photo = $technician->user_detail->profile_details->photo_details;
                if ($photo) {
                    try {
                        // $base64 = base64_encode(file_get_contents($photo->path));
                        // $technicianPhotoSrc = "data:{$photo->getMimeType()};base64,$base64";
                        $technicianPhotoSrc = $photo->path;
                    } catch (\Exception $e) {
                        //We do nothing. This is purely for this class not to crash on dev
                        //Never has been a problem on prod.
                    }
                }
            }


            $areas = PocomosSalesArea::where('office_id', $office->id)->get();
            // $areas = $this->entityManager->getRepository(Area::class)->findBy(array('showOnInvoice' => true, 'office' => $office));

            $select_query = "SELECT cf.* FROM pocomos_custom_fields AS cf ";
            $join_query = "JOIN pocomos_custom_field_configuration AS cfc ON cfc.id = cf.custom_field_configuration_id
        JOIN pocomos_pest_office_settings AS oc ON cfc.office_configuration_id = oc.id ";
            $where_query = "WHERE cfc.active = true AND cfc.show_on_precompleted_invoice = true AND oc.office_id = $office->id ";
            $group_query = " GROUP BY cfc.id";

            if ($contract && $contract->id) {
                $where_query .= " ANd cf.pest_control_contract_id = $contract->id ";
            }

            $merge_sql = $select_query . '' . $join_query . '' . $where_query . '' . $group_query;
            $customFields = DB::select(DB::raw($merge_sql));

            $parameters['invoice'][$i]['serviceCustomer'] =  $serviceCustomer;
            $parameters['invoice'][$i]['serviceContract'] = $serviceContract;
            $parameters['invoice'][$i]['billingCustomer'] = $billingCustomer;
            $parameters['invoice'][$i]['billingContract'] = $billingContract;
            $parameters['invoice'][$i]['invoice'] = $invoice;
            $parameters['invoice'][$i]['job'] = $job;
            $parameters['invoice'][$i]['lastJob'] = $lastJob;
            $parameters['invoice'][$i]['phoneNumbers'] = $customerPhone;
            $parameters['invoice'][$i]['office'] = $office;
            $parameters['invoice'][$i]['invoiceConfig'] = $invoiceConfig;
            $parameters['invoice'][$i]['products'] = $products;
            $parameters['invoice'][$i]['pestConfig'] = $pestConfig;
            $parameters['invoice'][$i]['outstanding'] = $outstandingBalance;
            $parameters['invoice'][$i]['technicianSignature'] = $technicianSignature;
            $parameters['invoice'][$i]['hash'] = $hash;
            $parameters['invoice'][$i]['autoPayAccountExpired'] = $autoPayAccountExpired;
            $parameters['invoice'][$i]['invoiceIntro'] = $invoiceIntro;
            $parameters['invoice'][$i]['technician'] = $technician;
            $parameters['invoice'][$i]['technician_photo_src'] = $technicianPhotoSrc;
            $parameters['invoice'][$i]['taxCode'] =  $taxCode[0];
            $parameters['invoice'][$i]['areas'] = $areas;
            $parameters['invoice'][$i]['customFields'] = $customFields;
            $parameters['invoice'][$i]['portalLink'] = $publicPaymentLink;
            // dd(667);

            $i = $i + 1;
        }

        $pdf = PDF::loadView('pdf.Invoice.index', compact('parameters'));
        // $pdf = PDF::loadView('pdf.serviceHistory', $parameters);

        return $pdf;
    }


    public function legacyInvoiceGeneratorGenerate($parameters, $options = array())
    {
        $office_id = $parameters['office']['id'];
        $parameters['officeSettings'] = PocomosPestOfficeSetting::where('office_id', $office_id)->first();
        $parameters['invoiceSettings'] = PocomosPestInvoiceSetting::where('office_id', $office_id)->first();
        $parameters['responsibleCustomer'] = $parameters['invoice']->contract->profile_details->customer_details;
        $customerState = PocomosCustomerState::where('customer_id', $parameters['responsibleCustomer']['id'])->firstOrFail();
        $parameters['outstanding_balance'] = $customerState->balance_outstanding ?? 00;
        $parameters['service_type'] = $parameters['pest_contract']->service_type_details->description;
        $job = $parameters['job'];
        $parameters['pests'] = count($job->jobs_pests) > 0 ? $job->jobs_pests : $parameters['pest_contract']->targeted_pests;
        $productLineItems = array();

        foreach ($job->get_job_products as $job_product) {
            $productLineItems[] = isset($job_product->invoice_item) ?? $job_product->invoice_item->id;
        }
        $jobProducts = $job->get_job_products;

        $invoice = $parameters['invoice'];
        $invoiceItems = $invoice->invoice_items;

        $i = 0;
        foreach ($invoiceItems as $job_item) {
            if (in_array($job_item->id, $productLineItems)) {
                unset($invoiceItems[$i]);
            }
            $invoiceItems[] = $job_item->invoice_item;
            $i = $i + 1;
        }

        $products = DB::select(DB::raw("SELECT p.*
        FROM pocomos_pest_products AS p
        WHERE p.active = true ANd p.enabled = true ANd p.office_id = $office_id
        ORDER BY p.id ASC"));

        $continue = true;
        while ($continue) {
            // $this->getPageTemplate($builder, $parameters, $options);
            if (($parameters['job']['status'] && config('constants.COMPLETE') === $parameters['job']['status']) || ($parameters['job']['status'] && config('constants.CANCELLED') === $parameters['job']['status'])) {
                // dd($parameters);
                $parameters['invoiceItems'] = $invoiceItems;
                $parameters['jobProducts'] = $jobProducts;
                $pdf = PDF::loadView('pdf.Invoice.invoice_body', compact('parameters'));
                // dd(44);
                $continue = false;
            } else {
                // dd(11);
                $parameters['products'] = $products;
                $pdf = PDF::loadView('pdf.invoice_body_with_inventory', compact('parameters'));
                $continue = false;
            }
        }

        return $pdf;
    }

    public function fileContentBaseUploadS3($folder, $pdf_content, $id = null)
    {
        $url =  $folder . preg_replace('/[^A-Za-z0-9\-]/', '', $id) . '.pdf';
        Storage::disk('s3')->put($url, $pdf_content->output(), 'public');
        $path = Storage::disk('s3')->url($url);
        return $path;
    }

    public function legacyMiscInvoiceGenerator($parameters, $options = array())
    {
        $office_id = $parameters['office']['id'];
        $parameters['officeSettings'] = PocomosPestOfficeSetting::where('office_id', $office_id)->first();
        $parameters['invoiceSettings'] = PocomosPestInvoiceSetting::where('office_id', $office_id)->first();
        $parameters['responsibleCustomer'] = $parameters['invoice']->contract->profile_details->customer_details;
        $customerState = PocomosCustomerState::where('customer_id', $parameters['responsibleCustomer']['id'])->firstOrFail();
        $parameters['outstanding_balance'] = $customerState->balance_outstanding ?? 00;
        $parameters['service_type'] = $parameters['pest_contract']->service_type_details->description;
        $job = $parameters['job'];
        $parameters['pests'] = count($job->jobs_pests ?? []) > 0 ? $job->jobs_pests ?? [] : $parameters['pest_contract']->targeted_pests;
        $logo = $parameters['office']->logo->full_path;
        $agreement = $parameters['agreement'];
        $parameters['invoiceIntro'] = $this->renderDynamicTemplate($agreement->invoice_intro, null, $parameters['customer'], null, null, true);

        $pdf = PDF::loadView('pdf.Invoice.legacy_misc_invoice', compact('parameters'));

        return $pdf;
    }

    public function getMutipleinvoiceHistoryPdf($invoices)
    {
        $i = 0;
        $parameters['invoice'] = [];
        foreach ($invoices as $oneinvoice) {
            $invoice = PocomosInvoice::findOrFail($oneinvoice);
            $profile = $invoice->contract->profile_details;
            $contract = $invoice->contract;
            $office = $profile->office_details;
            $customer = $invoice->contract->profile_details->customer_details;
            $customerPhone = $customer->sales_profile->phone_numbers;
            $invoiceConfig = PocomosPestInvoiceSetting::where('office_id', $office->id)->firstOrFail();
            $customerState = PocomosCustomerState::where('customer_id', $customer->id)->firstOrFail();
            $outstandingBalance = $customerState->balance_outstanding ?? 00;
            $taxCode = explode('-', $contract->pest_contract_details->contract_details->tax_details->code);
            if ($invoiceConfig->use_legacy_layout) {
                // Use the old generator
                $params = array(
                    'office' => $office,
                    'customer' => $contract->profile_details->customer_details,
                    'agreement' => $contract->agreement_details,
                    'pest_contract' => $contract->pest_contract_details,
                    'invoice' => $invoice,
                );
                if ($job = $invoice->job) {
                    $params['job'] = $job;

                    return $this->legacyInvoiceGeneratorGenerate($params);
                }

                return $this->legacyMiscInvoiceGenerator($params);
            }

            $products = array(); // We'll only load these if we need them.

            $job = $invoice->job;
            $serviceCustomer = $billingCustomer = $profile->customer_details;
            $serviceContract = $billingContract = $contract->pest_contract_details;

            $technicianSignature = false;
            if ($job !== null) {
                $serviceCustomer = $job->contract->contract_details->profile_details->customer_details;
                $serviceContract = $job->contract;

                if (!(config('constants.COMPLETE') === $job->status)) {
                    $products = DB::select(DB::raw("SELECT p.*
                FROM pocomos_pest_products AS p
                WHERE p.active = true AND p.enabled = true AND p.office_id = $office->id ANd p.shows_on_invoices = true
                ORDER BY p.id ASC"));
                }

                $technicianSignature = DB::select(DB::raw("SELECT s.path
            FROM pocomos_technicians AS t
            JOIN pocomos_routes AS r ON r.technician_id = t.id
            JOIN pocomos_route_slots AS sl ON r.id = sl.route_id
            JOIN pocomos_jobs AS j ON sl.id = j.slot_id
            JOIN pocomos_company_office_users AS ou ON t.user_id = ou.id
            JOIN pocomos_company_office_user_profiles AS p ON ou.profile_id = p.id
            JOIN orkestra_files AS s ON p.signature_id = s.id
            WHERE j.id = $job->id AND r.office_id = $office->id"));
            }

            if (is_array($products)) {
                $products = array_slice($products, 0, 7);
            }

            $pestConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();

            $hash = 0;
            if ($profile->office_user_detail === null) {
                // $hash = $this->container->get('orkestra.application.helper.hashed_entity')->create($billingCustomer, new \DateTime('+24 hours'));
                // $this->entityManager->persist($hash);
                // $this->entityManager->flush();
            }

            $lastJob = $this->getLastJob($serviceContract);

            $autoPayAccount = $profile->autopay_account;
            $currentYear = date("Y");
            $currentMonth = date("m");
            $autoPayAccountExpired = false;

            if ($autoPayAccount) {
                if ($autoPayAccount && date('Y', strtotime($autoPayAccount->card_exp_year)) <= $currentYear && date('m', strtotime($autoPayAccount->card_exp_month)) <= $currentMonth) {
                    $autoPayAccountExpired = true;
                }
            }

            $agreement = $contract->agreement_details;
            $invoiceIntro = $this->renderDynamicTemplate($agreement->invoice_intro, null, $serviceCustomer, $serviceContract, null, true);
            // $this->dynamicParameterHelper->getCustomerProfilePaymentLink($serviceCustomer)
            $publicPaymentLink = '';
            $technician = null;

            if ($job) {
                $technician = DB::select(DB::raw("SELECT t.*
            FROM pocomos_technicians AS t
            JOIN pocomos_routes AS r ON r.technician_id = t.id
            JOIN pocomos_route_slots AS sl ON r.id = sl.route_id
            JOIN pocomos_jobs AS j ON sl.id = j.slot_id
            WHERE j.id = $job->id AND r.office_id = $office->id"));
            }

            $technicianPhotoSrc = null;
            if ($technician) {
                $technician = PocomosTechnician::findOrFail($technician->id);
                $photo = $technician->user_detail->profile_details->photo_details;
                if ($photo) {
                    try {
                        // $base64 = base64_encode(file_get_contents($photo->path));
                        // $technicianPhotoSrc = "data:{$photo->getMimeType()};base64,$base64";
                        $technicianPhotoSrc = $photo->path;
                    } catch (\Exception $e) {
                        //We do nothing. This is purely for this class not to crash on dev
                        //Never has been a problem on prod.
                    }
                }
            }

            $areas = PocomosSalesArea::where('office_id', $office->id)->get();
            // $areas = $this->entityManager->getRepository(Area::class)->findBy(array('showOnInvoice' => true, 'office' => $office));

            $select_query = "SELECT cf.* FROM pocomos_custom_fields AS cf ";
            $join_query = "JOIN pocomos_custom_field_configuration AS cfc ON cfc.id = cf.custom_field_configuration_id
        JOIN pocomos_pest_office_settings AS oc ON cfc.office_configuration_id = oc.id ";
            $where_query = "WHERE cfc.active = true AND cfc.show_on_precompleted_invoice = true AND oc.office_id = $office->id ";
            $group_query = " GROUP BY cfc.id";

            if ($contract && $contract->id) {
                $where_query .= " ANd cf.pest_control_contract_id = $contract->id ";
            }

            $merge_sql = $select_query . '' . $join_query . '' . $where_query . '' . $group_query;
            $customFields = DB::select(DB::raw($merge_sql));

            $parameters['invoice'][$i]['serviceCustomer'] =  $serviceCustomer;
            $parameters['invoice'][$i]['serviceContract'] = $serviceContract;
            $parameters['invoice'][$i]['billingCustomer'] = $billingCustomer;
            $parameters['invoice'][$i]['billingContract'] = $billingContract;
            $parameters['invoice'][$i]['invoice'] = $invoice;
            $parameters['invoice'][$i]['job'] = $job;
            $parameters['invoice'][$i]['lastJob'] = $lastJob;
            $parameters['invoice'][$i]['phoneNumbers'] = $customerPhone;
            $parameters['invoice'][$i]['office'] = $office;
            $parameters['invoice'][$i]['invoiceConfig'] = $invoiceConfig;
            $parameters['invoice'][$i]['products'] = $products;
            $parameters['invoice'][$i]['pestConfig'] = $pestConfig;
            $parameters['invoice'][$i]['outstanding'] = $outstandingBalance;
            $parameters['invoice'][$i]['technicianSignature'] = $technicianSignature;
            $parameters['invoice'][$i]['hash'] = $hash;
            $parameters['invoice'][$i]['autoPayAccountExpired'] = $autoPayAccountExpired;
            $parameters['invoice'][$i]['invoiceIntro'] = $invoiceIntro;
            $parameters['invoice'][$i]['technician'] = $technician;
            $parameters['invoice'][$i]['technician_photo_src'] = $technicianPhotoSrc;
            $parameters['invoice'][$i]['taxCode'] =  $taxCode[0];
            $parameters['invoice'][$i]['areas'] = $areas;
            $parameters['invoice'][$i]['customFields'] = $customFields;
            $parameters['invoice'][$i]['portalLink'] = $publicPaymentLink;
            $parameters['invoice']['serviceCustomer'] =  $serviceCustomer;

            $i = $i + 1;
        }

        $pdf = PDF::loadView('pdf.invoiceExportSummary', $parameters);

        return $pdf;
    }

    public function getLastJob($contract)
    {
        $jobs = PocomosJob::where('contract_id', $contract->id)->get()->toArray();
        if (!$jobs) {
            return null;
        }

        // dateScheduled DESC
        usort($jobs, function ($a, $b) {
            return $a['date_scheduled'] < $b['date_scheduled'];
        });

        foreach ($jobs as $job) {
            if (
                in_array($job['type'], array(config('constants.REGULAR'), config('constants.INITIAL')))
                && $job['status'] != config('constants.CANCELLED')
            ) {
                return $job;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getFilename($dir, $sub_dir = null, $file_name, $extension)
    {
        $path = $dir . DIRECTORY_SEPARATOR;

        if ($sub_dir) {
            $path .= $sub_dir . DIRECTORY_SEPARATOR;
        }

        return $path . $file_name . $extension;
    }

    /*Send remote completion email */
    public function sendRemoteCompletionEmail($customer_id, $contract_id, $officeUserId)
    {
        $data = DB::select(DB::raw("SELECT CONCAT(pc.first_name, ' ' , pc.last_name ) as 'customer_name', co.name as 'office_name', pa.name as 'agreement_name', co.fax as 'office_fax', pc.email as 'customer_email'
        FROM pocomos_customers AS pc
        JOIN pocomos_customer_sales_profiles AS csp ON pc.id = csp.customer_id
        JOIN pocomos_company_offices AS co ON csp.office_id = co.id
        JOIN pocomos_contracts AS pcd ON csp.id = pcd.profile_id
        JOIN pocomos_agreements AS pa ON pcd.agreement_id = pa.id
        WHERE pc.id = '$customer_id' AND pc.active = 1
        ORDER BY co.id ASC"));

        if (!count($data)) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        }
        $data = $data[0];

        $pest_contract = PocomosPestContract::where('contract_id', $contract_id)->firstOrFail();
        $profile = $pest_contract->contract_details->profile_details;
        $office = $profile->office_details;

        $email = Mail::to($data->customer_email);
        $email->send(new RemoteCompletionCustomer($data, $pest_contract->id));

        $from = unserialize($office->email);
        $from = $from[0] ?? '';

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUserId;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = 'Remote Customer Completion';
        $email_input['body'] = '';
        $email_input['subject'] = __('email_subject.remote_completion_customer');
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $data->customer_email;
        $input['recipient_name'] = $data->customer_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUserId;
        PocomosEmailMessage::create($input);

        return true;
    }

    /**Get office email */
    public function getOfficeEmail($office_id)
    {
        $office = PocomosCompanyOffice::findOrFail($office_id);
        $office_email = unserialize($office->email);
        $from = null;

        if (isset($office_email[0])) {
            $from = $office_email[0];
        } else {
            throw new \Exception(__('strings.message', ['message' => 'Office email is required for sending emails!']));
        }

        return $from;
    }

    public function confirmJob($job)
    {
        if (!($slot = $job->route_detail)) {
            throw new \Exception(__('strings.message', ['message' => 'Job must be assigned to a route in order to be confirmed']));
        }

        /*if ($slot->isConfirmed()) {
            return $result;
        }*/

        if (config('constants.HARD') == $slot->schedule_type || config('constants.HARD_CONFIRMED') == $slot->schedule_type) {
            $slot->schedule_type = config('constants.HARD_CONFIRMED');
        } else {
            $slot->schedule_type = config('constants.CONFIRMED');
        }
        $slot->save();

        $emailResult = $this->sendRouteSlotBaseEmail($slot);

        return true;
    }

    public function sendRouteSlotBaseEmail($slot)
    {
        $route = $slot->route_detail;

        if (!$route || !$slot->job_detail) {
            return;
        }

        $contract = $slot->job_detail->contract;
        $profile = $contract->contract_details->profile_details;
        $office = $route->office_detail;
        $from = $this->getOfficeEmail($office->id);

        $pestConfig = PocomosPestOfficeSetting::where('office_id', $office->id)->firstOrFail();
        //$initialJob = $pestContract->getInitialJob();
        $customer = $slot->job_detail->contract->contract_details->profile_details->customer_details;
        $contract = $slot->job_detail ? $slot->job_detail->contract : null;
        $timeWindow = '';
        $officeId = auth()->user()->pocomos_company_office_user->office_id;
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId(auth()->user()->id)->first();

        if (!$slot->anytime && !$pestConfig->only_show_date) {
            if ($pestConfig->include_time_window) {
                $startTime = (clone ($slot))->time_begin;
                $endTime = (clone ($slot))->time_begin;
                $endTime = new DateTime($endTime);
                // dd($startTime);
                $date = new DateTime($startTime);
                $startTime = $date->format('h:i:s');

                $endTime->modify('+' . $pestConfig->time_window_length . ' hours');
                $endTime = $endTime->format('h:i:s');

                $timeWindow = sprintf('between %s and %s', date('h:i A', strtotime($startTime)), date('h:i A', strtotime($endTime)));
            } else {
                $timeWindow = date('h:i A', strtotime($slot->time_begin));
            }
        }

        $params = array(
            'config' => $pestConfig,
            'profile' => $profile,
            'slot' => $slot,
            'timeWindow' => $timeWindow,
        );

        $subject = $profile->office_details->name . ' has scheduled your service';

        $body = " ";

        // dd($params);

        $body .= view('pdf.assign_template', ['params' => $params])->render();

        $body .= $this->renderDynamicTemplate($pestConfig->assign_message, null, $customer, $contract, $slot->job_detail);

        if($customer->email !== ''){
            Mail::send('pdf.assign_template', ['params' => $params], function ($message) use ($subject, $customer, $from) {
                $message->from($from);
                $message->to($customer->email);
                $message->subject($subject);
            });
        }

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUser->id;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = config('constants.JOB_CONFIRMED');
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUser->id;
        PocomosEmailMessage::create($input);

        return true;
    }

    /**
     * Clone office
     */
    public function cloneOffice($office)
    {
        $cloneOffice = clone $office;
        unset($cloneOffice['id']);

        if ($logo = $cloneOffice->logo_file_id) {
            $file = OrkestraFile::findOrFail($logo);
            $cloneOffice->logo_file_id = $cloneOffice->logo_file_id;
            $path = $file->path;
            // $path .= uniqid('', true);dd($path);
            // if (file_exists($path)) {
            //     copy($file->getPath(), $path);
            // }
            // $this->writeProperty($logo, 'path', $path);
        }

        /**Contact address */
        $contactAddress = ($cloneOffice->coontact_address ? $cloneOffice->coontact_address->toArray() : array());
        if ($contactAddress) {
            unset($contactAddress['id'], $contactAddress['primary_phone'], $contactAddress['alt_phone'], $contactAddress['region']);

            $phone = PocomosPhoneNumber::findOrFail($contactAddress['phone_id'])->toArray();
            unset($phone['id']);
            $clonePhone = PocomosPhoneNumber::create($phone);

            $altPhone = PocomosPhoneNumber::findOrFail($contactAddress['alt_phone_id'])->toArray();
            unset($altPhone['id']);
            $cloneAltPhone = PocomosPhoneNumber::create($altPhone);

            $contactAddress['phone_id'] = $clonePhone->id ?? null;
            $contactAddress['alt_phone_id'] = $cloneAltPhone->id ?? null;
            $contactAddress = PocomosAddress::create($contactAddress);
        }
        /**End contact address */

        /**Billing address */
        $billingAddress = ($cloneOffice->billing_address ? $cloneOffice->billing_address->toArray() : array());
        if ($billingAddress) {
            unset($billingAddress['id'], $billingAddress['primary_phone'], $billingAddress['alt_phone'], $billingAddress['region']);

            $phone = PocomosPhoneNumber::findOrFail($billingAddress['phone_id'])->toArray();
            unset($phone['id']);
            $clonePhone = PocomosPhoneNumber::create($phone);

            $altPhone = PocomosPhoneNumber::findOrFail($billingAddress['alt_phone_id'])->toArray();
            unset($altPhone['id']);
            $cloneAltPhone = PocomosPhoneNumber::create($altPhone);

            $billingAddress['phone_id'] = $clonePhone->id ?? null;
            $billingAddress['alt_phone_id'] = $cloneAltPhone->id ?? null;
            $billingAddress = PocomosAddress::create($billingAddress);
        }
        /**End billing address */

        /**Routing address */
        $routingAddress = ($cloneOffice->routing_address ? $cloneOffice->routing_address->toArray() : array());
        if ($routingAddress) {
            unset($routingAddress['id'], $routingAddress['primary_phone'], $routingAddress['alt_phone'], $routingAddress['region']);

            $phone = PocomosPhoneNumber::findOrFail($routingAddress['phone_id']);
            if ($phone) {
                $phone = $phone->toArray();
                unset($phone['id']);
                $clonePhone = PocomosPhoneNumber::create($phone);
                $routingAddress['phone_id'] = $clonePhone->id ?? null;
            }

            $altPhone = PocomosPhoneNumber::findOrFail($routingAddress['alt_phone_id']);
            if ($altPhone) {
                $altPhone = $altPhone->toArray();
                unset($altPhone['id']);
                $cloneAltPhone = PocomosPhoneNumber::create($altPhone);
                $routingAddress['alt_phone_id'] = $cloneAltPhone->id ?? null;
            }

            $routingAddress = PocomosAddress::create($routingAddress);
        }
        /**End routing address */
        $cloneOffice = $cloneOffice->toArray();
        $cloneOffice['logo_file_id'] = null;
        $cloneOffice['contact_address_id'] = ($contactAddress ? $contactAddress->id : null);
        $cloneOffice['billing_address_id'] = ($billingAddress ? $billingAddress->id : null);
        $cloneOffice['routing_address_id'] = ($routingAddress ? $routingAddress->id : null);
        $cloneOffice = PocomosCompanyOffice::create($cloneOffice);

        $this->cloneSalesConfig($office, $cloneOffice);
        $this->cloneSalesTrackerConfig($office, $cloneOffice);
        $this->clonePestConfig($office, $cloneOffice);
        $this->cloneRecruitConfig($office, $cloneOffice);
        $this->cloneDefaultSchedule($office, $cloneOffice);
        $this->cloneInvoiceConfig($office, $cloneOffice);
        $this->cloneQuickbooksConfig($office, $cloneOffice);
        $this->cloneInventory($office, $cloneOffice);
        $this->cloneMisc($office, $cloneOffice);
        $this->cloneTypes($office, $cloneOffice);
        $this->cloneLetters($office, $cloneOffice);
        $this->cloneAgreements($office, $cloneOffice);

        return $cloneOffice;
    }

    /**Clone office sales configuration */
    public function cloneSalesConfig($otherOffice, $newOffice)
    {
        $originalConfig = PocomosOfficeSetting::where('office_id', $otherOffice->id)->first();

        if (!$originalConfig) {
            return null;
        }

        $salesConfig = clone $originalConfig;
        unset($salesConfig['id']);
        $salesConfig['office_id'] = $newOffice->id;
        $salesConfig = $salesConfig->toArray();

        if ($creds = ($originalConfig->ach_cred_details ? $originalConfig->ach_cred_details->toArray() : array())) {
            unset($creds['id']);
            $creds['date_created'] = date('Y-m-d H:i:s');
            $neeCreds = OrkestraCredential::create($creds);
            $salesConfig['cash_credentials_id'] = $neeCreds->id;
        }

        if ($creds = ($originalConfig->card_cred_details ? $originalConfig->card_cred_details->toArray() : array())) {
            unset($creds['id']);
            $creds['date_created'] = date('Y-m-d H:i:s');
            $neeCreds = OrkestraCredential::create($creds);
            $salesConfig['card_credentials_id'] = $neeCreds->id;
        }

        if ($creds = ($originalConfig->check_cred_details ? $originalConfig->check_cred_details->toArray() : array())) {
            unset($creds['id']);
            $creds['date_created'] = date('Y-m-d H:i:s');
            $neeCreds = OrkestraCredential::create($creds);
            $salesConfig['check_credentials_id'] = $neeCreds->id;
        }

        if ($creds = ($originalConfig->cash_cred_details ? $originalConfig->cash_cred_details->toArray() : array())) {
            unset($creds['id']);
            $creds['date_created'] = date('Y-m-d H:i:s');
            $neeCreds = OrkestraCredential::create($creds);
            $salesConfig['cash_credentials_id'] = $neeCreds->id;
        }

        if ($creds = ($originalConfig->external_cred_details ? $originalConfig->external_cred_details->toArray() : array())) {
            unset($creds['id']);
            $creds['date_created'] = date('Y-m-d H:i:s');
            $neeCreds = OrkestraCredential::create($creds);
            $salesConfig['external_credentials_id'] = $neeCreds->id;
        }
        $salesConfig = PocomosOfficeSetting::create($salesConfig);

        return $salesConfig;
    }

    /**Clone sale tracking configuration */
    public function cloneSalesTrackerConfig($otherOffice, $newOffice)
    {
        $originalConfig = PocomosSalestrackerOfficeSetting::where('office_id', $otherOffice->id)->first();

        if (!$originalConfig) {
            return null;
        }

        $salesTrackerConfig = clone $originalConfig;
        $salesTrackerConfig = $salesTrackerConfig->toArray();
        unset($salesTrackerConfig['id']);
        $salesTrackerConfig['office_id'] = $newOffice->id;

        if ($config = $originalConfig->initial_service_alert_config_id) {
            $notificationConfig = PocomosNotificationSetting::findOrFail($config);
            $notificationConfig = clone $notificationConfig;
            $notificationConfig = $notificationConfig->toArray();
            unset($notificationConfig['id']);
            $notificationConfig = PocomosNotificationSetting::create($notificationConfig);
            $salesTrackerConfig['initial_service_alert_config_id'] = $notificationConfig->id;
        }
        $salesTrackerConfig = PocomosSalestrackerOfficeSetting::create($salesTrackerConfig);

        return $salesTrackerConfig;
    }

    /**Clone pest office settings configuration */
    public function clonePestConfig($otherOffice, $newOffice)
    {
        $originalConfig = PocomosPestOfficeSetting::where('office_id', $otherOffice->id)->first();

        if (!$originalConfig) {
            return null;
        }

        $pestConfig = clone $originalConfig;
        $pestConfig = $pestConfig->toArray();
        unset($pestConfig['id']);
        $pestConfig['office_id'] = $newOffice->id;
        $pestConfig = PocomosPestOfficeSetting::create($pestConfig);
        // if ($config = $originalConfig->getDefaultOptimizationConfig()) {
        //     $pestConfig->setDefaultOptimizationConfig(clone $config);
        //     $pestConfig->getDefaultOptimizationConfig()->setRelated($newOffice);
        // }

        foreach (PocomosPestContractServiceType::where('office_id', $otherOffice->id)->get() as $config) {
            $serviceTypeConfig = $config->toArray();
            unset($serviceTypeConfig['id']);
            $serviceTypeConfig['office_id'] = $newOffice->id;
            PocomosPestContractServiceType::create($serviceTypeConfig);
            $serviceTypeConfig = array();
        }

        foreach (PocomosCustomFieldConfiguration::where('office_configuration_id', $originalConfig->id)->get() as $config) {
            $pestCustomConfig = $config->toArray();
            unset($pestCustomConfig['id']);
            $pestCustomConfig['office_configuration_id'] = $pestConfig->id;
            PocomosCustomFieldConfiguration::create($pestCustomConfig);
            $pestCustomConfig = array();
        }

        foreach (range(1, 5) as $number) {
            $chemsheetConfig['office_config_id'] = $pestConfig->id;
            $chemsheetConfig['name'] = sprintf('Auto-fill %s', $number);
            $chemsheetConfig['active'] = false;

            PocomosPestOfficeDefaultChemsheetSettings::create($chemsheetConfig);
            $chemsheetConfig = array();
        }

        foreach (PocomosBestfitThreshold::where('office_configuration_id', $originalConfig->id)->get() as $threshold) {
            $threshold = $threshold->toArray();
            unset($threshold['id']);
            $threshold['office_configuration_id'] = $pestConfig->id;
            PocomosBestfitThreshold::create($threshold);
            $chemsheetConfig = array();
        }

        return $pestConfig;
    }

    /**Clone recruit settings configuration */
    public function cloneRecruitConfig($otherOffice, $newOffice)
    {
        $originalConfig = PocomosRecruitingOfficeConfiguration::where('office_id', $otherOffice->id)->first();

        if (!$originalConfig) {
            return null;
        }

        $recruitConfig = (clone $originalConfig)->toArray();
        unset($recruitConfig['id']);
        $recruitConfig['office_id'] = $newOffice->id;
        $recruitConfig = PocomosRecruitingOfficeConfiguration::create($recruitConfig);

        foreach (PocomosRecruitCustomFieldConfiguration::where('office_configuration_id', $originalConfig->id)->get() as $config) {
            $recuitConfig = $config->toArray();
            unset($recuitConfig['id']);
            $recuitConfig['office_configuration_id'] = $recruitConfig->id;
            PocomosRecruitCustomFieldConfiguration::create($recuitConfig);
            $recuitConfig = array();
        }

        foreach (PocomosRecruitingRegion::where('office_configuration_id', $originalConfig->id)->get() as $region) {
            $recuitConfig = $config->toArray();
            unset($recuitConfig['id']);
            $recuitConfig['office_configuration_id'] = $recruitConfig->id;
            PocomosRecruitCustomFieldConfiguration::create($recuitConfig);
            $recuitConfig = array();
        }

        foreach (PocomosRecruitStatus::where('recruiting_office_configuration_id', $originalConfig->id)->get() as $recruitStatusDetail) {
            $recruitStatus = $recruitStatusDetail->toArray();
            unset($recruitStatus['id']);
            $recruitStatus['recruiting_office_configuration_id'] = $recruitConfig->id;
            PocomosRecruitStatus::create($recruitStatus);
            $recruitStatus = array();
        }

        return $recruitConfig;
    }

    /**Clone office default schedule settings */
    public function cloneDefaultSchedule($otherOffice, $newOffice)
    {
        $schedule = PocomosSchedule::where('office_id', $otherOffice->id)->first();
        if (!$schedule) {
            return;
        }

        $cloneSchedule = (clone $schedule)->toArray();
        unset($cloneSchedule['id']);
        $cloneSchedule['office_id'] = $newOffice->id;
        $schedule = PocomosSchedule::create($cloneSchedule);

        return $schedule;
    }

    /**Clone office invoice configuration */
    public function cloneInvoiceConfig($otherOffice, $newOffice)
    {
        $originalConfig = PocomosPestInvoiceSetting::where('office_id', $otherOffice->id)->first();

        if (!$originalConfig) {
            return null;
        }

        $invoiceConfig = (clone $originalConfig)->toArray();
        unset($invoiceConfig['id']);
        $invoiceConfig['office_id'] = $newOffice->id;
        $invoiceConfig = PocomosPestInvoiceSetting::create($invoiceConfig);
        return $invoiceConfig;
    }

    /**Clone quick books configuration */
    public function cloneQuickbooksConfig($otherOffice, $newOffice)
    {
        $quickBookSetting = PocomosPestQuickbooksSetting::where('office_id', $otherOffice->id)->first();

        if (!$quickBookSetting) {
            return;
        }

        $quickbooksConfig = (clone $quickBookSetting)->toArray();
        unset($quickbooksConfig['id']);
        $quickbooksConfig['office_id'] = $newOffice->id;
        $quickbooksConfig = PocomosPestQuickbooksSetting::create($quickbooksConfig);
        return $quickbooksConfig;
    }

    /**Clone office inventory details */
    public function cloneInventory($otherOffice, $newOffice)
    {
        $distMap = array();

        $products = PocomosPestProduct::where('office_id', $otherOffice->id)->get();

        foreach ($products as $product) {
            $cloneProduct = (clone $product)->toArray();
            unset($cloneProduct['id']);
            $cloneProduct['office_id'] = $newOffice->id;

            if ($distributor = $product->distributor_detail) {
                $hash = spl_object_hash($distributor);
                $cloneDistributor = (clone $distributor)->toArray();
                unset($cloneDistributor['id']);
                $cloneDistributor['office_id'] = $newOffice->id;
                $cloneDistributor = PocomosDistributor::create($cloneDistributor);
                $cloneProduct['distributor_id'] = $cloneDistributor->id;
                $distMap[$hash] = $distributor;
            }
            $cloneProduct = PocomosPestProduct::create($cloneProduct);
        }

        $distributors = PocomosDistributor::where('office_id', $otherOffice->id)->get();
        foreach ($distributors as $distributor) {
            $hash = spl_object_hash($distributor);
            if (isset($distMap[$hash])) {
                continue;
            }

            $newDistributor = (clone $distributor)->toArray();
            unset($newDistributor['id']);
            $newDistributor['office_id'] = $newOffice->id;
            $newDistributor = PocomosDistributor::create($newDistributor);
        }

        $vehicles = PocomosVehicle::where('office_id', $otherOffice->id)->get();
        foreach ($vehicles as $vehicle) {
            $cloneVehicle = (clone $vehicle)->toArray();
            unset($cloneVehicle['id']);
            $cloneVehicle['office_id'] = $newOffice->id;
            $cloneVehicle = PocomosVehicle::create($cloneVehicle);
        }

        return;
    }

    /**Clone office misc pest details */
    public function cloneMisc($otherOffice, $newOffice)
    {
        $pests = PocomosPest::where('office_id', $otherOffice->id)->get();
        foreach ($pests as $pest) {
            $clonePest = (clone $pest)->toArray();
            unset($clonePest['id']);
            $clonePest['office_id'] = $newOffice->id;
            $clonePest = PocomosPest::create($clonePest);
        }

        $areas = PocomosArea::where('office_id', $otherOffice->id)->get();
        foreach ($areas as $area) {
            $cloneArea = (clone $area)->toArray();
            unset($cloneArea['id']);
            $cloneArea['office_id'] = $newOffice->id;
            $cloneArea = PocomosArea::create($cloneArea);
        }

        $counties = PocomosCounty::where('office_id', $otherOffice->id)->get();
        foreach ($counties as $county) {
            $cloneCounty = (clone $county)->toArray();
            unset($cloneCounty['id']);
            $cloneCounty['office_id'] = $newOffice->id;
            $cloneCounty = PocomosCounty::create($cloneCounty);
        }

        $tags = PocomosTag::where('office_id', $otherOffice->id)->get();
        foreach ($tags as $tag) {
            $cloneTag = (clone $tag)->toArray();
            unset($cloneTag['id']);
            $cloneTag['office_id'] = $newOffice->id;
            $cloneTag = PocomosTag::create($cloneTag);
        }

        $taxCodes = PocomosTaxCode::where('office_id', $otherOffice->id)->get();
        foreach ($taxCodes as $taxCode) {
            $cloneTaxCode = (clone $taxCode)->toArray();
            unset($cloneTaxCode['id']);
            $cloneTaxCode['office_id'] = $newOffice->id;
            $cloneTaxCode = PocomosTaxCode::create($cloneTaxCode);
        }

        return;
    }

    /**Clone office types details */
    public function cloneTypes($otherOffice, $newOffice)
    {
        $types = PocomosPestContractServiceType::where('office_id', $otherOffice->id)->get();
        foreach ($types as $type) {
            $cloneType = (clone $type)->toArray();
            unset($cloneType['id']);
            $cloneType['office_id'] = $newOffice->id;
            $cloneType = PocomosPestContractServiceType::create($cloneType);
        }

        $services = PocomosService::where('office_id', $otherOffice->id)->get();
        foreach ($services as $service) {
            $cloneService = (clone $service)->toArray();
            unset($cloneService['id']);
            $cloneService['office_id'] = $newOffice->id;
            $cloneService = PocomosService::create($cloneService);
        }

        $marketingTypes = PocomosMarketingType::where('office_id', $otherOffice->id)->get();
        foreach ($marketingTypes as $type) {
            $marketingType = (clone $type)->toArray();
            unset($marketingType['id']);
            $marketingType['office_id'] = $newOffice->id;
            $marketingType = PocomosMarketingType::create($marketingType);
        }
        return;
    }

    /**Clone office letters details */
    public function cloneLetters($otherOffice, $newOffice)
    {
        $letters = PocomosFormLetter::where('office_id', $otherOffice->id)->get();
        foreach ($letters as $letter) {
            $cloneLetter = (clone $letter)->toArray();
            unset($cloneLetter['id']);
            $cloneLetter['office_id'] = $newOffice->id;
            $cloneLetter = PocomosFormLetter::create($cloneLetter);
        }

        $letters = PocomosSmsFormLetter::where('office_id', $otherOffice->id)->get();
        foreach ($letters as $letter) {
            $cloneLetter = (clone $letter)->toArray();
            unset($cloneLetter['id']);
            $cloneLetter['office_id'] = $newOffice->id;
            $cloneLetter = PocomosSmsFormLetter::create($cloneLetter);
        }

        return;
    }

    /**Clone office agreeement details */
    public function cloneAgreements($otherOffice, $newOffice)
    {
        $agreements = DB::select(DB::raw("SELECT pca.*
            FROM pocomos_pest_agreements AS pca
            JOIN pocomos_agreements AS a ON pca.agreement_id = a.id
            WHERE a.office_id = '$otherOffice->id'"));

        foreach ($agreements as $pestAgreement) {
            $pestAgreement = PocomosPestAgreement::findOrFail($pestAgreement->id);
            $agreement = (clone $pestAgreement->agreement_detail)->toArray();
            $pestAgreement = $pestAgreement->toArray();

            unset($agreement['id']);
            $agreement['office_id'] = $newOffice->id;
            $agreement = PocomosAgreement::create($agreement);

            unset($pestAgreement['id']);
            $pestAgreement['agreement_id'] = $agreement->id;
            $pestAgreement = PocomosPestAgreement::create($pestAgreement);
        }

        return;
    }

    /**Clone office email types based on office */
    public function createEmailTypeSettings($office, $cloneOffice)
    {
        $emailTypes = PocomosEmailTypeSetting::whereOfficeId($office->id)->get();

        foreach ($emailTypes as $value) {
            $emailTypeSetting = new PocomosEmailTypeSetting();
            $emailTypeSetting->office_id = $cloneOffice->id;
            $emailTypeSetting->email_type = $value->email_type;
            $emailTypeSetting->enabled = true;
            $emailTypeSetting->active = true;
            $emailTypeSetting->date_created = date('Y-m-d H:i:s');
            $emailTypeSetting->save();
        }

        return true;
    }

    /**Start and end date base manage generators */
    public function createGenerator($contract, $dateScheduled, $renewalEndDate, $service_frequency = null)
    {
        $generator = array();
        $date = $dateScheduled;
        if (!$service_frequency) {
            $service_frequency = $contract->service_frequency;
        }

        while ($date < $renewalEndDate) {
            switch ($service_frequency) {
                case config('constants.ANNUALLY'):
                    $multiplier = '12 Months';
                    break;
                case config('constants.SEMI_ANNUALLY'):
                    $multiplier = '6 Months';
                    break;
                case config('constants.HEXA_WEEKLY'):
                    $multiplier = '6 Weeks';
                    break;

                case config('constants.QUARTERLY'):
                    $multiplier = '4 Months';
                    break;
                case config('constants.TRI_WEEKLY'):
                    $multiplier = '3 Weeks';
                    break;
                case config('constants.BI_MONTHLY'):
                    $multiplier = '2 Months';
                    break;
                case config('constants.BI_WEEKLY'):
                    $multiplier = '2 Weeks';
                    break;
                case config('constants.MONTHLY'):
                    $multiplier = '1 Months';
                    break;
                case config('constants.WEEKLY'):
                    $multiplier = '1 Weeks';
                    break;
                default:
                    $multiplier = '1 Weeks';
            }
            $date = new DateTime($date);
            $date = $date->modify('+' . $multiplier)->format('Y-m-d');
            $generator[] = $date;
        }

        if (!count($generator)) {
            throw new \Exception(__('strings.message', ['message' => 'No generator available for the given pest control contract.']));
        }

        return $generator;
    }

    /**Update contract billing frequency */
    public function updateBillingFrequency($contract, $salesContract, $serviceFrequency, $initialDate)
    {
        if (in_array(
            $serviceFrequency,
            array(
                config('constants.CUSTOM'),
                config('constants.CUSTOM_MANUAL')
            )
        )) {
            throw new \Exception(__('strings.message', ['message' => 'Cannot update to a custom service frequency']));
        }

        $serviceSchedule = array();
        $serviceSchedule = $this->createServiceSchedule($serviceFrequency, $serviceSchedule, $contract->pest_agreement_details->agreement_detail->length);
        $contract->service_schedule = $serviceSchedule;
        $contract->service_frequency = ($serviceFrequency);
        $contract->save();

        $this->cancelIncompleteJobs($contract);

        $jobs = $this->recreateJobsForBalanceOfContract($contract, $initialDate);

        return true;
    }

    /**Cerate contract service schedule */
    public function createServiceSchedule($serviceFrequency, $serviceSchedule, $agreementLength)
    {
        switch ($serviceFrequency) {
            case config('constants.CUSTOM'):
            case config('constants.CUSTOM_MANUAL'):
                return $serviceSchedule ?: array();

            default:
                $divisor = $this->getServiceFrequencyFactor($serviceFrequency);
        }

        switch ($serviceFrequency) {
            case config('constants.BI_WEEKLY'):
            case config('constants.TRI_WEEKLY'):
            case config('constants.HEXA_WEEKLY'):
            case config('constants.WEEKLY'):
                $agreementLength *= 4;
        }

        if ($agreementLength == 1 || $divisor <= 0) {
            $divisor = 1;
        }

        $i = 1;
        while ($i <= $agreementLength) {
            if (($i % $divisor) == 0) {
                $serviceSchedule[] = $divisor;
            }

            $i++;
        }

        if ($serviceSchedule === null) {
            throw new \Exception("Could not generate Service Schedule. Please check your Agreement's Length, chances are - it's too low for the selected service frequency");
        }

        return $serviceSchedule;
    }

    /**Gwt contract service frequencies */
    public function getServiceFrequencyFactor($serviceFrequency)
    {
        switch ($serviceFrequency) {
            case config('constants.ANNUALLY'):
                return 12;

            case config('constants.SEMI_ANNUALLY'):
            case config('constants.HEXA_WEEKLY'):
                return 6;

            case config('constants.TRI_ANNUALLY'):
                return 4;

            case config('constants.QUARTERLY'):
            case config('constants.TRI_WEEKLY'):
                return 3;

            case config('constants.BI_MONTHLY'):
            case config('constants.BI_WEEKLY'):
                return 2;

            case config('constants.MONTHLY'):
            case config('constants.WEEKLY'):
            case config('constants.TWICE_PER_MONTH'):
            default:
                return 1;
        }
    }

    /**Cancel contract in completed jobs */
    public function cancelIncompleteJobs($contract)
    {
        $salesContract = $contract->contract_details;

        if ($salesContract->status == config('constants.ACTIVE')) {
            foreach ($contract->jobs_details as $job) {
                if (in_array($job->status, array(config('constants.PENDING'), config('constants.RESCHEDULED')))) {
                    $invoice = $job->invoice_detail ?? array();
                    $this->cancelJob($job, $invoice);
                }
            }
        }

        return true;
    }

    /**Check contract service frequency is weekly or not */
    public function isWeeklyServiceFrequency($contract)
    {
        return in_array($contract->service_frequency, array(
            config('constants.WEEKLY'),
            config('constants.BI_WEEKLY'),
            config('constants.TRI_WEEKLY'),
            config('constants.HEXA_WEEKLY'),
            config('constants.TWICE_PER_MONTH'),
        ));
    }

    /**Recreate contract jobs balance */
    public function recreateJobsForBalanceOfContract($contract, $startDate)
    {
        $weekly = $this->isWeeklyServiceFrequency($contract);
        $firstStep = ($contract->service_schedule ?? array());
        $firstStep = $firstStep[0];

        // date in the past, start counting from it
        if ($startDate < new DateTime()) {
            $dateScheduled = $startDate;
        } else {
            // date in the future, start counting from the past (by frequency times) to the scheduled date
            $dateScheduled = $startDate->modify('-' . $firstStep . ($weekly ? ' week' : ' month'));
        }

        $maxDateScheduled = $contract->contract_details->date_end;

        if ($maxDateScheduled < new DateTime()) {
            $sdate = date('Y-m-d h:i:s', strtotime($startDate));
            $dateOneYearAdded = date('Y-m-d h:i:s', strtotime($sdate . " +1 year"));

            $maxDateScheduled = $from = new DateTime($dateOneYearAdded);
        }
        $dateScheduled = date('Y-m-d', strtotime($dateScheduled));
        $maxDateScheduled = $maxDateScheduled->format('Y-m-d');

        $generator = $this->createGenerator($contract, $dateScheduled, $maxDateScheduled);

        foreach ($generator as $dateScheduled) {
            $this->createContractJob($contract, config('constants.REGULAR'), null, $dateScheduled, $contract->preferred_time);
        }

        return true;
    }

    /**Update contract invoices recurring price */
    public function updateRecurringPrice($contract, $recurringPrice)
    {
        if (abs($contract->recurring_price - $recurringPrice) == 0.00) {
            return true;;
        }

        $contract->recurring_price = $recurringPrice;
        $contract->save();

        $billingFrequency = $contract->contract_details->billing_frequency;
        if (in_array($billingFrequency, array(config('constants.MONTHLY'), config('constants.INITIAL_MONTHLY')))) {
            $miscInvoices = $this->findFutureMiscInvoices($contract->id);
            foreach ($miscInvoices as $invoice) {
                $this->updateInvoiceRecurringPrice($contract->id, $invoice, $recurringPrice);
            }
        } else {
            $invoices = $this->findFutureInvoices($contract->id);
            foreach ($invoices as $invoice) {
                $this->updateInvoiceRecurringPrice($contract, $invoice, $recurringPrice);
            }
        }

        return true;
    }

    /**Create fake customer details */
    public function createFakeCustomer($pestAgreement, $email = array())
    {
        $customer = new PocomosCustomer();
        $address = new PocomosAddress();
        $profile = new PocomosCustomerSalesProfile();
        $salesAgreement = $pestAgreement->agreement_detail;
        $office = $salesAgreement->office_details;

        $phone = PocomosPhoneNumber::create([
            'alias' => 'Fake',
            'type' => 'Fake',
            'number' => '5555555555',
            'active' => true
        ]);

        $region = PocomosRegion::create([
            'name' => 'California',
            'description' => '',
            'active' => true
        ]);

        $address->street = '123 Main St.';
        $address->city = 'Ameritown';
        $address->postal_code = '12345';
        $address->phone_id = $phone->id;
        $address->region_id = $region->id;
        $address->suite = '';
        $address->active = true;
        $address->validated = true;
        $address->valid = true;
        $address->save();

        $customer->first_name = 'John';
        $customer->last_name = 'Doe';
        $customer->billing_address_id = $address->id;
        $customer->contact_address_id = $address->id;
        $customer->company_name = 'ABC Company LLC';
        $customer->email = array_shift($email);
        if (!is_array($email)) {
            $customer->secondary_emails = $email;
        } else {
            $customer->secondary_emails = implode(', ', $email);
        }
        $customer->subscribed = true;
        $customer->status = '';
        $customer->active = true;
        $customer->date_created = date('Y-m-d H:i:s');
        $customer->email_verified = true;
        $customer->external_account_id = '';
        $customer->save();

        $profile->date_signed_up = date('Y-m-d H:i:s');
        $profile->autopay = true;
        $profile->customer_id = $customer->id;
        $profile->office_id = $office->id;
        $profile->balance = $office->id;
        $profile->active = true;
        $profile->imported = true;
        $profile->save();

        return $profile;
    }

    /**Create fake contract details */
    public function createFakeContracts($profile, $agreement)
    {
        $salesAgreement = $agreement->agreement_detail;
        $office = $salesAgreement->office_details;

        $foundByType = new PocomosMarketingType();
        $foundByType->name = 'Test Marketing-' . strtotime(date('Y-m-d H:i:s'));
        $foundByType->office_id = $office->id;
        $foundByType->description = '';
        $foundByType->active = true;
        $foundByType->date_created = date('Y-m-d H:i:s');
        $foundByType->save();

        $profile = new PocomosCompanyOfficeUserProfile();
        $profile->active = true;
        $profile->date_created = date('Y-m-d H:i:s');
        $profile->save();

        $officeUser = new PocomosCompanyOfficeUser();
        $officeUser->office_id = $office->id;
        $officeUser->active = true;
        $officeUser->date_created = date('Y-m-d H:i:s');
        $officeUser->deleted = false;
        $officeUser->profile_id = $profile->id;
        $officeUser->save();

        $salesperson = new PocomosSalesPeople();
        $salesperson->user_id = $officeUser->id;
        $salesperson->active = true;
        $salesperson->date_created = date('Y-m-d H:i:s');
        $salesperson->save();

        $model['agreement'] = $agreement;
        $model['startDate'] = date('Y-m-d H:i:s');
        $model['renewal'] = false;
        $model['autoRenew'] = false;
        $model['discount'] = 5;
        $model['initialDate'] = date('Y-m-d H:i:s');
        $model['initialDiscount'] = 10;
        $model['initialPrice'] = 99;
        $model['recurringPrice'] = 49.99;
        $model['regularInitialPrice'] = 109;
        $model['pests'] = array();
        $model['specialty'] = array();
        $model['tags'] = array();
        $model['customFields'] = array();
        $model['salesperson'] = $salesperson->id;
        $model['foundByType'] = $foundByType->id;
        $model['profile'] = $profile->id;
        $model['officeUser'] = $officeUser->id;

        return $model;
    }

    /**Array to convert contract */
    public function transformToContract($model)
    {
        $model = (object)$model;
        $serviceFrequency = $model->serviceFrequency ?? '';
        $billingFrequency = $model->billingFrequency ?? '';

        $exception = $model->agreement->exceptions;
        if (!empty($exception)) {
            $exception = $model->agreement->exceptions;
        } else {
            $exception = array();
        }

        if (isset($model->serviceType) && !$model->serviceType) {
            throw new \Exception(__('strings.message', ['message' => 'Invalid service type selected.']));
        }

        // TODO: Address this-- potentially move the setCollection logic into the entities?
        // $this->setCollection($contract->getPests(), $model->pests ?: array());
        // $this->setCollection($contract->getSpecialtyPests(), $model->specialty ?: array());
        // $this->setCollection($contract->getTags(), $model->tags ?: array());

        $contract_data['county_id'] = $model->county ?? null;
        $contract_data['regular_initial_price'] = $model->regularInitialPrice ?? 0.0;
        $contract_data['initial_discount'] = $model->initialDiscount ?? '';
        $contract_data['initial_price'] = $model->initialPrice ?? '';
        $contract_data['recurring_discount'] = $model->discount ?? '';
        $contract_data['recurring_price'] = $model->recurringPrice ?? '';
        $contract_data['map_code'] = $model->mapCode ?? '';
        $contract_data['exceptions'] = $exception;
        $contract_data['number_of_jobs'] = $model->numberOfJobs ?? 0;
        $contract_data['remotely_completed'] = $model->remotelyCompleted ?? 0;
        $contract_data['addendum'] = $model->addendum ?? '';

        $contract_data['installment_frequency'] = $model->installmentFrequency ?? '';
        $contract_data['installment_start_date'] = $model->installmentStartDate ?? null;
        $contract_data['installment_end_date'] = $model->installmentEndDate ?? null;

        if (isset($model->technician)) {
            $technician = $model->technician;
            $contract_data['technician_id'] = $technician->id;
        }

        $contractStartDate = new DateTime($model->startDate);
        $contractEndDate = new DateTime($model->date_renewal_end ?? date('Y-m-d H:i:s'));

        $contract_data['service_frequency'] = $serviceFrequency;
        $agreementLength = ceil($contractStartDate->diff($contractEndDate)->days / 30);
        $serviceSchedule = $this->createServiceSchedule($serviceFrequency, $model->serviceSchedule ?? array(), $agreementLength);

        $contract_data['service_schedule'] = '';
        $contract_data['active'] = true;

        $contract_data['agreement_id'] = $model->agreement ? $model->agreement->id : '';

        $contract_data['week_of_the_month'] = '';
        $contract_data['day_of_the_week'] = '';
        $contract_data['preferred_time'] = '';

        if (isset($model->specificRecurringSchedule)) {
            $contract_data['week_of_the_month'] = $model->recurringWeek ?? '';
            $contract_data['day_of_the_week'] = $model->recurringDay ?? '';
            $contract_data['preferred_time'] = $model->recurringTime ?? '';
        }

        if (isset($model->billToParent) && isset($model->parentContract)) {
            $contract_data['parent_contract_id'] = $model->parentContract ? $model->parentContract->id : null;
        }

        $contract_data['renew_initial_job'] = $model->renew_initial_job ?? 0;
        $contract_data['date_renewal_end'] = $contractEndDate->format('Y-m-d H:i:s');
        $contract_data['original_value'] = 0;
        $contract_data['modifiable_original_value'] = 0;

        foreach ($model->customFields as $key => $value) {
            PocomosCustomField::create(['pest_control_contract_id' => $model->parentContract->id, 'custom_field_configuration_id' => $key, 'value' => $value, 'active' => true]);
        }

        $salesContract = new PocomosContract();
        $salesContract->tax_code_id = $model->taxCode ? $model->taxCode : null;
        // $salesContract->sales_tax = $model->sales_tax ? $model->sales_tax : null;
        $salesContract->auto_renew = $model->autoRenew ?? false;
        $salesContract->status = config('constants.ACTIVE');
        $salesContract->date_start = $contractStartDate ?? '';
        $salesContract->date_end = $contractEndDate->format('Y-m-d H:i:s');
        $salesContract->found_by_type_id = ($model->foundByType);
        if (isset($model->salesStatus)) {
            $salesContract->sales_status_id = ($model->salesStatus);
        }
        $salesContract->salesperson_id = ($model->salesperson);
        $salesContract->billing_frequency = ($billingFrequency);
        $salesContract->two_payments_days_limit = $model->twoPaymentsDaysLimit ?? 60;
        $salesContract->active = true;

        $salesContract->agreement_id = ($model->agreement ? ($model->agreement->agreement_detail ? ($model->agreement->agreement_detail->id ?: null) : null) : null);
        // $salesContract->number_of_payments = $model->numberOfPayments;

        // if ($signature = $this->signatureTransformer->transform($model->signatureData, $salesContract)) {
        //     $salesContract->setSignature($signature);
        // }

        // if ($signature = $this->signatureTransformer->transform($model->autopaySignatureData, $salesContract)) {
        //     $salesContract->setAutopaySignature($signature);
        // }

        $salesContract->signed = false;
        $salesContract->sales_tax = 0;

        // if ($model->signed) {
        //     $salesContract->setSigned(true);
        // }
        $salesContract->save();
        $contract_data['contract_id'] = $salesContract->id;
        // dd($contract_data);
        $contract = PocomosPestContract::create($contract_data);
        return $contract;
    }

    /**The tax code recalculation job for maange contract invoice tax related details */
    public function createTaxRecalculationJob($oldTaxCode, $newTaxCode)
    {
        $contractIds = array();

        $contractIdsDetails = DB::select(DB::raw("SELECT pcc.id as contrid FROM pocomos_pest_contracts pcc
                JOIN pocomos_contracts pc ON pcc.contract_id = pc.id
                WHERE pc.tax_code_id = $oldTaxCode->id AND pcc.active = 1"));

        foreach ($contractIdsDetails as $val) {
            $contractIds[] = $val->contrid;
        }

        if (count($contractIds) > 0) {
            $batchCount = ceil((count($contractIds) / 750));
            $segment = (int)((count($contractIds) / $batchCount) + 1);

            $current = 0;
            while ($current < count($contractIds)) {
                $ids = array_slice($contractIds, $current, $segment);
                $current += $segment;

                $args = array_merge(array(
                    'pestContractIds' => $ids,
                    'taxCodeId' => $newTaxCode->id
                ));

                TaxRecalculationJob::dispatch($args);
            }

            return true;
        }
        // throw new \Exception(__('strings.something_went_wrong'));
    }

    /**Update tax code regarding details for contract invoices */
    public function updatePestContractTaxCode($contract, $taxCode)
    {
        $contract = PocomosPestContract::findOrFail($contract);
        $salesContract = $contract->contract_details;
        $salesContract->tax_code_id = $taxCode->id;
        $salesContract->sales_tax = $taxCode->tax_rate;
        $salesContract->save();

        foreach ($contract->jobs_details as $job) {
            $invoice = $job->invoice_detail;
            if ($invoice && $invoice->status === config('constants.NOT_SENT') && count($invoice->invoice_transactions) === 0) {
                $this->updateInvoiceTax($invoice->id, $salesContract, $invoice);
            }
        }

        foreach ($this->findFutureMiscInvoices($salesContract->id) as $invoice) {
            $invoice = (object)$invoice;
            $invoice = PocomosInvoice::findOrFail($invoice->id);
            if ($invoice && $invoice->status === config('constants.NOT_SENT') && count($invoice->invoice_transactions) === 0) {
                $this->updateInvoiceTax($invoice->id, $salesContract, $invoice);
            }
        }
        return true;
    }

    /**Get transaction invoices IDs */
    public function getmultipleTransactionInvoices($payment_history)
    {
        foreach ($payment_history as $history) {
            $transaction = PocomosUserTransaction::where('transaction_id', $history->id)->get();

            $gj = count($transaction);

            $multipleInvoices = array();

            $history->multipleInvoices = $multipleInvoices;

            if ($gj > 1) {
                foreach ($transaction as $ds) {
                    $multipleInvoices[] = $ds->invoice_id;
                }
                $history->multipleInvoices = $multipleInvoices;
            }
        }
    }

    /**Get transaction invoices */
    public function getTransactionInvoices($office, $filters = array(), $salespeople = array(), $users = array())
    {
        $transTypes = array(config('constants.SALE'));
        if (isset($filters['includeRefunds'])) {
            $transTypes[] = config('constants.REFUND');
        }

        $office = $office->id;
        $type = $this->convertArrayInStrings($transTypes);
        $status = config('constants.APPROVED');
        $complete = config('constants.COMPLETE');

        if (isset($filters['accrual']) && $filters['accrual'] === 'accrual') {
            //accrual should show all jobs completed within the date range and the total value
            //basically invoice based!
            //Accrual = Transactions for completed services + disjointed billing by due date (edited)
            //Add tax amount + Tax Name + Tax %
            //Check if Accrual is correct. It shouldn't match up
            $sql = "SELECT
                           DISTINCT t.id as tid,
                           j.id as jid,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           cu.id as custId,
                           cu.external_account_id as custAcctId,
                           CONCAT(cu.first_name, ' ', cu.last_name) as name,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           ca.street as custStreet,
                           ca.city  as custCity,
                           ca.postal_code  as custPostalCode,
                           reg.name  as custState,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           DATE_FORMAT(prcs.initial_service_date,'%c/%d/%Y') as initialServiceDate,
                           DATE_FORMAT(co.date_created,'%c/%d/%Y') as contractCreationDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount,t.amount/100),0),2) as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.sales_tax as taxRate,
                           i.balance,
                           r.external_id AS refNumber,
                           COALESCE(t.status, 'Unpaid') AS paymentStatus,
                           st.name AS service_type_name,
                           pcc.first_year_contract_value as first_year_contract_value,
                           csp.autopay as autopay,
                           j.type as jobtype,
                           co.date_start as initialdate,
                           pa.name as agreement_name
                           FROM pocomos_contracts co
                           JOIN pocomos_invoices i ON i.contract_id = co.id
                           LEFT JOIN pocomos_invoice_transactions it ON i.id = it.invoice_id
                           JOIN orkestra_transactions t on (it.transaction_id = t.id and t.type IN ($type) and t.status = 'Approved')
                           LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                           LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                           LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                           JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                           LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                           LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                           JOIN pocomos_customer_sales_profiles csp on co.profile_id = csp.id
                           JOIN pocomos_customers cu on csp.customer_id = cu.id
                           JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                           LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                           LEFT JOIN orkestra_results r ON r.transaction_id = t.id
                           LEFT JOIN pocomos_agreements pa on co.agreement_id = pa.id
                           LEFT JOIN pocomos_reports_contract_states prcs on co.id = prcs.contract_id
                           WHERE csp.office_id = $office
                           AND (j.status = '$complete' OR j.status is NULL)";

            if (isset($filters['startDate'], $filters['endDate'])) {
                $start = $filters['startDate'];
                $end = $filters['endDate'];
                $sql .= ' AND ((j.id is NULL && i.date_due BETWEEN ' . $start . ' AND ' . $end . ') OR (j.date_completed BETWEEN ' . $start . ' AND ' . $end . '))';
            }

            if (isset($filters['anyFilterResult'], $filters['anyFilterResult'])) {
                $filterby = isset($filters['filterResultBy']) ? $filters['filterResultBy'] : null;
                if ($filterby == "initialService") {
                    if (isset($filters['startDateFilter'], $filters['endDateFilter'])) {
                        $startDateFilter = $filters['startDateFilter'];
                        $endDateFilter = $filters['endDateFilter'];

                        $sql .= " AND ((prcs.initial_service_date BETWEEN $startDateFilter AND $endDateFilter))";
                    }
                } else {
                    if (isset($filters['startDateFilter'], $filters['endDateFilter'])) {
                        $startDateFilter = $filters['startDateFilter'];
                        $endDateFilter = $filters['endDateFilter'];
                        $sql .= " AND ((co.date_created BETWEEN $startDateFilter AND $endDateFilter))";
                    }
                }
            }
        } else {
            //cash show all revenue for payment (points, receive money) dates within the given range
            //as well as any misc invoice made in the date range (total value)
            //Cash = SHow all Transactions during period

            $sql = "SELECT
                           DISTINCT t.id as tid,
                           cu.id as custId,
                           cu.external_account_id as custAcctId,
                           CONCAT(cu.first_name, ' ', cu.last_name) as name,
                           CONCAT(ca.street, ' ', ca.city, ', ', reg.name, ' ', ca.postal_code) as address,
                           ca.street as cust_street,
                           ca.city  as cust_city,
                           ca.postal_code  as cust_postal_code,
                           reg.name  as cust_state,
                           GROUP_CONCAT(DISTINCT i.id SEPARATOR ', ') as id,
                           DATE_FORMAT(i.date_due,'%c/%d/%Y') as dateDue,
                           DATE_FORMAT(prcs.initial_service_date,'%c/%d/%Y') as initialServiceDate,
                           DATE_FORMAT(co.date_created,'%c/%d/%Y') as contractCreationDate,
                           DATE_FORMAT(t.date_created,'%c/%d/%Y') as paymentDate,
                           t.network as paymentType,
                           t.type as actualPaymentType,
                           FORMAT(IF(t.network <> 'Points',t.amount,t.amount/100),2) as paymentAmount,
                           FORMAT(COALESCE(IF(t.network <> 'Points',t.amount/(1+i.sales_tax),t.amount/100/(1+i.sales_tax)),0),2) as preTaxAmount,
                           i.balance,
                           i.sales_tax as taxRate,
                           r.external_id AS refNumber,
                           t.status AS paymentStatus,
                           st.name AS service_type_name,
                           pcc.first_year_contract_value as first_year_contract_value,
                           csp.autopay as autopay,
                           j.type as jobtype,
                           co.date_start as initialdate,
                           pa.name as agreement_name
                        FROM orkestra_transactions t
                        LEFT JOIN pocomos_user_transactions AS ut ON t.id = ut.transaction_id
                        LEFT JOIN orkestra_users AS u ON ut.user_id = u.id
                        JOIN orkestra_accounts a on t.account_id = a.id
                        JOIN pocomos_customers_accounts acct on a.id = acct.account_id
                        JOIN pocomos_customer_sales_profiles csp on acct.profile_id = csp.id
                        JOIN pocomos_customers cu on csp.customer_id = cu.id
                        JOIN pocomos_addresses ca on cu.contact_address_id = ca.id
                        LEFT JOIN orkestra_countries_regions reg on ca.region_id = reg.id
                        LEFT JOIN pocomos_invoice_transactions it on it.transaction_id = t.id
                        JOIN orkestra_results r ON r.transaction_id = t.id
                        LEFT JOIN pocomos_invoices i on it.invoice_id = i.id
                        LEFT JOIN pocomos_jobs j on j.invoice_id = i.id
                        LEFT JOIN pocomos_contracts co on i.contract_id = co.id
                        LEFT JOIN pocomos_pest_contracts pcc on co.id = pcc.contract_id
                        LEFT JOIN pocomos_pest_contract_service_types st ON pcc.service_type_id = st.id
                        LEFT JOIN pocomos_pest_contracts_tags pt on pcc.id = pt.contract_id
                        LEFT JOIN pocomos_agreements pa on co.agreement_id = pa.id
                        LEFT JOIN pocomos_reports_contract_states prcs on co.id = prcs.contract_id
                        WHERE csp.office_id = :office
                        AND t.type IN ($type)
                        AND t.status = $status";

            if (isset($filters['startDate'], $filters['endDate'])) {
                $sql .= ' AND t.date_created BETWEEN :start AND :end';
            }

            if (isset($filters['anyFilterResult'], $filters['anyFilterResult'])) {
                $filterby = isset($filters['filterResultBy']) ? $filters['filterResultBy'] : null;
                if ($filterby == "initialService") {
                    if (isset($filters['startDateFilter'], $filters['endDateFilter'])) {
                        $startDateFilter = $filters['startDateFilter'];
                        $endDateFilter = $filters['endDateFilter'];
                        $sql .= " AND ((prcs.initial_service_date BETWEEN $startDateFilter AND $endDateFilter))";
                    }
                } else {
                    if (isset($filters['startDateFilter'], $filters['endDateFilter'])) {
                        $startDateFilter = $filters['startDateFilter'];
                        $endDateFilter = $filters['endDateFilter'];
                        $sql .= " AND ((co.date_created BETWEEN $startDateFilter AND $endDateFilter))";
                    }
                }
            }
        }

        if (isset($filters['allOption']) && $filters['allOption'] == false) {
            $salesperson = $this->convertArrayInStrings($salespeople);
            $sql .= PHP_EOL . 'AND co.salesperson_id IN (' . $salesperson . ')';
        }

        if (count($users)) {
            $users = $this->convertArrayInStrings($users);
            $sql .= PHP_EOL . 'AND u.id IN (' . $users . ') ';
        }

        if (isset($filters['marketingType']) && count($filters['marketingType']) > 0) {
            $foundByType = $this->convertArrayInStrings($filters['marketingType']);
            $sql .= PHP_EOL . 'AND co.found_by_type_id IN (' . $foundByType . ')';
        }

        if (isset($filters['serviceType']) && count($filters['serviceType']) > 0) {
            $serviceType = $this->convertArrayInStrings($filters['serviceType']);
            $sql .= PHP_EOL . 'AND pcc.service_type_id IN (' . $serviceType . ')';
        }

        if (isset($filters['agreement']) && count($filters['agreement']) > 0) {
            $agreement = $this->convertArrayInStrings($filters['agreement']);
            $sql .= PHP_EOL . 'AND pa.id IN (' . $agreement . ')';
        }

        if (isset($filters['tags']) && count($filters['tags']) > 0) {
            $tags = $this->convertArrayInStrings($filters['tags']);
            $sql .= PHP_EOL . 'AND pt.tag_id IN (' . $tags . ')';
        }

        if (isset($filters['taxCode']) && count($filters['taxCode']) > 0) {
            $taxCode = $this->convertArrayInStrings($filters['taxCode']);
            $sql .= PHP_EOL . 'AND i.tax_code_id IN (' . $taxCode . ')';
        }

        if (isset($filters['hasEmailOnFile'])) {
            if ($filters['hasEmailOnFile'] === 1) {
                $sql .= PHP_EOL . "AND (cu.email != '' AND cu.email IS NOT null)";
            } else {
                $sql .= PHP_EOL . "AND (cu.email = '' OR cu.email IS null)";
            }
        }

        if (isset($filters['autopayOnFile']) && $filters['autopayOnFile'] === true) {
            $sql .= PHP_EOL . 'AND csp.autopay = 1';
        }

        if (isset($filters['serviceFrequency']) && in_array($filters['serviceFrequency'], $this->getServiceFrequency())) {
            $serviceFrequency = $filters['serviceFrequency'];
            $sql .= PHP_EOL . 'AND pcc.service_frequency = ' . $serviceFrequency . '';
        }

        if (isset($filters['jobType']) && in_array($filters['jobType'], $this->getJobTypes())) {
            $jobType = $filters['jobType'];
            $sql .= PHP_EOL . 'AND j.type = ' . $jobType . '';
        }

        if (isset($filters['networkType'])) {
            if ($filters['networkType'] != '') {
                $networkType = $filters['networkType'];
                $sql .= PHP_EOL . 'AND t.network = ' . $networkType . '';
            }
        }

        $sql .= PHP_EOL . 'GROUP BY t.id, IF(t.id IS NULL, i.id, 0)';

        if (isset($filters['anyFilterResult'], $filters['anyFilterResult'])) {
            $filterby = isset($filters['filterResultBy']) ? $filters['filterResultBy'] : null;
            if ($filterby == "initialService") {
                if (isset($filters['startDateFilter'], $filters['endDateFilter'])) {
                    $sql .= PHP_EOL . "ORDER BY prcs.initial_service_date;";
                }
            } else {
                if (isset($filters['startDateFilter'], $filters['endDateFilter'])) {
                    $sql .= PHP_EOL . "ORDER BY co.date_created;";
                }
            }
        } else {
            $sql .= PHP_EOL . "ORDER BY name;";
        }

        $res = DB::select(DB::raw($sql));
        return $res;
    }

    /**Get service frequency */
    public function getServiceFrequency($lenth_per_month = null)
    {
        $res = array();

        if ($lenth_per_month) {
            if ($lenth_per_month == 1) {
                $res = array(
                    'Weekly',
                    'Bi-weekly',
                    'Tri-weekly',
                    'One-Time',
                    'Twice Per Month'
                );
            } elseif ($lenth_per_month <= 2) {
                $res = array(
                    'Weekly',
                    'Bi-weekly',
                    'Tri-weekly',
                    'Monthly',
                    'Twice Per Month',
                    'Every Six Weeks',
                    'One-Time',
                    'Custom',
                    'Custom (Manual)'
                );
            } elseif ($lenth_per_month <= 3) {
                $res = array(
                    'Weekly',
                    'Bi-weekly',
                    'Tri-weekly',
                    'Monthly',
                    'Bi-monthly',
                    'Twice Per Month',
                    'Every Six Weeks',
                    'Quarterly',
                    'One-Time',
                    'Custom',
                    'Custom (Manual)'
                );
            } elseif ($lenth_per_month >= 4 && $lenth_per_month <= 5) {
                $res = array(
                    'Weekly',
                    'Bi-weekly',
                    'Tri-weekly',
                    'Monthly',
                    'Bi-monthly',
                    'Twice Per Month',
                    'Every Six Weeks',
                    'Quarterly',
                    'Tri-Annually',
                    'One-Time',
                    'Custom',
                    'Custom (Manual)'
                );
            } elseif ($lenth_per_month >= 6 && $lenth_per_month <= 11) {
                $res = array(
                    'Weekly',
                    'Bi-weekly',
                    'Tri-weekly',
                    'Monthly',
                    'Bi-monthly',
                    'Twice Per Month',
                    'Every Six Weeks',
                    'Quarterly',
                    'Semi-annually',
                    'Tri-Annually',
                    'One-Time',
                    'Custom',
                    'Custom (Manual)'
                );
            } else {
                $res = array(
                    'Weekly',
                    'Bi-weekly',
                    'Tri-weekly',
                    'Monthly',
                    'Bi-monthly',
                    'Twice Per Month',
                    'Every Six Weeks',
                    'Quarterly',
                    'Semi-annually',
                    'Annually',
                    'Tri-Annually',
                    'One-Time',
                    'Custom',
                    'Custom (Manual)'
                );
            }
        } else {
            $res = array(
                'Weekly',
                'Bi-weekly',
                'Tri-weekly',
                'Monthly',
                'Bi-monthly',
                'Twice Per Month',
                'Every Six Weeks',
                'Quarterly',
                'Semi-annually',
                'Annually',
                'Tri-Annually',
                'One-Time',
                'Custom',
                'Custom (Manual)'
            );
        }
        return $res;
    }

    /**Get job types */
    public function getJobTypes()
    {
        return array(
            'Initial',
            'Regular',
            'Re-service',
            'Inspection',
            'Follow-up',
            'Pickup Service'
        );
    }

    /**Get billing frequency */
    public function getBillingFrequency()
    {
        return array(
            'Per service',
            'Monthly',
            'Initial monthly',
            'Due at signup',
            'Two payments',
            'Installments'
        );
    }

    public function updateResponsibleForBilling($pestContract,  $responsibleContract = null)
    {
        if ($responsibleContract == null || $responsibleContract == $pestContract) {
            $sql = 'UPDATE `pocomos_pest_contracts` SET parent_contract_id = null WHERE id = ' . $pestContract;
            DB::select(DB::raw($sql));
        } else {

            $pestContractde = PocomosPestContract::findOrFail($pestContract);
            $PocomosContract = PocomosContract::where('id', $pestContractde->contract_id)->firstorfail();
            $sale_profile = PocomosCustomerSalesProfile::where('id', $PocomosContract->profile_id)->first();
            $contractCustomer = PocomosCustomer::where('id', $sale_profile->customer_id)->first();

            $responsibleContractde = PocomosPestContract::findOrFail($responsibleContract);
            $PocomosContract = PocomosContract::where('id', $responsibleContractde->contract_id)->firstorfail();
            $sale_profile = PocomosCustomerSalesProfile::where('id', $PocomosContract->profile_id)->first();
            $responsibleCustomer = PocomosCustomer::where('id', $sale_profile->customer_id)->first();

            $subCustomer = PocomosSubCustomer::whereChildId($contractCustomer->id)->first();
            if ($subCustomer && $subCustomer->parent_id !== null) {
                $parentCust = $subCustomer->getParentNew();
                if ($parentCust->id != $responsibleCustomer->id) {
                    throw new \Exception("Only a customers parent account may be responsible for billing");
                }
            }

            $psql = 'UPDATE `pocomos_customers` SET billing_address_id = ' . $responsibleCustomer->billing_address_id . ' WHERE id = ' . $contractCustomer->id;
            DB::select(DB::raw($psql));

            $mysql = 'UPDATE `pocomos_pest_contracts` SET parent_contract_id = ' . $responsibleContractde->id . ' WHERE id = ' . $pestContractde->id;
            DB::select(DB::raw($mysql));
        }

        $pestContract = PocomosPestContract::findOrFail($pestContract);
        if ($responsibleContract != null) {
            $responsibleContract = PocomosPestContract::findOrFail($responsibleContract);
        }

        $responsibleSalesContract = $responsibleContract != null
            ? $responsibleContract->contract_id
            : $pestContract->contract_id;

        $getInvoice = PocomosJob::where('contract_id', $pestContract->id)->pluck('invoice_id')->toArray();
        $getMiscInvoices = PocomosPestContractsInvoice::where('pest_contract_id', $pestContract->id)->pluck('invoice_id')->toArray();

        $invoices = array_merge($getInvoice, $getMiscInvoices);

        foreach ($invoices as $invoice) {
            $mysql = 'UPDATE `pocomos_invoices` SET contract_id = ' . $responsibleSalesContract . ' WHERE id = ' . $invoice;
            DB::select(DB::raw($mysql));
        }

        return true;
    }

    /* Creates a new route */
    public function createRoute_routeFactory($officeId, $dateScheduled, $technicianId = null, $createdBy)
    {
        $routeArr['name'] = 'Route';
        $routeArr['office_id'] = $officeId;
        $routeArr['created_by'] = $createdBy;
        $routeArr['date_scheduled'] = $dateScheduled;
        $routeArr['active'] = 1;
        $routeArr['locked'] = 0;

        if ($technicianId !== null) {
            $routeArr['technician_id'] = $technicianId;
        }

        $route = PocomosRoute::create($routeArr);

        $office = PocomosCompanyOffice::find($officeId);

        $schedule = $this->getEffectiveScheduleImproved($office, $dateScheduled);

        if ($schedule->lunch_duration > 0) {
            $this->assignLunchSlot($route);
        }

        return $route;
    }

    /* Reschedules a Job */
    public function rescheduleJobReschedulingHelper($job, $dateScheduled, $timeScheduled = null, $route = null)
    {
        if (($job->status == "Complete") ||  ($job->status == "Cancelled")) {
            throw new \Exception(__('strings.message', ['message' => 'A completed or cancelled job may not be rescheduled.']));
        }

        $dateScheduledNew = new DateTime($dateScheduled);
        $diffFromNow = $dateScheduledNew->diff(new DateTime());
        if ($diffFromNow->days > 0 && !$diffFromNow->invert && !$this->isGranted('ROLE_MOVE_JOBS_TO_PAST')) {
            // Todo : remove comment
            // throw new \Exception(__('strings.message', ['message' => 'A job cannot be scheduled in the past.']));
        }

        $job->date_scheduled =  $dateScheduled;
        $job->original_date_scheduled =   $dateScheduled;
        $job->time_scheduled =  $timeScheduled;
        $job->status =   'Re-scheduled';
        $job->save();
        $previousSlot = PocomosRouteSlots::where('id', $job->slot_id)->first();
        $oldDuration = null;
        if ($previousSlot !== null) {
            $previousRoute = PocomosRoute::where('id', $previousSlot->route_id)->first();
            if ($previousRoute !== null) {
                $previousSlot->route_id =  null;
                $previousSlot->save();
            }

            $oldDuration = $previousSlot->duration;
        }
        $slot = null;
        if ($route !== null) {
            $slot = $this->assignJobToRoute(
                $job,
                $route,
                $job->time_scheduled,
                /* anytime */
                false,
                /* durationOverride */
                $oldDuration
            );
        } elseif ($job->time_scheduled !== null) {
            $slot = $this->assignJobBasedOnSchedule($job);
        }
        if ($slot) {
            // TODO: This is mixing concerns quite a bit: should this helper know how to hard-schedule? The job
            //       helper has a confirmJob() method that also changes schedule type...
            if (
                $previousSlot && $previousSlot->isConfirmed() && $previousRoute
                && $route->date_scheduled->format('Y-m-d') === $previousRoute->date_scheduled->format('Y-m-d')
                && $slot->time_begin->format('H:i') === $previousSlot->time_begin->format('H:i')
            ) {
                $slot->schedule_type =  "Hard-scheduled, Confirmed";
                $slot->save();
            } else {
                $slot->schedule_type =  "Hard-scheduled";
                $slot->save();
            }
        }

        $invoice = PocomosInvoice::where('id', $job->invoice_id)->first();
        if ($invoice) {
            $invoice->date_due = $dateScheduled;
            $invoice->save();
        }

        // $this->sendRescheduleJobEmail($job);
        // dd($job->id);


        $job = PocomosJob::with('contract.contract_details.profile_details.customer')->whereId($job->id)->firstOrFail();

        // dd($job->contract->contract_details->profile_id);

        $fromTime = $job->date_scheduled;

        // dd($fromTime);

        if ($job->time_scheduled != null) {
            $fromTime = $fromTime . ' at ' . $job->time_scheduled;
            $toTime = $job->date_scheduled;
            $toTime = $toTime . ' at ' . $job->time_scheduled;
        } else {
            $fromTime = $fromTime . ' at anytime';
            $toTime = $fromTime . ' at anytime';
        }

        $profileId = 'null';
        if (isset($job->contract->contract_details->profile_id)) {
            $profileId = $job->contract->contract_details->profile_id;
        }

        // dd($profileId);
        $customer = $job->contract->contract_details->profile_details->customer;

        $desc = '';
        if (auth()->user()) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/" . auth()->user()->id . "/show'>" . auth()->user()->full_name . "</a> ";
        } else {
            $desc .= 'The system ';
        }

        $desc .= 'rescheduled a job for';

        if (isset($customer)) {
            $desc .= " customer <a href='/pocomos-admin/app/Customers/" . $customer->id . "/service-information'>" . $customer->first_name . " " . $customer->last_name . "</a> ";
        }
        if (isset($fromTime) && !empty($fromTime)) {
            $desc .= ' to ' . $fromTime;
        }
        if (isset($invoice) && !empty($invoice)) {
            $desc .= " with invoice <a href='/pocomos-admin/app/Customers/" . $customer->id . "/invoice/" . $invoice->id . "/show'> " . $invoice->id . " </a> ";
        }
        $desc .= '.';


        $sql = 'INSERT INTO pocomos_activity_logs
                    (type, office_user_id, customer_sales_profile_id, description, context, date_created)
                    VALUES("Job Rescheduled", ' . auth()->user()->pocomos_company_office_user->id . ',
                        ' . $profileId . ', "' . $desc . '", "", "' . date('Y-m-d H:i:s') . '")';

        DB::select(DB::raw($sql));
        return $job;
    }

    public function removeHardScheduled($slot)
    {
        if ($slot->schedule_type == 'Confirmed' || $slot->schedule_type == 'Hard-scheduled, Confirmed') {
            $slot->schedule_type = 'Confirmed';
        } else {
            $slot->schedule_type = 'Dynamic';
        }

        $slot->save();
    }

    /**Get default unpaid search terms */
    public static function createDefaultUnpaidSearchTerms()
    {
        $self['dateStart'] = new DateTime('midnight -1 month');
        $self['dateEnd'] = new DateTime('midnight');
        $self['status'] = 'Unpaid';
        $self['paid'] = null;
        $self['jobType'] = null;
        $self['includeMiscInvoices'] = true;
        return $self;
    }

    /**Get unpaid invoices */
    public function getUnpaidInvoices($office, $terms)
    {
        /**THIS MODULE IS UNDER DEVELOPMENT */
        $jobs = $this->findUnpaidJobsWithSearchTerms($office, $terms);

        $miscInvoices = $this->getUnpaidMiscInvoices($office, $terms);
        $outstanding = $this->calculateUnpaidOutstandingBalance($jobs, $miscInvoices);

        $csvOutput = '"Total Invoices","Outstanding Amount"' .
            (count($jobs) + count($miscInvoices)) . ',' . $outstanding;


        // $csvOutput .= $this->exportHelper->export($jobs);
        // $lines = explode("\n", $this->exportHelper->exportInvoices($miscInvoices));
        // array_shift($lines);
        // $csvOutput .= implode("\n", $lines);

        // dd($jobs);
        // dd($miscInvoices);
        return $miscInvoices;
    }

    public function findUnpaidJobsWithSearchTerms($office, $terms)
    {
        $terms = (object)$terms;
        $sql = "SELECT j.*, i.balance FROM pocomos_jobs AS j";
        $where = " WHERE ";
        $join = '';
        $dateRanges = '';
        $orderBy = ' ORDER BY ';

        if ($terms->dateStart && $terms->dateEnd) {
            $dateStart = $terms->dateStart->modify('midnight')->format('Y-m-d H:i:s');
            $dateEnd = $terms->dateEnd->modify('midnight +1 day -1 second')->format('Y-m-d H:i:s');
            $dateRanges .= ' AND j.date_completed BETWEEN "' . $dateStart . '" and "' . $dateEnd . '"';
        }

        $multidateSpans = false;
        if (isset($terms->dateSpans) && $terms->dateSpans) {
            $multidateSpans = true;
            foreach ($terms->dateSpans as $span) {
                $start = $span[0]->format('Y-m-d H:i:s');
                $end = $span[1]->format('Y-m-d H:i:s');

                if ($start === null) {
                    $dateRanges .= ' AND j.date_completed > "' . $end . '"';
                } elseif (is_null($end)) {
                    $dateRanges .= ' AND j.date_completed < "' . $start . '"';
                } else {
                    $dateRanges .= ' AND j.date_completed BETWEEN "' . $start . '" and "' . $end . '"';
                }
            }
        }
        $jobStatuses = $this->convertArrayInStrings(array(config('constants.COMPLETE')));
        $invoiceStatuses = $this->convertArrayInStrings(array(config('constants.CANCELLED')));

        $join .= ' JOIN pocomos_invoices AS i ON j.invoice_id = i.id JOIN pocomos_route_slots as s ON j.slot_id = s.id ';
        $where .= ' j.status IN (' . $jobStatuses . ') AND i.status NOT IN (' . $invoiceStatuses . ') ';
        $orderBy .= ' j.date_scheduled ASC, j.time_scheduled ASC, s.time_begin ASC';

        if ($terms->status !== null) {
            if ($terms->status === 'Unpaid' || $terms->status === 'Paid') {
                $operator = $terms->status === 'Unpaid' ? '!=' : '=';
                $where .= " AND i.status " . $operator . " '" . config('constants.PAID') . "' ";
            } else {
                $operator = $terms->status === 'Unpaid' ? '!=' : '=';
                $where .= " AND i.status " . $operator . " '" . $terms->status . "' ";
            }
        }

        $where .= $dateRanges;
        $db = array(
            'sql' => $sql, 'join' => $join, 'where' => $where, 'orderBy' => $orderBy
        );

        $db = $this->applySearchTerms($db, $terms);

        $sql = $sql . '' . $join . '' . $where . '' . $orderBy;

        $data = DB::select(DB::raw($sql));
        return $data;
        // return $this->applySearchFilters($qb, $terms);
    }

    /**Apply search terms */
    public function applySearchTerms($qb, $terms)
    {
        $sql = $qb['sql'];
        $where = $qb['where'];
        $join = $qb['join'];
        $orderBy = $qb['orderBy'];

        if (isset($terms->confirmed) && $terms->confirmed !== null) {
            $confirmedStatuses = $this->convertArrayInStrings(array(config('constants.CONFIRMED'), config('constants.HARD_CONFIRMED'), config('constants.HARD')));
            if ($terms->confirmed === true) {
                $where .= ' s.schedule_type IN (' . $confirmedStatuses . ')';
            } else {
                $where .= ' s.schedule_type NOT IN (' . $confirmedStatuses . ')';
            }
        }

        if ($terms->paid !== null) {
            $operator = $terms->paid === false ? '!=' : '=';
            $where .= ' i.status ' . $operator . '' . config('constants.PAID');
        }

        if (isset($terms->serviceType) && $terms->serviceType !== null) {
            $join .= ' JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id ';
            $join .= ' JOIN pocomos_pest_contract_service_types AS ppst ON pcc.service_type_id = ppst.id ';
            $where .= ' ppst.name = ' . $terms->serviceType;
        }

        if (isset($terms->serviceFrequency) && $terms->serviceFrequency) {
            $where .= ' pcc.service_frequency = ' . $terms->serviceFrequency;
        }

        if (isset($terms->jobType) && $terms->jobType !== null) {
            $where .= ' j.type = ' . $terms->jobType;
        }

        if (isset($terms->postalCode) && $terms->postalCode !== null) {
            $join .= ' JOIN pocomos_technicians AS pt ON j.technician_id = pt.id ';
            $join .= ' JOIN pocomos_addresses AS ca ON pt.routing_address_id = ca.id ';
            $where .= ' ca.postal_code = ' . $terms->postalCode;
        }

        if ($terms) {
            if (isset($terms->foundByType) && $terms->foundByType != null) {
                $join .= ' JOIN pocomos_contracts AS c ON pcc.contract_id = c.id ';
                $join .= ' JOIN pocomos_customer_sales_profiles AS pcsp ON c.profile_id = pcsp.id ';
                $join .= ' JOIN pocomos_customers AS cu ON pcsp.customer_id = cu.id ';
                $join .= ' JOIN pocomos_marketing_types AS pmt ON c.found_by_type_id = pmt.id ';
                $where .= ' pmt.name = ' . $terms->foundByType;
            }

            if (isset($terms->salesperson) && $terms->salesperson != null) {
                $join .= ' JOIN pocomos_salespeople AS sp ON c.salesperson_id = sp.id ';
                $join .= ' JOIN pocomos_company_office_users AS pcou ON sp.user_id = pcou.id ';
                $join .= ' JOIN orkestra_users AS ou ON pcou.user_id = ou.id ';
                $where .= ' ou.first_name = ' . $terms->salesperson;
            }

            // if ($terms->acctOnFile) {
            //     $qb->join('p.accounts', 'acct')
            //         ->andWhere('acct INSTANCE OF Orkestra\Transactor\Entity\Account\BankAccount OR acct INSTANCE OF Orkestra\Transactor\Entity\Account\CardAccount');
            // }

            if (isset($terms->autopayOnFile) && $terms->autopayOnFile) {
                $where .= ' c.auto_renew = true';
            }

            if (isset($terms->hasEmailOnFile)) {
                if ($terms->hasEmailOnFile == true) {
                    $where .= " cu.email != '' AND cu.email IS NOT null";
                } else {
                    $where .= " cu.email = '' AND cu.email IS null";
                }
            }

            if ($terms) {
                if (isset($terms->email) && $terms->email != null) {
                    // Simple check that the customer has an email address
                    $where .= " cu.email like \'%@%\'";

                    if ($terms->email == config('constants.VERIFIED_EMAIL')) {
                        $where .= " cu.email_verified = true";
                    }
                }

                if (isset($terms->technician) && $terms->technician != null) {
                    $where .= " j.technician_id = '.$terms->technician.'";
                }
            }
        }
        if (isset($terms->terms) && $terms->terms) {
            $where .= " cu.firstName like %'.$terms->terms.'%";
            $where .= " cu.last_name like %'.$terms->technician.'%";
            $where .= " cu.email like %'.$terms->technician.'%";
            $where .= " ca.street like %'.$terms->technician.'%";
            $where .= " ca.suite like %'.$terms->technician.'%";
            $where .= " ca.city like %'.$terms->technician.'%";
            $where .= " ca.street like %'.$terms->technician.'%";
            $where .= " a.name like %'.$terms->technician.'%";
        }
        $qb = array('sql' => $sql, 'join' => $join, 'where' => $where, 'orderBy' => $orderBy);
        return $qb;
    }

    public function getUnpaidMiscInvoices($office, $terms)
    {
        $miscInvoices = array();

        if ($terms['jobType'] === null && $terms['includeMiscInvoices'] === true) {
            $miscInvoices = $this->findUnpaidMiscInvoicesWithSearchTerms($office, $terms);
        }

        return $miscInvoices;
    }

    public function findUnpaidMiscInvoicesWithSearchTerms($office, $terms)
    {
        $invIds = PocomosInvoice::join('pocomos_jobs as pj', 'pocomos_invoices.id', 'pj.invoice_id')->pluck('invoice_id')->toArray();

        $query = PocomosInvoice::select(
            'pcu.external_account_id',
            'pcs.balance_overall',
            'pocomos_invoices.id',
            'pocomos_invoices.date_due as invoice_date_due',
            'pcu.first_name',
            'pcu.last_name',
            'pcu.email',
            'pcu.status',
            'pocomos_invoices.amount_due',
            'pocomos_invoices.balance as invoice_balance',
            'ppn.number',
            'pa.city',
            'ocr.name',
            'pa.postal_code',
            'pbpn.number',
            // 'pba.street','pba.suite',
            DB::raw("CONCAT(pba.street,pba.suite)"),
            'pba.city',
            'ocr.name',
            'pba.postal_code'
        )
            ->join('pocomos_contracts as pc', 'pocomos_invoices.contract_id', 'pc.id')
            ->join('pocomos_pest_contracts as ppc', 'pc.id', 'ppc.contract_id')
            ->join('pocomos_customer_sales_profiles as pcsp', 'pc.profile_id', 'pcsp.id')
            ->join('pocomos_customers as pcu', 'pcsp.customer_id', 'pcu.id')
            ->leftJoin('pocomos_customer_state as pcs', 'pcu.id', 'pcs.customer_id')
            ->join('pocomos_addresses as pa', 'pcu.contact_address_id', 'pa.id')
            ->join('pocomos_addresses as pba', 'pcu.billing_address_id', 'pba.id')
            ->leftJoin('pocomos_phone_numbers as ppn', 'pa.phone_id', 'ppn.id')
            ->leftJoin('pocomos_phone_numbers as pbpn', 'pba.phone_id', 'pbpn.id')
            ->leftJoin('orkestra_countries_regions as ocr', 'pa.region_id', 'ocr.id')
            ->join('pocomos_agreements as pag', 'pc.agreement_id', 'pag.id')
            ->whereNotIn('pocomos_invoices.id', $invIds);

        if ($terms['dateStart'] && $terms['dateEnd']) {
            $dateStart = $terms['dateStart']->modify('midnight')->format('Y-m-d H:i:s');
            $dateEnd = $terms['dateEnd']->modify('midnight +1 day -1 second')->format('Y-m-d H:i:s');

            $query->whereBetween('pocomos_invoices.date_due', [$dateStart, $dateEnd]);
        }

        $multidateSpans = false;
        if (isset($terms['dateSpans']) && $terms['dateSpans']) {
            $multidateSpans = true;
            foreach ($terms['dateSpans'] as $span) {
                $start = $span[0]->format('Y-m-d H:i:s');
                $end = $span[1]->format('Y-m-d H:i:s');

                if ($start === null) {
                    $query->where('pocomos_invoices.date_due', '>', $end);
                } elseif (is_null($end)) {
                    $query->where('pocomos_invoices.date_due', '<', $start);
                } else {
                    $query->whereBetween('pocomos_invoices.date_due', [$start, $end]);
                }
            }
        }

        $query->where('pag.office_id', $office->id)
            ->where('pocomos_invoices.status', '!=', 'Cancelled');

        if ($terms['status'] !== null) {
            if ($terms['status'] === 'Unpaid') {
                $query->whereIn('pocomos_invoices.status', ['Past due', 'Due', 'In collections', 'Collections']);
            } elseif ($terms['status'] === 'Paid') {
                $query->where('pocomos_invoices.status', 'Paid');
            } else {
                $query->where('pocomos_invoices.status', $terms['status']);
            }
        }

        $columns = [
            'Account ID',
            'Account Balance',
            'Invoice ID',
            'Due Date',
            'First Name',
            'Last Name',
            'Email',
            'Account Status',
            'Service Price',
            'Unpaid Balance',
            'Service Phone',
            'Service Address',
            'Service City',
            'Service State',
            'Service Zip',
            'Billing Phone',
            'Billing Address',
            'Billing City',
            'Billing State',
            'Billing Zip',
        ];

        $result = $query->get()->toArray();

        $q = array_merge($columns, $result);

        return $result;
    }

    public function calculateUnpaidOutstandingBalance($jobs, $miscInvoices)
    {
        $balances = array_merge(
            array_map(function ($job) {
                return $job->balance;
            }, $jobs),
            array_map(function ($invoice) {
                return $invoice['invoice_balance'];
            }, $miscInvoices)
        );

        return array_sum($balances);
    }

    public function calculateUnpaidOutstandingBalanceImproved($jobs, $miscInvoices)
    {
        $balances = array_merge(
            array_map(function ($job) {
                return $job['invoice_balance'];
            }, $jobs),
            array_map(function ($invoice) {
                return $invoice['invoice_balance'];
            }, $miscInvoices)
        );

        return array_sum($balances);
    }

    /**Update office billing profile numbers */
    public function updateOfficeBillingProfileNumbers($officeBillingProfile)
    {
        $office = $officeBillingProfile->office_details;

        if ($office->parent_id === null) {
            $officeState = PocomosReportsOfficeState::where('office_id', $office->id)->latest('date_created')->first();
        } else {
            $officeState = PocomosReportsOfficeState::where('office_id', $office->parent_id)->latest('date_created')->first();
        }

        $standardPrice = PocomosStandardPrice::whereActive(true)->whereEnabled(true)->where('min_customer_number', '<=', $officeState->customers)->where('max_customer_number', '>=', $officeState->customers)->latest('date_created')->first();

        if ($standardPrice) {
            $price = $standardPrice->price;
        } else {
            $price = config('constants.DEFAULT_PRICE');
        }

        if (!$officeBillingProfile->price_per_active_customer_override) {
            $officeBillingProfile->price_per_active_customer = $price;
        }
        if (!$officeBillingProfile->price_per_active_user_override) {
            $officeBillingProfile->price_per_active_user = 0;
        }
        if (!$officeBillingProfile->price_per_sales_user_override) {
            $officeBillingProfile->price_per_sales_user = 0;
        }
        if (!$officeBillingProfile->price_per_sent_sms_override) {
            $officeBillingProfile->price_per_sent_sms = 3;
        }
        $officeBillingProfile->save();
        return true;
    }

    /**
     * Notify Admin users that office report is being updated
     */
    public function notifyAdminUsersWithBillingUpdates()
    {
        $officeUsers = PocomosCompanyOfficeUser::get();

        foreach ($officeUsers as $officeUser) {
            $alert = $this->createAlert(
                /* title */
                'Office Billing Updated',
                /* message */
                'Started generating the Billing Report',
                /* priority */
                config('constants.SUCCESS')
            );
        }
        return true;
    }

    public function createAssignedAlert($assignedBy, $assignedTo, $name, $description, $priority)
    {
        return $alert = $this->createAlert($name, $description, $priority);
    }

    /**Create alert */
    public function createAlert($title, $message, $priority)
    {
        $input['name'] = $title;
        $input['description'] = $message;
        $input['priority'] = $priority;
        $input['status'] = 'Posted';
        $input['type'] = 'Alert';
        $input['active'] = 1;
        $input['notified'] = 1;
        $alert = PocomosAlert::create($input);

        $office_alert_details['alert_id'] = $alert->id;
        $office_alert_details['assigned_by_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
        $office_alert_details['assigned_to_user_id'] = auth()->user()->pocomos_company_office_user->id ?? null;
        $office_alert_details['active'] = true;
        $office_alert_details['date_created'] = date('Y-m-d H:i:s');
        PocomosOfficeAlert::create($office_alert_details);
    }

    public function getPestpacCustomerForExport($exportCustId, $office, $isOwnerUser = false)
    {
        $sql = "SELECT pec.* FROM pocomos_pestpac_export_customers AS pec
        JOIN pocomos_company_offices AS o ON pec.office_id = o.id
        WHERE pec.active = true AND pec.id = $exportCustId AND o.active = true ";

        $whereCondition = ' AND pec.office_id = ' . $office . ' ';
        if ($isOwnerUser) {
            $officeDetail = PocomosCompanyOffice::findOrFail($office);
            $office = $officeDetail->getParent();
            $office = $office->id;
            $whereCondition .= ' AND (o.parent_id = ' . $office . ' OR pec.office_id = ' . $office . ') ';
        }
        $sql .= $whereCondition . ' LIMIT 1';
        return DB::select(DB::raw($sql));
    }

    public function getPestpacConfigurationForExportCustomer($exportCustomer)
    {
        $pestPacConfig = PocomosPestpacConfig::where('office_id', $exportCustomer->office_id)->where('active', true)->where('enabled', true)->get();

        if (!$pestPacConfig) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to find an enabled PestPac Configuration.']));
        }

        return $pestPacConfig;
    }

    public function getCreationMap()
    {
        return [
            [
                'functionName' => 'LocationCreation',
                'preRequisites' => [],
                'resultingValue' => 'location_id',
            ],
            [
                'functionName' => 'CustomerNotes',
                'preRequisites' => ['location_id'],
                'resultingValue' => 'notes_added',
            ],
            [
                'functionName' => 'DocumentCreation',
                'preRequisites' => ['location_id'],
                'resultingValue' => 'contract_file_id',
            ],
            [
                'functionName' => 'DocumentUpload',
                'preRequisites' => ['location_id', 'contract_file_id'],
                'resultingValue' => 'ContractFileUploaded',
            ],
            [
                'functionName' => 'ServiceSetupCreation',
                'preRequisites' => ['location_id', 'bill_to_id'],
                'resultingValue' => 'service_setup_id',
            ],
            [
                'functionName' => 'ServiceOrderCreation',
                'preRequisites' => ['location_id', 'bill_to_id', 'service_setup_id'],
                'resultingValue' => 'ServiceOrderId',
            ],
            // [
            //     'functionName' => 'VantivToken',
            //     'preRequisites' => [],
            //     'resultingValue' => 'card_token',
            // ],
            [
                'functionName' => 'PaymentToken',
                'preRequisites' => ['bill_to_id'],
                'resultingValue' => 'card_id',
            ],
            [
                'functionName' => 'ServiceSetupCreditCard',
                'preRequisites' => ['card_id'],
            ],
        ];
    }

    public function createEveryThing($exportCust, $pestpacConfiguration)
    {
        //All functions are built to require the same params.
        $params = [$exportCust, $pestpacConfiguration];
        //We just want to track the entity for further flushing.
        $map = $this->getCreationMap();
        //Getting the number of the last key
        $lastArrayKeyNumber = count($map) - 1;
        foreach ($map as $key => $func) {
            if ($exportCust->status === config('constants.FAILED')) {
                break;
            }
            $proceed = true;
            $exportCustArr = $exportCust->toArray();

            foreach ($func['preRequisites'] as $prereq) {
                //Some functions return bools, others return ints/nulls.
                //If the prerequisites are not fullfilled, then stop.
                if (!$exportCustArr[$prereq]) {
                    $proceed = false;
                    break;
                }
            }
            if ($proceed) {
                //If a resulting value has already been acquired -> pass.
                if (isset($func['resultingValue']) && $exportCustArr[$func['resultingValue']]) {
                    continue;
                }

                //ry method is handles errors on it's own.
                call_user_func_array(array($exportCust, $func['functionName']), $params);

                //If the first part fails, it will not even try to do the second comparison.
                if ($key === $lastArrayKeyNumber && $exportCust->status != config('constants.FAILED')) {
                    $exportCust->status = config('constants.SUCCESS');
                }
                //We flush every loop, because if something fails - we can lose data.
                //is is supposed to run as a Worker, so we don't care for slower code if it saves our butts.
                $exportCust->save();
            }
        }
        return $exportCust->status == config('constants.SUCCESS');
    }

    /**
     * Update service order data
     */
    public function serviceOrderUpdate($pestpacExportCustomer)
    {
        $uri = "serviceOrders/" . $pestpacExportCustomer->service_order_id;
        $method = 'PATCH';

        try {
            $data = $this->getUpdatedServiceOrderData($pestpacExportCustomer);
            // $this->getHeaders()
            $result = $this->attemptRequest($uri, $method, array(), $data);
        } catch (\Exception $e) {
            $result = array('error' => true, 'errorMessage' => $e->getMessage(), 'URI' => $uri);
        }

        if (isset($result['error']) && $result['error']) {
            $pestpacExportCustomer->status = config('constants.FAILED');
            if (isset($result['URI'])) {
                $URI = $result['URI'];
            } else {
                $URI = '';
            }
            $errorMessage = $URI . ' Error:: ' . $result['errorMessage'];
            $errorMessage = $pestpacExportCustomer->errors . $errorMessage;
            $errorMessage = substr($errorMessage, 0, 1024);
            // $pestpacExportCustomer->errors = $pestpacExportCustomer->errors . "\n" . $errorMessage;
            $pestpacExportCustomer->errors = $URI . ' Error:: ' . $result['errorMessage'];
            $pestpacExportCustomer->save();
        }

        return $result;
    }

    /**
     * Get service order data to be updated
     */
    public function getUpdatedServiceOrderData($exportCustomer)
    {
        $pestContract = $exportCustomer->pest_contract;
        $initialJob = $this->findInitialServiceForContract($pestContract);

        if (!$initialJob) {
            return;
        }

        $initialJob = (object)$initialJob[0];
        $initialJob = PocomosJob::findOrFail($initialJob->id);

        $workDate = $initialJob->date_scheduled;
        $jobSlot = $initialJob->route_detail;
        if ($jobSlot) {
            // dd($workDate);
            // dd(date('H', strtotime($jobSlot->time_begin)) . ':' . date('i', strtotime($jobSlot->time_begin)));
            $workDate .= ' ' . date('H', strtotime($jobSlot->time_begin)) . ':' . date('i', strtotime($jobSlot->time_begin));
            // dd($workDate);
        }

        return array(
            array(
                'op' => 'replace',
                'path' => '/WorkDate',
                'value' => $this->getPestpacDate($exportCustomer->office_id, $workDate)
            ),
            array(
                'op' => 'replace',
                'path' => '/Duration',
                'value' => $initialJob->route_detail ? $initialJob->route_detail->duration : 60,
            ),
        );
    }

    public function findInitialServiceForContract($contract)
    {
        $res = DB::select(DB::raw("SELECT j.*
            FROM pocomos_jobs AS j
            WHERE j.type = '" . config('constants.INITIAL') . "' AND j.active = 1 ORDER By j.date_completed DESC LIMIT 1"));
        return $res;
    }

    public function getPestpacDate($office, $pocomosDateTime)
    {
        $pacSetting = PocomosPestpacSetting::whereOfficeId($office)->firstOrFail();
        $timezone = $pacSetting->timezone_detail;

        if (!$timezone) {
            throw new \Exception(__('strings.message', ['message' => 'No Timezone defined for pestpac.']));
        }

        $pestPacDate = new \DateTime(date('Y-m-d H:i:s', strtotime($pocomosDateTime)), new \DateTimeZone($timezone->php_name));

        return $pestPacDate->format(config('constants.DATE_ATOM'));
    }

    public function timeZoneDetail()
    {
        return $this->belongsTo(PocomosTimezone::class, 'office_id');
    }

    public function attemptRequest($uri, $method, $headers, $body = [], $bodyParam = null)
    {
        $response = [
            'error' => 0,
            'errorMessage' => '',
            'URI' => $uri,
            'message' => '',
            'response' => ''
        ];
        try {
            $this->client = new Client([
                'base_uri' => ''
            ]);

            if ($bodyParam) {
                // new Client
                $var = $this->client->request($method, $uri, array(
                    'headers' => $headers,
                    $bodyParam => $body
                ));
            } else {
                $request = new Request($method, $uri, $headers, json_encode($body, JSON_OBJECT_AS_ARRAY));
                $var = $this->client->send($request);
            }

            $message = json_decode($var->getBody());
            $response['message'] = $message;
            $response['response'] = $var;
        } catch (RequestException $e) {
            $response['error'] = 1;
            if ($e->hasResponse()) {
                $response['errorMessage'] = $e->getMessage();
                $response['response'] = $e->getResponse();
            }
        }

        return $response;
    }

    /**
     * Update service setup data
     */
    public function serviceSetupUpdate($pestpacExportCustomer)
    {
        $uri = "ServiceSetups/" . $pestpacExportCustomer->service_setup_id ?? null;
        $method = 'PATCH';

        try {
            $data = $this->getUpdatedServiceSetupData($pestpacExportCustomer);

            $result = $this->attemptRequest($uri, $method, [], $data);
        } catch (\Exception $e) {
            $result = array('error' => true, 'errorMessage' => $e->getMessage(), 'URI' => $uri);
        }

        if (isset($result['error']) && $result['error']) {
            $pestpacExportCustomer->status = config('constants.FAILED');
            if (isset($result['URI'])) {
                $URI = $result['URI'];
            } else {
                $URI = '';
            }
            $errorMessage = $URI . ' Error:: ' . $result['errorMessage'];
            $errorMessage = $pestpacExportCustomer->errors . $errorMessage;
            $errorMessage = substr($errorMessage, 0, 1024);
            // $pestpacExportCustomer->errors = $pestpacExportCustomer->errors . "\n" . $errorMessage;
            $pestpacExportCustomer->errors = $URI . ' Error:: ' . $result['errorMessage'];
            $pestpacExportCustomer->save();
        }

        return $result;
    }

    /**
     * Get service setup data to be updated
     */
    public function getUpdatedServiceSetupData($exportCustomer)
    {
        $pestContract = $exportCustomer->pest_contract;
        $initialJob = $this->findInitialServiceForContract($pestContract);

        $workTime = $initialJob->date_scheduled ?? null;
        $jobSlot = $initialJob->route_detail ?? null;
        if ($jobSlot) {
            // $workTime->time_scheduled = date('H', strtotime($jobSlot->time_begin)) . ':' . date('i', strtotime($jobSlot->time_begin));
            $workTime .= ' ' . date('H', strtotime($jobSlot->time_begin)) . ':' . date('i', strtotime($jobSlot->time_begin));
            // $workTime->save();
        }

        return array(
            array(
                'op' => 'replace',
                'path' => '/StartDate',
                'value' => $this->getPestpacDate($exportCustomer->office_id, $initialJob->date_scheduled ?? null),
            ),
            array(
                'op' => 'replace',
                'path' => '/LastGeneratedDate',
                "value" => $this->getPestpacDate($exportCustomer->office_id, $workTime),
            ),
        );
    }

    public function getPestContractsByJobIdsAndOffices($jobIds, $offices)
    {
        $jobIds = $this->convertArrayInStrings($jobIds);
        $offices = $this->convertArrayInStrings($offices);

        return DB::select(DB::raw("SELECT pcc.*
            FROM pocomos_pest_contracts AS pcc
            JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
            JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
            JOIN pocomos_jobs AS j ON pcc.contract_id = j.id
            JOIN pocomos_company_offices AS o ON csp.office_id = o.id
            WHERE j.id IN ($jobIds) AND o.id IN ($offices)"));
    }

    public function getPestContractsByInvoiceIdsAndOffices(array $invoiceIds, array $offices)
    {
        $invoiceIds = $this->convertArrayInStrings($invoiceIds);
        $offices = $this->convertArrayInStrings($offices);

        return DB::select(DB::raw("SELECT pcc.*
            FROM pocomos_pest_contracts AS pcc
            JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
            JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
            JOIN pocomos_invoices AS i ON c.id = i.contract_id
            JOIN pocomos_company_offices AS o ON csp.office_id = o.id
            WHERE i.id IN ($invoiceIds) AND o.id IN ($offices)"));
    }

    public function resendEmailAction($profile, $params)
    {
        try {
            switch ($params['type']) {
                case config('constants.VERIFICATION'):
                    $this->generateVerificationEmail($profile->customer_details, $params['officeUserId']);

                    break;

                case config('constants.INVOICES'):
                    $office = $profile->office_details;
                    $this->generateResendInvoicesEmail($office, $profile->customer_details, $params['invoices']);

                    break;

                case config('constants.SUMMARY'):
                    $pestContract = $this->hydratePestContract($params['contract_id']);
                    $this->generateBillingSummaryEmail($pestContract, $params['summary'] == 'paid', $params['officeUserId']);

                    break;

                case config('constants.CONTRACT'):
                    $pestContract = $this->hydratePestContract($params['contract_id']);
                    $this->generateWelcomeEmailNew($pestContract, $params['officeUserId']);

                    break;

                case config('constants.CUSTOMER_USER'):
                    $this->generateCustomerPortalEmail($profile, $params['officeUserId']);

                    break;

                case config('constants.REMOTE_COMPLETION'):
                    $pestContract = $this->hydratePestContract($params['contract_id']);
                    $this->generateRemoteCompletionEmail($pestContract, $params['officeUserId']);

                    break;
            }
        } catch (\Exception $e) {
            Log::info("Error : File - " . $e->getFile() . " Line - " . $e->getLine() . " Message - " . $e->getMessage() . ' type ' . $params['type']);
        }
    }

    /**
     * @param PocomosPestContract|int $contractId
     * @return PocomosPestContract
     */
    public function hydratePestContract($contractId = null)
    {
        // This allows the subclass ResendBulkEmailJob to skip any additional work,
        // since it already has the contracts hydrated.
        return $contractId ? PocomosPestContract::where('contract_id', $contractId)->firstOrFail() : array();
    }

    public function generateVerificationEmail($customer, $officeUserId)
    {
        /** @var PocomosCustomer $customer */
        $profile = $customer->sales_profile;
        $office = $customer->sales_profile->office_details;

        $subject = $office->name ?? '' . ' wants you to verify your email';
        $linkData = $this->getVerifyEmailLink($customer);

        $data = array(
            'customer' => $customer,
            'office' => $office,
            'id' => $customer->id,
            'hash' => $this->getEmailVerificationHash($customer),
            'html_link' => $linkData['html_link'] ?? '',
            'url' => $linkData['url'] ?? ''
        );
        $body = view('emails.verify_email', ['data' => $data])->render();

        $emails = unserialize($office->email);
        $from = $emails[0] ?? '';
        $customerEmail = $customer->email;

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUserId;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = 'Verify Email Address';
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUserId;
        PocomosEmailMessage::create($input);

        Mail::send('emails.verify_email', ['data' => $data], function ($message) use ($subject, $customerEmail, $from) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
        });
    }

    public function generateWelcomeEmailNew($pcc, $officeUserId)
    {
        /** @var PocomosPestContract $pestContract */
        $pestContract = $pcc;
        $salesContract = $pestContract->contract_details;
        $profile = $salesContract->profile_details;
        $office = $profile->office_details;
        $customer = $profile->customer_details;
        $config = PocomosPestOfficeSetting::where('office_id', $office->id)->first();
        $initialJob = PocomosJob::where('contract_id', $pestContract->id)->where('type', 'Initial')->first();
        if (!$initialJob) {
            $job = $this->getFirstJobNew($pestContract->id);
            if ($job) {
                $initialJob = $job;
            }
        }

        $body = $this->renderDynamicTemplate($config->welcome_letter, null, $customer, $pestContract, $initialJob);

        $subject = 'Welcome to ' . $office->name;

        $emails = unserialize($office->email);
        $from = $emails[0] ?? '';
        $customerEmail = $customer->email;

        $customer = $profile->customer_details;
        $agreement = $salesContract->agreement_detail;
        $path = $this->getContractFilename($salesContract);

        if (!file_exists($path)) {
            $pdf = $this->agreementGenerator(array(
                'office' => $office,
                'customer' => $customer,
                'agreement' => $agreement,
                'contract' => $salesContract,
                'pestContract' => $pestContract,
                'profile' => $profile
            ));
            file_put_contents($path, $pdf);
        }

        $customerName = $customer->first_name . ' ' . $customer->last_name;
        $customerName = str_replace(' ', '_', $customerName);
        $filename = $customerName . '_' . ($salesContract->signature_details ? 'signed_agreement.pdf' : 'agreement.pdf');

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUserId;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = 'Welcome Email';
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUserId;
        PocomosEmailMessage::create($input);

        $file_details = $this->getFileInfo($path);
        //store file into document folder
        $file_input['path'] = $path;
        //store your file into database
        $file_input['filename'] = $filename;
        $file_input['mime_type'] = 'application/pdf';
        $file_input['file_size'] = $file_details['fileSize'] ?? 0;
        $file_input['active'] = true;
        $file_input['md5_hash'] =  '';
        $file =  OrkestraFile::create($file_input);

        PocomosEmailsAttachedFile::create(['email_id' => $email->id, 'file_id' => $file->id]);

        Mail::send('emails.dynamic_email_render', ['agreement_body' => $body], function ($message) use ($subject, $customerEmail, $from, $path, $filename) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
            $message->attachData(file_get_contents($path), $filename);
        });
    }

    public function getFirstJobNew($contract_id)
    {
        $jobs = PocomosJob::where('contract_id', $contract_id)->get()->toArray();
        usort($jobs, function ($a, $b) {
            $a = (object) $a;
            $b = (object) $b;
            return $a->date_scheduled > $b->date_scheduled;
        });
        return reset($jobs);
    }

    /**
     * @param PocomosPestContract $pestContract
     */
    // THIS API IS UNDER DEVELOPMENT
    public function generateRemoteCompletionEmail($pestContract, $officeUserId)
    {
        $salesContract = $pestContract->contract_details;
        $profile = $salesContract->profile_details;
        $office = $profile->office_details;
        $customer = $profile->customer_details;

        $this->sendRemoteCompletionEmail($customer->id, $salesContract->id, $officeUserId);
        return true;
    }

    /**
     * @param PocomosCustomerSalesProfile $profile
     */
    public function generateCustomerPortalEmail($profile, $officeUserId)
    {
        $customer = $profile->customer_details;

        $office = $profile->office_details;
        $emails = unserialize($office->email);
        $from = $emails[0] ?? '';
        $customerEmail = $customer->email;

        $data = array(
            'customer' => $customer,
            'office' => $office,
            'pocomos_customer_user_start' => $office->customer_portal_link ?? '#',
        );
        $subject = 'Setup your Customer Portal Account';

        $body = view('emails.customer_portal_account', ['data' => $data])->render();

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUserId;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = $this->args['type'];
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUserId;
        PocomosEmailMessage::create($input);

        Mail::send('emails.customer_portal_account', ['data' => $data], function ($message) use ($subject, $customerEmail, $from) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
        });

        return true;
    }

    /**
     * @param PocomosPestContract $pcc
     */
    // THIS API IS UNDER DEVELOPMENT
    public function generateBillingSummaryEmail($pcc, $paid, $officeUserId)
    {
        /** @var PocomosPestContract $contract */
        $contract = $pcc;
        $valid_statuses = array(config('constants.PAID'));
        $invoice_ids = PocomosInvoice::whereIn('status', $valid_statuses)->pluck('id')->toArray();
        $jobs = PocomosJob::whereIn('invoice_id', $invoice_ids)->pluck('id')->toArray();
        $invoices = $invoice_ids;

        $ids = array_merge($jobs, $invoices);
        sort($ids);
        $path = $this->getFilename(config('constants.INVOICES'), config('constants.SUMMARY'), implode('_', $ids), '.pdf');

        // if (!file_exists($path)) {
        $jobIds = $jobs;
        $miscInvoiceIds = $invoices;
        $miscInvoiceIdsStr = $this->convertArrayInStrings($miscInvoiceIds);

        $ids = array_merge($jobIds, $miscInvoiceIds);
        sort($ids);
        $billingSummaryPath = $this->getFilename(config('constants.INVOICES'), config('constants.SUMMARY'), implode('_', $ids), '.pdf');
        $matches = array();
        preg_match('/(?<=summary\/)([^.]*)/', $billingSummaryPath, $matches);
        $billingSummaryHash = $matches[0];

        // if (file_exists($billingSummaryPath)) {
        //     return $billingSummaryHash;
        // }

        $jobIds = $this->convertArrayInStrings($jobs);

        $jobs = DB::select(DB::raw("SELECT j.*
            FROM pocomos_jobs AS j
            WHERE j.id IN ($jobIds)"));

        $miscInvoices = DB::select(DB::raw("SELECT c.*, a.*, o.*, csp.*, cu.*, i.id as 'id'
            FROM pocomos_invoices AS i
            JOIN pocomos_contracts AS c ON i.contract_id = c.id
            JOIN pocomos_agreements AS a ON c.agreement_id = a.id
            JOIN pocomos_company_offices AS o ON a.office_id = o.id
            JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
            JOIN pocomos_customers AS cu ON csp.customer_id = cu.id
            WHERE i.id IN ($miscInvoiceIdsStr)"));

        $invoices = array();
        $pdf = null;

        foreach ($jobs as $job) {
            $invoice = PocomosInvoice::findOrFail($job->invoice_id);
            $pdf = $this->getInvoiceBasePdf($invoice);
            $invoices[] = $pdf;
        }
        foreach ($miscInvoices as $val) {
            $invoice = PocomosInvoice::findOrFail($val->id);
            $pdf = $this->getInvoiceBasePdf($invoice);
            $invoices[] = $pdf;
        }

        $pdf = array_shift($invoices);
        $finalPdf = $pdf;

        // foreach ($invoices as $invoice) {
        //     $invoice = PdfDocument::parse($invoice);

        //     foreach ($invoice->pages as $page) {
        //         $finalPdf->pages[] = clone $page;
        //     }
        // }

        $file = $this->fileContentBaseUploadS3(config('constants.INVOICES'), $finalPdf, implode('_', $ids));
        // }

        $profile = $contract->contract_details->profile_details;
        $customer = $profile->customer_details;
        $office = $profile->office_details;
        $emails = unserialize($office->email);
        $from = $emails[0] ?? '';
        $customerEmail = $customer->email;
        $body = 'Please see attached summary';
        $subject = 'Paid Billing Summary';

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUserId;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = $this->args['type'];
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUserId;
        PocomosEmailMessage::create($input);

        Mail::send('emails.dynamic_email_render', ['agreement_body' => $body], function ($message) use ($subject, $customerEmail, $from, $file) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
            $message->attachData(file_get_contents($file), 'Summary.pdf');
        });

        $file_details = $this->getFileInfo($file);
        //store file into document folder
        $file_input['path'] = $file;
        //store your file into database
        $file_input['filename'] = 'Summary.pdf';
        $file_input['mime_type'] = 'application/pdf';
        $file_input['file_size'] = $file_details['fileSize'] ?? 0;
        $file_input['active'] = true;
        $file_input['md5_hash'] =  '';
        $file =  OrkestraFile::create($file_input);

        PocomosEmailsAttachedFile::create(['email_id' => $email->id, 'file_id' => $file->id]);

        return true;
    }

    /**
     * @param PocomosCompanyOffice $office
     * @param PocomosCustomer $customer
     * @param PocomosInvoice[] $invoices
     */
    // THIS API IS UNDER DEVELOPMENT
    public function generateResendInvoicesEmail($office, $customer, $invoices)
    {
        $invoices = $this->hydrateInvoices($office, $invoices);

        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($office->id)->whereUserId(auth()->user()->id)->first();

        $profile = $customer->sales_profile;
        $office = $profile->office_details;
        $emails = unserialize($office->email);
        $from = $emails[0] ?? '';
        $customerEmail = $customer->email;
        $body = 'Please see attached invoices.';
        $subject = 'Invoices';
        $filename = '';

        foreach ($invoices as $invoice) {
            $pdf = $this->getInvoiceBasePdf($invoice);
            $path = $this->fileContentBaseUploadS3(config('constants.INVOICES'), $pdf, $invoice->id);
            $filename = 'Invoice #' . $invoice->id . '.pdf';
        }

        $email_input['office_id'] = $office->id;
        $email_input['office_user_id'] = $officeUser->id;
        $email_input['customer_sales_profile_id'] = $profile->id;
        $email_input['type'] = config('constants.INVOICES');
        $email_input['body'] = $body;
        $email_input['subject'] = $subject;
        $email_input['reply_to'] = $from;
        $email_input['reply_to_name'] = $office->name ?? '';
        $email_input['sender'] = $from;
        $email_input['sender_name'] = $office->name ?? '';
        $email_input['active'] = true;
        $email = PocomosEmail::create($email_input);

        $input['email_id'] = $email->id;
        $input['recipient'] = $customer->email;
        $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
        $input['date_status_changed'] = date('Y-m-d H:i:s');
        $input['status'] = 'Delivered';
        $input['external_id'] = '';
        $input['active'] = true;
        $input['office_user_id'] = $officeUser->id;
        PocomosEmailMessage::create($input);

        $file_details = $this->getFileInfo($path);
        //store file into document folder
        $file_input['path'] = $path;
        //store your file into database
        $file_input['filename'] = $filename;
        $file_input['mime_type'] = 'application/pdf';
        $file_input['file_size'] = $file_details['fileSize'] ?? 0;
        $file_input['active'] = true;
        $file_input['md5_hash'] =  '';
        $file =  OrkestraFile::create($file_input);

        PocomosEmailsAttachedFile::create(['email_id' => $email->id, 'file_id' => $file->id]);

        Mail::send('emails.dynamic_email_render', ['agreement_body' => $body], function ($message) use ($subject, $customerEmail, $from, $path, $filename) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
            $message->attachData(file_get_contents($path), $filename);
        });

        return true;
    }

    public function hydrateInvoices($office, $invoiceIds)
    {
        $invoiceIds = $this->convertArrayInStrings($invoiceIds);

        $data = DB::select(DB::raw("SELECT pin.*
        FROM pocomos_invoices AS pin
        JOIN pocomos_contracts AS pc ON pin.contract_id = pc.id
        JOIN pocomos_agreements AS pa ON pc.agreement_id = pa.id
        WHERE pc.active = 1 AND pa.office_id = $office->id AND pin.id IN($invoiceIds)"));

        return $data;
    }

    public function getCustomerData($exportCustomer)
    {
        $customer = $exportCustomer->customerDetail;
        $address = $customer->contact_address;
        $pestContract = $exportCustomer->pest_contract;
        $contract = $pestContract->contract_details;

        $customerData = [
            // "Branch" => $this->getBranchName(),
            "Company" => $customer->company_name,
            "LastName" => $customer->last_name,
            "FirstName" => $customer->first_name,
            "Address" => $address->street,
            "Address2" => $address->suite,
            "City" => $address->city,
            "State" => $address->region->code,
            "Zip" => $address->postal_code,
            "Country" => $address->region->country_detail->name,
            "Phone" => preg_replace('/[^0-9]/', '', $address->primaryPhone->number),
            "AlternatePhone" => preg_replace('/[^0-9]/', '', $address->altPhone->number),
            "EMail" => $customer->email,
            "Active" => true,
            "IncludeInMailings" => true,
            "EnteredDate" => date('Y-m-d', strtotime($customer->date_created)),
            // "TaxCode" => $this->getPPTaxcode($contract),
            "DoNotGeocode" => true,
            "PurchaseOrderNumber" => $contract->purchase_order_number,
            // "UserDefinedFields" => $this->getUserDefinedFields($exportCustomer),
            "Source" => $exportCustomer->getPestpacSettings->source ?? '',
        ];
        $latitude = $address->latitude;
        $longitude = $address->longitude;
        if ($latitude !== null && $longitude !== null) {
            $customerData['Latitude'] = $latitude;
            $customerData['Longitude'] = $longitude;
        }
        return $customerData;
    }

    private function handleError($result, $exportCustomer, $appendToMessage = false)
    {
        if ($result['error']) {
            $exportCustomer->status = config('constants.FAILED');
            if (isset($result['URI'])) {
                $URI = $result['URI'];
            } else {
                $URI = '';
            }
            $errorMessage = $URI . ' Error:: ' . $result['errorMessage'];
            $errorMessage = $exportCustomer->errors . $errorMessage;
            $errorMessage = substr($errorMessage, 0, 1024);
            if ($appendToMessage) {
                $exportCustomer->errors = $exportCustomer->errors . "\n" . $errorMessage;
            } else {
                $exportCustomer->errors = $errorMessage;
            }
            $exportCustomer->save();
            return false;
        }
        return true;
    }

    public function transformImportedCustomerToCustomerModel($importedCustomer)
    {
        $phone = new PocomosPhoneNumber();
        $phone->alias = 'Primary';
        $phone->type = 'Home';
        $phone->number = $importedCustomer->phone ?? null;
        $phone->active = true;
        $phone->date_created = date('Y-m-d H:i:s');
        $phone->save();

        $altPphone = new PocomosPhoneNumber();
        $altPphone->alias = 'Alternate';
        $altPphone->type = 'Home';
        $altPphone->number = $importedCustomer->alt_phone ?? null;
        $altPphone->active = true;
        $altPphone->date_created = date('Y-m-d H:i:s');
        $altPphone->save();

        $customerModel = new PocomosCustomer();
        $customerModel->external_account_id = $importedCustomer->external_identifier ?? '';
        $customerModel->first_name = $importedCustomer->first_name ?? null;
        $customerModel->last_name = $importedCustomer->last_name ?? null;
        $customerModel->company_name = $importedCustomer->company_name ?? null;
        // $customerModel->mapcode = $importedCustomer->map_code ?? null;
        $customerModel->email = $importedCustomer->email ?? null;
        $customerModel->date_created = $importedCustomer->date_signed_up;
        // $customerModel->imported = true;

        $customerContactAddress = new PocomosAddress();
        $customerContactAddress->phone_id = $phone->id ?? null;
        $customerContactAddress->alt_phone_id = $altPphone->id ?? null;
        $customerContactAddress->city = $importedCustomer->city ?? null;
        $customerContactAddress->postal_code = $importedCustomer->postal_code ?? null;
        $customerContactAddress->region_id = $importedCustomer->region_id ?? null;
        $customerContactAddress->street = $importedCustomer->street ?? null;
        $customerContactAddress->suite = $importedCustomer->suite ?? null;
        $customerContactAddress->save();

        $customerBillingAddress = new PocomosAddress();
        $customerBillingAddress->phone_id = $phone->id ?? null;
        $customerBillingAddress->alt_phone_id = $altPphone->id ?? null;
        $customerBillingAddress->city = $importedCustomer->billing_city ?? null;
        $customerBillingAddress->postal_code = $importedCustomer->billing_postal_code ?? null;
        $customerBillingAddress->region_id = $importedCustomer->billing_region_id ?? null;
        $customerBillingAddress->street = $importedCustomer->billing_street ?? null;
        $customerBillingAddress->suite = $importedCustomer->billing_suite ?? null;
        $customerBillingAddress->save();

        $customerModel->contact_address_id = $customerContactAddress->id ?? null;
        $customerModel->billing_address_id = $customerBillingAddress->id ?? null;
        $customerModel->subscribed = true;
        $customerModel->active = true;
        $customerModel->status = config('constants.ACTIVE');
        $customerModel->email_verified = true;
        $customerModel->save();

        // $customerModel->billingAddressSame = false;

        // $customerModel->billingInformation = new BillingInfo();
        // $customerModel->billingInformation->paymentMethod = BillingInfo::METHOD_CARD;
        // $customerModel->billingInformation->accountNumber = $importedCustomer->getCardNumber();
        // $customerModel->billingInformation->expiryMonth = $importedCustomer->getExpiryMonth();
        // $customerModel->billingInformation->expiryYear = $importedCustomer->getExpiryYear();
        $importedCustomerNew = PocomosImportCustomer::findOrFail($importedCustomer->id);
        if ($importedCustomerNew) {
            $taxCode = PocomosTaxCode::findOrFail($importedCustomer->tax_code_id);

            // $customerProfile = new PocomosCustomerSalesProfile();
            // $customerProfile->customer_id = $customerModel->id ?? '';
            // $customerProfile->autopay = false;
            // $customerProfile->balance = 0.00;
            // $customerProfile->active = true;
            // $customerProfile->date_created = date('Y-m-d H:i:s');
            // $customerProfile->date_signed_up = date('Y-m-d H:i:s');
            // $customerProfile->imported = true;
            // $customerProfile->save();

            // $customerContract = new PocomosContract();
            // $customerContract->billing_frequency = '';
            // // $customerContract->profile_id = $customerProfile->id ?? '';
            // $customerContract->status = config('constants.ACTIVE');
            // $customerContract->date_start = date('Y-m-d H:i:s');
            // $customerContract->date_end = date('Y-m-d H:i:s');
            // $customerContract->active = true;
            // $customerContract->date_created = date('Y-m-d H:i:s');
            // $customerContract->auto_renew = true;
            // $customerContract->tax_code_id = $importedCustomer->tax_code_id ?? '1';
            // $customerContract->signed = false;
            // $customerContract->sales_tax = $taxCode->tax_rate ?? 0;
            // $customerContract->found_by_type_id = $importedCustomer->found_by_type_id ?: $importedCustomerNew->batch_details->found_by_type_id;
            // $customerContract->salesperson_id = $importedCustomer->salesperson_id ?: $importedCustomerNew->batch_details->salesperson_id;
            // $customerContract->save();

            // $customerPestContract = new PocomosPestContract();
            // $customerPestContract->contract_id = $customerContract->id ?? null;
            // $customerPestContract->agreement_id = $importedCustomerNew->batch_details->pest_agreement_detail ? $importedCustomerNew->batch_details->pest_agreement_detail->id : null;
            // $customerPestContract->county_id = $importedCustomer->county_id ?? null;
            // $customerPestContract->map_code = $importedCustomer->map_code ?? null;
            // $initial_service_price = 0;
            // if(!empty($importedCustomer->initial_service_price)){
            //     $initial_service_price = $importedCustomer->initial_service_price;
            // }
            // $customerPestContract->recurring_price = $initial_service_price;
            // $customerPestContract->service_frequency = $importedCustomer->service_frequency ?: $importedCustomerNew->batch_details->service_frequency ?? '';
            // $customerPestContract->service_schedule = $importedCustomerNew->batch_details->service_schedule ?? null;
            // $customerPestContract->service_type_id = $importedCustomer->service_type_id ?: $importedCustomerNew->batch_details->service_type_id ?? '';
            // // $customerPestContract->initialDate = !$importedCustomer->date_next_service || $importedCustomer->date_next_service ? null : $importedCustomer->date_next_service;

            // $customerPestContract->initial_price = $initial_service_price;
            // $customerPestContract->day_of_the_week = $importedCustomer->day_of_the_week ?? null;
            // $customerPestContract->week_of_the_month = $importedCustomer->week_of_the_month ?? null;
            // $customerPestContract->technician_id = $importedCustomer->last_technician_id ?: $importedCustomerNew->batch_details->technician_id ?? '';
            // $customerPestContract->date_renewal_end = $importedCustomer->date_last_service;
            // // $customerPestContract->previousBalance = $importedCustomer->previous_balance;

            // if ($customerPestContract->day_of_the_week && $customerPestContract->week_of_the_month) {
            //     $customerPestContract->service_schedule = true;
            // }
            // $customerPestContract->active = true;
            // $customerPestContract->regular_initial_price = 0;
            // $customerPestContract->initial_discount = 0;
            // $customerPestContract->original_value = 0;
            // $customerPestContract->modifiable_original_value = 0;
            // $customerPestContract->save();
        }

        $permanentNote['summary'] = $importedCustomer->notes ?? null;
        $permanentNote['interaction_type'] = 'Other';
        $permanentNote['active'] = true;
        $permanentNote['body'] = '';
        $permanentNote = PocomosNote::create($permanentNote);

        // $customerModel->notes = $note->id ?? null;

        // $customerPestContract->customer_id = $customerModel->id;

        $note = PocomosCustomersNote::create(['customer_id' => $customerModel->id, 'note_id' => $permanentNote->id]);

        return $customerModel;
    }

    public function convertCustomerToEntity($importedCustomer, $model, $office)
    {
        $salesProfile = $this->tansformToCustomer($model, $office);
        $customer = $salesProfile->customer;
        // $subCustomer = $salesProfile->getSubCustomer();
        $billingInformation = $this->customerBillingInfo($customer);
        $this->billingInfoTransform($billingInformation, $salesProfile);

        if ($model->lead_detail) {
            if ($model->lead_detail->status == config('constants.CUSTOMER')) {
                $model->lead_detail->customer_id = $customer->id ?? null;
                $model->lead_detail->save();
            }
        }

        if ($salesProfile) {
            $salesContract = $this->convertContractToEntity($importedCustomer, $salesProfile, $customer, $model->imported);
            $pestContract = $salesProfile->contract_details[0] ?? array();

            $salesProfile->salesperson_id = $pestContract->salesperson_id ?? '';

            // if ($salesProfile->contract_details->renewal) {
            //     $this->ignoreCustomerHelper->ignoreCustomer($customer);
            // }
        } else {
            // $this->ignoreCustomerHelper->ignoreCustomer($customer);
            $customer->active = false;
            $this->deactivateCustomer($customer->id, $status_reason = null, $deactivate_children = false);
        }
        $customer->save();
        $salesProfile->save();

        return true;
    }

    public function tansformToCustomer($model, $office)
    {
        $customer = new PocomosCustomer();
        $external_account_id = (!empty($model->external_account_id) ? $model->external_account_id : $this->getNextExternalId($office));
        $customer->external_account_id = $external_account_id[0]->account_id ?? null;
        $customer->company_name = $model->company_name;
        $customer->billing_name = $model->billing_name;
        $customer->first_name = $model->first_name;
        $customer->last_name = $model->last_name;
        $customer->account_type = !empty($model->account_type) ? $model->account_type : config('constants.RESIDENTIAL');
        $customer->email = $model->email;
        if (!is_array($model->secondary_emails)) {
            $customer->secondary_emails = $model->secondary_emails;
        } else {
            $customer->secondary_emails = implode(', ', $model->secondary_emails);
        }
        $customer->subscribed = $model->subscribed;
        // if ($model->contract) {
        //     $customer->setDefaultJobDuration($model->contract->defaultJobDuration);

        //     if($model->contract->addendum) {
        //         $addendumNote = new Note();
        //         $addendumNote->setSummary($model->contract->addendum);
        //         $customer->addNote($addendumNote);
        //     }
        // }

        if ($model->notes_details && $model->unpaid_note) {
            $unpaidNote = PocomosNote::findOrFail($model->unpaid_note);

            $note = new PocomosNote();
            $note->summary = $unpaidNote->summary ?? '';
            $note->body = '';
            $note->interaction_type = '';
            $note->active = true;
            $note->date_created = date('Y-m-d H:i:s');
            $note->save();

            PocomosCustomerNote::create(['customer_id' => $customer->id, 'note_id' => $note->id]);

            if ($model->worker_notes_details) {
                PocomosCustomersWorkorderNote::create(['customer_id' => $customer->id, 'note_id' => $note->id]);
            }
        }

        if (is_array($model->notes_details)) {
            foreach ($model->notes_details as $text) {
                $note = new PocomosNote();
                $note->summary = $text->summary;
                $note->body = $text->body;
                $note->interaction_type = $text->interaction_type;
                $note->active = true;
                $note->date_created = date('Y-m-d H:i:s');
                $note->save();

                PocomosCustomerNote::create(['customer_id' => $customer->id, 'note_id' => $note->id]);
            }
        }

        // if ($model->parent) {
        //     $customer->setParent($model->parent);
        // }

        $customerContactAddress = new PocomosAddress();
        $customerContactAddress->city = $model->contact_address->city ?? null;
        $customerContactAddress->postal_code = $model->contact_address->postal_code ?? null;
        $customerContactAddress->region_id = $model->contact_address->region_id ?? null;
        $customerContactAddress->street = $model->contact_address->street ?? null;
        $customerContactAddress->suite = $model->contact_address->suite ?? null;
        $customerContactAddress->save();

        $customerBillingAddress = new PocomosAddress();
        $customerBillingAddress->city = $model->billing_address->city ?? null;
        $customerBillingAddress->postal_code = $model->billing_address->postal_code ?? null;
        $customerBillingAddress->region_id = $model->billing_address->region_id ?? null;
        $customerBillingAddress->street = $model->billing_address->street ?? null;
        $customerBillingAddress->suite = $model->billing_address->suite ?? null;
        $customerBillingAddress->save();

        $customer->contact_address_id = $customerContactAddress->id ?? null;
        $customer->billing_address_id = $customerBillingAddress->id ?? null;
        $customer->subscribed = true;
        $customer->active = true;
        $customer->status = config('constants.ACTIVE');
        $customer->email_verified = true;
        $customer->save();

        $salesProfile = $this->createCustomerSalesProfile($customer, $model, $office);
        $salesProfile->customer_id = $customer->id;
        $salesProfile->save();
        return $salesProfile;
    }

    public function getNextExternalId($office)
    {
        $tidentifierPool = array();
        $officeId = $office->id;
        if (isset($tidentifierPool[$officeId])) {
            return ++$tidentifierPool[$officeId];
        }

        return DB::select(DB::raw("SELECT MAX(CONVERT(c.external_account_id, UNSIGNED))+1 as 'account_id' FROM pocomos_customers c JOIN pocomos_customer_sales_profiles csp ON csp.customer_id = c.id WHERE csp.office_id = $officeId"));
    }

    public function createCustomerSalesProfile($customer, $model, $office)
    {
        $profile = new PocomosCustomerSalesProfile();
        $profile->date_signed_up = $model->sales_profile ? ($model->sales_profile->date_signed_up ?? date('Y-m-d H:i:s')) : date('Y-m-d H:i:s');
        $profile->autopay = $model->sales_profile->autopay ?? false;
        $profile->imported = $model->sales_profile->imported ?? false;
        $profile->office_id = $office->id;
        $profile->balance = 0;
        $profile->active = true;
        $profile->save();

        if ($model->contact_address->primaryPhone) {
            $inputphone['type'] = $model->contact_address->primaryPhone->type ?? config('constants.HOME');
            $inputphone['number'] = $model->contact_address->primaryPhone->number ?? null;
            $inputphone['alias'] = 'Primary';
            $inputphone['active'] = 1;
            $phone =  PocomosPhoneNumber::create($inputphone);

            PocomosCustomersPhone::create(['profile_id' => $profile->id, 'phone_id' => $phone->id]);
            $customer->contact_address->phone_id = $phone->id;
            $customer->contact_address->save();

            if ($phone->type == config('constants.MOBILE')) {
                PocomosCustomersNotifyMobilePhone::create(['profile_id' => $profile->id, 'phone_id' => $phone->id]);
            }
        }

        if ($model->contact_address->altPhone) {
            $inputphone['type'] = $model->contact_address->altPhone->type ?? config('constants.HOME');
            $inputphone['number'] = $model->contact_address->altPhone->number ?? null;
            $inputphone['alias'] = 'Alternate';
            $inputphone['active'] = 1;
            $phone =  PocomosPhoneNumber::create($inputphone);

            PocomosCustomersPhone::create(['profile_id' => $profile->id, 'phone_id' => $phone->id]);
            $customer->contact_address->alt_phone_id = $phone->id;
            $customer->contact_address->save();

            if ($phone->type == config('constants.MOBILE')) {
                PocomosCustomersNotifyMobilePhone::create(['profile_id' => $profile->id, 'phone_id' => $phone->id]);
            }
        }

        return $profile;
    }

    public function billingInfoTransform($billingInformation, $profile)
    {
        $billingInformation = (object)$billingInformation;
        $customer = $profile->customer;
        $account = $this->createDefaultAccount($billingInformation);
        if ($account) {
            $account = $this->fillAccountWithDetails($account, $customer);
            PocomosCustomersAccount::create(['profile_id' => $profile->id, 'account_id' => $account->id]);

            if ($profile->autopay) {
                $alias = 'Auto-pay account';
                $profile->autopay_account_id = $account->id ?? null;
                $account->account_number = preg_replace('/[^0-9]/', '', $account->account_number);
            } else {
                $alias = 'Default account';
            }

            if ($billingInformation->alias) {
                $account->alias = $billingInformation->alias;
            } else {
                $account->alias = $alias;
            }
        } elseif ($profile->autopay) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $input_details['ip_address'] = '';
        $input_details['alias'] = 'Cash or check';
        $input_details['type'] =  'SimpleAccount';
        $input_details['account_number'] = $billingInformation->accountNumber ?? null;
        $input_details['ach_routing_number'] = $billingInformation->routingNumber ?? null;
        $input_details['name'] = '';
        $input_details['address'] = '';
        $input_details['city'] = '';
        $input_details['region'] = '';
        $input_details['country'] = '';
        $input_details['postal_code'] = '';
        $input_details['phoneNumber'] = '';
        $input_details['active'] = true;
        $input_details['email_address'] = '';
        $input_details['external_person_id'] = '';
        $input_details['external_account_id'] = '';
        $account =  OrkestraAccount::create($input_details);
        $account = $this->fillAccountWithDetails($account, $customer);
        PocomosCustomersAccount::create(['profile_id' => $profile->id, 'account_id' => $account->id]);

        $input_details['ip_address'] = '';
        $input_details['alias'] = 'Account credit';
        $input_details['type'] =  'PointsAccount';
        $input_details['account_number'] = $billingInformation->accountNumber ?? null;
        $input_details['ach_routing_number'] = $billingInformation->routingNumber ?? null;
        $input_details['name'] = '';
        $input_details['address'] = '';
        $input_details['city'] = '';
        $input_details['region'] = '';
        $input_details['country'] = '';
        $input_details['postal_code'] = '';
        $input_details['phoneNumber'] = '';
        $input_details['active'] = true;
        $input_details['email_address'] = '';
        $input_details['external_person_id'] = '';
        $input_details['external_account_id'] = '';
        $account =  OrkestraAccount::create($input_details);
        $account = $this->fillAccountWithDetails($account, $customer);
        PocomosCustomersAccount::create(['profile_id' => $profile->id, 'account_id' => $account->id]);
        $profile->points_account_id = $account->id;

        $input_details['ip_address'] = '';
        $input_details['alias'] = 'External account';
        $input_details['type'] =  'SimpleAccount';
        $input_details['account_number'] = $billingInformation->accountNumber ?? null;
        $input_details['ach_routing_number'] = $billingInformation->routingNumber ?? null;
        $input_details['name'] = '';
        $input_details['address'] = '';
        $input_details['city'] = '';
        $input_details['region'] = '';
        $input_details['country'] = '';
        $input_details['postal_code'] = '';
        $input_details['phoneNumber'] = '';
        $input_details['active'] = true;
        $input_details['email_address'] = '';
        $input_details['external_person_id'] = '';
        $input_details['external_account_id'] = '';
        $account =  OrkestraAccount::create($input_details);
        $account = $this->fillAccountWithDetails($account, $customer);
        PocomosCustomersAccount::create(['profile_id' => $profile->id, 'account_id' => $account->id]);
        $profile->external_account_id = $account->id;
        $profile->save();
    }

    public function customerBillingInfo($customer)
    {
        $mthod_card = 'card';
        $method_ach = 'ach';
        $enrollInAutopay = '';
        $paymentMethod = config('constants.METHOD_ACH');
        $accountNumber = '1222 2222 4444 8585';
        $routingNumber = '89898989';
        $expiryMonth = '';
        $expiryYear = '';
        $accountToken = '';
        $alias = 'BankAccount';

        return array(
            'mthodCard' => $mthod_card,
            'methodAch' => $method_ach,
            'enrollInAutopay' => $enrollInAutopay,
            'paymentMethod' => $paymentMethod,
            'accountNumber' => $accountNumber,
            'routingNumber' => $routingNumber,
            'expiryMonth' => $expiryMonth,
            'expiryYear' => $expiryYear,
            'accountToken' => $accountToken,
            'alias' => $alias
        );
    }

    public function fillAccountWithDetails($account, $customer)
    {
        $address = $customer->billing_address;

        $account->name = $customer->first_name ?? '' . $customer->last_name;
        $account->address = trim($address->street . ' ' . $address->suite);
        $account->city = $address->city;
        if ($address->region) {
            $account->region = $address->region->code;
            $account->country = $address->region->country_detail->code;
        }

        $account->postal_code = $address->postal_code;
        $account->phoneNumber = $address->primaryPhone ? $address->primaryPhone->number : '';
        $account->save();
        return $account;
    }

    public function getEntity_pymAC($request)
    {
        // dd($request);

        $input_details['alias'] = $request->account_name;
        $input_details['account_number'] = $request->account_number;
        $input_details['account_token'] =  null;

        // dd($request->payment_method);

        if ($request->payment_method == 'ach') {
            $paymentMethod = 'BankAccount';
            $input_details['ach_routing_number'] = $request->ach_routing_number;
        } elseif ($request->payment_method == 'card') {
            $paymentMethod = 'CardAccount';

            $input_details['card_exp_month'] = $request->card_exp_month;
            $input_details['card_exp_year'] = $request->card_exp_year;
        }

        $input_details['type'] =  $paymentMethod;

        $input_details['ip_address'] = '';
        $input_details['name'] = '';
        $input_details['address'] = '';
        $input_details['city'] = '';
        $input_details['region'] = '';
        $input_details['country'] = '';
        $input_details['postal_code'] = '';
        $input_details['phoneNumber'] = '';
        $input_details['active'] = true;
        $input_details['email_address'] = '';
        $input_details['external_person_id'] = '';
        $input_details['external_account_id'] = '';
        $account =  OrkestraAccount::create($input_details);

        return $account;
    }

    public function addAccount_custSalesProf($accountId, $profileId)
    {
        $custAc = PocomosCustomersAccount::whereAccountId($accountId)->whereProfileId($profileId)->first();

        if (!$custAc) {
            PocomosCustomersAccount::create(['profile_id' => $profileId, 'account_id' => $accountId]);
        }
    }

    public function setAutoPay_custHelper($profile, $account, $autoPay, $paymentMethod)
    {
        $autoPayAccountId = $profile->autopay_account_id;

        // dd($profile);

        if (in_array($paymentMethod, array('card', 'ach'))) {
            // dd(11);

            if ($autoPay) {
                // dd(22);
                $profile->autopay = true;
                $profile->autopay_account_id = $account->id;
            } elseif ($autoPayAccountId && $autoPayAccountId == $account->id) {
                // dd(11);
                $profile->autopay = false;
                $profile->autopay_account_id = $account->id;
            }
            $profile->save();
        }

        return $profile;
    }

    public function createDefaultAccount($model)
    {
        $account = null;
        switch ($model->paymentMethod) {
            case config('constants.METHOD_ACH'):
                if (!$model->accountNumber || !$model->routingNumber) {
                    break;
                }

                $input_details['ip_address'] = '';
                $input_details['alias'] = $model->alias;
                $input_details['type'] =  'BankAccount';
                $input_details['account_number'] = $model->accountNumber ?? null;
                $input_details['ach_routing_number'] = $model->routingNumber ?? null;
                $input_details['name'] = '';
                $input_details['address'] = '';
                $input_details['city'] = '';
                $input_details['region'] = '';
                $input_details['country'] = '';
                $input_details['postal_code'] = '';
                $input_details['phoneNumber'] = '';
                $input_details['active'] = true;
                $input_details['email_address'] = '';
                $input_details['external_person_id'] = '';
                $input_details['external_account_id'] = '';
                $account =  OrkestraAccount::create($input_details);
                // no break
            case config('constants.METHOD_CARD'):
                if (!$model->accountNumber || !$model->expiryMonth || !$model->expiryYear) {
                    break;
                }

                $input_details['ip_address'] = '';
                $input_details['alias'] = $model->alias;
                $input_details['type'] =  'CardAccount';
                $input_details['account_number'] = $model->accountNumber ?? null;
                $input_details['ach_routing_number'] = $model->routingNumber ?? null;
                $input_details['card_exp_month'] = $model->expiryMonth ?? null;
                $input_details['card_exp_year'] = $model->expiryYear ?? null;
                $input_details['name'] = '';
                $input_details['address'] = '';
                $input_details['city'] = '';
                $input_details['region'] = '';
                $input_details['country'] = '';
                $input_details['postal_code'] = '';
                $input_details['phoneNumber'] = '';
                $input_details['active'] = true;
                $input_details['email_address'] = '';
                $input_details['external_person_id'] = '';
                $input_details['external_account_id'] = '';
                $account =  OrkestraAccount::create($input_details);
        }
        return $account;
    }

    public function convertContractToEntity($importedCustomer, $profile, $model, $imported = false)
    {
        $pestContract = $this->contractTransformer($importedCustomer, $model);
        $contract = PocomosContract::findOrFail($pestContract->contract_id);
        if ($imported) {
            $pestContract->contract_details->signed = true;
        }

        if ($model->sales_status_id) {
            $pestContract->contract_details->sales_status_id = $model->sales_status_id;
        }
        $pestContract->contract_details->save();

        $contract->profile_id = $profile->id ?? null;
        $contract->save();

        // $this->serviceScheduleTransformer->transform($pestContract, $model, $imported);
        // $this->billingScheduleTransformer->transform($pestContract, $model);

        // $jobs = $pestContract->getJobs();
        // $originalValue = 0;
        // foreach ($jobs as $job) {
        //     $originalValue += $job->getInvoice()->getAmountDue();
        // }

        // $miscInvoices = $pestContract->getMiscInvoices();
        // foreach ($miscInvoices as $invoice) {
        //     $originalValue += $invoice->getAmountDue();
        // }
        // $pestContract->setOriginalValue($originalValue);
        // $pestContract->setModifiableOriginalValue($originalValue);

        // return new ContractCreationResult($pestContract);
        return true;
    }

    public function getInvoiceProfile($invoice, $profile)
    {
        $invoiceProfile = $invoice->contract->profile_id;

        if ($invoiceProfile !== $profile->id) {
            // $sub_customers = PocomosSubCustomer::where('parent_id', $customer_id)->get();

            $parentRelationship = PocomosSubCustomer::where('parent_id', $profile->customer_id)->first();

            if ($parentRelationship) {
                $custId = $parentRelationship->parent_id;
                $profile = PocomosCustomerSalesProfile::whereCustomerId($custId)->first();
            }
            if ($invoiceProfile !== $profile->id) {
                // throw new \Exception(__('strings.message', ['message' => 'Unable to find the Invoice Entitiy.']));
            }
        }
        return $profile;
    }

    public function contractTransformer($importedCustomer, $model)
    {
        $taxCode = PocomosTaxCode::findOrFail($importedCustomer->tax_code_id);
        $imprtBatch = PocomosImportBatch::findOrFail($importedCustomer->upload_batch_id);
        $pestAgreement = PocomosPestAgreement::findOrFail($imprtBatch->pest_agreement_id);

        $salesContract = new PocomosContract();
        $salesContract->tax_code_id = $importedCustomer->tax_code_id ?? 0;
        $salesContract->sales_tax = $taxCode->tax_rate ?? 0;
        $salesContract->auto_renew = true;
        $salesContract->status = config('constants.ACTIVE');
        $salesContract->date_start = date('Y-m-d');
        $salesContract->date_end = date('Y-m-d');
        $salesContract->found_by_type_id = $imprtBatch->found_by_type_id ?? null;
        $salesContract->salesperson_id = $importedCustomer->salesperson_id ?: $imprtBatch->salesperson_id;
        $salesContract->billing_frequency = '';
        $salesContract->salesperson_id = $pestAgreement->agreement_id;
        $salesContract->active = true;
        $salesContract->date_created = date('Y-m-d H:i:s');
        $salesContract->signed = false;
        $salesContract->save();

        $pestContract = new PocomosPestContract();
        $pestContract->contract_id = $salesContract->id ?? null;
        $pestContract->county_id = $importedCustomer->county_id ?? null;
        $pestContract->recurring_price = $importedCustomer->initial_service_price ?? 0;
        $pestContract->initial_discount = 0;
        $pestContract->initial_price = $importedCustomer->price ?? 0;
        $pestContract->recurring_discount = 0;
        $pestContract->map_code = $importedCustomer->map_code;
        $pestContract->exceptions = '';
        $pestContract->technician_id = $imprtBatch->technician_id;
        $contractStartDate = date('Y-m-d');
        $contractEndDate = date('Y-m-d');
        $pestContract->service_frequency = $imprtBatch->service_frequency ?? '';
        $contractStartDate = new DateTime($contractStartDate);
        $contractEndDate = new DateTime($contractEndDate);
        $agreementLength = ceil($contractStartDate->diff($contractEndDate)->days / 30);
        $serviceSchedule = $this->createServiceSchedule($imprtBatch->service_frequency, array(), $agreementLength);
        $pestContract->service_schedule = json_encode($serviceSchedule);
        $pestContract->service_type_id = $importedCustomer->service_type_id ?: $imprtBatch->service_type_id ?? '';
        $pestContract->agreement_id = $imprtBatch->pest_agreement_id;
        $pestContract->regular_initial_price = 0;
        $pestContract->week_of_the_month = '';
        $pestContract->day_of_the_week = '';
        $pestContract->date_renewal_end = date('Y-m-d', strtotime('+2 year'));
        $pestContract->original_value = 0;
        $pestContract->modifiable_original_value = 0;

        $pestContract->active = true;
        $pestContract->save();

        return $pestContract;
    }

    public function transformImportedCustomerToLead($importedCustomer)
    {
        $lead = new PocomosLead();
        $lead->external_account_id = $importedCustomer->external_identifier;
        $lead->first_name = $importedCustomer->first_name;
        $lead->last_name = $importedCustomer->last_name;
        $lead->company_name = $importedCustomer->company_name;
        $lead->email = $importedCustomer->email;

        $lead->status = config('constants.LEAD');

        $phone = new PocomosPhoneNumber();
        $phone->alias = 'Primary';
        $phone->type = 'Home';
        $phone->number = $importedCustomer->phone ?? null;
        $phone->active = true;
        $phone->date_created = date('Y-m-d H:i:s');
        $phone->save();

        $altPphone = new PocomosPhoneNumber();
        $altPphone->alias = 'Alternate';
        $altPphone->type = 'Home';
        $altPphone->number = $importedCustomer->alt_phone ?? null;
        $altPphone->active = true;
        $altPphone->date_created = date('Y-m-d H:i:s');
        $altPphone->save();

        $customerContactAddress = new PocomosAddress();
        $customerContactAddress->phone_id = $phone->id ?? null;
        $customerContactAddress->alt_phone_id = $altPphone->id ?? null;
        $customerContactAddress->city = $importedCustomer->city ?? null;
        $customerContactAddress->postal_code = $importedCustomer->postal_code ?? null;
        $customerContactAddress->region_id = $importedCustomer->region_id ?? null;
        $customerContactAddress->street = $importedCustomer->street ?? null;
        $customerContactAddress->suite = $importedCustomer->suite ?? null;
        $customerContactAddress->save();

        $customerBillingAddress = new PocomosAddress();
        $customerBillingAddress->phone_id = $phone->id ?? null;
        $customerBillingAddress->alt_phone_id = $altPphone->id ?? null;
        $customerBillingAddress->city = $importedCustomer->billing_city ?? null;
        $customerBillingAddress->postal_code = $importedCustomer->billing_postal_code ?? null;
        $customerBillingAddress->region_id = $importedCustomer->billing_region_id ?? null;
        $customerBillingAddress->street = $importedCustomer->billing_street ?? null;
        $customerBillingAddress->suite = $importedCustomer->billing_suite ?? null;
        $customerBillingAddress->save();

        if ($importedCustomer->card_number != '' && $importedCustomer->exp_month != '' && $importedCustomer->exp_year != '') {
            $input_details['ip_address'] = '';
            $input_details['alias'] = 'Card Account';
            $input_details['type'] =  'CardAccount';
            $input_details['account_number'] = $importedCustomer->accountNumber ?? '';
            $input_details['ach_routing_number'] = $importedCustomer->routingNumber ?? null;
            $input_details['card_exp_month'] = $importedCustomer->expiryMonth ?? null;
            $input_details['card_exp_year'] = $importedCustomer->expiryYear ?? null;
            $input_details['name'] = '';
            $input_details['address'] = '';
            $input_details['city'] = '';
            $input_details['region'] = '';
            $input_details['country'] = '';
            $input_details['postal_code'] = '';
            $input_details['phoneNumber'] = '';
            $input_details['active'] = true;
            $input_details['email_address'] = '';
            $input_details['external_person_id'] = '';
            $input_details['external_account_id'] = '';
            $account =  OrkestraAccount::create($input_details);
        }

        $importedCustomerNew = PocomosImportCustomer::findOrFail($importedCustomer->id);

        $pestControlQuote = new PocomosLeadQuote();
        $pestControlQuote->map_code = ($importedCustomer->map_code);
        $pestControlQuote->date_signed_up = ($importedCustomer->date_signed_up);

        if ($importedCustomer->salesperson_id) {
            $salesperson_id = $importedCustomer->salesperson_id;
        } else {
            $salesperson_id = $importedCustomerNew->batch_details ? $importedCustomerNew->batch_details->salesperson_id : null;
        }

        $pestControlQuote->salesperson_id = $salesperson_id;
        if ($importedCustomerNew->batch_details->pest_agreement_detail) {
            $pestControlQuote->pest_agreement_id = $importedCustomerNew->batch_details->pest_agreement_detail->id;
            $pestControlQuote->county_id = $importedCustomer->county_id;
            $pestControlQuote->recurring_price = $importedCustomer->recurring_price ?? 0;
            $pestControlQuote->auto_renew = true;

            if ($importedCustomer->salesperson_id) {
                $service_frequency = $importedCustomer->service_frequency;
            } else {
                $service_frequency = $importedCustomerNew->batch_details ? $importedCustomerNew->batch_details->service_frequency : null;
            }

            $pestControlQuote->service_frequency = $service_frequency;
            $serviceSchedule = $importedCustomerNew->batch_details->service_schedule;
            if (is_array($serviceSchedule)) {
                $pestControlQuote->service_schedule = $serviceSchedule;
            }

            if ($importedCustomer->salesperson_id) {
                $service_type_id = $importedCustomer->service_type_id;
            } else {
                $service_type_id = $importedCustomerNew->batch_details ? $importedCustomerNew->batch_details->service_type_id : null;
            }

            $pestControlQuote->service_type_id = $service_type_id;
            $pestControlQuote->initial_date = (!$importedCustomer->date_next_service ? null : $importedCustomer->date_next_service);
            $pestControlQuote->initial_price = $importedCustomer->initial_service_price;
            $pestControlQuote->found_by_type_id = $importedCustomer->found_by_type_id ?: $importedCustomerNew->batch_details->found_by_type_id;
            $pestControlQuote->week_of_the_month = $importedCustomer->week_of_the_month;
            $pestControlQuote->day_of_the_week = $importedCustomer->day_of_the_week;
            $pestControlQuote->technician_id = $importedCustomer->last_technician_id ?: $importedCustomerNew->batch_details->technician_id;
            $pestControlQuote->tax_code = $importedCustomer->tax_code_id ? $importedCustomer->tax_code_id : $importedCustomerNew->batch_details->tax_code_id ?? '';
            $pestControlQuote->date_last_serviced = $importedCustomer->date_last_service;
            $pestControlQuote->previous_balance = $importedCustomer->previous_balance;

            if ($pestControlQuote->week_of_the_month && $pestControlQuote->day_of_the_week) {
                $pestControlQuote->specific_recurring_schedule = true;
            } else {
                $pestControlQuote->specific_recurring_schedule = false;
            }
            $pestControlQuote->regular_initial_price = 0;
            $pestControlQuote->initial_discount = 0;
            $pestControlQuote->initial_price = 0;
            $pestControlQuote->recurring_price = 0;
            $pestControlQuote->autopay = false;
            $pestControlQuote->auto_renew = false;
            $pestControlQuote->active = true;
            $pestControlQuote->make_tech_preferred = false;

            $pestControlQuote->save();
        }
        $lead->quote_id = $pestControlQuote->id ?? null;
        $lead->external_account_id = '';
        $lead->subscribed = false;
        $lead->active = true;
        $lead->save();

        if (is_array($importedCustomer->notes)) {
            $notes = '';
            foreach ($importedCustomer->notes as $text) {
                $notes .= $text . '<br>';
            }
            $note = new PocomosNote();
            $note->summary = $notes;
            $note->body = '';
            $note->interaction_type = '';
            $note->active = true;
            $note->date_created = date('Y-m-d H:i:s');
            $note->save();
            PocomosLeadNote::create(['lead_id' => $lead->id, 'note_id' => $note->id]);
        }

        PocomosLeadsAccount::create(['lead_id' => $lead->id, 'account_id' => $account->id]);

        return $lead;
    }

    public function replaceDynamicVariables($template, $params)
    {
        $variables = array();
        $variables['addendum'] = $params['recruit'] ? $params['recruit']->contract_detail->addendum ?? '' : '';

        $company_logo = $params['office'] ? $params['office']->logo->path ?? '' : '';

        $variables['company_logo'] = '<img src="' . $company_logo . '" height="100px" width="100px">';
        $variables['recruit_current_address'] = date('m/d/Y');
        $custom_fields = $params['recruit'] ? $params['recruit']->contract_detail->custome_fields ?? array() : array();

        $custom_fields_str = '<table cellpadding="2" cellspacing="0" border="1" height="200" width="200">
            <tr>
                <th style="width: 100%">Additional Information</th>
            </tr>';
        foreach ($custom_fields as $customField) {
            $custom_fields_str .=
                '<tr>
                <td style="width: 50%;text-align: left;">' . $customField->custom_field->label . '</td>
                <td style="text-align: right;width: 50%">' . ($customField->value ?? 'Unspecified') . '</td>
            </tr>';
        }
        $custom_fields_str .= '</table>';
        $variables['custom_fields'] = $custom_fields_str;
        $variables['recruit_beginning_date'] = $params['recruit'] ? date('Y-m-d', strtotime($params['recruit']->contract_detail->date_start)) : '';
        $variables['recruit_current_address'] = $params['recruit'] ? $params['recruit']->current_address->suite . ', ' . $params['recruit']->current_address->street . ', ' . $params['recruit']->current_address->city : '';
        $variables['recruit_permanent_address'] = $params['recruit'] ? $params['recruit']->primary_address->suite . ', ' . $params['recruit']->primary_address->street . ', ' . $params['recruit']->primary_address->city : '';

        $variables['recruit_end_date'] = $params['recruit'] ? date('Y-m-d', strtotime($params['recruit']->contract_detail->date_end)) : '';
        $variables['recruiter_name'] = $params['recruiter'] ? $params['recruiter']->user->user_details->first_name . ' ' . $params['recruiter']->user->user_details->last_name : '';
        $variables['recruiter_signature'] = $params['recruit'] ? $params['recruit']->contract_detail->recruiter_signature->path ?? '' : '';
        $variables['recruit_first_name'] = $params['recruit'] ? $params['recruit']->first_name : '';
        $variables['recruit_last_name'] = $params['recruit'] ? $params['recruit']->last_name : '';
        $variables['recruiting_office'] = $params['recruit'] ? $params['recruit']->office_detail->name : '';

        $recruit_initials = $params['recruit'] ? $params['recruit']->contract_detail->recruit_initial->path ?? '' : '';
        $variables['recruit_initials'] = '<img src="' . $recruit_initials . '" height="100px" width="100px">';

        $variables['recruit_phone'] = $params['recruit'] ? ($params['recruit']->primary_address ? $params['recruit']->primary_address->primaryPhone->number : $params['recruit']->current_address->primaryPhone->number) : '';

        $recruit_signature = $params['recruit'] ? $params['recruit']->contract_detail->signature->path ?? '' : '';
        $variables['recruit_signature'] = '<img src="' . $recruit_signature . '" height="100px" width="100px">';

        $variables['recruit_signature_date'] = $params['recruit'] ? date('Y-m-d', strtotime($params['recruit']->contract_detail->signature ? $params['recruit']->contract_detail->signature->date_created : '')) ?? '' : '';

        // dd($variables);

        $matches = array();
        preg_match_all('/\{\{\s*?([\w_]+)\s*?\}\}/', $template, $matches, PREG_OFFSET_CAPTURE);

        foreach (array_reverse($matches[0], true) as $matchIndex => $match) {
            list($word, $pos) = $match;
            $param = $matches[1][$matchIndex][0];

            $value = isset($variables[$param]) ? $variables[$param] : '';

            $template = substr_replace($template, $value, $pos, strlen($word));
        }
        return $template;

        // return count($complete) == 1 ? $complete[0] : $complete;
    }

    /**
     * Array Describes the format expected of the uploaded file
     */
    public function getBatchFormateValues()
    {
        return array(
            'companyName' => 'Company Name',
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'emailAddress' => 'Email',
            'phone' => 'Phone',
            'altPhone' => 'Alternate Phone',
            'street' => 'Street',
            'suite' => 'Suite',
            'city' => 'City',
            'importedRegion' => 'State',
            'postalCode' => 'Postal Code',
            'county' => 'County',
            'mapCode' => 'Map Code',
            'nameOnCard' => 'Name on Card',
            'cardNumber' => 'Card Number',
            'expiryMonth' => 'Expiration Month',
            'expiryYear' => 'Expiration Year',
            'billingStreet' => 'Billing Street',
            'billingSuite' => 'Billing Suite',
            'billingCity' => 'Billing city',
            'importedBillingRegion' => 'Billing State',
            'billingPostalCode' => 'Billing Postal Code',
            'dateSignedUp' => 'Sign up Date',
            'dateLastService' => 'Last Service Date',
            'dateNextService' => 'Next Service Date',
            'serviceFrequency' => 'Service Frequency',
            'foundByType' => 'Marketing Type',
            'serviceType' => 'Service Type',
            'weekOfTheMonth' => 'Week of the Month',
            'dayOfTheWeek' => 'Day of the Week',
            'externalIdentifier' => 'External Identifier',
            'salesperson' => 'Salesperson',
            'recurringPrice' => 'Recurring Price',
            'previousBalance' => 'Previous Balance',
            'lastTechnician' => 'Last Technician',
            'taxCode' => 'Tax Code',
            'comment1' => 'Comment 1 *',
            'comment2' => 'Comment 2 *',
            'comment3' => 'Comment 3 *',
            'fax' => 'Fax *',
            'warranty' => 'Warranty *',
            'note1' => 'Note 1 *',
            'note2' => 'Note 2 *',
            'note3' => 'Note 3 *',
            'note4' => 'Note 4 *',
            'note5' => 'Note 5 *',
            'note6' => 'Note 6 *',
            'note7' => 'Note 7 *',
            'note8' => 'Note 8 *',
            'note9' => 'Note 9 *',
            'note10' => 'Note 10 *',
        );
    }

    /**
     * Array Describes the note fields expected of the uploaded file
     */
    private $noteFields = array(
        'comment1',
        'comment2',
        'comment3',
        'fax',
        'warranty',
        'note1',
        'note2',
        'note3',
        'note4',
        'note5',
        'note6',
        'note7',
        'note8',
        'note9',
        'note10',
    );

    /**
     * Array Describes an example value for each field
     */
    private $examples = array(
        'companyName' => 'Company Name',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'emailAddress' => 'john@doe.com',
        'phone' => '555-555-5555',
        'altPhone' => '555-555-5555',
        'street' => '123 Main',
        'suite' => '#413',
        'city' => 'Oceanside',
        'importedRegion' => 'CA',
        'postalCode' => '92056',
        'county' => 'San Diego',
        'mapCode' => 'E3',
        'nameOnCard' => 'Johnny Doe',
        'cardNumber' => '4111222233334444',
        'expiryMonth' => '10',
        'expiryYear' => '2014',
        'billingStreet' => '123 Main',
        'billingSuite' => '#413',
        'billingCity' => 'Oceanside',
        'importedBillingRegion' => 'CA',
        'billingPostalCode' => '92056',
        'dateSignedUp' => '2013-04-25',
        'dateLastService' => '2013-12-25',
        'dateNextService' => '2013-12-25',
        'serviceFrequency' => 'Monthly',
        'foundByType' => 'Other',
        'serviceType' => 'General Pest Control',
        'weekOfTheMonth' => 'First',
        'dayOfTheWeek' => 'Monday',
        'externalIdentifier' => '55',
        'salesperson' => 'Freddy Cooper',
        'recurringPrice' => '59.99',
        'previousBalance' => '199.99',
        'lastTechnician' => 'Frank Smith',
        'TaxCode' => 'Code 5467',
        'comment1' => 'Customer has a dog',
        'comment2' => 'Customer is Picky',
        'comment3' => 'Must call ahead!',
        'fax' => '555-555-4444',
        'warranty' => '12 month',
        'note1' => 'Unlock gate with key under stone',
        'note2' => '',
        'note3' => '',
        'note4' => '',
        'note5' => '',
        'note6' => '',
        'note7' => '',
        'note8' => '',
        'note9' => '',
        'note10' => '',
    );

    /**Get import CSV file template */
    public function getCsvTemplate()
    {
        $fields = array_values($this->getBatchFormateValues());

        array_walk($fields, function (&$value, $index) {
            $value = '"' . $value . '"';
        });

        $examples = array_values($this->examples);

        array_walk($examples, function (&$value, $index) {
            $value = '"' . addslashes($value) . '"';
        });

        $template = implode(',', $fields) . "\n" . implode(',', $examples);

        return $template;
    }

    /**
     * Contract base update jobs color
     */
    public function unColorJobsByContract($pestControlContract)
    {
        $today = "'" . (new \DateTime())->format('Y-m-d') . "'";
        $colorSql = "UPDATE pocomos_jobs SET color = 'f9f9f9' WHERE contract_id = " . $pestControlContract->id . " AND date_scheduled > " . $today;

        DB::select(DB::raw($colorSql));
        return true;
    }

    /**
     * Changes the given contract's default duration
     */
    public function updateDefaultDuration($contract, $defaultDuration)
    {
        $customer = $contract->contract_details->profile_details->customer_details;
        $customer->default_job_duration = $defaultDuration;
        $unableToChange = 0;
        $result = array();

        foreach ($contract->jobs_details as $job) {
            try {
                $this->updateJobDuration($job, $defaultDuration);
            } catch (\Exception $e) {
                $unableToChange++;
            }
        }

        $result['unableToChange'] = $unableToChange;

        return $result;
    }

    /**
     * Update the given Job's duration.
     *
     * If the Job is not currently assigned to a Route, nothing will happen
     */
    public function updateJobDuration($job, $duration)
    {
        if (!($slot = $job->slot)) {
            return false;
        }

        $oldDuration = $slot->duration;
        $difference = $duration - $oldDuration;

        if ($difference === 0) {
            return false;
        }

        $slot->duration = $duration;
        if (!$this->slotFitsInRoute($slot, $slot->route_detail)) {
            $slot->duration = $oldDuration;
            $slot->save();
            throw new \Exception(__('strings.message', ['message' => 'Unable to modify job duration. The job does not fit within the specified time']));
        }

        return true;
    }

    /**
     * Returns true if the given Slot fits in the given Route
     * @return bool
     */
    public function slotFitsInRoute($slot, $route)
    {
        if ($slot->anytime) {
            return true;
        }

        $time_begin = $slot->time_begin;
        $end_time = $slot->time_begin;
        $end_time = new \DateTime($end_time);
        $end_time = $end_time->modify(sprintf('+%s minutes', $slot->duration));

        if ($this->isOverlap($time_begin, $end_time, $slot, $route)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if there is an overlap with specified times
     */
    public function isOverlap($beginTime, $endTime, $currentSlot = null, $route)
    {
        if (isset($route->slots)) {

            foreach ($route->slots as $slot) {
                if ($slot === $currentSlot || $slot->anytime) {
                    continue;
                }

                $slotBeginTime = $slot->time_begin;
                $slotEndTime = $slot->time_begin;
                $slotEndTime = new \DateTime($slotEndTime);
                $slotEndTime = $slotEndTime->modify(sprintf('+%s minutes', $slot->duration));

                if (
                    ($endTime == $slotEndTime)
                    || (($beginTime < $slotEndTime)
                        && ($endTime > $slotBeginTime))
                    || ($beginTime == $slotBeginTime)
                ) {
                    return true;
                }
            }

            return false;
        }
    }

    /**Reschedule jobs details */
    public function rescheduleJobWithOptions($options, $job)
    {
        $result = [];

        $options       = (object)$options;
        $dateScheduled = $options->date_scheduled ?? null;
        $timeScheduled = $options->time_scheduled ?? null;

        if (!$dateScheduled) {
            throw new \Exception(__('strings.message', ['message' => 'A job\'s scheduled date may not be empty.']));
        }
        dd($options);
        if ($options->offset_future_jobs) {
            Log::info('Rescheduling job; offsetting future jobs' . json_encode(array('customer' => $job->contract->contract_details->profile_details->customer_details->id)));

            // Offset future jobs by the same net change in number of days
            $dateScheduled = new DateTime($dateScheduled);
            $job_end_date  = new DateTime($job->date_scheduled);
            $modification  = $dateScheduled->diff($job_end_date)->days;

            if ($dateScheduled < $job->date_scheduled) {
                $modification *= -1;
            }

            $newDateDay   = $dateScheduled->format('d');
            $modification = sprintf('%s days', $modification < 0 ? $modification : '+' . $modification);
            $contract     = $job->contract ?? null;
            $m30Arr       = ['February', 'April', 'June', 'September', 'November'];

            if ($contract->jobs_details) {
                foreach ($contract->jobs_details as $contractJob) {
                    if (
                        $contractJob->isFinished()
                        || $contractJob->date_scheduled < $job->date_scheduled
                        || $job === $contractJob
                    ) {
                        continue;
                    }

                    $modifiedDate = new DateTime($contractJob->date_scheduled);
                    $jobMonth     = $modifiedDate->format('F');
                    $jobYear      = $modifiedDate->format('Y');
                    $modifiedDate = $modifiedDate->modify($modification);

                    if (in_array($jobMonth, $m30Arr)) {
                        $lastDay = new DateTime('last day of ' . $jobMonth . ' ' . $jobYear);
                        if (strtotime($modifiedDate->format('Y-m-d H:i:s')) > strtotime($lastDay->format('Y-m-d H:i:s'))) {
                            $modifiedDate = $lastDay;
                        } else {
                            $lastDay      = new DateTime($newDateDay . '-' . $jobMonth . '-' . $jobYear);
                            $modifiedDate = $lastDay;
                        }
                    }
                    $date_scheduled = (new DateTime($contractJob->date_scheduled))->format('Y-m-d');
                    $modifiedDate   = $modifiedDate->format('Y-m-d H:i:s');

                    Log::info('Modifying' . $date_scheduled . $modification . 'to' . $modifiedDate . json_encode(array('customer' => $job->contract->contract_details->profile_details->customer_details->id)));

                    $result = $this->rescheduleJobReschedulingHelper($contractJob, $modifiedDate);
                }
            }
        } elseif ($options->reschedule_future_jobs) {
            // Move all future jobs to "first Monday" (example) of their respective month
            $weekOfTheMonth = $options->future_jobs['future_week'] ?? 'First';
            $dayOfTheWeek   = $options->future_jobs['future_day'] ?? 'Sunday';
            $scope          = $weekOfTheMonth . ' ' . $dayOfTheWeek;
            $preferredTime  = $options->future_jobs['future_time'] ?? '00:00:00';

            $contract = $job->contract;

            $contract->preferred_time    = $preferredTime;
            $contract->day_of_the_week   = $dayOfTheWeek;
            $contract->week_of_the_month = $weekOfTheMonth;

            foreach ($contract->jobs_details as $contractJob) {
                if (
                    $contractJob->isFinished()
                    || $contractJob->date_scheduled < $job->date_scheduled
                    || $job === $contractJob
                ) {
                    continue;
                }
                $month = (new DateTime($contractJob->date_scheduled))->modify('-1 month')->format('F Y');
                $dateScheduled  = 'last day of ' . $month . ' ' . $scope;
                // Todo : changes
                // $dateScheduled = StringHelper::parseDate('last day of ' . $month)->modify($scope);
                $result = $this->rescheduleJobReschedulingHelper($contractJob, $dateScheduled, $preferredTime);
            }
        }
        if ($options->any_time) {
            $result = $this->rescheduleJobReschedulingHelper($job, $contractJob->date_scheduled);
            $this->assignAnytimeSlot($job, $options->route);
        } else {
            $result = $this->rescheduleJobReschedulingHelper($job, $options->date_scheduled, $timeScheduled, $options->route);
        }

        return true;
    }

    // ReschedulingHelper.php
    public function rescheduleJobWithOptionsImproved($options, $job)
    {
        // dd(11);
        // return $options;
        // $options = (object)$options;
        $dateScheduled = $options['date_scheduled'];
        $timeScheduled = $options['time_scheduled'] ?? '00:00:00';

        if (!$dateScheduled) {
            throw new \Exception(__('strings.message', ['message' => 'A job\'s scheduled date may not be empty.']));
        }

        if (isset($options['offset_future_jobs'])) {
            // dd(1188);
            // Log::info('Rescheduling job; offsetting future jobs' . json_encode(array('customer' => $job->contract->contract_details->profile_details->customer_details->id)));

            // Offset future jobs by the same net change in number of days
            // dd($dateScheduled);
            $dateScheduled = new DateTime($dateScheduled);
            $job_end_date = new DateTime($job->date_scheduled);
            $modification = $dateScheduled->diff($job_end_date)->days;

            if ($dateScheduled < $job->date_scheduled) {
                $modification *= -1;
            }

            $modification = sprintf('%s days', $modification < 0 ? $modification : '+' . $modification);
            $contract = $job->contract;
            foreach ($contract->jobs_details as $contractJob) {
                if (
                    $contractJob->isFinished()
                    || $contractJob->date_scheduled < $job->date_scheduled
                    || $job === $contractJob
                ) {
                    continue;
                }

                $modifiedDate = $contractJob->date_scheduled;
                $modifiedDate = new DateTime($modifiedDate);
                $modifiedDate = $modifiedDate->modify($modification)->format('Y-m-d H:i:s');

                // Log::info('Modifying ' . $contractJob->date_scheduled->format('Y-m-d') . ' ' . $modification . ' to ' . $modifiedDate . json_encode(array('customer' => $job->contract->contract_details->profile_details->customer_details->id)));

                // $result->merge($this->rescheduleJob($contractJob, $modifiedDate));
            }
        } elseif (isset($options['reschedule_future_jobs'])) {
            // Move all future jobs to "first Monday" (example) of their respective month
            $weekOfTheMonth = $options['week_of_the_month'] ?? 'First';
            // dd($weekOfTheMonth);
            $dayOfTheWeek = $options['day_of_the_week'] ?? 'Monday';
            $scope = $weekOfTheMonth . ' ' . $dayOfTheWeek;
            $preferredTime = $options['preferred_time'] ?? '00:00:00';

            $contract = $job->contract;
            // dd($contract);
            $contract->preferred_time = $preferredTime;
            $contract->day_of_the_week = $dayOfTheWeek;
            $contract->week_of_the_month = $weekOfTheMonth;
            $contract->save();
            foreach ($contract->jobs_details as $contractJob) {
                if (
                    $contractJob->isFinished()
                    || $contractJob->date_scheduled < $job->date_scheduled
                    || $job === $contractJob
                ) {
                    continue;
                }

                $monthObj = new DateTime($contractJob->date_scheduled);
                $month = $monthObj->modify('-1 month')->format('F Y');

                // $dateScheduled = StringHelper::parseDate('last day of ' . $month)->modify($scope);
                // $result->merge($this->rescheduleJob($contractJob, $dateScheduled, $preferredTime));
            }

            // return 111;
        }
        if (isset($options['anytime'])) {
            $this->rescheduleJobReschedulingHelper($job, $options['date_scheduled']);
            // dd(11);
            $this->assignAnytimeSlot($job, $options['route']);
        } else {
            // dd(11);

            $this->rescheduleJobReschedulingHelper($job, $options['date_scheduled'], $timeScheduled, $options['route'] = null);
        }

        return true;
    }

    public function assignAnytimeSlot($job, $route)
    {
        // if ($route === null) {
        $dateScheduled = $job->date_scheduled ?? null;
        $office_id = $job->contract->contract->agreement_details->office_details->id ?? null;
        $route = PocomosRoute::where(['date_scheduled' => $dateScheduled, 'office_id' => $office_id])->first();

        if (!$route) {
            $route = $this->createRoute_routeFactory($office_id, $dateScheduled, null, 'SlotHelper:389');
        }
        // }
        return $this->assignJobToRoute($job, $route, null, true);
    }

    /**Get jobs details contract and statuses base */
    public function getJobsByContractAndStatus($contract, $statuses = array())
    {

        // $pocomos_job_query = PocomosJob::with('contract')->where('date_completed',null)->where('date_scheduled','>',date('Y-m-d'));

        // if (count($statuses)) {
        //     $pocomos_job_query = $pocomos_job_query->whereIn('status', $statuses);
        // }
        // return $pocomos_job_query->get();

        $sql = "SELECT j.* FROM pocomos_jobs AS j JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id WHERE j.date_completed IS NULL ANd j.date_scheduled > " . date('Y-m-d') . "";

        if (count($statuses)) {
            $statuses = $this->convertArrayInStrings($statuses);
            $sql .= " AND j.status IN ($statuses) AND pcc.id = $contract->id";
        }
        $results = DB::select(DB::raw($sql));
        return $results;
    }

    /**
     * Get pagination details
     * page : pagination start point like 1, 2, 3, etc..
     * perPage : per page data value like 5, 10, etc..
     * isRowQuery : if result data are from DB::row query then `true` else `false` normal laravel get listing
     */
    public function getPaginationDetails($page = null, $perPage = null, $isRowQuery = false)
    {
        if ($isRowQuery) {
            if ($page && $perPage) {
                $page    = $page;
                $perPage = $perPage;
                if ($page == 1) {
                    $page = config('constants.DEFAULT_OFFSET');
                } else {
                    $page = $perPage * ($page - 1);
                }
            } else {
                $page    = config('constants.DEFAULT_OFFSET');
                $perPage = config('constants.DEFAULT_PER_PAGE');
            }
        } else {
            if ($page && $perPage) {
                $page    = $page;
                $perPage = $perPage;
            } else {
                $page    = config('constants.DEFAULT_PAGE');
                $perPage = config('constants.DEFAULT_PER_PAGE');
            }
        }
        return array('page' => $page, 'perPage' => $perPage);
    }

    /**
     * Attempts to assign the Job to the given Route at the given time.
     *
     * @param Job $job
     * @param Route $route
     * @param \DateTime $timeSlot
     *
     * @param bool $anytime
     * @param null $durationOverride
     * @return Slot
     */
    public function assignJobToRoute($job, $route, $timeSlot = null, $anytime = false, $durationOverride = null)
    {
        $office = $job->contract->contract_details->agreement_details->office_details;
        $customer = ($profile = $job->contract->contract_details->profile_details)
            ? $profile->customer_details
            : null;

        $configuration = PocomosPestOfficeSetting::whereOfficeId($office->id)->firstOrFail();
        $duration = config('constants.INITIAL') === $job->type
            ? $configuration->initial_duration
            : $configuration->regular_duration;

        if ($customer && $customer->default_job_duration !== null) {
            $duration = $customer->default_job_duration;
        }


        if ($durationOverride) {
            $duration = $durationOverride;
        }

        if (!is_int($duration)) {
            $duration = (int)filter_var($duration, FILTER_SANITIZE_NUMBER_INT);
        }

        // dd($anytime);

        $slot = new PocomosRouteSlots();
        $slot->duration = $duration;
        if ($anytime === true) {
            $slot->time_begin = new \DateTime('midnight');
            $slot->anytime = true;
        } else {
            if ($timeSlot === null) {
                $availableTimeSlots = $this->getAvailableTimeSlots($route, $duration);
                $timeSlot = reset($availableTimeSlots);

                if (!$timeSlot) {
                    throw new \Exception(__('strings.message', ['message' => 'Unable to assign slot. A suitable route was unable to be located or created.']));
                }
            }
            $slot->time_begin = $timeSlot;
        }
        $this->doAssignSlot($slot, $route);

        $job->slot_id = $slot->id;
        $job->date_scheduled = $route->date_scheduled;
        $job->save();
        // $event = new JobAssignedToSlotEvent($job);
        // $this->eventDispatcher->dispatch(Events::JOB_ASSIGNED_TO_SLOT, $event);

        return $slot;
    }


    public function assignJobToRouteNew($job, $route, $timeSlot = null, $anytime = false, $durationOverride = null, $q)
    {
        $office = $job->contract->contract_details->agreement_details->office_details;
        $customer = ($profile = $job->contract->contract_details->profile_details)
            ? $profile->customer_details
            : null;

        $configuration = PocomosPestOfficeSetting::whereOfficeId($office->id)->firstOrFail();

        $duration = config('constants.INITIAL') === $job->type
            ? $configuration->initial_duration
            : $configuration->regular_duration;

        // dd($duration);
        // dd($customer);

        if ($customer && $customer->default_job_duration !== null && is_int($customer->default_job_duration)) {
            $duration = $customer->default_job_duration;
            // dd(int($duration));
        }

        // dd($duration);

        if ($durationOverride) {
            $duration = $durationOverride;
        }

        // dd($duration);

        $slot = new PocomosRouteSlots();
        $slot->duration = $duration;
        if ($anytime === true) {
            $slot->time_begin = new \DateTime('midnight');
            $slot->anytime = true;
        } else {

            if ($timeSlot === null) {
                $availableTimeSlots = $this->getAvailableTimeSlots($route, $duration);
                // dd($availableTimeSlots);

                // $timeSlot = reset($availableTimeSlots);
                $timeSlot = $availableTimeSlots[$q] ?? null;

                // if($q==2){
                //     dd(11);
                // }

                // dd($timeSlot);

                if (!$timeSlot) {
                    throw new \Exception(__('strings.message', ['message' => 'Unable to assign slot. A suitable route was unable to be located or created.']));
                }
            }

            // dd($availableTimeSlots[$q]);
            $slot->time_begin = $timeSlot;
        }

        $this->doAssignSlot($slot, $route);

        $job->slot_id = $slot->id;
        // $job->date_scheduled = $route->date_scheduled;
        $job->save();

        // $event = new JobAssignedToSlotEvent($job);
        // $this->eventDispatcher->dispatch(Events::JOB_ASSIGNED_TO_SLOT, $event);

        return $slot;
    }

    /**
     * Diffs the given Route and a set of "standard" time slots, returning the result
     *
     * @param $route
     * @param int|null $fitsDuration
     *
     * @throws \RuntimeException
     * @return array|\DateTime[]
     */
    public function getAvailableTimeSlots($route, $fitsDuration = null)
    {
        return $this->diffRouteAndStandardTimeSlots($route, $fitsDuration);
    }

    public function getStandardTimeSlots($officeId, $date, $techId = null)
    {
        $route = $this->createRoute_routeFactory($officeId, $date, $techId, 'TimeSlotHelper:71');

        return $this->diffRouteAndStandardTimeSlots($route);
    }

    public function diffRouteAndStandardTimeSlots($route, $fitsDuration = null)
    {
        $timeSlots = array();
        $office = $route->office_detail;
        $technician = $route->technician_detail;
        $dateScheduled = $route->date_scheduled;
        $dateScheduledNew = new \DateTime($dateScheduled);
        $today = $dateScheduledNew->diff(new \DateTime('today midnight'))->days === 0;

        $configuration = PocomosPestOfficeSetting::whereOfficeId($office->id)->firstOrFail();

        $effectiveSchedule = $this->getEffectiveScheduleImproved($technician, $office, $dateScheduled);
        $effectiveSchedule = (object) $effectiveSchedule;

        if (!$effectiveSchedule->open) {
            return $timeSlots;
        }

        if (!$configuration || !$effectiveSchedule) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to load pest management configuration for office']));
        }

        // Clone DateTimes to ensure no funky persistence problems when we modify them
        $currentTime = $effectiveSchedule->time_tech_start;

        $currentTimeNew = new \DateTime($currentTime);
        $now = new \DateTime();
        $nowNew = $now->format('H:i:s');

        if ($today && $nowNew > $currentTime) {
            $currentTime = $now->format('H') . ':' . $currentTimeNew->format('i') . ':' . $currentTimeNew->format('s');
        }
        $endTime = $effectiveSchedule->time_tech_end;

        $step = $configuration->regular_duration;

        $endTimeNew = new \DateTime($endTime);

        if($endTimeNew->format('i') == '59' && $endTimeNew->format('H') == "23"){
            $endTime = "23:30:00";
        }

        while ($currentTime < $endTime) {
            if (isset($route->id)) {

                if (!$route->hasSlotAt($currentTime)) {
                    if ($fitsDuration !== null) {
                        $currentTime = new \DateTime($currentTime);
                        $currentEndTime = clone $currentTime;
                        $currentEndTime->modify('-1 second');
                        $checkUntil = clone $currentEndTime;
                        $checkUntil->modify(sprintf('+%s minutes', $fitsDuration));
                        $checkUntil = $checkUntil->format('H') . ':' . $checkUntil->format('i') . ':' . $checkUntil->format('s');

                        if ($checkUntil <= $endTime) {
                            $hasSlot = false;
                            while ($currentEndTime < $checkUntil) {
                                $currentEndTime->modify(sprintf('+%s minutes', 5));
                                if ($route->hasSlotAt($currentEndTime)) {
                                    $hasSlot = true;
                                }
                            }

                            if (!$hasSlot) {
                                $currentTime = $currentTime->format('H') . ':' . $currentTime->format('i') . ':' . $currentTime->format('s');
                                $timeSlots[] = $currentTime;
                            }
                        }
                    } else {
                        $timeSlots[] = $currentTime;
                    }
                }
            }

            if (is_string($currentTime)) {
                $currentTime = new \DateTime($currentTime);
            }
            $currentTime->modify(sprintf('+%s minutes', $step));

            $currentTime = $currentTime->format('H') . ':' . $currentTime->format('i') . ':' . $currentTime->format('s');
        }

        return $timeSlots;
    }

    /**
     * Gets the effective schedule for the given (optional) date
     *
     * @param $techOrOffice
     * @param \DateTime|null $date
     *
     * @deprecated
     * @see getWrappedEffectiveSchedule
     * @return mixed
     * @throws \RuntimeException
     */
    public function getEffectiveSchedule($techOrOffice, $date = null)
    {
        $technican = PocomosTechnician::find($techOrOffice->id);

        if ($technican) {
            $office = $techOrOffice->user_detail->company_details;
            $technician = $techOrOffice;
        } elseif ($techOrOffice) {
            $office = $techOrOffice;
        } else {
            throw new \Exception(__('strings.message', ['message' => 'The ScheduleHelper can only get effective schedules for Technicians or Offices']));
        }

        $date = new \DateTime((string)$date);

        if (isset($technician) && $date) {
            $schedule = $this->getTechnicianOverrideSchedule($technician, $date);
        }

        if (empty($schedule) && $date) {
            $schedule = $this->getOfficeOverrideSchedule($office, $date);
        }

        if (empty($schedule) && isset($technician)) {
            $schedule = $this->getDefaultTechnicianSchedule($technician);
        }

        if (empty($schedule)) {
            $schedule = $this->getDefaultOfficeSchedule($office);
        }

        if (empty($schedule)) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to create effective schedule, no default schedule found.']));
        }

        return $schedule;
    }

    public function getEffectiveScheduleImproved($technican = null, $office = null, $date = null)
    {
        if ($technican) {
            $technican = PocomosTechnician::findorfail($technican->id);
        }
        // dd($office);

        $office = PocomosCompanyOffice::find($office->id);

        if ($technican) {
            $office = $technican->user_detail->company_details;
            $technician = $technican;
        } else {
            if ($office) {
                $office = $office;
            } else {
                throw new \Exception(__('strings.message', ['message' => 'The ScheduleHelper can only get effective schedules for Technicians or Offices']));
            }
        }


        $date = new \DateTime();

        if (isset($technician) && $date) {
            $schedule = $this->getTechnicianOverrideSchedule($technician, $date);
        }

        if (empty($schedule) && $date) {
            $schedule = $this->getOfficeOverrideSchedule($office, $date);
        }

        if (empty($schedule) && isset($technician)) {
            $schedule = $this->getDefaultTechnicianSchedule($technician);
        }

        if (empty($schedule)) {
            $schedule = $this->getDefaultOfficeSchedule($office);
        }

        if (empty($schedule)) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to create effective schedule, no default schedule found.']));
        }
        return $schedule;
    }

    public function getTechnicianOverrideSchedule($technician, $date)
    {
        $date = $date->format('Y-m-d');
        $data = DB::select(DB::raw("SELECT s.*
        FROM pocomos_schedules AS s
        WHERE s.technician_id = $technician->id AND s.date = '$date'"));

        $res = $data[0] ?? null;
        return $res;
    }

    public function getOfficeOverrideSchedule($office, $date)
    {
        $date = $date->format('Y-m-d');
        $data = DB::select(DB::raw("SELECT s.*
        FROM pocomos_schedules AS s
        WHERE s.office_id = $office->id AND s.date = $date"));

        $res = $data[0] ?? null;
        return $res;
    }

    public function getDefaultTechnicianSchedule($technician)
    {
        $data = DB::select(DB::raw("SELECT s.*
        FROM pocomos_schedules AS s
        WHERE s.technician_id = $technician->id"));

        $res = $data[0] ?? null;
        return $res;
    }

    public function getDefaultOfficeSchedule($office)
    {
        $data = DB::select(DB::raw("SELECT s.*
        FROM pocomos_schedules AS s
        WHERE s.office_id = $office->id"));

        $res = $data[0] ?? null;
        return $res;
    }

    /**
     * Actually assigns a slot to the given route, throwing an exception failure.
     *
     * @param $slot
     * @param $route
     */
    public function doAssignSlot($slot, $route)
    {
        if ($route->locked) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to assign job to route. The route is locked and cannot be edited.']));
        }

        if (!$this->slotFitsInRoute($slot, $route)) {
            throw new \Exception(__('strings.message', ['message' => 'Unable to assign job to route. The job does not fit within the specified time.']));
        }

        // dd($slot->id);

        $slot->route_id = $route->id ?? null;
        $slot->type = 'Hard';
        $slot->schedule_type = 'Hard';
        $slot->type_reason = '';
        $slot->active = true;
        $slot->anytime = false;
        $slot->save();
        return;
    }

    /**
     * Assigns a Lunch Slot to the Route, moving an existing one if it exists.
     *
     * If $timeSlot is null, the configured default time should be used.
     */
    public function assignLunchSlot($route, $timeSlot = null)
    {
        $slot = $this->getLunchSlot($route);

        if ($slot) {
            $slot->route_id = null;
            $slot->save();
        } else {
            // dd(11);
            $slot = new PocomosRouteSlots();
            $slot->type = config('constants.LUNCH');
            $slot->time_begin = '';
            $slot->duration = 0;
            $slot->type_reason = '';
            $slot->active = true;
            $slot->schedule_type = '';
            $slot->anytime = 0;
            $slot->save();
            // dd(11);
        }

        $schedule = $this->getEffectiveScheduleImproved($route->technician_detail, $route->office_detail, $route->date_scheduled);

        /* if($route->technician_id){
            $schedule = $this->getTechnicianOverrideSchedule($route->technician_id, $route->date_scheduled);
        }

        if($route->technician_id){
            $schedule = $this->getOfficeOverrideSchedule($route->office_id, $route->date_scheduled);
        } */

        if ($slot->duration === null) {
            $slot->duration = $schedule->lunch_duration;
        }

        if ($timeSlot === null) {
            $timeSlot = $schedule->time_lunch_start;
        }

        $slot->time_begin = $timeSlot;
        $slot->save();

        $this->doAssignSlot($slot, $route);

        return $slot;
    }

    public function getLunchSlot($route)
    {
        if (isset($route->slots)) {
            $filtered = $route->slots->filter(function ($slot) {
                return $slot->isLunch();
            });
            // dd($filtered->first());
            return $filtered->first();
        }
    }

    /**
     * Finds a single Job with the given id and the office
     */
    public function findJobByIdAndOffice($jobId, $officeId)
    {
        $data = DB::select(DB::raw("SELECT j.*
        FROM pocomos_jobs AS j
        JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id
        JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
        JOIN pocomos_customer_sales_profiles AS p ON c.profile_id = p.id
        JOIN pocomos_agreements AS a ON c.agreement_id = a.id
        WHERE p.office_id = $officeId AND j.id = $jobId"));

        $res = $data[0] ?? null;
        return $res;
    }

    /**
     * Send sms form letter by customer phone
     */
    public function sendSmsFormLetterByPhone($customer, $phone, $letter, $officeUser, $job = null)
    {
        $parameters = $this->getDynamicParameters(
            $customer, /* PestControlContract */
            null,
            $job
        );

        $message = $this->parseMessageVariables($letter->message, $parameters);

        return $this->sendMessage($customer->sales_profile->office_details, $phone, $message, $officeUser, true /* seen */);
    }

    /**
     * Send sms form letter by lead phone
     */
    public function sendLeadSmsFormLetterByPhone($lead, $phone, $letter, $officeUser, $job = null)
    {
        $parameters = $this->getLeadDynamicParameters($lead, $pestContract = null, $job = null, $pdf = false);

        $message = $this->parseMessageVariables($letter->message, $parameters);

        $office = PocomosCompanyOffice::findOrFail($letter->office_id);

        return $this->sendMessage($office, $phone, $message, $officeUser);
    }

    /**Get salesperson state base details */
    public function getSalespersonState($salesperson)
    {
        $res = DB::select(DB::raw("SELECT ss.*
        FROM pocomos_reports_salesperson_states AS ss
        WHERE ss.salesperson_id = $salesperson->id"));

        $res = $res[0] ?? array();
        return $res;
    }

    /**Get branch state base details */
    public function getBranchState($office)
    {
        $salesPeopleTempIds = DB::select(DB::raw("SELECT s.id
        FROM pocomos_salespeople AS s
        JOIN pocomos_company_office_users AS u ON s.user_id = u.id
        JOIN pocomos_company_offices AS o ON u.office_id = o.id
        JOIN orkestra_users AS user ON u.user_id = user.id
        WHERE s.active = true AND u.active = true AND user.active = true AND o.id = $office->id"));

        $salesPeopleIds = array_map(function ($row) {
            return $row->id;
        }, $salesPeopleTempIds);
        $salesPeopleIds = $this->convertArrayInStrings($salesPeopleIds);

        $res = DB::select(DB::raw("SELECT SUM(cs.autopay_account_percentage ) / COUNT(cs.id) as autopay_account_percentage  ,
            SUM(cs.serviced_this_year) as serviced_this_year,
            SUM(cs.services_scheduled_today) as services_scheduled_today,
            SUM(cs.average_contract_value) / COUNT(cs.id) as average_contract_value,
            SUM(cs.serviced_this_month) as serviced_this_month,
            SUM(cs.total_sold) as total_sold,
            SUM(cs.total_active) as total_active,
            SUM(cs.total_paid) as total_paid,
            SUM(cs.count_active_contracts) as count_active_contracts,
            (SUM(cs.total_paid) + (sum(cs.total_active) - sum(cs.total_sold)) / sum(cs.total_sold)) as service_ratio
        FROM pocomos_reports_salesperson_states AS cs
        WHERE cs.salesperson_id IN ($salesPeopleIds)"));

        $res = $res[0] ?? array();
        return $res;
    }

    /**Get company state base details */
    public function getCompanyState($office)
    {
        $salesPeopleTempIds = DB::select(DB::raw("SELECT s.id
        FROM pocomos_salespeople AS s
        JOIN pocomos_company_office_users AS u ON s.user_id = u.id
        JOIN pocomos_company_offices AS o ON u.office_id = o.id
        JOIN orkestra_users AS user ON u.user_id = user.id
        WHERE s.active = true AND u.active = true AND user.active = true AND o.id = $office->id"));

        $salesPeopleIds = array_map(function ($row) {
            return $row->id;
        }, $salesPeopleTempIds);
        $salesPeopleIds = $this->convertArrayInStrings($salesPeopleIds);

        $res = DB::select(DB::raw("SELECT SUM(cs.autopay_account_percentage ) / COUNT(cs.id) as autopay_account_percentage  ,
            SUM(cs.serviced_this_year) as serviced_this_year,
            SUM(cs.services_scheduled_today) as services_scheduled_today,
            SUM(cs.average_contract_value) / COUNT(cs.id) as average_contract_value,
            SUM(cs.serviced_this_month) as serviced_this_month,
            SUM(cs.total_sold) as total_sold,
            SUM(cs.total_active) as total_active,
            SUM(cs.total_paid) as total_paid,
            SUM(cs.count_active_contracts) as count_active_contracts,
            (SUM(cs.total_paid) + (sum(cs.total_active) - sum(cs.total_sold)) / sum(cs.total_sold)) as service_ratio
        FROM pocomos_reports_salesperson_states AS cs
        WHERE cs.salesperson_id IN ($salesPeopleIds)"));

        $res = $res[0] ?? array();
        return $res;
    }

    /**
     * @param \Pocomos\Bundle\SalesTrackerBundle\Entity\PocomosCommissionSetting $settings
     * @param $numberOfCurrentAccounts
     * @param $averageContractValue
     *
     * @param DefaultOfficeSchedule $defaultOfficeSchedule
     * @return array
     */
    public function calculateCommissions($settings, $numberOfCurrentAccounts, $averageContractValue, $defaultOfficeSchedule)
    {
        $commissionPercentage = $settings->commission_percentage / 100;
        $contractGoal = $settings->goal;
        $lastSalesDay = $settings->last_day_summer;
        $bonuses = $settings->bonuse_details;
        $deductions = $settings->deduction_details;
        $deductionTotal = 0;
        $bonusTotal = 0;
        $theoreticalBonusTotal = 0;

        /** @var PocomosCommissionBonuse $bonus */
        foreach ($bonuses as $bonus) {
            if ($bonus->accounts_needed <= $numberOfCurrentAccounts) {
                $bonusTotal += $bonus->bonus_value;
            }

            if ($bonus->accounts_needed <= $contractGoal) {
                $theoreticalBonusTotal += $bonus->bonus_value;
            }
        }

        /** @var PocomosCommissionDeduction $deduction */
        foreach ($deductions as $deduction) {
            $deductionTotal += $deduction->amount;
        }

        $actualTotalCommissions = $numberOfCurrentAccounts * ($averageContractValue * $commissionPercentage);
        $dName = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
        /**THIS DAYS OPEN WILL UPDATE ACCORDING TO NEW DEVELOPEMNT NOW THIS IS SKIPPED SO STATIC DEFINED HERE
         * SANDBOX MODULE : https://sandbox.pocomos.com/pest/office-configuration/edit
         */
        // $daysOpen = $defaultOfficeSchedule->days_open;
        $daysOpen = ['true', 'true', 'true', 'true', 'true', 'true', 'true'];

        $daysOpenInAWeek = array_filter($daysOpen, function ($val) {
            return $val;
        });

        $today = new \DateTime();
        if ($today > $lastSalesDay) {
            $daysLeft = 0;
        } else {
            $a = array_search($today->format('D'), $dName);
            $b = array_search($lastSalesDay->format('D'), $dName);
            $totalDays = $lastSalesDay->diff($today)->days - (6 - $a + $b);
            $fullWeeksLeft = $totalDays / 7;
            $countPartial = function ($start = 0, $end = 7) use ($daysOpen) {
                $days = 0;
                for (; $start < $end; $start++) {
                    $days += $daysOpen[$start] ? 1 : 0;
                }

                return $days;
            };
            $daysLeft = $fullWeeksLeft * count($daysOpenInAWeek) + $countPartial($a) + $countPartial(0, $b + 1);
        }
        $salesPerDay = round((($contractGoal - $numberOfCurrentAccounts) / ($daysLeft == 0 ? 1 : $daysLeft)), 2);
        $theoreticalTotalCommissions = $contractGoal * ($averageContractValue * $commissionPercentage);

        $calculations = array(
            'actual' => array(
                'total_commissions' => $actualTotalCommissions,
                'total_bonuses' => $bonusTotal,
                'total_deductions' => $deductionTotal,
                'total_earned' => $actualTotalCommissions + $bonusTotal - $deductionTotal,
            ),
            'theoretical' => array(
                'days_left' => $daysLeft,
                'sales_per_day' => $salesPerDay,
                'total_commissions' => $theoreticalTotalCommissions,
                'total_bonuses' => $theoreticalBonusTotal,
                'total_Deductions' => $deductionTotal,
                'total_earned' => $theoreticalTotalCommissions + $theoreticalBonusTotal - $deductionTotal,
            ),
        );

        return $calculations;
    }

    /**Get sales person user base team */
    public function getSalesPersonTeam($userId)
    {
        $officeUser = PocomosCompanyOfficeUser::whereUserId($userId)->firstOrFail();
        $salesPeople = PocomosSalesPeople::whereUserId($officeUser->id)->firstOrFail();
        $userTeam = PocomosMembership::where('salesperson_id', $salesPeople->id)->first();
        if (!$userTeam) {
            return null;
        }

        $team = PocomosTeam::findOrFail($userTeam->team_id);
        return $team ?? null;
    }

    /**Get available slots based on dates */
    public function getAvailableSpots($team, $date)
    {
        $spots = array();
        if (!($team)) {
            return $spots;
        }

        $office = $team->office_detail;
        $config = PocomosPestOfficeSetting::whereOfficeId($office->id)->first();
        $initialDuration = $config->initial_duration;
        $timeWindow = $config->include_time_window ? $config->include_time_window : 0;
        $routes = $this->findByDateScheduled($date, $office);

        foreach ($routes as $route) {
            $route = PocomosRoute::findOrFail($route->id);
            $timeSlots = $this->getAvailableTimeSlotsBasedTeam($route, $team, $initialDuration);
            $firstSlot = reset($timeSlots);
            if (!$firstSlot) {
                continue;
            }
            $start = $firstSlot->format('i');
            foreach ($timeSlots as $timeSlot) {
                $key = $timeSlot->format('Hi');
                if (!isset($spots[$key])) {
                    $timeUntil = null;
                    if ($timeWindow > 0) {
                        $timeUntil = $timeSlot;
                        $timeUntil->modify(sprintf('+%s hours', $timeWindow));
                    }

                    $spots[$key] = array($date, $timeSlot, $timeUntil);
                }

                $spot = $spots[$key];
                $spot->addRoute($route);
            }
        }

        ksort($spots);

        return array_values($spots);
    }

    public function findByDateScheduled($date, $office)
    {
        $res = DB::select(DB::raw("SELECT r.*
        FROM pocomos_routes AS r
        LEFT JOIN pocomos_technicians AS t ON r.technician_id = t.id
        LEFT JOIN pocomos_company_office_users AS ou ON t.user_id = ou.id
        LEFT JOIN orkestra_users AS u ON ou.user_id = u.id
        WHERE r.date_scheduled = '$date' AND r.office_id = $office->id ORDER BY u.first_name, u.last_name, r.id"));

        return $res ?? array();
    }

    /**
     * Gets available time slots for the given Team on the given Route
     */
    public function getAvailableTimeSlotsBasedTeam($route, $team, $fitsDuration = null)
    {
        $timeSlots = $this->getAvailableTimeSlots($route, $fitsDuration);

        $assignments = DB::select(DB::raw("SELECT ta.*
        FROM pocomos_teams_route_assignments AS ta
        JOIN pocomos_teams AS t ON ta.team_id = t.id
        JOIN pocomos_routes AS r ON ta.route_id = r.id
        WHERE ta.team_id = $team->id AND ta.route_id = $route->id"));

        $filtered = array();
        foreach ($timeSlots as $timeSlot) {
            foreach ($assignments as $assignment) {
                if ($this->timeIsWithinAssignment($timeSlot, $assignment)) {
                    $filtered[] = $timeSlot;

                    break;
                }
            }
        }

        return $filtered;
    }

    public function timeIsWithinAssignment($time, $assignment)
    {
        $endTime = $assignment->time_begin;
        $endTime->modify(sprintf('+%s minutes', $assignment->duration));

        return $time >= $assignment->time_begin && $time < $endTime;
    }

    public function getReservedSpots($officeUser)
    {
        $res = DB::select(DB::raw("SELECT s.*
        FROM pocomos_route_slots AS s
        JOIN pocomos_company_office_users AS ou ON s.office_user_id = ou.id
        WHERE s.type = '" . config("constants.RESERVED") . "' AND s.office_user_id = $officeUser->id"));
        return $res;
    }

    public function privilegedUser($officeUser)
    {
        $roles = $officeUser->user_details->permissions->toArray();
        foreach ($roles as $role) {
            $roleName = $role['permission']['role'];
            if (in_array($roleName, array('ROLE_SECRETARY', 'ROLE_BRANCH_MANAGER', 'ROLE_OWNER', 'ROLE_ADMIN'))) {
                return true;
            }
        }

        return false;
    }

    public function findOneByOfficeAndIdViewableByOfficeUser($office, $id, $officeUser)
    {
        if ($this->privilegedUser($officeUser)) {
            $res = DB::select(DB::raw("SELECT r.*
            FROM pocomos_recruits AS r
            JOIN pocomos_recruit_status AS s ON r.recruit_status_id = s.id
            JOIN pocomos_recruiting_office_configurations AS oc ON s.recruiting_office_configuration_id = oc.id
            JOIN pocomos_company_offices AS o ON oc.office_id = o.id
            WHERE o.id = $office->id AND r.id = $id AND r.active = true"));

            return $res[0] ?? null;
        }

        $res = DB::select(DB::raw("SELECT r.*
            FROM pocomos_recruits AS r
            JOIN pocomos_recruit_status AS s ON r.recruit_status_id = s.id
            JOIN pocomos_recruiting_office_configurations AS oc ON s.recruiting_office_configuration_id = oc.id
            JOIN pocomos_company_offices AS o ON oc.office_id = o.id
            JOIN pocomos_recruiters AS recruiter ON r.recruiter_id = recruiter.id
            JOIN pocomos_company_office_users AS rctru ON recruiter.user_id = rctru.id
            JOIN pocomos_company_office_user_profiles AS oup ON rctru.profile_id = oup.id
            WHERE o.id = $office->id AND r.id = $id AND r.active = true AND oup.id = $officeUser->profile_id"));

        return $res[0] ?? null;
    }

    /**Get loggend in user roles */
    public function getUserAllRoles()
    {
        //Get user base active groups
        $groups_ids = OrkestraUserGroup::where('user_id', auth()->user()->id)->pluck('group_id')->toArray();
        $user_roles = OrkestraGroup::whereIn('id', $groups_ids)->pluck('role')->toArray();
        $configured_roles = config('roles');

        $allRoles = array();
        foreach ($configured_roles as $key => $val) {
            //  || in_array('ROLE_ADMIN', $user_roles)
            if (in_array($key, $user_roles)) {
                $allRoles[] = $key;
                foreach ($val as $role) {
                    $allRoles[] = $role;

                    if (isset($configured_roles[$role]) && count($configured_roles[$role])) {
                        $allRoles = array_merge($allRoles, $configured_roles[$role]);
                    }
                }
            }
        }
        return array_values(array_unique($allRoles));
    }

    /**Send email for rescheule job */
    public function sendRescheduleJobEmail($job)
    {
        $contract = $job->contract;
        $profile = $contract->contract_details->profile_details;
        $office = $profile->office_details;
        $pestConfig = PocomosPestOfficeSetting::whereOfficeId($office->id)->first();
        $customer = $job->contract->contract_details->profile_details->customer;
        $contract = $job ? $job->contract : null;
        $customerEmail = $profile->customer->email;
        $from = unserialize($office->email);
        $from = $from[0] ?? null;

        $params = array(
            'config' => $pestConfig,
            'profile' => $profile,
            'job' => $job,
        );

        $subject = $office->name . ' has updated your service';

        $agreement_body = " ";
        $agreement_body .= view('emails.reschedule_template', $params);
        $agreement_body .= $this->renderDynamicTemplate($pestConfig->reschedule_message, null, $customer, $contract, $job);
        // dd($job);

        $email = new PocomosEmail();
        $email->office_id = $office->id;
        $email->customer_sales_profile_id = $profile->id;
        $email->type = config('constants.JOB_RESCHEDULED');
        $email->body = $agreement_body;
        $email->subject = $subject;
        $email->reply_to = $from;
        $email->reply_to_name = $office->name ?? '';
        $email->sender = $from;
        $email->sender_name = $office->name ?? '';
        $email->active = true;
        $email->save();

        Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($customerEmail, $from, $subject) {
            $message->from($from);
            $message->to($customerEmail);
            $message->subject($subject);
        });

        return;
    }

    /**Get system primary roles */
    public function getPrimaryRoles()
    {
        $primaryRoles = array(
            'ROLE_ADMIN',
            'ROLE_OWNER',
            'ROLE_BRANCH_MANAGER',
            'ROLE_SECRETARY',
            'ROLE_SALES_MANAGER',
            'ROLE_SALES_ADMIN',
            'ROLE_ROUTE_MANAGER',
            'ROLE_COLLECTIONS',
            'ROLE_TECHNICIAN',
            'ROLE_RECRUITER',
            'ROLE_SALESPERSON'
        );
        return $primaryRoles;
    }

    /**Assign role */
    public function assignRole($profile, $role)
    {
        $group = OrkestraGroup::where('role', $role)->firstOrFail();
        // OrkestraUserGroup::create(['group_id' => $group->id, 'user_id' => $profile->id]);
        OrkestraUserGroup::updateOrCreate(['group_id' => $group->id, 'user_id' => $profile->id], ['group_id' => $group->id, 'user_id' => $profile->id]);
    }

    /**Unassign role */
    public function unassignRole($profile, $role)
    {
        $group = OrkestraGroup::where('role', $role)->firstOrFail();
        OrkestraUserGroup::where('group_id', $group->id)->where('user_id', $profile->id)->delete();
    }

    /**
     * Disable a user's Salesperson privileges.
     * @param $profile
     */
    public function disableSalesperson($profile)
    {
        foreach ($profile->pocomos_company_office_users->toArray() as $officeUser) {
            $salesperson = PocomosSalesPeople::where('user_id', $officeUser['id'])->first();
            if ($salesperson) {
                $salesperson->active = false;
                $salesperson->save();
            }
        }
        $this->unassignRole($profile, 'ROLE_SALESPERSON');
        $this->unassignRole($profile, 'ROLE_SUPPRESS_SALESPERSON_VIEW');
        return true;
    }

    /**
     * Disable a user's Recruiter privileges
     * @param $profile
     */
    public function disableRecruiter($profile)
    {
        foreach ($profile->pocomos_company_office_users->toArray() as $officeUser) {
            $recruiter = PocomosRecruiter::where('user_id', $officeUser['id'])->first();
            if ($recruiter) {
                $recruiter->active = false;
                $recruiter->save();
            }
        }

        $this->unassignRole($profile, 'ROLE_RECRUITER');
        return true;
    }

    /**
     * Disable a user's Technician privileges.
     * @param $profile
     */
    public function disableTechnician($profile)
    {
        foreach ($profile->pocomos_company_office_users->toArray() as $officeUser) {
            $technician = PocomosTechnician::where('user_id', $officeUser['id'])->first();
            if ($technician) {
                $technician->active = false;
                $technician->save();
            }
        }

        $this->unassignRole($profile, 'ROLE_TECHNICIAN');
        $this->unassignRole($profile, 'ROLE_TECH_RESTRICTED');
        return true;
    }

    /**Create charge */
    public function createCharge($data, $credentials_id)
    {
        // dd(11);
        $cardDetail = OrkestraCredential::findOrFail($credentials_id);

        $exceptions = unserialize($cardDetail->credentials);

        $url = config('constants.ZIFT_SANDBOX_URL');

        $data = array(
            'requestType' => $data['requestType'],
            'userName' => $exceptions['username'],
            'password' => $exceptions['password'],
            'accountId' => $exceptions['account_id'] ?? '',
            'amount' => $data['amount'] * 100 ?? null,
            'accountType' => $data['accountType'] ?? null,
            'transactionIndustryType' => $data['transactionIndustryType'] ?? null,
            'accountNumber' => $data['accountNumber'] ?? null,
            'accountAccessory' => $data['accountAccessory'] ?? null,
            'csc' => $data['csc'] ?? null,
            'holderName' => $data['holderName'] ?? null,
            'holderType' => $data['holderType'] ?? null,
            'street' => $data['street'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zipCode' => $data['zipCode'] ?? null,
            'countryCode' => $data['countryCode'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['zipCode'] ?? null,
            'transactionCategoryType' => $data['transactionCategoryType'] ?? null, //Bill payment.
            'transactionModeType' => $data['transactionModeType'] ?? null, //For card not present.
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // dd($result);

        return $result;
    }

    /**Create Refund */
    public function createRefund($data, $credentials_id)
    {
        $cardDetail = OrkestraCredential::findOrFail($credentials_id);
        $exceptions = unserialize($cardDetail->credentials);

        $url = config('constants.ZIFT_SANDBOX_URL');
        $data = array(
            'requestType' => $data['requestType'],
            'userName' => $exceptions['username'],
            'password' => $exceptions['password'],
            'accountId' => $exceptions['account_id'] ?? $data['accountId'],
            'amount' => $data['amount'] ?? null,
            'transactionId' => $data['transactionId'] ?? null,
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * Get customer mobile phone
     */
    public function getLastMessagePhoneByCustomer($customerId)
    {
        $res = DB::select(DB::raw('SELECT s.phone_id
                FROM `pocomos_sms_usage` AS s
                JOIN pocomos_customers_notify_mobile_phones AS cnmp ON cnmp.phone_id = s.phone_id
                JOIN pocomos_customers_phones AS cp ON cp.phone_id = s.phone_id
                JOIN pocomos_phone_numbers as ph ON cp.phone_id = ph.id AND ph.active = 1
                JOIN pocomos_customer_sales_profiles AS csp ON csp.id = cp.profile_id
                JOIN pocomos_customers AS c ON c.id = csp.customer_id
                WHERE c.id = ' . $customerId . '
                GROUP BY s.phone_id
                ORDER By s.date_created ASC
                LIMIT 1'));
        return count($res) ? $res[0]->phone_id : null;
    }

    /**
     * Get customer profile & phone id base details
     */
    public function findActiveNotifyPhone($profile, $phoneId = null)
    {
        $phones = DB::table('pocomos_customers')
            ->join('pocomos_customer_sales_profiles', 'pocomos_customer_sales_profiles.customer_id', '=', 'pocomos_customers.id')
            ->join('pocomos_customers_notify_mobile_phones', 'pocomos_customers_notify_mobile_phones.profile_id', '=', 'pocomos_customer_sales_profiles.id')
            ->join('pocomos_phone_numbers', 'pocomos_phone_numbers.id', '=', 'pocomos_customers_notify_mobile_phones.phone_id')
            ->selectRaw('pocomos_phone_numbers.*')
            ->where('pocomos_customer_sales_profiles.id', $profile->id)
            ->where('pocomos_phone_numbers.active', true)
            ->get()->toArray();

        if ($phoneId) {
            // dd($phones);
            // dd($phoneId);
            foreach ($phones as $value) {
                if ($value->id == $phoneId) {
                    return $value;
                }
            }
        }
        return count($phones) ? $phones[0] : array();
    }

    /**
     * Get phone messages
     */
    public function getPhoneMessages($officeId, $phoneId, $limit = false, $start = false)
    {
        // dd($officeId);
        $sql = 'SELECT sms.*, CONCAT(u.first_name, \' \', u.last_name) as office_user
                FROM `pocomos_sms_usage` AS sms
                LEFT JOIN pocomos_company_office_users AS ou ON ou.id = sms.office_user_id
                LEFT JOIN orkestra_users AS u ON ou.user_id = u.id
                    WHERE sms.office_id = ' . $officeId . '
                    AND (sms.phone_id = ' . $phoneId . ' OR sms.sender_phone_id = ' . $phoneId . ')
                    ORDER BY sms.id ASC, sms.date_created DESC';

        if ($limit) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $start;
        }
        $res = DB::select(DB::raw($sql));
        return $res;
    }

    /**
     * Get unanswered messages sent by customer (Inbound)
     */
    public function getUnansweredMessages($officeId, $seen = false)
    {
        $seenCondition = '';
        if (!$seen) {
            $seenCondition = ' AND seen = 0';
        }
        $sql = 'SELECT sms.*, c.id AS customer_id, CONCAT(c.first_name, \' \', c.last_name) as customer_name
                FROM pocomos_sms_usage AS sms
                JOIN (
                      SELECT max(id) as id
                      FROM pocomos_sms_usage
                      WHERE office_id = ' . $officeId . ' AND answered = 0 ' . $seenCondition . ' GROUP BY sender_phone_id
                  ) as sms2 ON sms.id = sms2.id
                JOIN pocomos_customers_phones AS cp ON sms.sender_phone_id = cp.phone_id
                JOIN pocomos_customer_sales_profiles AS csp ON cp.profile_id = csp.id
                JOIN pocomos_customers AS c ON csp.customer_id = c.id
                WHERE sms.inbound = 1
                ORDER BY sms.date_created DESC';
        return DB::select(DB::raw($sql));
    }

    public function getActiveNotifyMobilePhones($profile)
    {
        $phones = DB::table('pocomos_customers')
            ->join('pocomos_customer_sales_profiles', 'pocomos_customer_sales_profiles.customer_id', '=', 'pocomos_customers.id')
            ->join('pocomos_customers_notify_mobile_phones', 'pocomos_customers_notify_mobile_phones.profile_id', '=', 'pocomos_customer_sales_profiles.id')
            ->join('pocomos_phone_numbers', 'pocomos_phone_numbers.id', '=', 'pocomos_customers_notify_mobile_phones.phone_id')
            ->selectRaw('pocomos_phone_numbers.*')
            ->where('pocomos_customer_sales_profiles.id', $profile->id)
            ->where('pocomos_phone_numbers.active', true)
            ->get()->toArray();

        return $phones;
    }

    /**
     * Get unanswered messages sent by customer (Inbound)
     * @return array
     */
    public function getInboundMessages($officeId, $seen = false, $search = null, $sort, $sortType)
    {
        $seenCondition = '';
        $searchCondition = '';
        $sortCondition = '';
        if (!$seen) {
            // dd(11);
            $seenCondition = ' AND seen = 0';
        }
        if ($search) {
            $searchCondition = ' AND (c.first_name LIKE "%' . $search . '%" OR c.last_name LIKE "%' . $search . '%" OR sms.date_created LIKE "%' . date('Y-m-d', strtotime($search)) . '%")';
        }

        if ($sort && $sortType) {
            $sortCondition .= ' ORDER BY  ';

            if ($sort == 'date') {
                $sortCondition .= " sms.date_created ";
                if ($sortType == 'desc') {
                    $sortCondition .= " DESC ";
                } else {
                    $sortCondition .= " ASC ";
                }
            } else {
                $sortCondition .= " c.first_name ";
                if ($sortType == 'desc') {
                    $sortCondition .= " DESC ";
                } else {
                    $sortCondition .= " ASC ";
                }
                $sortCondition .= ", c.last_name ";
                if ($sortType == 'desc') {
                    $sortCondition .= " DESC ";
                } else {
                    $sortCondition .= " ASC ";
                }
            }
        }
        $sql = 'SELECT sms.*, c.id AS customer_id, unread_sms,
                CONCAT(c.first_name, \' \', c.last_name) as customer_name, c.first_name, c.last_name
                FROM pocomos_sms_usage AS sms
                JOIN (
                      SELECT max(id) as id, COUNT(IF(seen = 0, 1, NULL)) unread_sms
                      FROM pocomos_sms_usage
                      WHERE office_id = ' . $officeId . ' ' . $seenCondition . ' GROUP BY sender_phone_id
                  ) as sms2 ON sms.id = sms2.id
                JOIN pocomos_customers_phones AS cp ON sms.sender_phone_id = cp.phone_id
                JOIN pocomos_customer_sales_profiles AS csp ON cp.profile_id = csp.id
                JOIN pocomos_customers AS c ON csp.customer_id = c.id
                WHERE sms.inbound = 1 ' . $searchCondition . '
                ' . $sortCondition;
        return DB::select(DB::raw($sql));
    }

    /**
     * Get unanswered messages sent by customer (Inbound)
     * @return array
     */
    public function getEmployeeMessages($officeId, $seen = false, $search = null, $sort, $sortType)
    {
        $seenCondition = '';
        $searchCondition = '';
        $sortCondition = '';
        if (!$seen) {
            $seenCondition = ' AND seen = 0';
        }
        if ($search) {
            $searchCondition = ' WHERE (u.first_name LIKE "%' . $search . '%" OR u.last_name LIKE "%' . $search . '%" OR sms.date_created LIKE "%' . date('Y-m-d', strtotime($search)) . '%")';
        }

        // return $sortType;
        if ($sort && $sortType) {
            $sortCondition .= ' ORDER BY  ';

            if ($sort == 'date') {
                $sortCondition .= " sms.date_created ";
                if ($sortType == 'desc') {
                    $sortCondition .= " DESC ";
                } else {
                    $sortCondition .= " ASC ";
                }
            } else {
                $sortCondition .= " u.first_name ";
                if ($sortType == 'desc') {
                    $sortCondition .= " DESC ";
                } else {
                    $sortCondition .= " ASC ";
                }
                $sortCondition .= ", u.last_name ";
                if ($sortType == 'desc') {
                    $sortCondition .= " DESC ";
                } else {
                    $sortCondition .= " ASC ";
                }
            }
        }

        // return $sortCondition;


        $sql = 'SELECT sms.*, u.id AS user_id,  ou.id AS office_user_id, u.username as username, unread_sms,
                CONCAT(u.first_name,  \' \', u.last_name) AS customer_name, u.first_name, u.last_name
                FROM pocomos_sms_usage AS sms
                JOIN (
                SELECT max(id) as id, COUNT(IF(seen = 0, 1, NULL)) unread_sms
                FROM pocomos_sms_usage
                WHERE office_id = ' . $officeId . ' ' . $seenCondition . '  GROUP BY phone_id
                ) as sms2 ON sms.id = sms2.id
                JOIN pocomos_phone_numbers AS ph ON sms.phone_id = ph.id
                JOIN pocomos_company_office_user_profiles AS ou ON ou.phone_id = ph.id
                JOIN orkestra_users AS u ON ou.user_id = u.id
                ' . $searchCondition . '
                ' . $sortCondition;
        return DB::select(DB::raw($sql));
    }

    public function isDependentChild($childId)
    {
        $sql = 'SELECT sc.*
                FROM pocomos_sub_customers AS sc
                JOIN pocomos_customers AS pc ON sc.parent_id = pc.id
                JOIN pocomos_customers AS cc ON sc.child_id = cc.id
                JOIN pocomos_customer_sales_profiles AS ccsp ON cc.id = ccsp.id
                JOIN pocomos_contracts AS csc ON ccsp.id = csc.profile_id
                JOIN pocomos_pest_contracts AS cpcc ON csc.id = cpcc.contract_id
                JOIN pocomos_pest_contracts AS ppcc ON cpcc.contract_id = ppcc.id
                JOIN pocomos_contracts AS psc ON ppcc.contract_id = psc.id
                JOIN pocomos_customer_sales_profiles AS pcsp ON psc.profile_id = pcsp.id
                WHERE cc.id = ' . $childId . ' AND pcsp.customer_id = pc.id
                ';

        $result = DB::select(DB::raw($sql));
        return array_shift($result);
    }

    /**Convert lead details result to csv formate */
    public function convert_csv_formate_lead_data($heading, $data, $columns)
    {
        $res = array();
        $res[] = $heading;

        foreach ($data as $value) {
            $row = array();

            $row[] = $value->lead_id ?? '';
            $row[] = $value->lead_name ?? '';
            $row[] = $value->phone ?? '';
            $row[] = $value->email ?? '';
            $row[] = $value->contact_address ?? '';
            $row[] = $value->postal_code ?? '';
            $row[] = $value->status ?? '';
            $row[] = $value->date_created ?? '';
            $row[] = $value->first_name ?? '';
            $row[] = $value->last_name ?? '';
            $row[] = $value->office_name ?? '';
            $row[] = $value->company_name ?? '';
            $row[] = $value->street ?? '';
            $row[] = $value->city ?? '';
            $row[] = $value->region_name ?? '';
            $row[] = $value->lead_status ?? '';
            $row[] = $value->agreement_name ? 'Yes' : 'No';
            $row[] = $value->service_type ?? '';
            $row[] = $value->service_frequencies ?? '';
            $row[] = $value->salesperson ?? '';
            $row[] = $value->map_code ?? '';
            $row[] = $value->autopay ?? '';
            $row[] = $value->initial_price ?? '';
            $row[] = $value->regular_initial_price ?? '';
            $row[] = $value->technician_name ?? '';
            $row[] = $value->pests ?? '';
            $row[] = $value->special_pests ?? '';
            $row[] = $value->tags ?? '';

            $res[] = $row;
        }
        return $res;
    }

    /**Get service schedule details */
    public function getRemoteEmailServiceSchedule($data)
    {
        $service_schedule = '<div id="service-schedule"><ul class="table-list clearfix">';
        $schedule = array();
        $contract_type_id = $data['contract_id'];

        $pest_contract = PocomosPestContract::whereId($contract_type_id)->firstOrFail();

        $pest_agreement = PocomosPestAgreement::whereId($pest_contract->agreement_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }

        $current = date('Y-m-d', strtotime($data['initial_date'] ?? date('Y-m-d')));
        $current_month = date('F', strtotime($current));

        $agreement_length = $agreement['length'];
        $end = date('Y-m-d', strtotime("+$agreement_length month", strtotime($current)));

        if ($exceptions && in_array($current_month, $exceptions)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $c = 1;
        $exception_css = '';

        while ($current <= $end) {
            $current_month = date('F', strtotime($current));

            if ($exceptions && in_array($current_month, $exceptions)) {
                $exception_css = 'box-disabled';
            } else {
                $exception_css = '';
            }

            if ($c == 1) {
                $text = 'X';
            } else {
                $text = '';
            }

            $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
            <span class="list-value">' . $text . '</span></li>';
            $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
            $c = $c + 1;
        }
        $service_schedule .= '</ul></div>';
        return $service_schedule;
    }

    /**Get billing schedule details */
    public function getRemoteEmailBillingSchedule($data)
    {
        $billing_schedule = '<div id="billing-schedule"><ul class="table-list clearfix">';
        $schedule = array();

        $contract_type_id = $data['contract_id'];

        $pest_contract = PocomosPestContract::whereId($contract_type_id)->firstOrFail();

        $contract = PocomosContract::whereId($pest_contract->contract_id)->firstOrFail();

        $pest_agreement = PocomosPestAgreement::whereId($pest_contract->agreement_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }

        $initial_price = $pest_contract['initial_price'] ?? 0;
        $current = date('Y-m-d', strtotime($contract['date_start'] ?? date('Y-m-d')));
        $initial_date = date('Y-m-d', strtotime($data['initial_date'] ?? date('Y-m-d')));

        $agreement_length = $agreement['length'];
        $end = date('Y-m-d', strtotime("+$agreement_length month", strtotime($current)));

        $c = 1;
        $initial_month = date('m', strtotime($initial_date));
        $current_month = date('m', strtotime($current));

        if (!$pest_agreement['allow_dates_in_the_past'] && ($initial_month < $current_month)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        if ($exceptions && in_array($current_month, $exceptions)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        while ($current <= $end) {
            $current_month = date('m', strtotime($current));

            $current_month_str = date('F', strtotime($current));

            if ($exceptions && in_array($current_month_str, $exceptions)) {
                $exception_css = 'box-disabled';
            } else {
                $exception_css = '';
            }

            if ($initial_month == $current_month) {
                $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
            } else {
                $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0.00);
            }

            $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
            <span class="list-value">' . $initial_amount . '</span></li>';
            $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
            $c = $c + 1;
        }
        $billing_schedule .= '</ul></div>';
        return $billing_schedule;
    }

    public function getRoleBaseDefaultValues($role)
    {
        $res = array();
        if ($role == 'ROLE_ADMIN') {
            $res[] = 'ROLE_ROUTE_READ';
            $res[] = 'ROLE_FULL_AGREEMENT';
            $res[] = 'ROLE_FULL_SERVICE_TYPE';
            $res[] = 'ROLE_FULL_PEST_PRODUCT';
            $res[] = 'ROLE_JOB_CANCEL';
            $res[] = 'ROLE_JOB_RESCHEDULE';
            $res[] = 'ROLE_VTP_ADMIN';
            $res[] = 'ROLE_GEO_CODE';
            $res[] = 'ROLE_EDIT_CUSTOMER_ID';
            $res[] = 'ROLE_RECRUIT_LINKER';
            $res[] = 'ROLE_RECRUIT_DELETE';
            $res[] = 'ROLE_ACCOUNT_NOTES';
            $res[] = 'ROLE_CHANGE_SERVICE_PRICE';
            $res[] = 'ROLE_HIDE_SETTING';
            $res[] = 'ROLE_HIDE_REPORTS';
            $res[] = 'ROLE_HIDE_FINANCIAL';
            $res[] = 'ROLE_HIDE_SALES_TRACKER';
            $res[] = 'ROLE_HIDE_VTP';
            $res[] = 'ROLE_RECEIVE_INBOUND_SMS';
            $res[] = 'ROLE_RECEIVE_CUSTOM_CONTRACT_ENDING_NOTIFICATION';
            $res[] = 'ROLE_USER_VIEWADD';
            $res[] = 'ROLE_FULL_ACCOUNT_NUMBER';
        } elseif ($role == 'ROLE_OWNER') {
            $res[] = 'ROLE_ROUTE_READ';
            $res[] = 'ROLE_JOB_CANCEL';
            $res[] = 'ROLE_JOB_RESCHEDULE';
            $res[] = 'ROLE_VTP_ADMIN';
            $res[] = 'ROLE_HIDE_SETTING';
        } elseif ($role == 'ROLE_BRANCH_MANAGER') {
            $res[] = 'ROLE_JOB_CANCEL';
            $res[] = 'ROLE_JOB_RESCHEDULE';
            $res[] = 'ROLE_VTP_ADMIN';
            $res[] = 'ROLE_GEO_CODE';
            $res[] = 'ROLE_HIDE_SETTING';
        } elseif ($role == 'ROLE_SECRETARY') {
            $res[] = 'ROLE_ROUTE_READ';
            $res[] = 'ROLE_JOB_CANCEL';
            $res[] = 'ROLE_JOB_RESCHEDULE';
            $res[] = 'ROLE_VTP_ADMIN';
            $res[] = 'ROLE_HIDE_SETTING';
        } elseif ($role == 'ROLE_SALES_MANAGER') {
        } elseif ($role == 'ROLE_SALES_ADMIN') {
            $res[] = 'ROLE_JOB_RESCHEDULE';
            $res[] = 'ROLE_VTP_ADMIN';
        } elseif ($role == 'ROLE_ROUTE_MANAGER') {
            $res[] = 'ROLE_JOB_CANCEL';
        } elseif ($role == 'ROLE_COLLECTIONS') {
        }

        return $res;
    }

    /**
     */
    public function getInitialServiceWindow($office, $pestControlContract = null)
    {
        if ($pestControlContract === null) {
            return "";
        }
        $serviceWindow = null;
        $job = $pestControlContract->getInitialJob();
        $slot = $job ? $job->slot : null;

        if ($slot && !$slot->anytime) {
            $pestConfig = PocomosPestOfficeSetting::whereOfficeId($office->id)->first();
            if ($pestConfig->only_show_date) {
                $serviceWindow = date('m-d-Y', strtotime($job->date_scheduled));
            } else {
                if ($pestConfig->include_time_window) {
                    $startTime = new DateTime($slot->time_begin);
                    $endTime = new DateTime($slot->time_begin);
                    $endTime->modify('+' . $pestConfig->time_window_length . ' hours');

                    $serviceWindow = sprintf('between %s and %s', $startTime->format('h:i A'), $endTime->format('h:i A'));
                } else {
                    $serviceWindow = $slot->getBeginTime()->format('h:i A');
                }
            }
        }

        return $serviceWindow;
    }

    /**
     */
    public function getServiceWindow($office, $job = null)
    {
        $serviceWindow = null;
        $slot = $job ? $job->slot : null;

        if ($slot && !$slot->anytime) {
            $pestConfig = PocomosPestOfficeSetting::whereOfficeId($office->id)->first();
            if ($pestConfig->only_show_date) {
                $serviceWindow = date('m-d-Y', strtotime($job->date_scheduled));
            } else {
                if ($pestConfig->include_time_window) {
                    $startTime = new DateTime($slot->time_begin);
                    $endTime = new DateTime($slot->time_begin);
                    $endTime->modify('+' . $pestConfig->time_window_length . ' hours');

                    $serviceWindow = sprintf('between %s and %s', $startTime->format('h:i A'), $endTime->format('h:i A'));
                } else {
                    $serviceWindow = $slot->getBeginTime()->format('h:i A');
                }
            }
        }

        return $serviceWindow;
    }

    /**
     */
    public function getServiceCalender($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $schedule = $this->getPricingSchedule($pcc);
        // dd($pcc);
        $exceptions = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        if (is_array($exceptions)) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        $schedWithSchortenedYear = [];
        $jobs = $pcc->jobs_details;
        $dates = [];
        $rendered = '';

        foreach ($jobs as $job) {
            $date = new DateTime($job->date_scheduled);
            $dates[] = $date->format('M Y');
        }

        if (isset($schedule->prices)) {
            foreach ($schedule->prices as $key => $value) {
                $newKey = new DateTime($key);
                if (in_array($key, $dates)) {
                    $schedWithSchortenedYear[$newKey->format('M \'y')]['isScheduled'] = true;
                } else {
                    $schedWithSchortenedYear[$newKey->format('M \'y')]['isScheduled'] = false;
                }
                $schedWithSchortenedYear[$newKey->format('M \'y')] = $value;
                $schedWithSchortenedYear[$newKey->format('M \'y')]['totalAmount'] = $schedule->prices[$key]['amount'] + $schedule->prices[$key]['sales_tax'];
            }

            $rendered = view('pdf.serviceSchedule', array('schedule' => $schedWithSchortenedYear, 'exceptions' => $exceptionsArray))->render();
        }
        return $rendered;
    }

    /**
     */
    public function getPricingSchedule($contract)
    {
        $defaultData = array(
            'amount' => 0,
            'sales_tax' => 0,
            'isScheduled' => 0,
            'initial_amount' => 0,
        );

        $result = (object)array();
        if ($contract->jobs_details) {
            foreach ($contract->jobs_details as $job) {
                $date = new DateTime($job->date_scheduled);
                $identifier = $date->format('M Y');
                if (!isset($result->prices[$identifier])) {
                    $result->prices[$identifier] = $defaultData;
                }
                $result->dates[] = new DateTime($job->date_scheduled);
                $result->prices[$identifier]['isScheduled'] = 1;
                if (isset($job->invoice_detail->status) && $job->invoice_detail->status != config('constants.CANCELLED')) {
                    $result->prices[$identifier]['amount'] = $job->invoice_detail->amount_due ?? null;
                    $result->prices[$identifier]['sales_tax'] = $job->invoice_detail->sales_tax ?? null;
                }
                if ($job->id ===  $contract->getInitialJob() ? $contract->getInitialJob()->id : null) {
                    $result->prices[$identifier]['initial_amount'] = $contract->initial_price ?? 0;
                }
            }
        } elseif ($contract->jobs_details) {
            $job = $contract->jobs_details;
            $date = new DateTime($job->date_scheduled);
            $identifier = $date->format('M Y');
            if (!isset($result->prices[$identifier])) {
                $result->prices[$identifier] = $defaultData;
            }
            $result->dates[] = new DateTime($job->date_scheduled);
            $result->prices[$identifier]['isScheduled'] = 1;
            if ($job->invoice_detail->status != config('constants.CANCELLED')) {
                $result->prices[$identifier]['amount'] = $job->invoice_detail->amount_due;
                $result->prices[$identifier]['sales_tax'] = $job->invoice_detail->sales_tax;
            }
            if ($job === $contract->getInitialJob()) {
                $result->prices[$identifier]['initial_amount'] = $contract->initial_price ?? 0;
            }
        }
        // return array();

        // BELOW CODE NEED TO IMPROVEMENTS
        foreach ($contract->misc_invoices as $invoice) {
            $date = $invoice->invoice->date_due;
            $result->dates[] = $date;
            $date = new DateTime($date);
            $identifier = $date->format('M Y');
            if (!isset($result->prices[$identifier])) {
                $result->prices[$identifier] = $defaultData;
            }

            $result->prices[$identifier]['amount'] = $invoice->amount_due;
            $result->prices[$identifier]['sales_tax'] = $invoice->sales_tax;
        }

        return $result;
    }

    public function getCustomerCardOnFileStatus($profile)
    {
        $cardOnFile = false;
        if (count($profile->getCardAccounts()->filter(function ($account) {
            if ($account->account_detail->active) {
                return $account;
            }
        })) > 0) {
            $cardOnFile = true;
        }
        return $cardOnFile;
    }

    public function getCustomerAchOnFileStatus($profile)
    {
        $achOnFile = false;
        if (count($profile->getBankAccounts()->filter(function ($account) {
            if ($account->account_detail->active) {
                return $account;
            }
        })) > 0) {
            $achOnFile = true;
        }
        return $achOnFile;
    }

    public function getCustomerAutopayAccStatus($profile)
    {
        $autopaytype = 'unknown';
        if ($profile) {
            if ($profile->autopay_account) {
                $type = $profile->autopay_account->type;
                if (preg_match('/bank/i', $type)) {
                    $autopaytype = 'ach';
                } elseif (preg_match('/card/i', $type)) {
                    $autopaytype = 'card';
                } else {
                    $autopaytype = 'unknown';
                }
            }
        }
        return $autopaytype;
    }

    public function processAcsJobEvent($jobEvent)
    {
        switch ($jobEvent->event_type) {
            case 'Job Scheduled':
            case 'Job Completed':
                $this->processAcsScheduledJobs($jobEvent);
                break;

            case 'New Customer':
                //$this->processAcsNewCustomer($jobEvent);
                break;

            case 'Invoice Due':
                $this->processAcsInvoiceDue($jobEvent);
                break;

            case 'Payment Failed':
                //$this->processAcsPaymentFailed($jobEvent);
                break;

            default:
                return $this->sendResponse(false, 'No Event type provided.');
        }
    }

    /**
     * Process ACS Scheduled jobs
     */

    public function processAcsScheduledJobs($event)
    {
        $officeConfig = PocomosOfficeSetting::whereOfficeId($event->office_id)->firstOrFail();

        $timeZone = PocomosTimezone::findOrFail($officeConfig->timezone_id);

        $jobs = $this->getJobsByJobEventAndTimeZone($event, $timeZone);

        foreach ($jobs as $jobData) {
            FacadesDB::beginTransaction();
            try {
                return $notification = $this->createACSJobNotification($jobData, $event, $timeZone);
                FacadesDB::commit();
            } catch (\Exception $e) {
                FacadesDB::rollback();
                throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
            }
        }
    }

    /**
     * Process ACS Invoice due
     */

    public function processAcsInvoiceDue($event)
    {
        $officeConfig = PocomosOfficeSetting::whereOfficeId($event->office_id)->firstOrFail();
        $plusMinus = $this->getEventSign($event);

        $timeZone = PocomosTimezone::findOrFail($officeConfig->timezone_id);

        $invoices = $this->getInvoicesDueByJobEventAndTimeZone($event, $plusMinus, $timeZone);

        foreach ($invoices as $invoiceData) {
            FacadesDB::beginTransaction();
            try {
                return $notification = $this->createACSJobNotification($invoiceData, $event, $timeZone);
                FacadesDB::commit();
            } catch (\Exception $e) {
                FacadesDB::rollback();
                throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
            }
        }
    }

    public function getEventSign($jobEvent)
    {
        $plusMinus = '-';

        if ($jobEvent->before_after == "Before") {
            $plusMinus = '+';
        }

        return $plusMinus;
    }

    /**
     * Get jobs by office and schedule date
     *
     */
    public function getJobsByJobEventAndTimeZone($jobEvent, $timeZone = null)
    {
        $sql = 'SELECT j.* , csp.customer_id, s.time_begin
                FROM pocomos_jobs AS j
                JOIN pocomos_pest_contracts AS pcc ON j.contract_id = pcc.id
                JOIN pocomos_contracts AS c ON pcc.contract_id = c.id
                JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id';

        if ($jobEvent->event_type == 'Job Completed') {
            $sql .= ' LEFT JOIN pocomos_route_slots AS s ON j.slot_id = s.id ';
        } else {
            $sql .= ' JOIN pocomos_route_slots AS s ON j.slot_id = s.id ';
        }

        $sql .= ' JOIN pocomos_pest_agreements AS pa ON pcc.agreement_id = pa.id';

        $getExceptions = PocomosAcsJobEventsException::where('acs_event_id', $jobEvent->id)->pluck('exception_id')->toArray();
        $getTags = PocomosAcsJobEventsTag::where('acs_event_id', $jobEvent->id)->pluck('tag_id')->toArray();

        if (count($getExceptions) > 0) {
            $sql .= ' JOIN pocomos_pest_contracts_tags AS ct ON pcc.id = ct.contract_id';
        } elseif (count($getTags) > 0) {
            $sql .= ' JOIN pocomos_pest_contracts_tags AS ct ON pcc.id = ct.contract_id';
        }

        if ($jobEvent->event_type == 'Job Completed') {
            $statuses = array('Complete');
        } else {
            $statuses = array('Pending', 'Re-scheduled');
        }

        $statuses = $this->convertArrayInStrings($statuses);

        $sql .= ' LEFT JOIN pocomos_acs_notifications an ON (an.job_id = j.id AND an.acs_event_id = "' . $jobEvent->id . '")
                WHERE csp.office_id = "' . $jobEvent->office_id . '" AND j.status IN (' . $statuses . ')
                AND an.id is NULL
                ';

        if ($jobEvent->autopay) {
            if ($jobEvent->customer_autopay) {
                $sql .= ' AND csp.autopay = 0 AND csp.autopay_account_id IS NULL';
            } else {
                $sql .= ' AND csp.autopay = 1 AND csp.autopay_account_id IS NOT NULL';
            }
        }

        $plusMinus = '+';
        if ($jobEvent->event_type == 'Job Completed') {
            $plusMinus = '-';
        }

        $timeAdjustment = $plusMinus . $jobEvent->amount_of_time . ' ' . $jobEvent->unit_of_time;
        $fromDate = new \DateTime();
        $fromDate->modify($timeAdjustment)->modify('-120 minutes');

        if ($timeZone) {
            $dateTimeZone = new \DateTimeZone($timeZone->php_name);
            $fromDate->setTimezone($dateTimeZone);
        }

        $toDate = clone $fromDate;
        $toDate->modify('+3 Hours');

        $fromDate = $fromDate->format('Y-m-d H:i:s');
        $toDate = $toDate->format('Y-m-d H:i:s');

        if ($jobEvent->event_type == 'Job Completed') {
            //$sql .= ' AND CONCAT(j.date_completed, " ", j.time_end) BETWEEN "' . $fromDate . '" AND "' . $toDate . '"';
        } else {
            //$sql .= ' AND CONCAT(j.date_scheduled, " ", s.time_begin) BETWEEN "' . $fromDate . '" AND "' . $toDate . '"';
        }

        if ($jobEvent->job_type) {
            $jobTypes = $this->convertArrayInStrings(unserialize($jobEvent->job_type));
            $sql .= ' AND j.type IN (' . $jobTypes . ')';
        }

        $eventServiceTypes = PocomosAcsJobEventsServiceType::where('acs_event_id', $jobEvent->id)->pluck('service_type_id')->toArray();
        if (count($eventServiceTypes)) {
            $serviceTypes = array();
            foreach ($eventServiceTypes as $eventServiceType) {
                $serviceTypes[] = $eventServiceType;
            }
            $serviceTypes = $this->convertArrayInStrings($serviceTypes);
            $sql .= ' AND pcc.service_type_id IN (' . $serviceTypes . ')';
        }

        $eventAgreements = PocomosAcsJobEventsAgreement::where('acs_event_id', $jobEvent->id)->pluck('agreement_id')->toArray();
        if (count($eventAgreements)) {
            $agreements = array();
            foreach ($eventAgreements as $eventAgreement) {
                $agreements[] = $eventAgreement;
            }
            $agreements = $this->convertArrayInStrings($agreements);
            $sql .= ' AND pa.agreement_id IN (' . $agreements . ')';
        }

        $eventTags = $getTags;

        $eventExceptions = $getExceptions;

        if (count($eventExceptions) > 0) {
            $exceptions = array();
            foreach ($eventExceptions as $eventException) {
                $exceptions[] = $eventException;
            }
            $exceptions = $this->convertArrayInStrings($exceptions);
            $sql .= ' AND ct.tag_id IN (' . $exceptions . ')';
        } elseif (count($eventTags) > 0) {
            $tags = array();
            foreach ($eventTags as $eventTag) {
                $tags[] = $eventTag;
            }
            $tags = $this->convertArrayInStrings($tags);
            $sql .= ' AND ct.tag_id IN (' . $tags . ')';
        }

        $sql .= ' GROUP BY j.id';

        return DB::select(DB::raw($sql));
    }

    /**
     * Get invoices due by office and schedule date
     *
     */
    public function getInvoicesDueByJobEventAndTimeZone($jobEvent, $plusMinus, $timeZone = null)
    {
        $sql = 'SELECT cu.id AS customer_id, IFNULL(ot.date_modified, ot.date_created) as transaction_date, ot.id as transaction_id, i.*
                FROM pocomos_invoices AS i
                LEFT JOIN pocomos_jobs AS j ON i.id = j.invoice_id
                JOIN pocomos_contracts AS c ON i.contract_id = c.id
                JOIN pocomos_pest_contracts AS pcc ON c.id = pcc.contract_id
                JOIN pocomos_customer_sales_profiles AS csp ON c.profile_id = csp.id
                JOIN pocomos_customers AS cu ON csp.customer_id = cu.id
                JOIN (SELECT MAX(transaction_id) transaction_id, invoice_id FROM pocomos_invoice_transactions group by invoice_id) t_max ON i.id = t_max.invoice_id
                JOIN orkestra_transactions ot ON t_max.transaction_id = ot.id';

        $eventAgreements = PocomosAcsJobEventsAgreement::where('acs_event_id', $jobEvent->id)->pluck('agreement_id')->toArray();

        if (count($eventAgreements) > 0) {
            $sql .= ' JOIN pocomos_pest_agreements AS pa ON pcc.agreement_id = pa.id';
        }

        $getExceptions = PocomosAcsJobEventsException::where('acs_event_id', $jobEvent->id)->pluck('exception_id')->toArray();
        $getTags = PocomosAcsJobEventsTag::where('acs_event_id', $jobEvent->id)->pluck('tag_id')->toArray();

        if (count($getExceptions) > 0) {
            $sql .= ' JOIN pocomos_pest_contracts_tags AS ct ON pcc.id = ct.contract_id';
        } elseif (count($getTags) > 0) {
            $sql .= ' JOIN pocomos_pest_contracts_tags AS ct ON pcc.id = ct.contract_id';
        }

        $sql .= ' LEFT JOIN pocomos_acs_notifications an ON (an.invoice_id = i.id AND an.acs_event_id =  "' . $jobEvent->id . '" )
                WHERE csp.office_id = "' . $jobEvent->office_id . '"  AND cu.active = 1 AND cu.status = "Active"
                AND c.active = 1 AND c.status = "Active"
                AND i.status NOT IN ("Cancelled","Paid") AND i.balance > 0
                AND (j.id IS NULL OR j.status = "Complete")';

        if ($jobEvent->autopay) {
            if ($jobEvent->customer_autopay) {
                $sql .= ' AND csp.autopay = 0 AND csp.autopay_account_id IS NULL';
            } else {
                $sql .= ' AND csp.autopay = 1 AND csp.autopay_account_id IS NOT NULL';
            }
        }

        $timeAdjustment = $plusMinus . $jobEvent->amount_of_time . ' ' . $jobEvent->unit_of_time;
        $notificationDate = new \DateTime();
        $notificationDate->modify($timeAdjustment);

        if ($timeZone) {
            $dateTimeZone = new \DateTimeZone($timeZone->php_name);
            $notificationDate->setTimezone($dateTimeZone);
        }

        $notificationDate = $notificationDate->format('Y-m-d');

        $sql .= ' AND an.id is NULL AND i.date_due = "' . $notificationDate . '"';

        $eventServiceTypes = PocomosAcsJobEventsServiceType::where('acs_event_id', $jobEvent->id)->pluck('service_type_id')->toArray();
        if (count($eventServiceTypes)) {
            $serviceTypes = array();
            foreach ($eventServiceTypes as $eventServiceType) {
                $serviceTypes[] = $eventServiceType;
            }
            $serviceTypes = $this->convertArrayInStrings($serviceTypes);
            $sql .= ' AND pcc.service_type_id IN (' . $serviceTypes . ')';
        }

        if (count($eventAgreements)) {
            $agreements = array();
            foreach ($eventAgreements as $eventAgreement) {
                $agreements[] = $eventAgreement;
            }
            $agreements = $this->convertArrayInStrings($agreements);
            $sql .= ' AND pa.agreement_id IN (' . $agreements . ')';
        }

        if ($jobEvent->job_type) {
            $jobTypes = $this->convertArrayInStrings(unserialize($jobEvent->job_type));
            $sql .= ' AND j.type IN (' . $jobTypes . ')';
        }

        $eventTags = $getTags;
        $eventExceptions = $getExceptions;

        if (count($eventExceptions) > 0) {
            $exceptions = array();
            foreach ($eventExceptions as $eventException) {
                $exceptions[] = $eventException;
            }
            $exceptions = $this->convertArrayInStrings($exceptions);
            $sql .= ' AND ct.tag_id IN (' . $exceptions . ')';
        }
        if (count($eventTags) > 0) {
            $tags = array();
            foreach ($eventTags as $eventTag) {
                $tags[] = $eventTag;
            }
            $tags = $this->convertArrayInStrings($tags);
            $sql .= ' AND ct.tag_id IN (' . $tags . ')';
        }

        $sql .= ' GROUP BY i.id';

        return DB::select(DB::raw($sql));
    }

    public function getInvoicesReportHelper($pestContract, $onlyCompletedJobs = false)
    {
        $jobs = $pestContract->jobs_details->toArray();

        // dd($jobs);

        // if ($onlyCompletedJobs) {
        //     // dd(11);
        //     $jobs = array_filter($jobs, function ($job) {
        //         return $job['status'] == 'Complete';
        //     });
        // }

        $invIds = array_merge(array_map(function ($job) {
            return $job['invoice_id'];
        }, $jobs), $pestContract->misc_invoices->pluck('invoice_id')->toArray());

        return $invoices = PocomosInvoice::whereIn('id', $invIds)->get();
    }

    public function createACSJobNotification($eventData, $event, $timeZone, $customerCheck = null, $jobCheck = null)
    {
        $job = null;
        $invoice = null;
        $agreement = null;

        if ($eventData instanceof PestControlAgreement) {
            $customer = $customerCheck;
        } elseif ($eventData instanceof Invoice) {
            $customer = $eventData->getContract()->getProfile()->getCustomer();
        } else {
            $customer = PocomosCustomer::findOrFail($eventData->customer_id);
        }

        $notificationType = 'Job Event';
        $systemTimeZone = new \DateTimeZone(date_default_timezone_get());
        $customerTimeZone = new \DateTimeZone($timeZone->php_name);

        //        This should handle creation of notifications ONLY for job events. Of which there are 2 types.
        //        The else is to avoid someone screwign things up badly. Also the assumption is Completion being after. And Scheduled being before
        switch ($event->event_type) {
            case 'Job Completed':
                $notificationTime = new \DateTime($eventData->date_completed . ' ' . $eventData->time_end, $customerTimeZone);
                $duration = '+';
                $job = PocomosJob::findOrFail($eventData->id);
                break;
            case 'New Customer':
                $notificationTime = new \DateTime($customer->date_created, $customerTimeZone);
                $duration = '+';
                $notificationType = 'New Customer';
                $agreement = $eventData;
                $job = $jobCheck;
                break;
            case 'Job Scheduled':
                $duration = '-';
                $notificationTime = new \DateTime($eventData->date_scheduled . ' ' . $eventData->time_begin, $customerTimeZone);
                $job = PocomosJob::findOrFail($eventData->id);
                break;
            case 'Invoice Due':
                $duration = '+';
                $notificationTime = new \DateTime($eventData->date_due . '12:00:00', $customerTimeZone);
                $notificationType = 'Invoice Due';
                $invoice = PocomosInvoice::findOrFail($eventData->id);
                break;
            case 'Payment Failed':
                if ($eventData instanceof Invoice) {
                    $duration = '+';
                    $notificationTime = new \DateTime($eventData->transaction_date . '12:00:00', $customerTimeZone);
                    $notificationType = 'Payment Failed';
                    $invoice = PocomosInvoice::findOrFail($eventData->id);
                }
                break;
        }
        $duration .= $event->amount_of_time . ' ' . $event->unit_of_time;

        // Add our duration to the customer time ( in their timezone ), then convert to our system time so we send it on schedule
        if ($notificationTime instanceof \DateTime) {
            $notificationTime->modify($duration)->setTimezone($systemTimeZone);
        } else {
            $notificationTime = new \DateTime('now', $systemTimeZone);
            $notificationTime->modify($duration)->setTimezone($systemTimeZone);
        }

        FacadesDB::beginTransaction();
        try {
            $notification = $this->createAcsNotification($customer, $event, $notificationTime, $notificationType, $job, $invoice, $agreement);
            FacadesDB::commit();
        } catch (\Exception $e) {
            FacadesDB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }

        return $notification;
    }

    public function createAcsNotification($customer, $event, $notificationTime, $notificationType, $job = null, $invoice = null, $agreement = null)
    {
        $notification = new PocomosAcsNotification();

        $notification->acs_event_id = $event->id;
        $notification->office_id =  $event->office_id;
        $notification->event_type = $notificationType;
        $notification->customer_id =  $customer->id ?? null;
        $notification->form_letter_id =  $event->form_letter_id;
        $notification->sms_form_letter_id =  $event->sms_form_letter_id;
        $notification->voice_form_letter_id =  $event->voice_form_letter_id;
        $notification->job_id =  $job->id ?? null;
        $notification->invoice_id =  $invoice->id ?? null;
        $notification->notification_time =  $notificationTime;
        $notification->pest_control_agreement_id = $agreement;
        $notification->save();

        return $notification;
    }

    /**Adds an existing InvoiceItem to this Invoice.*/

    public function addInvoiceItems($invoice, $invoiceItem, $typeOfDiscount = null)
    {
        $itemType = $invoiceItem['itemType'];

        $taxCode = PocomosTaxCode::findOrFail($invoice->tax_code_id);

        if ($itemType == 'Discount' || $itemType == 'Credit') {
            $item['description'] = $invoiceItem['description'];

            $price = -abs($invoiceItem['price']);

            if ($typeOfDiscount === 'percent') {
                $price = round($invoice->amount_due, 2) * round($price / 100, 2);
            }

            //            If the discount is larger than the amount due. Then we set the Discount to the amount due
            //            If it's credit. Then we add the excess credit to the account. Ezpz Cheezy Smeezy.
            //            !!!!!!! IN A NEW PATCH. I removed Credit Invoice Items Altogether. Cos screw that noice, making stuff complicated for no good reason.

            $diff = abs($price) - $invoice->amount_due;
            if ($diff > 0) {
                $item['price'] = - ($invoice->amount_due);
                if ($itemType == 'Credit') {
                    $item['description'] =  $invoiceItem['description'] . ' Credit of $' . number_format(abs($diff), 2) . ' Added to Account Credit.';
                }
            } else {
                $item['price'] = $price;
            }

            $item['invoice_id'] = $invoice->id;
            $item['active'] = true;
            $item['tax_code_id'] = $invoice->tax_code_id;
            $item['sales_tax'] = $taxCode->tax_rate;
            $item['type'] = $itemType;
            $item['value_type'] = $typeOfDiscount;

            $item = PocomosInvoiceItems::create($item);

            $this->addInvoiceItemSwitch($item, $invoice);

            return $item;
        }

        $item['invoice_id'] = $invoice->id;
        $item['description'] = $invoiceItem['description'];
        $item['price'] = $invoiceItem['price'];
        $item['active'] = true;
        $item['tax_code_id'] = $invoice->tax_code_id;
        $item['sales_tax'] = $taxCode->tax_rate;
        $item['type'] = $itemType;
        $item['value_type'] = $typeOfDiscount;

        $item = PocomosInvoiceItems::create($item);

        $this->addInvoiceItemSwitch($item, $invoice);

        return $item;
    }

    /**
     * getContractValuePreCreationWithTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreationWithTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $getContractValue = (string) $this->getContractValuePreCreation($pcc);
        $getTaxRate       = (string) $this->getTaxRate($pcc);
        $contractValue    = str_replace(',', '', $getContractValue);
        $taxRate          = str_replace(',', '', $getTaxRate);

        return $this->moneyFormat($contractValue + $contractValue * $taxRate);
    }

    /**
     * getContractValuePreCreation
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreation($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $jobs = $pcc->jobs_details;
        $contractValue = 0;
        foreach ($jobs as $job) {
            $invoice = $job->invoice_detail;
            if (isset($invoice) && $invoice->amount_due) {
                $contractValue += round($invoice->amount_due, 2);
            }
        }
        $miscInvoices = $pcc->misc_invoices;
        if ($miscInvoices) {
            foreach ($miscInvoices as $miscInvoice) {
                $contractValue += round($miscInvoice->amount_due, 2);
            }
        }
        return $this->moneyFormat($contractValue);
    }

    /**
     * getTaxRate
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getTaxRate($pcc = null)
    {
        $taxRate = null;

        if ($pcc) {
            $taxRate = $pcc->contract_details->sales_tax;
        }

        return floatval($taxRate);
    }

    /**
     * getContractValuePreCreationFirstYear
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreationFirstYear($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $jobs          = $pcc->jobs_details;
        $contractValue = 0;
        $yearAway      = new \DateTime();

        $yearAway->modify('+1 year');
        $yearAway->modify('-1 day');

        foreach ($jobs as $job) {
            if ($job->date_scheduled > $yearAway) {
                break;
            }
            $invoice = $job->invoice_detail;
            if ($invoice) {
                $contractValue += round($invoice->amount_due, 2);
            }
        }
        return $this->moneyFormat($contractValue);
    }

    /**
     * getContractValuePreCreationAfterFirstYear
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreationAfterFirstYear($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $price         = 0;
        $recuringPrice = $pcc->recurring_price;
        $taxRate       = $this->getTaxRate($pcc);
        $jobs          = $pcc->jobs_details;
        $yearAway      = new \DateTime();

        $yearAway->modify('+1 year');
        $yearAway->modify('-1 day');

        foreach ($jobs as $job) {
            if ($job->date_scheduled > $yearAway) {
                break;
            }
            $price = $price + $recuringPrice;
        }
        return $this->moneyFormat($price + ($price * $taxRate));
    }

    /**
     * getContractValuePreCreationSecondYear
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreationSecondYear($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $price          = 0;
        $recuringPrice  = $pcc->recurring_price;
        $taxRate        = $this->getTaxRate($pcc);
        $jobs           = $pcc->jobs_details;
        $yearAway       = new \DateTime();

        $yearAway->modify('+1 year');
        $yearAway->modify('-1 day');

        $secondYear = clone $yearAway;

        $secondYear->modify('+1 year');
        $secondYear->modify('-1 day');

        foreach ($jobs as $job) {
            if ($job->date_scheduled < $yearAway) {
                continue;
            } elseif ($job->date_scheduled > $secondYear) {
                break;
            }
            $price = $price + $recuringPrice;
        }
        return $this->moneyFormat($price + ($price * $taxRate));
    }

    /**
     * @param PestControlContract|null $pcc
     * @return string
     */
    public function getBillingCalendar($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $schedule        = $this->getBillingScheduleNew($pcc);
        $exceptions      = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        $rendered        = '';

        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        if (isset($schedule->prices)) {
            $rendered = view('pdf.billingSchedule', array('schedule' => $schedule, 'exceptions' => $exceptionsArray))->render();
        }
        return $rendered;
    }

    public function getPendingNotification()
    {
        $date = new \DateTime();

        $PocomosAcsNotification = DB::table('pocomos_acs_notifications as pan')
            ->select('*', 'pan.id as acs_notification_id')
            ->join('pocomos_acs_events as pae', 'pan.acs_event_id', 'pae.id')
            ->leftJoin('pocomos_form_letters as pfl', 'pae.form_letter_id', 'pfl.id')
            ->leftJoin('pocomos_voice_form_letters as pfv', 'pae.voice_form_letter_id', 'pfv.id')
            ->leftJoin('pocomos_sms_form_letters as psfl', 'pae.sms_form_letter_id', 'psfl.id')
            ->where('pfl.active', 1)
            ->where('pfv.active', 1)
            ->where('psfl.active', 1)
            ->where('pan.active', 1)
            ->where('pae.active', 1)
            ->where('pae.enabled', 1)
            ->where('pan.sent', 0)
            ->where('pan.notification_time', '<=', $date)
            ->select('pan.*')
            ->get();

        return $PocomosAcsNotification;
    }

    public function sendNotification($notification)
    {
        $notification = PocomosAcsNotification::findOrFail($notification->id);

        $jobEvent = $notification->ace_event;
        $formLetter = $jobEvent->form_letter_id;
        $smsFormLetter = $jobEvent->sms_form_letter_id;
        $voiceFormLetter = $jobEvent->voice_form_letter_id;
        $customer = $notification->customers;
        $job = $notification->jobs;
        $agreement = $notification->pest_agreement_detail;
        $contracts = $customer->sales_profile->contract_details;

        $officeId = auth()->user()->pocomos_company_office_user->office_id;
        $office = PocomosCompanyOffice::findOrFail($officeId);
        $officeUser = PocomosCompanyOfficeUser::whereOfficeId($officeId)->whereUserId(auth()->user()->id)->first();

        if ($notification->event_type === 'New Customer' && $notification->job_id === null) {
            foreach ($contracts as $contract) {
                if ($contract->agreement_details->id === $agreement->agreement_detail->id && ($contract->active == 1)) {
                    $pest_contract = $contract->pest_contract_details;
                    $job = $this->getFirstJobNew($pest_contract->id);
                    $job = PocomosJob::findOrFail($job['id']);
                }
            }
        }
        FacadesDB::beginTransaction();
        // try {
        if ($formLetter) {
            if ($job !== null) {
                $agreement_body = $this->sendFormLetter($formLetter, $customer, $job);

                $from = $this->getOfficeEmail($notification->office_id);
                $profile = $customer->sales_profile;

                $formLetter = PocomosFormLetter::findOrFail($formLetter);

                Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customer, $from) {
                    $message->from($from);
                    $message->to($customer->email);
                    $message->subject($formLetter['subject']);
                });

                $email_input['office_id'] = $notification->office_id;
                $email_input['office_user_id'] = $officeUser->id;
                $email_input['customer_sales_profile_id'] = $profile->id;
                $email_input['type'] = $formLetter['title'];
                $email_input['body'] = $agreement_body;
                $email_input['subject'] = $formLetter['subject'];
                $email_input['reply_to'] = $from;
                $email_input['reply_to_name'] = $office->name ?? '';
                $email_input['sender'] = $from;
                $email_input['sender_name'] = $office->name ?? '';
                $email_input['active'] = true;
                $success =  PocomosEmail::create($email_input);

                $input['email_id'] = $success->id;
                $input['recipient'] = $customer->email;
                $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
                $input['date_status_changed'] = date('Y-m-d H:i:s');
                $input['status'] = 'Delivered';
                $input['external_id'] = '';
                $input['active'] = true;
                $input['office_user_id'] = $officeUser->id;
                PocomosEmailMessage::create($input);

                if ($success) {
                    $notification->sent = true;
                }
            }

            if ($job == null && $notification->event_type == 'Invoice Due') {
                $office = PocomosCompanyOffice::findOrFail($notification->office_id);
                $office_email = unserialize($office->email);

                if (isset($office_email[0])) {
                    $from = $office_email[0];
                } else {
                    throw new \Exception(__('strings.something_went_wrong'));
                }

                $customerEmail = $customer->email;
                if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                    $agreement_body = $this->sendFormLetter($formLetter, $customer);

                    $formLetter = PocomosFormLetter::findOrFail($formLetter);
                    $profile = $customer->sales_profile;
                    Mail::send('emails.dynamic_email_render', compact('agreement_body'), function ($message) use ($formLetter, $customerEmail, $from) {
                        $message->from($from);
                        $message->to($customerEmail);
                        $message->subject($formLetter['subject']);
                    });

                    $email_input['office_id'] = $office->id;
                    $email_input['office_user_id'] = $officeUser->id;
                    $email_input['customer_sales_profile_id'] = $profile->id;
                    $email_input['type'] = 'Welcome Email';
                    $email_input['body'] = $agreement_body;
                    $email_input['subject'] = $formLetter['subject'];
                    $email_input['reply_to'] = $from;
                    $email_input['reply_to_name'] = $office->name ?? '';
                    $email_input['sender'] = $from;
                    $email_input['sender_name'] = $office->name ?? '';
                    $email_input['active'] = true;
                    $success =  PocomosEmail::create($email_input);

                    $input['email_id'] = $success->id;
                    $input['recipient'] = $customer->email;
                    $input['recipient_name'] = $customer->first_name . ' ' . $customer->last_name;
                    $input['date_status_changed'] = date('Y-m-d H:i:s');
                    $input['status'] = 'Delivered';
                    $input['external_id'] = '';
                    $input['active'] = true;
                    $input['office_user_id'] = $officeUser->id;
                    PocomosEmailMessage::create($input);
                }

                if ($success) {
                    $notification->sent = true;
                }
            }

            // if ($notification->event_type == 'Payment Failed') {
            //     $formLetterResult = $this->sendFormLetter($formLetter, $customer);
            //     if ($formLetterResult->getEmails()) {
            //         $notification->sent = true;
            //     }
            // }
        }

        if ($smsFormLetter) {
            $smsFormLetter = PocomosSmsFormLetter::findOrFail($smsFormLetter);


            if ($job !== null) {
                $smsFormLetterResult =   $this->sendSmsFormLetter($smsFormLetter, $customer, $job->contract, $job);
                if ($smsFormLetterResult) {
                    $notification->sent = true;
                }
            }
            if ($job == null && ($notification->event_type == 'Invoice Due' || $notification->event_type == 'Payment Failed')) {
                $smsFormLetterResult =   $this->sendSmsFormLetter($smsFormLetter, $customer);

                if ($smsFormLetterResult) {
                    $notification->sent = true;
                }
            }
        }

        if ($notification->sent === false) {
            $notification->active = false;
        }
        $notification->save();

        FacadesDB::commit();
        // } catch (\Exception $e) {
        //     FacadesDB::rollback();
        //     throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        // }
    }

    public function createPestOfficeSetting($office_id)
    {
        $input_details['initial_duration'] = 0;
        $input_details['regular_duration'] = 0;
        $input_details['active'] = 1;
        $input_details['office_id'] = $office_id;
        $input_details['include_schedule'] = 1;
        $input_details['include_pricing'] = 1;
        $input_details['separated_by_type'] = 1;
        $input_details['enable_optimization'] = 1;
        $input_details['anytime_enabled'] = 1;
        $input_details['disable_recurring_jobs'] = 1;
        $input_details['require_map_code'] = 1;
        $input_details['only_show_date'] = 1;
        $input_details['coloring_scheme'] = 1;
        $input_details['route_map_coloring_scheme'] = 1;
        $input_details['enable_remote_completion'] = 1;
        $input_details['my_spots_duration'] = 1;
        $input_details['show_service_duration_option_agreement'] = 1;
        $input_details['validate_zipcode'] = 0;
        $input_details['notify_on_assign'] = 1;
        $input_details['include_time_window'] = 1;
        $input_details['time_window_length'] = 1;
        $input_details['notify_on_reschedule'] = 1;
        $input_details['send_welcome_email'] = 1;
        $input_details['assign_message'] = '';
        $input_details['reschedule_message'] = '';
        $input_details['notify_only_verified'] = 1;
        $input_details['welcome_letter'] = '';
        $input_details['include_begin_end_in_invoice'] = 1;
        $input_details['bill_message'] = '';
        $input_details['complete_message'] = '';
        return PocomosPestOfficeSetting::create($input_details);
    }

    /**
     * getBillingScheduleNew
     *
     * @return void
     */
    public function getBillingScheduleNew($contract)
    {
        $defaultData = array(
            'amount'    => 0,
            'sales_tax' => 0,
            'shaded'    => 0,
        );
        $result = (object)array();

        foreach ($contract->jobs_details as $job) {

            $date       = new DateTime($job->date_scheduled);
            $identifier = $date->format('M Y');

            if (!isset($result->prices[$identifier])) {
                $result->prices[$identifier] = $defaultData;
            }

            if (isset($job->invoice->status) && $job->invoice_detail->status != config('constants.CANCELLED')) {
                $result->prices[$identifier]['shaded'] = $job->date_scheduled ? true : false;
                $result->prices[$identifier]['amount'] += $job->invoice_detail->amount_due;
                $result->prices[$identifier]['sales_tax'] += $job->invoice_detail->sales_tax;
            }
        }
        foreach ($contract->misc_invoices as $invoice) {

            $date            = $invoice->invoice->date_due;
            $result->dates[] = $date;
            $date            = new DateTime($date);
            $identifier      = $date->format('M Y');

            if (!isset($result->prices[$identifier])) {
                $result->prices[$identifier] = $defaultData;
            }

            $result->prices[$identifier]['amount'] += $invoice->amount_due;
            $result->prices[$identifier]['sales_tax'] += $invoice->sales_tax;
        }
        return $result;
    }

    /**
     * getContractValuePreCreationFirstYearJustTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreationFirstYearJustTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $getContractValue = $this->getContractValuePreCreationFirstYear($pcc);
        $getTaxRate       = $this->getTaxRate($pcc);
        $contractValue    = str_replace(',', '', $getContractValue);
        $taxRate          = str_replace(',', '', $getTaxRate);

        return $this->moneyFormat($contractValue * $taxRate);
    }

    /**
     * getMonthlyContractValuePreCreationFirstYear
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getMonthlyContractValuePreCreationFirstYear($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $jobs          = $pcc->jobs_details;
        $miscInvoices  = $pcc->misc_invoices;
        $contractValue = 0;
        $yearAway      = new \DateTime();

        $yearAway->modify('+12 month');
        $yearAway->modify('-1 day');

        foreach ($jobs as $job) {
            if ($job->date_scheduled > $yearAway) {
                break;
            }
            $invoice = $job->invoice_detail;
            if ($invoice) {
                $contractValue += round($invoice->amount_due, 2);
            }
        }
        foreach ($miscInvoices as $miscInvoice) {
            if ($miscInvoice->invoice->date_due > $yearAway) {
                break;
            }
            $contractValue += round($miscInvoice->invoice->amount_due, 2);
        }

        return ($this->moneyFormat($contractValue));
    }

    /**
     * getContractValuePreCreationTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractValuePreCreationTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $getTaxRate       = (string) $this->getTaxRate($pcc);
        $getContractValue = (string) $this->getContractValuePreCreation($pcc);
        $contractValue    = str_replace(',', '', $getContractValue);
        $taxRate          = str_replace(',', '', $getTaxRate);

        return $this->moneyFormat($contractValue * $taxRate);
    }

    /**
     * getContractInitialJobDate
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractInitialJobDate($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $date = '';
        foreach ($pcc->jobs_details as $job) {
            if ($job->type == config('constants.INITIAL') && $job->active) {

                $date_scheduled = new DateTime($job->date_scheduled);
                $date           = $date_scheduled->format("m/d/Y");
                break;
            }
        }
        return $date;
    }

    /**
     * getContractInitialJobTime
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractInitialJobTime($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $time = '';
        foreach ($pcc->jobs_details as $job) {
            if ($job->type == config('constants.INITIAL') && $job->active && $job->slot_id) {

                $date      = new DateTime($job->route_detail->time_begin);
                $startTime = $date->format("g:i A");
                $endTime   = date('g:i A', strtotime($startTime . ' +' . $job->route_detail->duration . 'minutes'));
                $time      = $startTime . ' - ' . $endTime;
                break;
            }
        }
        return $time;
    }

    /**
     * getContractInitialJobNote
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractInitialJobNote($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $note = null;
        foreach ($pcc->jobs_details as $job) {
            if ($job->type == config('constants.INITIAL') && $job->active) {
                $note = $job->note;
                break;
            }
        }
        return $note;
    }

    /**
     * getCustomerCCExpirationDate
     *
     * @param  mixed $customer
     * @return void
     */
    public function getCustomerCCExpirationDate($customer)
    {
        $expDate = null;
        $account = $this->getAccountByType($customer, 'Credit Card');
        if ($account) {
            $expMonth = null;
            if ($account->card_exp_month) {
                $expMonth = $account->card_exp_month;
            }

            $expyear = null;
            if ($account->card_exp_year) {
                $expyear = (new DateTime($account->card_exp_year))->format("y");
            }
            $expDate = ($expMonth && $expyear) ? $expMonth . '/' . $expyear :  $expMonth . $expyear;
        }
        return $expDate;
    }

    /**
     * getSignatureDate
     *
     * @param  mixed $pcc
     * @param  mixed $pdf
     * @return void
     */
    public function getSignatureDate($pcc = null, $pdf)
    {
        if (!$pcc) {
            return '';
        }
        $format = 'd \of M Y';
        $file   = $pcc->contract->signature_details;
        if (!$file) {
            return (new \DateTime())->format($format);
        }
        $dateCreated = $file->date_created;
        if ($dateCreated === null) {
            return (new \DateTime())->format($format);
        }

        return (new \DateTime($file->date_created))->format($format);
    }

    /**
     * getContractRegularJobCount
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractRegularJobCount($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $count = 0;
        foreach ($pcc->jobs_details as $job) {
            if ($job->type == config('constants.REGULAR') && $job->active) {
                $count++;
            }
        }

        return $count;
    }
    /**
     * getSalespersonSignature
     *
     * @param  mixed $pcc
     * @param  mixed $pdf
     * @return void
     */
    public function getSalespersonSignature($pcc = null, $pdf)
    {
        if (!$pcc) {
            return '';
        }

        $salesPerson = $pcc->contract->salespeople;
        if (!$salesPerson) {
            return '';
        }

        $profile = $salesPerson->office_user_details->profile_details;
        if (!$profile) {
            return '';
        }

        $signature = $profile->signature_details;
        if (!$signature) {
            return '';
        }

        if ($pdf) {
            return view(
                'pdf.img_pdf',
                array(
                    'src'    => $signature->path,
                    'width'  => 210,
                    'height' => 75
                )
            )->render();
        }
        return view(
            'pdf.img',
            array(
                'id'     =>  $signature->id,
                'width'  => 210,
                'height' => 75
            )
        )->render();
    }
    /**
     * getSalespersonSignatureStretched
     *
     * @param  mixed $pcc
     * @param  mixed $pdf
     * @return void
     */
    public function getSalespersonSignatureStretched($pcc = null, $pdf)
    {
        if (!$pcc) {
            return '';
        }

        $salesPerson = $pcc->contract->salespeople;
        if (!$salesPerson) {
            return '';
        }
        $profile = $salesPerson->office_user_details->profile_details;
        if (!$profile) {
            return '';
        }
        $signature = $profile->signature_details;
        if (!$signature) {
            return '';
        }
        $src = $signature->path;
        $isFileExist = false;
        if ($signature) {
            if (file_exists(env('ASSET_URL') . '' . $src)) {
                $src = env('ASSET_URL') . '' . $src;
                $isFileExist = true;
            }
            if ($isFileExist) {
                $src = 'data:image/' . pathinfo($src, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($src));
            }
            $rendered = view(
                'pdf.img_pdf',
                array(
                    'src'      => $src,
                    'width'    => '100%',
                    'height'   => '100%'
                )
            )->render();
            return $rendered;
        }
        return '';
    }

    /**
     * getVerifyEmailLink
     *
     * @param  mixed $customer
     * @return void
     */
    public function getVerifyEmailLink($customer)
    {
        if (!$customer->id) {
            return '';
        }

        // $hash       = (string) $this->getEmailVerificationHash($customer);
        // $verifyLink = (string) (url("/public") . '/' . $customer->id . '/' . $hash);

        $verifyLink = (string) (url("/public") . '/' . $customer->id);

        if (strpos($verifyLink, 'mypocomos.net')) {
            $url = "https://mypocomos.net/public/{$customer->id}";
        } elseif (strpos($verifyLink, 'sandbox.pocomos.com')) {
            $url = "https://sandbox.pocomos.com/public/{$customer->id}";
        } elseif (strpos($verifyLink, '15.206.7.200')) {
            $url = "http://15.206.7.200/pocomos-admin/app/verify-email/{$customer->id}";
        } else {
            $url = "http://15.206.7.200/pocomos-admin/app/verify-email/{$customer->id}";
        }
        $format = "<a href='%s' >Click here to verify your email address</a>";
        return ["html_link" => sprintf($format, $url), "url" => $url];
    }

    /**
     * getTechnician
     *
     * @param  mixed $job
     * @return void
     */
    public function getTechnician($job = null)
    {
        $tech = null;
        if (!$job) {
            return null;
        }

        if ($job->getTechnician) {
            $tech = $job->getTechnician;
        } elseif ($job->slot && $job->slot->route_detail && $job->slot->route_detail->technician_detail) {
            $tech = $job->slot->route_detail->technician_detail;
        } elseif ($job->contract && $job->contract->technician_details) {
            $tech = $job->contract->technician_details;
        }
        return $tech;
    }
    /**
     * getTechnicianBio
     *
     * @param  mixed $job
     * @return void
     */
    public function getTechnicianBio($job = null)
    {
        if (!$job || !$tech = $this->getTechnician($job)) {
            return null;
        }

        return $tech->user_detail->profile_details->bio ?? null;
    }
    /**
     * getTechnicianPhoto
     *
     * @param  mixed $job
     * @param  mixed $pdf
     * @return void
     */
    public function getTechnicianPhoto($job = null, $pdf = false)
    {
        if (!$job || !$tech = $this->getTechnician($job)) {
            return null;
        }
        $officeUserProfile = $tech->user_detail->profile_details;
        $technicianPhoto   = $officeUserProfile->photo_details;

        if ($technicianPhoto) {
            $src = $technicianPhoto->path;
            $isFileExist = false;
            if (file_exists(env('ASSET_URL') . '' . $src)) {
                $src = env('ASSET_URL') . '' . $src;
                $isFileExist = true;
            }
            if ($isFileExist) {
                $src = 'data:image/' . pathinfo($src, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($src));
            }
            $rendered = view(
                'pdf.img_pdf',
                array(
                    'src'      => $src,
                    'width'    => 210,
                    'height'   => 75
                )
            )->render();
            return $rendered;
        }
        return null;
    }
    /**
     * getServiceDate
     *
     * @param  mixed $job
     * @param  mixed $pdf
     * @return void
     */
    public function getServiceDate($job = null, $pdf)
    {
        $format = $pdf ? 'd-M-Y' : 'm-d-Y';

        return $job ? (new \DateTime($job->date_scheduled))->format($format) : null;
    }

    /**
     * getServiceTime
     *
     * @param  mixed $job
     * @return void
     */
    public function getServiceTime($job = null)
    {
        $slot = $job ? $job->slot : null;
        return $slot ? (new \DateTime($slot->time_begin))->format('h:i A') : null;
    }

    /**
     * getScheduledServices
     *
     * @param  mixed $customer
     * @return void
     */
    public function getScheduledServices($customer)
    {
        if (!$customer->id) {
            return '';
        }

        $customer_id = $customer->id;

        $query_data = DB::select(
            DB::raw(
                "SELECT pco.*, sco.*,p.*,c.*,j.*,s.*,r.*,t.*,ou.*,u.*,pco.id AS id
               FROM  pocomos_pest_contracts AS pco

               JOIN pocomos_contracts AS sco ON sco.id = pco.contract_id
               JOIN pocomos_customer_sales_profiles AS p ON p.id = sco.profile_id
               JOIN pocomos_customers AS c ON c.id = p.customer_id
               JOIN pocomos_jobs AS j ON j.contract_id = pco.contract_id

               LEFT JOIN pocomos_route_slots AS s ON s.id = j.slot_id
               LEFT JOIN pocomos_routes AS r ON r.id = s.route_id
               LEFT JOIN pocomos_technicians AS t ON t.id = r.technician_id
               LEFT JOIN pocomos_company_office_users AS ou ON ou.id = t.user_id
               LEFT JOIN orkestra_users AS u ON u.id = ou.user_id

               WHERE (c.id = $customer_id) AND (sco.status = 'Active') AND (j.status = 'Pending'  OR j.status = 'Rescheduled')

               ORDER BY sco.status ASC
           "
            )
        );

        $collection = collect($query_data);

        $contracts = $collection->map(function ($pcc) {
            $jobs = PocomosJob::where('contract_id', $pcc->contract_id)->whereIn('status', ['Rescheduled', 'Pending'])->get()->toArray();
            $service_type = PocomosPestContractServiceType::findOrFail($pcc->service_type_id);

            usort($jobs, function ($a, $b) {
                return $a['date_scheduled'] > $b['date_scheduled'];
            });
            return array(
                'serviceType' => $service_type->name,
                'jobs' => $jobs
            );
        });

        return view('pdf.schedule_services', array('contracts' =>  $contracts))->render();
    }

    /**
     * moneyFormat
     *
     * @param  mixed $number
     * @return void
     */
    public function moneyFormat($currency)
    {
        return sprintf(number_format($currency, 2));
    }

    /**
     * getBillingCalendarNew
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getBillingCalendarNew($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $schedule        = $this->getBillingScheduleNew($pcc);
        $exceptions      = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        $rendered        = '';

        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        if (isset($schedule->prices)) {
            $rendered = view('pdf.billingScheduleNew', array('schedule' => $schedule, 'exceptions' => $exceptionsArray))->render();
        }
        return $rendered;
    }

    /**
     * getCustomerPublicPaymentLink
     *
     * @param  mixed $customer
     * @return void
     */
    public function getCustomerPublicPaymentLink($customer)
    {
        if (!$customer->id) {
            return '';
        }
        $hash = (string) $this->getPaymentVerificationHash($customer);
        $paymentLink = (string) (url("/public") . '/' . $customer->id . '/' . $hash);

        if (strpos($paymentLink, 'mypocomos.net')) {
            $url = "https://mypocomos.net/public/{$customer->id}/{$hash}";
        } elseif (strpos($paymentLink, 'sandbox.pocomos.com')) {
            $url = "https://sandbox.pocomos.com/public/{$customer->id}/{$hash}";
        } elseif (strpos($paymentLink, '15.206.7.200/pocomos-admin')) {
            $url = "https://15.206.7.200/pocomos-admin/public/{$customer->id}/{$hash}";
        } else {
            $url = "";
        }
        $format = "<a href=%s clicktracking='off' >Click here to make a payment</a>";
        return sprintf($format, $url);
    }

    /**
     * getPaymentVerificationHash
     *
     * @param  mixed $customer
     * @return void
     */
    public function getPaymentVerificationHash($customer)
    {
        return md5($customer->id . $customer->email . $customer->date_created);
    }

    /**
     * getCustomerPublicPaymentLinkSms
     *
     * @param  mixed $customer
     * @return void
     */
    public function getCustomerPublicPaymentLinkSms($customer)
    {
        if (!$customer->id) {
            return '';
        }
        $hash = (string) $this->getPaymentVerificationHash($customer);
        $paymentLink = (string) (url("/public") . '/' . $customer->id . '/' . $hash);
        if (strpos($paymentLink, 'mypocomos.net')) {
            $url = "https://mypocomos.net/public/{$customer->id}/{$hash}";
        } elseif (strpos($paymentLink, 'sandbox.pocomos.com')) {
            $url = "https://sandbox.pocomos.com/public/{$customer->id}/{$hash}";
        } elseif (strpos($paymentLink, '15.206.7.200/pocomos-admin')) {
            $url = "https://15.206.7.200/pocomos-admin/public/{$customer->id}/{$hash}";
        } else {
            $url = "";
        }
        return $url;
    }

    /**
     * getProDefenseBillingCalendar
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getProDefenseBillingCalendar($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $schedule        = $this->getBillingScheduleNew($pcc);
        $exceptions      = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        $rendered        = '';

        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        if (isset($schedule->prices)) {
            $rendered = view('pdf.billing', array('schedule' => $schedule, 'exceptions' => $exceptionsArray))->render();
        }
        return $rendered;
    }
    /**
     * getProDefenseNewBillingCalendar
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getProDefenseNewBillingCalendar($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $schedule        = $this->getBillingScheduleNew($pcc);
        $exceptions      = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        $rendered        = '';

        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        if (isset($schedule->prices)) {
            $rendered = view('pdf.newBilling', array('billingSched' => $schedule, 'exceptions' => $exceptionsArray))->render();
        }
        return $rendered;
    }

    /**
     * getCustomerName
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getCustomerName($customer)
    {
        if (!$customer->id) {
            return '';
        }
        if ($customer->account_type == config('constants.COMMERCIAL') && strlen($customer->company_name) > 0) {
            return $customer->company_name;
        }
        return $customer->first_name . ' ' . $customer->last_name;
    }

    /**
     * getLeadsDynamicParameters
     *
     * @param  mixed $lead
     * @param  mixed $office
     * @param  mixed $pdf
     * @return void
     */
    public function getLeadsDynamicParameters($lead)
    {
        $customFieldsParameters = [];
        return array_merge(array(
            'lead_first_name'   => $lead->first_name ?? '',
            'lead_last_name'    => $lead->last_name ?? '',
            'lead_name'         => $this->getLeadName($lead),
            'lead_id'           =>  $lead->id ?? '',
            'lead_email'        => $lead->email ?? '',
            'lead_phone_number' => $this->getLeadPhone($lead),
            'lead_address'      => $this->getAddress($lead),
            'lead_phone'        =>  $lead->contact_address->primaryPhone->number,
            'lead_street'       => $lead->contact_address ? $lead->contact_address->street : '',
            'lead_suite'        => $lead->contact_address ? $lead->contact_address->suite : '',
            'lead_city'         => $lead->contact_address ? $lead->contact_address->city : '',

        ), $customFieldsParameters);
    }

    /**
     * getLeadName
     *
     * @param  mixed $lead
     * @return void
     */
    public function getLeadName($lead)
    {
        $first_name = $lead->first_name ?? '';
        $last_name  = $lead->last_name ?? '';

        return $first_name . " " . $last_name;
    }
    /**
     * getLeadPhone
     *
     * @param  mixed $lead
     * @return void
     */
    public function getLeadPhone($lead)
    {
        return $lead->addresses;
    }

    /**
     * getLeadAddress
     *
     * @param  mixed $lead
     * @return void
     */
    public function getLeadAddress($lead)
    {
        return $lead->contact_address;
    }
    /**
     * getOfficePhone
     *
     * @param  mixed $lead
     * @return void
     */
    public function getOfficePhone($office)
    {
        return $office->contact_address;
    }
    /**
     * getAutoPayCheckbox
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getAutoPayCheckbox($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $rendered = view('pdf.autopayCheckbox', array('checked' => $pcc->contract->profile_details->autopay))->render();
        return $rendered;
    }
    /**
     * getAgreementLength
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getAgreementLength($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        return sprintf('%d', $pcc->contract_details->agreement_details->length ?? null);
    }
    /**
     * getContractAddendum
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractAddendum($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        return $pcc->addendum ?? null;
    }

    /**
     * getContractInitialPrice
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractInitialPrice($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $initial_price = $pcc->initial_price ? $pcc->initial_price : 0.0;

        return $this->moneyFormat($initial_price);
    }
    /**
     * getLastServiceDate
     *
     * @param  mixed $customerState
     * @param  mixed $pdf
     * @return void
     */
    public function getLastServiceDate($customer = null, $pdf)
    {
        if ($customer->state_details && $customer->state_details->last_service_date) {
            $format = $pdf ? 'd-M-Y' : 'm-d-Y';
            return (new \DateTime($customer->state_details->last_service_date))->format($format);
        }
        return '';
    }
    /**
     * getContractRecurringDiscount
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractRecurringDiscount($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $recurring_discount = $pcc->recurring_discount ? $pcc->recurring_discount : 0.0;
        return $this->moneyFormat($recurring_discount);
    }
    /**
     * getContractInitialDiscount
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractInitialDiscount($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $initial_discount = $pcc->initial_discount ? $pcc->initial_discount : 0.0;
        return $this->moneyFormat($initial_discount);
    }
    /**
     * getContractRegularInitialPrice
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractRegularInitialPrice($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $regular_initial_price = $pcc->regular_initial_price ? $pcc->regular_initial_price : 0.0;
        return $this->moneyFormat($regular_initial_price);
    }
    /**
     * getContractRecurringPrice
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractRecurringPrice($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $recurring_price = $pcc->recurring_price ? $pcc->recurring_price : 0.0;
        return $this->moneyFormat($recurring_price);
    }
    /**
     * getCustomFields
     *
     * @param  mixed $office
     * @param  mixed $pestContract
     * @return void
     */
    public function getCustomFields($office, $pestContract = null)
    {
        if (!$pestContract) {
            return array();
        }

        $customFieldsParameters = array();
        if ($pestContract->custom_fields()->count() > 0) {
            $customFields = $pestContract->custom_fields;
            foreach ($customFields as $customField) {
                if ($customField->custom_field && $customField->custom_field->active) {
                    $customFieldLabel = $customField->custom_field->label;
                    $key = 'custom_variable_' . str_replace(' ', '_', strtolower($customFieldLabel));
                    $customFieldsParameters[$key] = $customField->value;
                }
            }
        }
        return $customFieldsParameters;
    }
    /**
     * getInitialPriceTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getInitialPriceTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $price   = $pcc->initial_price ? $pcc->initial_price : 0.0;
        $taxRate = $pcc->contract_details->sales_tax ? $pcc->contract_details->sales_tax : 0.0;

        return $this->moneyFormat($price * $taxRate);
    }
    /**
     * getRecurringPriceTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getRecurringPriceTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }

        $price   = $pcc->recurring_price ? $pcc->recurring_price : 0.0;
        $taxRate = $pcc->contract_details->sales_tax ? $pcc->contract_details->sales_tax : 0.0;
        return $this->moneyFormat($price * $taxRate);
    }
    /**
     * getContractRecurringPriceWithTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractRecurringPriceWithTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $price   = $pcc->recurring_price ? $pcc->recurring_price : 0.0;
        $taxRate = $pcc->contract_details->sales_tax ? $pcc->contract_details->sales_tax : 0.0;

        return $this->moneyFormat($price + ($price * $taxRate));
    }
    /**
     * getContractInitialPriceWithTax
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractInitialPriceWithTax($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $price   = $pcc->initial_price ? $pcc->initial_price : 0.0;
        $taxRate = $pcc->contract_details->sales_tax ? $pcc->contract_details->sales_tax : 0.0;

        return $this->moneyFormat($price + ($price * $taxRate));
    }
    /**
     * getSelectedPestsNew
     *
     * @param  mixed $pcc
     * @param  mixed $office
     * @return void
     */
    public function getSelectedPestsNew($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }
        $allPests = PocomosPest::where('office_id', $office->id)->where('active', 1)->orderBy('name', 'ASC')->get()->toArray();

        $pests = array_filter($allPests, function ($pest) {
            return ($pest['type'] == 'regular') || ($pest['type'] == 'Regular');
        });

        $specialty = array_filter($allPests, function ($pest) {
            return ($pest['type'] == 'specialty') || ($pest['type'] == 'Specialty');
        });

        $contractPests = PocomosPestContractsPest::where('contract_id', $pcc->id)->get()->toArray();
        $contractSpecialty = PocomosPestContractsSpecialtyPest::where('contract_id', $pcc->id)->get()->toArray();

        $selected = array_merge($contractPests, $contractSpecialty);

        $rendered = view(
            'pdf.selectedPests',
            array(
                'selected'  => $selected,
                'pests'     => $pests,
                'specialty' => $specialty
            )
        )->render();
        return $rendered;
    }
    /**
     * getInsightCalendar
     *
     * @param  mixed $pcc
     * @param  mixed $lang
     * @return void
     */
    public function getInsightCalendar($pcc = null, $lang = '')
    {
        if (!$pcc) {
            return '';
        }
        $array = $this->getBillingScheduleNew($pcc);
        $rendered = '';

        if (isset($array->prices)) {
            $billingSched = $this->convertBillingScheduleTo($array->prices, $lang);
            $rendered     = view('pdf.insightBilling', array('billingSched' => $billingSched))->render();
        }

        return $rendered;
    }

    /**
     * convertBillingScheduleTo
     *
     * @param  mixed $billingSched
     * @param  mixed $lang
     * @return void
     */
    public function convertBillingScheduleTo(array $billingSched, $lang = '')
    {
        $newBillingSchedule = [];
        foreach ($billingSched as $key => $value) {

            $newKey = \DateTime::createFromFormat('d M Y', '01 ' . $key)->format('n');
            $newBillingSchedule[$newKey]['timeoftheyear'] = $this->getTimeOfTheYear($newKey);

            if ($lang === 'French') {
                $newBillingSchedule[$newKey]['timeoftheyear'] = $this->getTimeOfTheYearFrench($newKey);
            } else {
                $newBillingSchedule[$newKey]['timeoftheyear'] = $this->getTimeOfTheYear($newKey);
            }
            $newBillingSchedule[$newKey]['totalAmount'] = $billingSched[$key]['amount'] + $billingSched[$key]['sales_tax'];
            $newBillingSchedule[$newKey]['label']       = \DateTime::createFromFormat('d M Y', '01 ' . $key)->format('M y');
            $newBillingSchedule[$newKey]['labelNew']    = \DateTime::createFromFormat('d M Y', '01 ' . $key)->format('M');
            $newBillingSchedule[$newKey]['shaded']      = isset($billingSched[$key]['shaded']) ? $billingSched[$key]['shaded'] : 0;
        }

        ksort($newBillingSchedule);
        $jan = '';
        if (isset($newBillingSchedule[1])) {
            $jan = $newBillingSchedule[1];
            unset($newBillingSchedule[1]);
            $newBillingSchedule[1] = $jan;
        }

        $feb = '';
        if (isset($newBillingSchedule[2])) {
            $feb = $newBillingSchedule[2];
            unset($newBillingSchedule[2]);
            $newBillingSchedule[2] = $feb;
        }
        return $newBillingSchedule;
    }

    /**
     * getTimeOfTheYearFrench
     *
     * @param  mixed $month
     * @return void
     */
    public function getTimeOfTheYearFrench($month)
    {
        if (in_array($month, [12, 1, 2])) {
            return "hiver";
        }

        if (in_array($month, [3, 4, 5])) {
            return 'Printemps';
        }

        if (in_array($month, [6, 7, 8])) {
            return "été";
        }

        return "automne";
    }

    /**
     * getTimeOfTheYear
     *
     * @param  mixed $month
     * @return void
     */
    public function getTimeOfTheYear($month)
    {
        if (in_array($month, [12, 1, 2])) {
            return 'winter';
        }

        if (in_array($month, [3, 4, 5])) {
            return 'spring';
        }

        if (in_array($month, [6, 7, 8])) {
            return 'summer';
        }

        return 'fall';
    }

    /**
     * getInsightAddress
     *
     * @param  mixed $office
     * @return void
     */
    public function getInsightAddress($office)
    {
        if (!$office) {
            return '';
        }
        $rendered = view('pdf.insightAddress', array('office' => $office))->render();
        return $rendered;
    }
    /**
     * getInsightPests
     *
     * @param  mixed $pcc
     * @param  mixed $office
     * @return void
     */
    public function getInsightPests($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }

        $pests    = PocomosPest::where('office_id', $office->id)->get()->toArray();
        $selected = PocomosPestContractsPest::where('contract_id', $pcc->id)->get()->toArray();

        $rendered = view(
            'pdf.insightPests',
            array(
                'selected' => $selected,
                'pests'    => $pests,
                'colNum'   => 4
            )
        )->render();
        return $rendered;
    }
    /**
     * getInsightSpecialtyPests
     *
     * @param  mixed $pcc
     * @param  mixed $office
     * @return void
     */
    public function getInsightSpecialtyPests($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }
        $pests    = PocomosPest::where('type', 'specialty')->where('office_id', $office->id)->get()->toArray();
        $selected = PocomosPestContractsSpecialtyPest::where('contract_id', $pcc->id)->get()->toArray();

        $rendered = view(
            'pdf.insightPests',
            array(
                'selected' => $selected,
                'pests'    => $pests,
                'colNum'   => 3
            )
        )->render();
        return $rendered;
    }
    /**
     * getInsightContractLength
     *
     * @param  mixed $pcc
     * @param  mixed $pdf
     * @param  mixed $lang
     * @return void
     */
    public function getInsightContractLength($pcc = null, $pdf, $lang)
    {
        if (!$pcc) {
            return '';
        }

        $agreementLength = $this->getContractLengthInMonths($pcc);
        $dateStart       = $this->getContractStartDate($pcc, $pdf);
        $dateEnd         = $this->getContractEndDate($pcc, $pdf);

        if ($lang === "French") {
            $rendered = view(
                'pdf.insightAgreementLengthFrench',
                array(
                    'contract_length_in_months' => $agreementLength,
                    'contract_start_date'       => $dateStart,
                    'contract_end_date'         => $dateEnd
                )
            )->render();
        } else {
            $rendered = view(
                'pdf.insightAgreementLength',
                array(
                    'contract_length_in_months' => $agreementLength,
                    'contract_start_date'       => $dateStart,
                    'contract_end_date'         => $dateEnd
                )
            )->render();
        }
        return $rendered;
    }
    /**
     * getContractStartDate
     *
     * @param  mixed $pcc
     * @param  mixed $pdf
     * @return void
     */
    public function getContractStartDate($pcc = null, $pdf)
    {
        if (!$pcc) {
            return '';
        }
        $format = $pdf ? 'd-M-Y' : 'm-d-Y';
        return (new DateTime($pcc->contract->date_start))->format($format);
    }
    /**
     * getContractEndDate
     *
     * @param  mixed $pcc
     * @param  mixed $pdf
     * @return void
     */
    public function getContractEndDate($pcc = null, $pdf)
    {
        if (!$pcc) {
            return '';
        }
        $format = $pdf ? 'd-M-Y' : 'm-d-Y';
        return (new DateTime($pcc->contract->date_end))->format($format);
    }
    /**
     * getStretchedCustomerSignature
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getStretchedCustomerSignature($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        if ($pcc->contract->signature_details) {
            $src = $pcc->contract->signature_details->path;
            $isFileExist = false;
            if (file_exists(env('ASSET_URL') . '' . $src)) {
                $src = env('ASSET_URL') . '' . $src;
                $isFileExist = true;
            }
            if ($isFileExist) {
                $src = 'data:image/' . pathinfo($src, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($src));
            }
            $rendered = view(
                'pdf.img_pdf_stretchy',
                array(
                    'src'      => $src,
                    'width'    => '100%',
                    'height'   => '100%',
                    'imgClass' => 'customerSig'
                )
            )->render();
            return $rendered;
        }
        return null;
    }
    /**
     * getBillingOrServiceAddress
     *
     * @param  mixed $customer
     * @return void
     */
    public function getBillingOrServiceAddress($customer)
    {
        if ($customer->billing_address) {
            $suite  = $customer->billing_address->suite ?? '';
            $street = $customer->billing_address->street ?? '';
            $city   = $customer->billing_address->city ?? '';

            $address = $suite . ',' . $street . ',' . $city;
        } elseif ($customer->contact_address) {
            $suite  = $customer->contact_address->suite ?? '';
            $street = $customer->contact_address->street ?? '';
            $city   = $customer->contact_address->city ?? '';

            $address = $suite . ',' . $street . ',' . $city;
        } else {
            $address = '';
        }
        return $address;
    }
    /**
     * getStretchedCustomerAutopaySignature
     *
     * @param  mixed $pcc
     * @param  mixed $profile
     * @return void
     */
    public function getStretchedCustomerAutopaySignature($pcc = null, $profile = null)
    {

        if (!$pcc || !$profile || !$profile->autopay) {
            return '';
        }

        if ($pcc->contract->autopay_signature_details) {
            $src = $pcc->contract->autopay_signature_details->path;
            $isFileExist = false;
            if (file_exists(env('ASSET_URL') . '' . $src)) {
                $src = env('ASSET_URL') . '' . $src;
                $isFileExist = true;
            }
            if ($isFileExist) {
                $src = 'data:image/' . pathinfo($src, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($src));
                $rendered = view(
                    'pdf.img_pdf_stretchy',
                    array(
                        'src'      => $src,
                        'width'    => '100%',
                        'height'   => '100%',
                        'imgClass' => 'autopaySig'
                    )
                )->render();
                return $rendered;
            }
            $rendered = view(
                'pdf.img_pdf_stretchy',
                array(
                    'src'      => '#',
                    'width'    => '100%',
                    'height'   => '100%',
                    'imgClass' => 'autopaySig'
                )
            )->render();
            return $rendered;
        }
        return null;
    }
    /**
     * getTerminixCalendar
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getTerminixCalendar($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $exceptions = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        $serviceSched = $this->getPricingSchedule($pcc);
        $schedWithSchortenedYear = [];
        $jobs = $pcc->jobs_details;
        $dates = [];

        foreach ($jobs as $job) {
            $dates[] = (new DateTime($job->date_scheduled))->format('M Y');
        }
        if (isset($serviceSched->prices)) {
            foreach ($serviceSched->prices as $key => $value) {
                $newKey = new DateTime($key);
                if (in_array($key, $dates)) {
                    $schedWithSchortenedYear[$newKey->format('M \'y')]['isScheduled'] = true;
                } else {
                    $schedWithSchortenedYear[$newKey->format('M \'y')]['isScheduled'] = false;
                }
                $schedWithSchortenedYear[$newKey->format('M \'y')] = $value;
            }

            $rendered = view(
                'pdf.serviceCalendar',
                array(
                    'serviceSched' => $schedWithSchortenedYear,
                    'exceptions'   => $exceptionsArray
                )
            )->render();
            return $rendered;
        }
        return '';
    }
    /**
     * getTerminixCalendarMonthly
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getTerminixCalendarMonthly($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $exceptions = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        $serviceSched = $this->getPricingSchedule($pcc);
        $schedWithSchortenedYear = [];
        $jobs = $pcc->jobs_details;
        $dates = [];
        foreach ($jobs as $job) {
            $dates[] = (new DateTime($job->date_scheduled))->format('M Y');
        }
        if (isset($serviceSched->prices)) {
            foreach ($serviceSched->prices as $key => $value) {
                $newKey = new DateTime($key);
                if (in_array($key, $dates)) {
                    $schedWithSchortenedYear[$newKey->format('M \'y')]['isScheduled'] = true;
                } else {
                    $schedWithSchortenedYear[$newKey->format('M \'y')]['isScheduled'] = false;
                }
                $schedWithSchortenedYear[$newKey->format('M \'y')] = $value;
                $schedWithSchortenedYear[$newKey->format('M \'y')]['totalAmount'] = $serviceSched->prices[$key]['amount'] + $serviceSched->prices[$key]['sales_tax'];
            }
            $rendered = view(
                'pdf.serviceCalendarMonthly',
                array(
                    'serviceSched' => $schedWithSchortenedYear,
                    'exceptions'   => $exceptionsArray
                )
            )->render();
            return $rendered;
        }
        return '';
    }
    /**
     * getTerminixSpecialtyPests
     *
     * @param  mixed $pcc
     * @param  mixed $office
     * @return void
     */
    public function getTerminixSpecialtyPests($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }
        $pests    = PocomosPest::where('type', 'specialty')->where('office_id', $office->id)->get()->toArray();
        $selected = PocomosPestContractsSpecialtyPest::where('contract_id', $pcc->id)->get()->toArray();

        $rendered = view(
            'pdf.pests',
            array(
                'selected' => $selected,
                'pests'    => $pests
            )
        )->render();
        return $rendered;
    }
    /**
     * getTerminixRegularPests
     *
     * @param  mixed $pcc
     * @param  mixed $office
     * @return void
     */
    public function getTerminixRegularPests($pcc = null, $office)
    {
        if (!$pcc) {
            return '';
        }
        $pests = PocomosPest::where('type', 'regular')->where('office_id', $office->id)->get()->toArray();

        $selected = $pests;
        $rendered = view(
            'pdf.pests',
            array(
                'selected' => $selected,
                'pests'    => $pests
            )
        )->render();
        return $rendered;
    }
    /**
     * getTerminixPaymentMethod
     *
     * @param  mixed $customer
     * @return void
     */
    public function getTerminixPaymentMethod($customer)
    {
        $apayAccount = $customer->sales_profile->autopay_account ?? null;
        if ($apayAccount === null) {
            return '';
        }
        $rendered = view('pdf.payment', array('accountType' => $apayAccount['type']))->render();
        return $rendered;
    }
    /**
     * getTerminixAutopay
     *
     * @param  mixed $customer
     * @return void
     */
    public function getTerminixAutopay($customer)
    {
        $apayAccount = $customer->sales_profile->autopay_account ?? null;
        if ($apayAccount === null) {
            return '';
        }
        $rendered = view('pdf.autopay', array('autopay' => $apayAccount))->render();
        return $rendered;
    }
    /**
     * getContractRegularJobCountForYear
     *
     * @param  mixed $pcc
     * @param  mixed $year
     * @return void
     */
    public function getContractRegularJobCountForYear($pcc = null, $year)
    {
        if (!$pcc) {
            return '';
        }
        $yearInt = $this->getYearFromDate($year);
        $count   = 0;
        $jobs    = $pcc->jobs_details ?? null;

        if (count($jobs)) {
            foreach ($jobs as $job) {
                if ($job->type == 'Regular' && $job->active && $yearInt === $this->getYearFromDate($job->date_scheduled)) {
                    $count++;
                }
            }
        }
        return $count;
    }
    /**
     * getYearFromDate
     *
     * @param  mixed $dateTime
     * @return void
     */
    public function getYearFromDate($dateTime)
    {
        return (int) (new DateTime($dateTime))->format('Y');
    }
    /**
     * getContractMonthsArray
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractMonthsArray($pcc)
    {
        $months = [];
        foreach ($pcc->jobs_details as $job) {
            if ($job->active) {
                $dateSched = $job->date_scheduled;
                $months[(new DateTime($dateSched))->format('n')] = (new DateTime($dateSched))->format('F');
            }
        }
        ksort($months);
        return $months;
    }
    /**
     * getContractEarliestMonth
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractEarliestMonth($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $monthsArray = $this->getContractMonthsArray($pcc);
        if (count($monthsArray) > 0) {
            return reset($monthsArray);
        }
        return '';
    }
    /**
     * getContractLatestMonth
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractLatestMonth($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $monthsArray = $this->getContractMonthsArray($pcc);
        if (count($monthsArray) > 0) {
            return end($monthsArray);
        }
        return '';
    }
    /**
     * getContractUniqueMonthCount
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractUniqueMonthCount($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $monthsArray = $this->getContractMonthsArray($pcc);
        return count($monthsArray);
    }
    /**
     * getContractRegularJobTotalCost
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getContractRegularJobTotalCost($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $contractValue = 0;
        foreach ($pcc->jobs_details as $job) {
            if ($job->type == 'Regular' && $job->active) {
                $invoice = $job->invoice_detail;
                if ($invoice) {
                    $contractValue += round($invoice->amount_due, 2);
                }
            }
        }
        return $contractValue;
    }
    /**
     * getRecurringYearlyForMosquitosTerminix
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getRecurringYearlyForMosquitosTerminix($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $jobCount = $this->getContractRegularJobCount($pcc);
        $amount   = $pcc->recurring_price * ($jobCount + 1);

        return $this->moneyFormat($amount);
    }
    /**
     * getProDefenseCalendar
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getProDefenseCalendar($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $billingSched    = $this->getBillingScheduleNew($pcc);
        $exceptions      = unserialize($pcc->exceptions);
        $exceptionsArray = [];
        $rendered        = '';

        if ($exceptions) {
            foreach ($exceptions as $exception) {
                $exceptionsArray[] = date('M', strtotime($exception));
            }
        }
        if (isset($billingSched->prices)) {
            $rendered = view(
                'pdf.billing',
                array(
                    'schedule'   => $billingSched,
                    'exceptions' => $exceptionsArray
                )
            )->render();
        }
        return $rendered;
    }
    /**
     * getAgreementPriceInfoNew
     *
     * @param  mixed $pcc
     * @return void
     */
    public function getAgreementPriceInfoNew($pcc = null)
    {
        if (!$pcc) {
            return '';
        }
        $overview = $this->getPricingOverview($pcc);
        $rendered = view('pdf.pricingBoxes', array('pricing_overview' => $overview))->render();
        return $rendered;
    }
    /**
     * getListOfRegularPestsNew
     *
     * @param  mixed $pcc
     * @param  mixed $office
     * @return void
     */
    public function getListOfRegularPestsNew($pcc = null, $office)
    {
        $pests_ids = PocomosPestContractsPest::where('contract_id', $pcc->id)->get('pest_id')->toArray();
        $contractPests = PocomosPest::whereIn('id', $pests_ids)->pluck('name')->toArray();
        return implode(', ', $contractPests);
    }
    /**
     * reschedulePendingJobs
     *
     * @param  mixed $jobs
     * @return void
     */
    public function reschedulePendingJobs($jobs)
    {
        // $result = new EntityOperationResult();
        $result = [];
        foreach ($jobs as $job) {
            $contract = $job->contract;
            $scope = $contract->week_of_the_month . ' ' . $contract->day_of_the_week;
            $month = (new DateTime($job->date_scheduled))->modify('-1 month')->format('F Y');
            // $dateScheduled = StringHelper::parseDate('last day of ' . $month)->modify($scope);
            $dateScheduled  = 'last day of ' . $month . ' ' . $scope;
            $result->merge($this->rescheduleJob($job, $dateScheduled, $contract->preferred_time));
        }
        return $result;
    }
    /**
     * activateCustomerf
     *
     * @param  mixed $customer
     * @return void
     */
    public function activateCustomer($customer_id)
    {
        $customer = PocomosCustomer::find($customer_id);
        if ($customer) {
            $customer->status = config('constants.ACTIVE');
            $customer->date_deactivated = null;
            $customer->status_reason_id = null;
            $customer->save();
        }
    }

    /**
     * placeCustomerOnHold
     *
     * @param  mixed $customer
     * @return void
     */
    public function placeCustomerOnHold($customer_id, $office_id, $deactivate_children = false)
    {
        $customer = PocomosCustomer::findOrFail($customer_id);
        if ($customer) {
            $customer->status           = config('constants.ON_HOLD');
            $customer->date_deactivated = date('Y-m-d H:i:s');
            $customer->status_reason_id = null;
            $customer->save();

            if ($customer->id) {
                $jobs = $this->findScheduledServices($customer);
                foreach ($jobs as $job) {
                    // update slot_id
                    $pocomos_job = PocomosJob::find($job->id)->update(['slot_id' => null]);
                }
            }
            $profile = $customer->sales_profile ?? null;

            if ($profile->id) {
                // update autopay
                $pocomos_customer_sales_profile = PocomosCustomerSalesProfile::find($profile->id)->update(['autopay' => 1]);
            }
            try {
                $currentUser = PocomosCompanyOfficeUser::whereOfficeId($office_id)->whereUserId(auth()->user()->id)->first();
            } catch (\Exception $e) {
                $currentUser = null;
            }
            if ($deactivate_children) {
                $sub_customers = PocomosSubCustomer::where('parent_id', $customer_id)->get();
                foreach ($sub_customers as $val) {
                    $this->placeCustomerOnHold($val->child_id, $office_id, true);
                }
            }
            $type = 'Customer On-Hold';
            $this->track($type, $currentUser, $profile);
        }
    }
    /**
     * findScheduledServices
     *
     * @param  mixed $customer
     * @return void
     */
    public function findScheduledServices($customer)
    {
        $query = DB::table('pocomos_jobs as j')
            ->select('j.*')
            ->join('pocomos_pest_contracts as co', 'co.id', 'j.contract_id')
            ->join('pocomos_contracts as sco', 'sco.id', 'co.contract_id')
            ->join('pocomos_customer_sales_profiles as p', 'p.id', 'sco.profile_id')
            ->join('pocomos_customers as c', 'c.id', 'p.customer_id')
            ->join('pocomos_invoices as i', 'i.id', 'j.invoice_id')
            ->where('c.id', $customer->id)
            ->where('j.date_completed', NULL)
            ->orderBy('j.date_scheduled')
            ->get()->toArray();
        return $query;
    }

    /**
     * track
     *
     * @param  mixed $type
     * @param  mixed $officeUser
     * @param  mixed $profile
     * @param  mixed $context
     * @return void
     */
    public function track($type, $officeUser = null, $profile = null, array $context = array())
    {
        $handler = $this->getHandler($type);

        $context['office_user'] = $officeUser;
        $context['profile']     = $profile;

        $params = array(
            'type'          => (string) $type,
            'office_user'   => $officeUser ? $officeUser->id : null,
            'profile'       => $profile ? $profile->id : null,
            'description'   => $this->getDescription($context),
            // 'context'       => json_encode($this->getFilteredContext($context)),
            'context'       => '',
            'date_created'  => date('Y-m-d H:i:s')
        );
        $sql = 'INSERT INTO pocomos_activity_logs (type, office_user_id, customer_sales_profile_id, description, context, date_created)
            VALUES("' . $params['type'] . '", ' . $params['office_user'] . ',' . $params['profile'] . ', "' . $params['description'] . '", "' . $params['context'] . '", "' . $params['date_created'] . '")';

        DB::select(DB::raw($sql));
    }

    /**
     * getHandler
     *
     * @param  mixed $type
     * @return void
     */
    public function getHandler($type)
    {
        $type = (string)$type;
        if (!isset($type)) {
            throw new \Exception(__('strings.message', ['message' => 'No handler exists for' . $type]));
        }
        return $type;
    }

    /**
     * getDescription
     *
     * @param  mixed $context
     * @return void
     */
    public function getDescription(array $context = array())
    {
        $desc = '';
        if (isset($context['office_user'])) {
            $desc .= "<a href='/pocomos-admin/app/employees/users/" . $context['office_user']->user_details->id . "/show'>" . $context['office_user']->user_details->full_name . "</a> cancelled";
        } else {
            $desc .= 'The system cancelled ';
        }

        if (isset($context['profile'])) {
            $desc .= " a contract belonging to <a href='/pocomos-admin/app/Customers/" . $context['profile']->customer->id . "/service-information'>" . $context['profile']->customer->first_name . " " . $context['profile']->customer->last_name . "</a>.";
        } else {
            $desc .= ' a customer\'s contract.';
        }

        return $desc;
    }
    /**
     * getFilteredContext
     *
     * @param  mixed $context
     * @return void
     */
    public function getFilteredContext(array $context = array())
    {
        return array(
            'profile_id' => isset($context['office_user']) ? $context['office_user']->profile_details->id : null,
            'customer_id' => isset($context['profile']) ? $context['profile']->customer->id : null
        );
    }

    /**
     * @param PocomosPestContract $contract
     *
     * @return PocomosJob|null
     */
    public function findLastServiceForContract($pestContractId)
    {
        return $this->createFindContractServicesQueryBuilder($pestContractId)
            ->where('pocomos_jobs.status', config('constants.COMPLETE'))
            ->orderBy('pocomos_jobs.date_completed', 'DESC')
            ->take(1)->first();
    }

    public function getDatesBaseServiceSchedules($contract_start_date, $contract_end_date, $service_frequency, $exceptions, $specific_day_and_week, $options)
    {
        $schedule_services = array();
        $number_of_jobs = 5;

        if ($service_frequency == config('constants.WEEKLY')) {
            $service_frequency_str = '+1 week';
        } else if ($service_frequency == config('constants.MONTHLY')) {
            $service_frequency_str = '+1 month';
        } else if ($service_frequency == config('constants.BI_WEEKLY')) {
            $service_frequency_str = '+2 week';
        } else if ($service_frequency == config('constants.TRI_WEEKLY')) {
            $service_frequency_str = '+3 week';
        } else if ($service_frequency == config('constants.BI_MONTHLY')) {
            $service_frequency_str = '+2 month';
        } else if ($service_frequency == config('constants.TWICE_PER_MONTH')) {
            $service_frequency_str = '+15 day';
        } else if ($service_frequency == config('constants.HEXA_WEEKLY')) {
            $service_frequency_str = '+6 week';
        } else if ($service_frequency == config('constants.QUARTERLY')) {
            $service_frequency_str = '+3 month';
        } else if ($service_frequency == config('constants.SEMI_ANNUALLY')) {
            $service_frequency_str = '+6 month';
        } else if ($service_frequency == config('constants.ANNUALLY')) {
            $service_frequency_str = '+1 year';
        } else if ($service_frequency == config('constants.TRI_ANNUALLY')) {
            $service_frequency_str = '+4 month';
        }
        $schedule_services[] = $contract_start_date;
        if ($service_frequency == config('constants.ONE_TIME')) {
            $schedule_services[] = $contract_start_date;
        } else {
            $c = 0;
            while ($contract_start_date < $contract_end_date || count($schedule_services) < $number_of_jobs) {
                if($specific_day_and_week){
                    $contract_start_date = new DateTime($contract_start_date);
                    $contract_start_date->modify($service_frequency_str);
                    $contract_start_date = $contract_start_date->format('Y-m-d');

                    $contract_start_date = date('Y-m-01', strtotime($contract_start_date));

                    if(strtolower(date('l', strtotime($contract_start_date))) != strtolower($options['day'])){
                        $contract_start_date = new DateTime($contract_start_date);
                        $contract_start_date->modify($options['week'].' '.$options['day']);
                        $contract_start_date = $contract_start_date->format('Y-m-d');
                    }
                }
                if (!in_array(date('F', strtotime($contract_start_date)), $exceptions)) {
                    $schedule_services[] = $contract_start_date;
                }

                $contract_start_date = new DateTime($contract_start_date);
                if(!$specific_day_and_week){
                    $contract_start_date->modify($service_frequency_str);
                }
                $contract_start_date = $contract_start_date->format('Y-m-d');
                $c = $c + 1;
            }
        }

        return $schedule_services;
    }

    /**Get service schedule details */
    public function getServiceBillningScheduleV2($data)
    {
        $serviceFrequency = $data['service_information'] ? ($data['service_information']['service_frequency'] ? $data['service_information']['service_frequency'] : '') : '';

        $service_schedule = '<div id="service-schedule"><ul class="table-list clearfix">';
        $schedule = array();
        $contract_type_id = $data['service_information']['contract_type_id'];

        $pest_agreement = PocomosPestAgreement::whereId($contract_type_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        $variable_length = $agreement->variable_length ?? 1;
        
        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }

        $current = date('Y-m-d', strtotime($data['service_information']['scheduling_information']['initial_date'] ?? date('Y-m-d')));
        $current_month = date('F', strtotime($current));

        $agreement_length = $agreement['length'];

        if($variable_length){
            $end = date('Y-m-d', strtotime($data['service_information']['contract_end_date']));
        }else{
            $end = date('Y-m-01', strtotime("+$agreement_length month", strtotime($current)));
        }

        if ($exceptions && in_array($current_month, $exceptions)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        $exception_css = '';
        $billingSequence = array();

        $num_of_jobs = $data['service_information'] ? ($data['service_information']['num_of_jobs'] ? $data['service_information']['num_of_jobs'] : '') : '';
        
        $specifyNumberOfJobs = $agreement->specifyNumberOfJobs;
        $oneMonthFollowup = $pest_agreement->one_month_followup;

        if(!$specifyNumberOfJobs){
            $num_of_jobs = $agreement_length;
        }

        $c = 1;
        switch ($serviceFrequency) {
            case config('constants.QUARTERLY'):
                while ($current < $end) {
                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $qrAr = [1];
                    $isOneMonthFollowup = false;
                    for($j=1 ; $j<= $agreement_length ; $j++){
                        $last = end($qrAr) + 3;
                        if($oneMonthFollowup && !$isOneMonthFollowup){
                            $isOneMonthFollowup = true;
                            $qrAr[] = end($qrAr) + 1;
                        }
                        if($last <= $agreement_length){
                            $qrAr[] = $last;
                        }
                    }
                    
                    if (in_array($c, $qrAr) && $c <= $num_of_jobs) {
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    } else {
                        $text = '';
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $text . '</span></li>';
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.WEEKLY'):
            case config('constants.BI_WEEKLY'):
            case config('constants.TRI_WEEKLY'):
            case config('constants.MONTHLY'):
            case config('constants.TWICE_PER_MONTH'):
                while ($current < $end) {
                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    if ($c <= $num_of_jobs) {
                        $text = 'X';
                    } else {
                        $text = '';
                    }

                    $billingSequence[] = date('M Y', strtotime($current));
                    
                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $text . '</span></li>';
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.BI_MONTHLY'):
                while ($current < $end) {
                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $qrAr = [1];
                    $isOneMonthFollowup = false;
                    for($j=1 ; $j<= $agreement_length ; $j++){
                        if($oneMonthFollowup && !$isOneMonthFollowup){
                            $last = end($qrAr) + 1;
                            $isOneMonthFollowup = true;
                            $qrAr[] = $last;
                        }
                        $last = end($qrAr) + 2;
                        if($last <= $agreement_length){
                            $qrAr[] = $last;
                        }
                    }

                    if (in_array($c, $qrAr) && $c <= $num_of_jobs) {
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    } else {
                        $text = '';
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $text . '</span></li>';
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.HEXA_WEEKLY'):
                $date = new DateTime(date("Y-m-d H:i:s"));
                $hexaMonths = array();
                $currentNew = $current;
                
                while ($currentNew < $end) {
                    $currentMonth = date('M', strtotime($currentNew));
                    $hexaMonths[] = $currentMonth;

                    $nextHexaMonth = $date->modify('+6 week');
                    $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));
                }

                while ($current < $end) {
                    $currentMonthStr = date('M', strtotime($current));

                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $current_month = date('M Y', strtotime($current));

                    $text = '';
                    if(in_array($currentMonthStr, $hexaMonths) && $c <= $num_of_jobs){
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . $current_month . '</span>
                    <span class="list-value">' . $text . '</span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.SEMI_ANNUALLY'):
                $date = new DateTime(date("Y-m-d H:i:s"));
                $hexaMonths = array();
                $currentNew = $current;
                $isOneMonthFollowup = false;
                
                while ($currentNew < $end) {
                    $currentMonth = date('M', strtotime($currentNew));
                    $hexaMonths[] = $currentMonth;

                    if($oneMonthFollowup && !$isOneMonthFollowup){
                        $nextHexaMonth = $date->modify('+1 month');
                        $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));
                        
                        $currentMonth = date('M', strtotime($currentNew));
                        $hexaMonths[] = $currentMonth;

                        $isOneMonthFollowup = true;
                    }

                    $nextHexaMonth = $date->modify('+6 month');
                    $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));;
                }

                while ($current < $end) {
                    $currentMonthStr = date('M', strtotime($current));

                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $current_month = date('M Y', strtotime($current));

                    $text = '';
                    if(in_array($currentMonthStr, $hexaMonths) && $c <= $num_of_jobs){
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . $current_month . '</span>
                    <span class="list-value">' . $text . '</span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.ANNUALLY'):
                $date = new DateTime(date("Y-m-d H:i:s"));
                $hexaMonths = array();
                $currentNew = $current;
                $isOneMonthFollowup = false;
                
                while ($currentNew < $end) {
                    $currentMonth = date('M', strtotime($currentNew));
                    $hexaMonths[] = $currentMonth;

                    if($oneMonthFollowup && !$isOneMonthFollowup){
                        $nextHexaMonth = $date->modify('+1 month');
                        $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));
                        
                        $currentMonth = date('M', strtotime($currentNew));
                        $hexaMonths[] = $currentMonth;

                        $isOneMonthFollowup = true;
                    }

                    $nextHexaMonth = $date->modify('+12 month');
                    $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));;
                }

                while ($current < $end) {
                    $currentMonthStr = date('M', strtotime($current));

                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $current_month = date('M Y', strtotime($current));

                    $text = '';
                    if(in_array($currentMonthStr, $hexaMonths) && $c <= $num_of_jobs){
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . $current_month . '</span>
                    <span class="list-value">' . $text . '</span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.TRI_ANNUALLY'):
                $date = new DateTime(date("Y-m-d H:i:s"));
                $hexaMonths = array();
                $currentNew = $current;
                
                $isOneMonthFollowup = false;
                while ($currentNew < $end) {
                    $currentMonth = date('M', strtotime($currentNew));
                    $hexaMonths[] = $currentMonth;

                    if($oneMonthFollowup && !$isOneMonthFollowup){
                        $nextHexaMonth = $date->modify('+1 month');
                        $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));
                        
                        $currentMonth = date('M', strtotime($currentNew));
                        $hexaMonths[] = $currentMonth;

                        $isOneMonthFollowup = true;
                    }
                    $nextHexaMonth = $date->modify('+4 month');
                    $currentNew = date('Y-m-d', strtotime($nextHexaMonth->format('Y-m-d')));
                }

                while ($current < $end) {
                    $currentMonthStr = date('M', strtotime($current));

                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $current_month = date('M Y', strtotime($current));

                    $text = '';
                    if(in_array($currentMonthStr, $hexaMonths) && $c <= $num_of_jobs){
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . $current_month . '</span>
                    <span class="list-value">' . $text . '</span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.ONE_TIME'):
                $c = 0;
                while ($current < $end) {
                    $currentMonthStr = date('M', strtotime($current));

                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $current_month = date('M Y', strtotime($current));

                    $text = '';
                    if($c == 0){
                        $text = 'X';
                        $billingSequence[] = date('M Y', strtotime($current));
                    }

                    $service_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . $current_month . '</span>
                    <span class="list-value">' . $text . '</span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            default:
                break;
        }
        
        $service_schedule .= '</ul></div>';
        
        $billing_schedule = $this->getBillingScheduleV2($data, $billingSequence);

        return array('service_schedule' => $service_schedule, 'billing_schedule' => $billing_schedule);
    }

    /**Get billing schedule details */
    public function getBillingScheduleV2($data, $billingSequence)
    {
        $serviceFrequency = $data['service_information'] ? ($data['service_information']['service_frequency'] ?? '') : '';

        $billingFrequency = $data['service_information'] ? ($data['service_information']['billing_frequency'] ?? '') : '';

        $tax_code_id = $data['service_information'] ? ($data['service_information']['additional_information'] ? ($data['service_information']['additional_information'] ? $data['service_information']['additional_information']['tax_code_id'] : '') : '') : '';

        $num_of_jobs = $data['service_information'] ? ($data['service_information']['num_of_jobs'] ? $data['service_information']['num_of_jobs'] : '') : '';

        $taxCode = PocomosTaxCode::find($tax_code_id);
        $taxRate = $taxCode->tax_rate ?? 0;

        $billing_schedule = '<div id="billing-schedule"><ul class="table-list clearfix">';
        $schedule = array();

        $contract_type_id = $data['service_information']['contract_type_id'];

        $pest_agreement = PocomosPestAgreement::whereId($contract_type_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }

        $initial_price = $data['service_information']['pricing_information']['initial_price'] ?? 0;
        $recurring_price = $data['service_information']['pricing_information']['recurring_price'] ?? 0;
        $current = date('Y-m-d', strtotime($data['service_information']['contract_start_date'] ?? date('Y-m-d')));
        $initial_date = date('Y-m-d', strtotime($data['service_information']['scheduling_information']['initial_date'] ?? date('Y-m-d')));

        $agreement_length = $agreement['length'];
        
        $variable_length = $agreement->variable_length ?? 1;

        if($variable_length){
            $end = date('Y-m-d', strtotime($data['service_information']['contract_end_date']));
        }else{
            $end = date('Y-m-01', strtotime("+$agreement_length month", strtotime($current)));
        }

        $initial_month = date('m', strtotime($initial_date));
        $current_month = date('m', strtotime($current));

        if (!$pest_agreement['allow_dates_in_the_past'] && ($initial_month < $current_month)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }

        if ($exceptions && in_array($current_month, $exceptions)) {
            throw new \Exception(__('strings.something_went_wrong'));
        }
        
        $specifyNumberOfJobs = $agreement->specifyNumberOfJobs;

        if(!$specifyNumberOfJobs){
            $num_of_jobs = $agreement_length;
        }

        $c = 1;
        switch ($billingFrequency) {
            case config('constants.PER_SERVICE'):
                while (strtotime($current) < strtotime($end)) {
                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    $rWeek = 0;
                    $currentStr = date('M Y', strtotime($current));

                    if (in_array($currentStr, $billingSequence)) {
                        $key = array_search($currentStr, $billingSequence);
                        $value = explode(' ',$billingSequence[$key]);
                        $m = date("n",strtotime($value[0]));

                        if($serviceFrequency == config('constants.WEEKLY')){
                            if($m == date('n')){
                                $tWeek = $this->getWeeks(date("Y-m-t"), 'friday');
                                $cWeek = $this->getWeeks(date('Y-m-d'), 'friday');
                            }else{
                                $tWeek = $this->getWeeks(date('Y-m-t', strtotime($value[1].'-'.$m)), 'friday');
                                $cWeek = $this->getWeeks(date('Y-m-d', strtotime($value[1].'-'.$m.'-01')), 'friday');
                            }
                            $rWeek = $tWeek - $cWeek;
                            $currentNew = date('Y-m-d', strtotime("first day of next month", strtotime($value[1].'-'.$m.'-01')));
                        }else if($serviceFrequency == config('constants.BI_WEEKLY')){
                            if($m == date('n')){
                                $date = new DateTime(date("Y-m-d"));
                                $currentNew = $current;
                                $mEnd = date("Y-m-t", strtotime($currentNew));

                                if(date('n', strtotime($currentNew)) == $m){
                                    $rWeek = $rWeek + 1;
                                }

                                while (strtotime($currentNew) < strtotime($mEnd)) {
                                    $date = new DateTime(date("Y-m-d", strtotime($currentNew)));
                                    $nextBiDay = $date->modify('+2 week');
                                    $nextBiDay = $nextBiDay->format('Y-m-d');
                                    
                                    if(date('n', strtotime($nextBiDay)) == $m){
                                        $rWeek = $rWeek + 1;
                                    }
                                    $currentNew = date('Y-m-d', strtotime($nextBiDay));
                                }
                            }else{
                                $date = new DateTime(date("Y-".$m."-01"));
                                $currentNew = $currentNew;
                                $mEnd = date("Y-".$m."-t", strtotime($currentNew));

                                if(date('n', strtotime($currentNew)) == $m){
                                    $rWeek = $rWeek + 1;
                                }
                                while (strtotime($currentNew) < strtotime($mEnd)) {
                                    $date = new DateTime(date("Y-m-d", strtotime($currentNew)));
                                    $nextBiDay = $date->modify('+2 week');
                                    $nextBiDay = $nextBiDay->format('Y-m-d');
                                    
                                    if(date('n', strtotime($nextBiDay)) == $m){
                                        $rWeek = $rWeek + 1;
                                    }
                                    $currentNew = date('Y-m-d', strtotime($nextBiDay));
                                }
                            }
                        }else if($serviceFrequency == config('constants.TRI_WEEKLY')){
                            if($m == date('n')){
                                $date = new DateTime(date("Y-m-d"));
                                $currentNew = $current;
                                $mEnd = date("Y-m-t", strtotime($currentNew));

                                if(date('n', strtotime($currentNew)) == $m){
                                    $rWeek = $rWeek + 1;
                                }

                                while (strtotime($currentNew) < strtotime($mEnd)) {
                                    $date = new DateTime(date("Y-m-d", strtotime($currentNew)));
                                    $nextBiDay = $date->modify('+3 week');
                                    $nextBiDay = $nextBiDay->format('Y-m-d');
                                    
                                    if(date('n', strtotime($nextBiDay)) == $m){
                                        $rWeek = $rWeek + 1;
                                    }
                                    $currentNew = date('Y-m-d', strtotime($nextBiDay));
                                }
                            }else{
                                $date = new DateTime(date("Y-".$m."-01"));
                                $currentNew = $currentNew;
                                $mEnd = date("Y-".$m."-t", strtotime($currentNew));

                                if(date('n', strtotime($currentNew)) == $m){
                                    $rWeek = $rWeek + 1;
                                }
                                while (strtotime($currentNew) < strtotime($mEnd)) {
                                    $date = new DateTime(date("Y-m-d", strtotime($currentNew)));
                                    $nextBiDay = $date->modify('+3 week');
                                    $nextBiDay = $nextBiDay->format('Y-m-d');
                                    
                                    if(date('n', strtotime($nextBiDay)) == $m){
                                        $rWeek = $rWeek + 1;
                                    }
                                    $currentNew = date('Y-m-d', strtotime($nextBiDay));
                                }
                            }
                        }else{
                            $rWeek = $rWeek + 1;
                            $currentNew = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                        }
                    } else {
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                        $currentNew = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    }

                    if($c <= $num_of_jobs){
                        
                        if($c != 1){
                            $initial_price = $recurring_price;
                        }

                        $tax = $initial_price * $taxRate;
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax * $rWeek;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price * $rWeek, 2);
                    }else{
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                    }
        
                    $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';

                    // $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $current = date('Y-m-d', strtotime($currentNew));
                    $c = $c + 1;
                }
                break;
            case config('constants.MONTHLY'):
                while (strtotime($current) < strtotime($end)) {
                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    if($c <= $num_of_jobs){
                        $initial_price = $agreement->monthly_default_price;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
                        $tax = $initial_price * $taxRate;
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;
                    }else{
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                    }
        
                    $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.INITIAL_MONTHLY'):
                while (strtotime($current) < strtotime($end)) {
                    $current_month = date('F', strtotime($current));
                    $initial_price = $data['service_information']['pricing_information']['initial_price'] ?? 0;
                    $recurring_price = $data['service_information']['pricing_information']['recurring_price'] ?? 0;

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    if($c <= $num_of_jobs){
                        if($c != 1){
                            $initial_price = $recurring_price;
                        }
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
                        $tax = $initial_price * $taxRate;
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;
                    }else{
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                    }

                    $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span>
                    <span class="list-value">' . $initial_amount . '</span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.DUE_AT_SIGNUP'):
                while (strtotime($current) < strtotime($end)) {
                    $current_month = date('F', strtotime($current));
                    $initial_price = $data['service_information']['pricing_information']['initial_price'] ?? 0;

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    if($c <= $num_of_jobs){
                        if($c != 1){
                            $initial_price = 0;
                        }
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
                        $tax = $initial_price * $taxRate;
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;
                    }else{
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                    }
        
                    $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.TWO_PAYMENTS'):
                $second_invoice_after_of_days = $data['service_information']['second_invoice_after_of_days'] ?? 30;

                $date = new DateTime(date("Y-m-d", strtotime($current)));
                $nextBiDay = $date->modify('+'.$second_invoice_after_of_days.' days');
                $nextBiDay = $nextBiDay->format('Y-m-d');
                $currentStr = date('M Y', strtotime($nextBiDay));

                while (strtotime($current) < strtotime($end)) {
                    $current_month = date('F', strtotime($current));
                    $first_payment = $data['service_information']['pricing_information']['initial_price'] ?? 0;
                    $secound_payment = $data['service_information']['pricing_information']['recurring_price'] ?? 0;

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }
                    $initial_price = 0;
                    if($c == 1){
                        $initial_price = $first_payment;
                    }

                    if (in_array($currentStr, $billingSequence)) {
                        $key = array_search($currentStr, $billingSequence);
                        $value = explode(' ',$billingSequence[$key]);
                        $m = date("n",strtotime($value[0]));

                        if(date('n', strtotime($current)) == $m){
                            $initial_price = $secound_payment;
                        }
                    }
                    
                    if($c <= $num_of_jobs){
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
                        $tax = $initial_price * $taxRate;
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;
                    }else{
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                    }
        
                    $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
            case config('constants.INSTALLMENTS'):
                $billing_schedule = $this->getInstallmentFrequencyBaseSchedule($data);
                break;
            default:
                while (strtotime($current) < strtotime($end)) {
                    $current_month = date('F', strtotime($current));

                    if ($exceptions && in_array($current_month, $exceptions)) {
                        $exception_css = 'box-disabled';
                    } else {
                        $exception_css = '';
                    }

                    if($c <= $num_of_jobs){
                        $initial_price = $agreement->monthly_default_price;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($initial_price, 2);
                        $tax = $initial_price * $taxRate;
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;
                    }else{
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                    }
        
                    $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                    <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';

                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
                break;
        }
        $billing_schedule .= '</ul></div>';
        return $billing_schedule;
    }

    /**Get date base get week number */
    public function weeks_in_month($timestamp) {
        $maxday    = date("t",$timestamp);
        $thismonth = getdate($timestamp);
        $timeStamp = mktime(0,0,0,$thismonth['mon'],1,$thismonth['year']);    //Create time stamp of the first day from the give date.
        $startday  = date('w',$timeStamp);    //get first day of the given month
        $day = $thismonth['mday'];
        $weeks = 0;
        $week_num = 0;

        for ($i=0; $i<($maxday+$startday); $i++) {
            if(($i % 7) == 0){
                $weeks++;
            }
            if($day == ($i - $startday + 1)){
                $week_num = $weeks;
            }
        }     
        return $week_num;
    }

    /**
     * @param $lead
     * @param $status
     * @return bool True if the status changed
     */
    private function setActionStatus($lead, $status)
    {
        if ($lead->status == config('constants.CUSTOMER')) {
            throw new \Exception(__('strings.message', ['message' => 'A Customer may not be modified.']));
        }

        // if ($lead->status !== $status) {
            $this->trackChange($lead, $status);

            return true;
        // }

        return false;
    }

    /**
     */
    public function trackChange($lead, $newStatus)
    {
        switch ($newStatus) {
            case config('constants.CUSTOMER'):
                $this->ensureAction($lead, config('constants.SALE'));
                $this->ensureAction($lead, config('constants.LEAD'));
                $this->ensureAction($lead, config('constants.TALK'));
                $this->ensureAction($lead, config('constants.KNOCK'));

                break;
            case config('constants.LEAD'):
                $this->ensureAction($lead, config('constants.LEAD'));
                $this->ensureAction($lead, config('constants.TALK'));
                $this->ensureAction($lead, config('constants.KNOCK'));

                break;
            case config('constants.NOT_INTERESTED'):
                $this->ensureAction($lead, config('constants.TALK'));
                $this->ensureAction($lead, config('constants.KNOCK'));

                break;
            case config('constants.NOT_HOME'):
                $this->ensureAction($lead, config('constants.KNOCK'));

                break;
            case config('constants.MONITOR'):
                $this->ensureAction($lead, config('constants.LEAD'));
                $this->ensureAction($lead, config('constants.TALK'));
                $this->ensureAction($lead, config('constants.KNOCK'));

                break;
        }
    }

    /**
     * @param string $type
     */
    private function ensureAction($lead, $type)
    {
        if (!in_array($type, $lead->get_actions->toArray())) {
            $lead_actions['lead_id'] = $lead->id;
            $lead_actions['type'] = strtolower($type);
            $lead_actions['active'] = true;

            PocomosLeadAction::create($lead_actions);
        }

        return true;
    }

    /**
     * Get offices
     *
     * @param $office
     * @return array
     */
    public function getOfficeWithChildren($office)
    {
        $data = DB::select(DB::raw("SELECT o.id
        FROM pocomos_company_offices AS o
        WHERE (o.id = '$office' OR o.parent_id = '$office') AND o.active = 1 ORDER BY o.name"));

        return $data;
    }
    
    /**
     * Get offices
     *
     * @param $office
     * @return array
     */
    public function getInstallmentFrequencyBaseSchedule($data){
        $current = date('Y-m-d', strtotime($data['service_information']['pricing_information']['first_payment_date'] ?? date('Y-m-d')));
        $current_month = date('m', strtotime($current));
        $installment_frequency = $data['service_information']['pricing_information']['installment_frequency'] ?? 0;

        $tax_code_id = $data['service_information'] ? ($data['service_information']['additional_information'] ? ($data['service_information']['additional_information'] ? $data['service_information']['additional_information']['tax_code_id'] : '') : '') : '';

        $num_of_jobs = $data['service_information'] ? ($data['service_information']['num_of_jobs'] ? $data['service_information']['num_of_jobs'] : 0) : 0;

        $taxCode = PocomosTaxCode::find($tax_code_id);
        $taxRate = $taxCode->tax_rate ?? 0;

        $billing_schedule = '<div id="billing-schedule"><ul class="table-list clearfix">';

        $contract_type_id = $data['service_information']['contract_type_id'];

        $pest_agreement = PocomosPestAgreement::whereId($contract_type_id)->firstOrFail();
        $agreement = PocomosAgreement::findOrFail($pest_agreement->agreement_id);

        if ($pest_agreement->exceptions) {
            $exceptions = unserialize($pest_agreement->exceptions);
        } else {
            $exceptions = array();
        }
        $agreement_length = $agreement['length'];
        
        $variable_length = $agreement->variable_length ?? 1;

        if($variable_length){
            $end = date('Y-m-d', strtotime($data['service_information']['contract_end_date']));
        }else{
            $end = date('Y-m-01', strtotime("+$agreement_length month", strtotime($current)));
        }

        $specifyNumberOfJobs = $agreement->specifyNumberOfJobs;
        if(!$specifyNumberOfJobs){
            $num_of_jobs = $agreement_length;
        }

        $c = 1;
        switch ($installment_frequency) {
            case config('constants.MONTHLY'):
                while (strtotime($current) < strtotime($end)) {
                    if($c <= $num_of_jobs){
                        $installment_amount = $data['service_information']['pricing_information']['installment_amount'] ?? 0;
                        $first_payment_date = $data['service_information']['pricing_information']['first_payment_date'] ?? 0;
                        $number_of_payments = $data['service_information']['pricing_information']['number_of_payments'] ?? 0;
                        $average_installment_amount = $data['service_information']['pricing_information']['average_installment_amount'] ?? 0;
                        
                        $current_month = date('F', strtotime($current));

                        if ($exceptions && in_array($current_month, $exceptions)) {
                            $exception_css = 'box-disabled';
                        } else {
                            $exception_css = '';
                        }

                        $initial_price = $installment_amount;
                        
                        $tax_full_amt = $initial_price * $taxRate;
                        $tax_amt = $tax_full_amt / $number_of_payments;
                        $tax = number_format(round($tax_amt), 2);
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;

                        if($c <= $number_of_payments){
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($average_installment_amount, 2);
                        }else{
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                            $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        }
            
                        $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                        <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';
                    }
                    $c = $c + 1;
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                }
            break;
            case config('constants.WEEKLY'):
                $number_of_payments = $data['service_information']['pricing_information']['number_of_payments'] ?? 0;

                $sDate = $current;
                $eDate = new DateTime($current);
                $eDate = $eDate->modify('+'.$number_of_payments.' week');
                $eDate = $eDate->format('Y-m-d');

                $mArray = array();
                $mWeekCount = array();
                while(strtotime($sDate) < strtotime($eDate)){
                    $nDate = new DateTime(date('Y-m-d',strtotime($sDate)));
                    $nDate = $nDate->modify('+1 week');
                    $month = $nDate->format('F');
                    
                    if(!in_array($month, $mArray)){
                        $mArray[] = $month;
                        $mWeekCount[$month] = 1;
                    }else{
                        $mWeekCount[$month] = $mWeekCount[$month] + 1;
                    }
                    $sDate = $nDate->format('Y-m-d');
                }

                while (strtotime($current) < strtotime($end)) {
                    if($c <= $num_of_jobs){
                        $installment_amount = $data['service_information']['pricing_information']['installment_amount'] ?? 0;
                        $first_payment_date = $data['service_information']['pricing_information']['first_payment_date'] ?? 0;
                        $average_installment_amount = $data['service_information']['pricing_information']['average_installment_amount'] ?? 0;
                        
                        $current_month = date('F', strtotime($current));

                        if ($exceptions && in_array($current_month, $exceptions)) {
                            $exception_css = 'box-disabled';
                        } else {
                            $exception_css = '';
                        }

                        $initial_price = $installment_amount;
                        
                        $tax_full_amt = $initial_price * $taxRate;
                        $tax_amt = $tax_full_amt / $number_of_payments;
                        

                        if(in_array(date('F', strtotime($current)), $mArray)){
                            $existWc = $mWeekCount[date('F', strtotime($current))];

                            $amount = $average_installment_amount * $existWc;
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($amount, 2);

                            $tax = number_format(round($tax_amt), 2);
                            $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax * $existWc;
                        }else{
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                            $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        }

            
                        $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                        <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';
                    }
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
            break;
            case config('constants.BI_MONTHLY'):
                $number_of_payments = $data['service_information']['pricing_information']['number_of_payments'] ?? 0;

                $sDate = $current;
                $eDate = $end;

                $mArray = array();
                $mArray[] = date('F', strtotime($current));
                while(strtotime($sDate) < strtotime($eDate)){
                    $nDate = new DateTime(date('Y-m-d',strtotime($sDate)));
                    $nDate = $nDate->modify('+2 month');
                    $month = $nDate->format('F');
                    
                    if(!in_array($month, $mArray)){
                        $mArray[] = $month;
                    }
                    $sDate = $nDate->format('Y-m-d');
                }

                while (strtotime($current) < strtotime($end)) {
                    if($c <= $num_of_jobs){
                        $installment_amount = $data['service_information']['pricing_information']['installment_amount'] ?? 0;
                        $first_payment_date = $data['service_information']['pricing_information']['first_payment_date'] ?? 0;
                        $number_of_payments = $data['service_information']['pricing_information']['number_of_payments'] ?? 0;
                        $average_installment_amount = $data['service_information']['pricing_information']['average_installment_amount'] ?? 0;
                        
                        $current_month = date('F', strtotime($current));

                        if ($exceptions && in_array($current_month, $exceptions)) {
                            $exception_css = 'box-disabled';
                        } else {
                            $exception_css = '';
                        }

                        $initial_price = $installment_amount;
                        
                        $tax_full_amt = $initial_price * $taxRate;
                        $tax_amt = $tax_full_amt / $number_of_payments;
                        $tax = number_format(round($tax_amt), 2);
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;

                        if(in_array(date('F', strtotime($current)), $mArray)){
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($average_installment_amount, 2);
                        }else{
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                            $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        }

            
                        $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                        <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';
                    }
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
            break;
            case config('constants.QUARTERLY'):
                $number_of_payments = $data['service_information']['pricing_information']['number_of_payments'] ?? 0;

                $sDate = $current;
                $eDate = $end;

                $mArray = array();
                $mArray[] = date('F', strtotime($current));
                while(strtotime($sDate) < strtotime($eDate)){
                    $nDate = new DateTime(date('Y-m-d',strtotime($sDate)));
                    $nDate = $nDate->modify('+3 month');
                    $month = $nDate->format('F');
                    
                    if(!in_array($month, $mArray)){
                        $mArray[] = $month;
                    }
                    $sDate = $nDate->format('Y-m-d');
                }

                while (strtotime($current) < strtotime($end)) {
                    if($c <= $num_of_jobs){
                        $installment_amount = $data['service_information']['pricing_information']['installment_amount'] ?? 0;
                        $first_payment_date = $data['service_information']['pricing_information']['first_payment_date'] ?? 0;
                        $number_of_payments = $data['service_information']['pricing_information']['number_of_payments'] ?? 0;
                        $average_installment_amount = $data['service_information']['pricing_information']['average_installment_amount'] ?? 0;
                        
                        $current_month = date('F', strtotime($current));

                        if ($exceptions && in_array($current_month, $exceptions)) {
                            $exception_css = 'box-disabled';
                        } else {
                            $exception_css = '';
                        }

                        $initial_price = $installment_amount;
                        
                        $tax_full_amt = $initial_price * $taxRate;
                        $tax_amt = $tax_full_amt / $number_of_payments;
                        $tax = number_format(round($tax_amt), 2);
                        $tax_amount = config('constants.DEFAULT_CURRENCY') . $tax;

                        if(in_array(date('F', strtotime($current)), $mArray)){
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format($average_installment_amount, 2);
                        }else{
                            $initial_amount = config('constants.DEFAULT_CURRENCY') . number_format(0, 2);
                            $tax_amount = config('constants.DEFAULT_CURRENCY') . 0;
                        }
            
                        $billing_schedule .= '<li class="' . $exception_css . '"><span class="list-title">' . date('M Y', strtotime($current)) . '</span>
                        <span class="list-value">' . $initial_amount . '</br><span style="font-size: 95%;">tx. '.$tax_amount.'</span></span></li>';
                    }
                    $current = date('Y-m-d', strtotime("first day of next month", strtotime($current)));
                    $c = $c + 1;
                }
            break;
        }

        return $billing_schedule;
    }

     function getWeeks($date, $rollover)
    {
        $cut = substr($date, 0, 8);
        $daylen = 86400;

        $timestamp = strtotime($date);
        $first = strtotime($cut . "00");
        $elapsed = ($timestamp - $first) / $daylen;

        $weeks = 1;

        for ($i = 1; $i <= $elapsed; $i++)
        {
            $dayfind = $cut . (strlen($i) < 2 ? '0' . $i : $i);
            $daytimestamp = strtotime($dayfind);

            $day = strtolower(date("l", $daytimestamp));

            if($day == strtolower($rollover))  $weeks ++;
        }

        return $weeks;
    }
}
