<?php

namespace App\Http\Controllers\API\Pocomos\Reports;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosService;
use App\Models\Pocomos\PocomosPestEstimates;
use App\Models\Pocomos\PocomosCompanyOfficeUser;
use App\Models\Pocomos\PocomosPestEstimateProducts;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;
use PDF;

class EstimateReportController extends Controller
{
    use Functions;

    public function findCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'search_term' => 'min:3',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $sql = 'SELECT cu.id, CONCAT(cu.first_name, \' \', cu.last_name) As name, 
                CONCAT(ca.street, " ",  ca.city, ", ",reg.code, " ", ca.postal_code) as address, 
                cu.email, pn.number
                FROM pocomos_customers AS cu
                JOIN pocomos_customer_sales_profiles AS csp ON cu.id = csp.customer_id
                JOIN pocomos_customer_state cus                  on cus.customer_id = cu.id
                JOIN pocomos_addresses ca                        on cu.contact_address_id = ca.id
                JOIN orkestra_countries_regions reg              on ca.region_id = reg.id
                LEFT JOIN pocomos_customers_phones AS cph ON csp.id = cph.profile_id
                JOIN pocomos_phone_numbers AS pn ON cph.phone_id = pn.id
                WHERE csp.office_id = ' . $officeId . ' AND cu.active = 1 AND csp.Active = 1
                ';

        if ($request->search_term) {
            $searchTerm = '%' . $request->search_term . '%';
            $sql .= ' AND (
                cu.first_name LIKE "' . $searchTerm . '"
                OR cu.last_name LIKE "' . $searchTerm . '"
                OR cu.email LIKE "' . $searchTerm . '"
                OR CONCAT(cu.first_name, \' \', cu.last_name) LIKE "' . $searchTerm . '"
                OR ca.street LIKE "' . $searchTerm . '" OR ca.suite LIKE "' . $searchTerm . '"
                OR ca.city LIKE "' . $searchTerm . '"
                OR ca.postal_code LIKE "' . $searchTerm . '" ';

            $phoneNumber = preg_replace('/[^0-9]/', '', $searchTerm);
            if (is_numeric($phoneNumber)) {
                $phoneNumber = '%' . $phoneNumber . '%';
                $sql .= ' OR pn.number LIKE ' . $phoneNumber . ' ';
            }

            $sql .= ')';
        }

        $sql .= ' GROUP BY cu.id';

        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(true, __('strings.list', ['name' => 'Customers']), $result);
    }

    public function findLead(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'search_term' => 'min:3',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $sql = "SELECT l.id,
                  CONCAT(a.street, ', ', a.city, ', ', r.code, ' ', a.postal_code) as address,
                  CONCAT(l.first_name, ' ', l.last_name) as name,
                  l.status as status,
                  (SELECT n2.summary FROM pocomos_notes n2 JOIN pocomos_leads_notes ln2 
                  ON ln2.note_id = n2.id WHERE ln2.lead_id = l.id LIMIT 1) as note,
                  CONCAT( '(', LEFT(pn.number,3) , ') ' , MID(pn.number,4,3) , '-', RIGHT(pn.number,4)) as phone,
                  q.map_code as map_code,
                  CONCAT(u.first_name, ' ', u.last_name) as salesperson,
                  l.date_created as date_added,
                  l.first_name,
                  l.last_name,
                  l.not_interested_reason_id as reason,
                  a.street,
                  a.suite,
                  a.city,
                  a.postal_code,
                  r.code as region_code
                FROM pocomos_leads l
                    JOIN pocomos_lead_quotes q ON l.quote_id = q.id
                    JOIN pocomos_salespeople s ON q.salesperson_id = s.id
                    JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                    JOIN orkestra_users u ON ou.user_id = u.id
                    JOIN pocomos_addresses a ON l.contact_address_id = a.id
                    LEFT JOIN orkestra_countries_regions r ON a.region_id = r.id
                    LEFT JOIN pocomos_phone_numbers pn ON a.phone_id = pn.id
                WHERE ou.office_id = " . $officeId . " AND l.active = 1
                    -- where 1=1
                 ";

        if ($request->search_term) {
            // return 1;
            $searchTerm = '%' . $request->search_term . '%';

            $sql .= ' AND (
                l.first_name LIKE "' . $searchTerm . '"
                OR l.last_name LIKE "' . $searchTerm . '"
                OR CONCAT(l.first_name, \' \', l.last_name) LIKE "' . $searchTerm . '"
                OR a.street LIKE "' . $searchTerm . '" OR a.suite LIKE "' . $searchTerm . '"
                OR a.city LIKE "' . $searchTerm . '"
                OR a.postal_code LIKE "' . $searchTerm . '" ';

            $phoneNumber = preg_replace('/[^0-9]/', '', $searchTerm);
            if (is_numeric($phoneNumber)) {
                $phoneNumber = '%' . $phoneNumber . '%';
                $sql .= ' OR pn.number LIKE "' . $phoneNumber . '" ';
            }

            $sql .= ')';
        }

        $sql .= ' GROUP BY l.id';

        $result = DB::select(DB::raw($sql));

        return $this->sendResponse(true, __('strings.list', ['name' => 'Leads']), $result);
    }

    public function getFilters(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosPestProducts = PocomosPestProduct::whereOfficeId($officeId)->whereActive(1)->get();
        $PocomosTaxCodes = PocomosTaxCode::whereOfficeId($officeId)->whereActive(1)->get();
        $PocomosServices = PocomosService::whereOfficeId($officeId)->whereActive(1)->get();

        return $this->sendResponse(true, 'Create estimate filters', [
            'pest_products'    => $PocomosPestProducts,
            'tax_codes'        => $PocomosTaxCodes,
            'services'        => $PocomosServices,
        ]);
    }

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $sentOn = date("Y-m-d", strtotime($request->sent_on));

        if ($request->product_id || $request->service_id) {
            // $q = PocomosPestEstimateProducts::with('tax_details')->whereId($request->estimate_product_id)->firstOrFail();
            $productId = $request->product_id;
            $taxRate = $request->tax_rate;
            $cost = $request->cost;
            $qty = $request->qty;

            $subTotal = $cost * $qty;
            $totalTax = $subTotal * $taxRate / 100;
            $total = $subTotal + $totalTax;

            $discount = $request->discount;

            $finalTotal = $total - $discount;
        } else {
            $subTotal = 0.00;
            $discount = 0.00;
            $finalTotal = 0.00;
        }

        $estimate = PocomosPestEstimates::create([
            'office_id' => $request->office_id,
            'customer_id' => $request->customer_id,
            'lead_id' => $request->lead_id,
            'sent_on' => $sentOn,
            'po_number' => $request->po_number,
            'name' => $request->name,
            'subtotal' => $subTotal,
            'discount' => $discount,
            'total' => $finalTotal,
            'terms' => $request->terms,
            'note' => $request->note,
            'status' => 'draft',
        ]);

        return $this->sendResponse(true, __('strings.create', ['name' => 'Estimate']), $estimate);

        /*$PocomosPestEstimates = PocomosPestEstimates::create($request->only('name','po_number','subtotal','discount','total','status','terms','note','search_for','sent_on','lead_id','customer_id','office_id'));
        foreach ($request->products as $product) {
                $pocomos_pest_estimate_products = [];
                $pocomos_pest_estimate_products['estimate_id'] = $PocomosPestEstimates->id;

                if($product['product'] == null){
                    $pocomos_pest_estimate_products['service_type_id'] = $product['service_type_id'];
                }
                else{
                    $pocomos_pest_estimate_products['product_id'] = $product['product'];
                    $pocomos_pest_estimate_products['tax_code_id'] = $product['taxCode'];
                }
                $pocomos_pest_estimate_products['cost'] = $product['cost'];
                $pocomos_pest_estimate_products['tax'] = $product['tax'];

                $pocomos_pest_estimate_products['calculate_amount'] = $product['calculateAmount'];
                $pocomos_pest_estimate_products['amount'] = $product['amount'];
                $pocomos_pest_estimate_products['description'] = $product['description'];
                $pocomos_pest_estimate_products['quantity'] = $product['quantity'];
                $PocomosPestEstimateProducts =  PocomosPestEstimateProducts::create($pocomos_pest_estimate_products);
            }*/
    }


    public function indexAction(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'page' => 'integer|nullable|min:1',
            'perPage' => 'integer|nullable|min:1',
            // 'customer_id' => 'required|exists:pocomos_customers,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $PocomosLead = PocomosPestEstimates::where('office_id', $request->office_id)
                    ->whereBetween('date_created', [$startDate, $endDate]);

        // if ($request->customer_id) {
        //     $PocomosLead = $PocomosLead->where('customer_id', $request->customer_id)->where('active', 1);
        // }

        if ($request->status) {
            $PocomosLead = $PocomosLead->whereIn('status', $request->status);
        }

        if ($request->type == 'customer') {
            $PocomosLead->whereNotNull('customer_id');
        } elseif ($request->type == 'lead') {
            $PocomosLead->whereNotNull('lead_id');
        }

        $PocomosLead = $PocomosLead->orderBy('date_created', 'desc');

        if ($request->search) {
            $search = $request->search;
            $PocomosLead->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('date_created', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('sent_on', 'like', '%' . $search . '%')
                    ->orWhere('total', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $PocomosLead->count();
        $PocomosLead->skip($perPage * ($page - 1))->take($perPage);

        $PocomosLead = $PocomosLead->get();

        $PocomosLead->map(function ($PocomosLead) {
            $findProducts = PocomosPestEstimateProducts::where('estimate_id', $PocomosLead->id)
                ->with('service_data')->with('tax_details')->with('product_data')->get();
            $PocomosLead['product_data'] = $findProducts;
        });

        return $this->sendResponse(true, 'List', [
            'Estimate' => $PocomosLead,
            'count' => $count,
        ]);
    }


    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $query = PocomosPestEstimates::select(
            '*',
            'pocomos_pest_estimates.lead_id',
            'pocomos_pest_estimates.customer_id',
            'pl.first_name as l_fname',
            'pl.last_name as l_lname',
            'pc.first_name as c_fname',
            'pc.last_name as c_lname',
            'pocomos_pest_estimates.status',
            'pocomos_pest_estimates.date_created'
        )
                ->leftjoin('pocomos_leads as pl', 'pocomos_pest_estimates.lead_id', 'pl.id')
                ->leftjoin('pocomos_customers as pc', 'pocomos_pest_estimates.customer_id', 'pc.id')
                ->whereBetween('pocomos_pest_estimates.date_created', [$startDate, $endDate]);

        if ($request->type == 'customer') {
            $query->whereNotNull('pocomos_pest_estimates.customer_id');
        } elseif ($request->type == 'lead') {
            $query->whereNotNull('lead_id');
        }

        if ($request->status) {
            $query->whereIn('pocomos_pest_estimates.status', $request->status);
        }


        if ($request->search) {
            $search = '%'.$request->search.'%';

            $formatDate = date('Y/m/d', strtotime($request->search));
            $date = '%'.str_replace("/", "-", $formatDate).'%';

            $query->where(function ($query) use ($search, $date) {
                $query->where(DB::raw("CONCAT(pc.first_name, ' ', pc.last_name)"), 'like', $search)
                ->orWhere(DB::raw("CONCAT(pl.first_name, ' ', pl.last_name)"), 'like', $search)
                ->orWhere('total', 'like', $search)
                ->orWhere('sent_on', 'like', $date)
                ->orWhere('pocomos_pest_estimates.date_created', 'like', $date)
                ;
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $query->count();
        $query->skip($perPage * ($page - 1))->take($perPage);

        $result = $query->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Estimate report']), $result);
        /*
        {% if estimate.status == 'Sent' %}
                            {{estimate.sentOn|date("m/d/Y")}}
                            {% else %}
                            Not Sent
                            {% endif %}
        */
    }

    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $sentOn = date("Y-m-d", strtotime($request->sent_on));

        if ($request->product_id || $request->service_id) {
            $productId = $request->product_id;
            $taxRate = $request->tax_rate;
            $cost = $request->cost;
            $qty = $request->qty;

            $subTotal = $cost * $qty;
            $totalTax = $subTotal * $taxRate / 100;
            $total = $subTotal + $totalTax;

            $discount = $request->discount;

            $finalTotal = $total - $discount;
        } else {
            $subTotal = 0.00;
            $discount = 0.00;
            $finalTotal = 0.00;
        }

        $estimate = PocomosPestEstimates::findOrFail($id)->update([
            'office_id' => $request->office_id,
            'customer_id' => $request->customer_id,
            'lead_id' => $request->lead_id,
            'sent_on' => $sentOn,
            'po_number' => $request->po_number,
            'name' => $request->name,
            'subtotal' => $subTotal,
            'discount' => $discount,
            'total' => $finalTotal,
            'terms' => $request->terms,
            'note' => $request->note,
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Estimate']), $estimate);
    }

    public function updateStatus(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $estimate = PocomosPestEstimates::findOrFail($id)->update([
            'status' => $request->status
        ]);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Status']), $estimate);
    }

    public function delete(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $estimate = PocomosPestEstimates::findOrFail($id)->delete();

        return $this->sendResponse(true, __('strings.delete', ['name' => 'Estimate']), $estimate);
    }


    public function downloadPdf(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestEstimateProducts = PocomosPestEstimateProducts::with(['product_data', 'tax_details'])->first();

        $items = $PocomosPestEstimateProducts->product_data->name;
        $Description = $PocomosPestEstimateProducts->product_data->description;
        $Cost = $PocomosPestEstimateProducts->cost;
        $Qty = $PocomosPestEstimateProducts->quantity;
        $Tax = $PocomosPestEstimateProducts->tax_details->tax_rate;
        $Total = $PocomosPestEstimateProducts->amount;

        $variables = ['{{Items}}', '{{Description}}', '{{Cost}}', '{{Qty}}', '{{Tax}}', '{{Total}}'];

        $values = [$items, $Description, $Cost, $Qty, $Tax, $Total];

        // $estimateReport = str_replace($variables, $values, implode(',',$values));
        $estimateReport = implode(',', $values);

        $pdf = PDF::loadView('pdf.estimate_report', compact('estimateReport'));

        return $pdf->download('estimate_report_' . $id . '.pdf');
    }
}
