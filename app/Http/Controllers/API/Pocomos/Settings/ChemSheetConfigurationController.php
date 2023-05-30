<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosTaxCode;
use App\Models\Pocomos\PocomosInvoiceItems;
use App\Models\Pocomos\PocomosJobsProducts;
use App\Models\Pocomos\PocomosJobsProductsAreas;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\PocomosPestInvoiceSetting;
use App\Models\Pocomos\PocomosPestOfficeDefaultInvoiceNote;
use App\Models\Pocomos\PocomosPestOfficeDefaultChemsheetSettings;
use App\Models\Pocomos\PocomosPestOfficeDefaultChemsheetsProducts;
use Illuminate\Support\Facades\Session;

class ChemSheetConfigurationController extends Controller
{
    use Functions;

    /**
     * API for list of Invoice Note
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $pestOfficeDefaultInvoiceNote = PocomosPestOfficeDefaultInvoiceNote::where('deleted', false)->where('office_configuration_id', $pestOfficeConfig->id);

        if ($request->search) {
            $search = $request->search;

            if ($search == 'Yes' || $search == 'yes') {
                $search = 1;
            } elseif ($search == 'No' || $search == 'no') {
                $search = 0;
            }

            $pestOfficeDefaultInvoiceNote = $pestOfficeDefaultInvoiceNote->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('active', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $pestOfficeDefaultInvoiceNote->count();
        $allIds = $pestOfficeDefaultInvoiceNote->orderBy('position', 'asc')->pluck('id');
        $pestOfficeDefaultInvoiceNote = $pestOfficeDefaultInvoiceNote->skip($perPage * ($page - 1))->take($perPage)->orderBy('position', 'asc')->get();

        $data = [
            'technician_note_templates' => $pestOfficeDefaultInvoiceNote,
            'count' => $count,
            'all_ids' => $allIds
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Technician note templates']), $data);
    }

    /**
     * API for details of Invoice Note
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosPestOfficeDefaultInvoiceNote = PocomosPestOfficeDefaultInvoiceNote::findOrFail($id);
        if (!$PocomosPestOfficeDefaultInvoiceNote) {
            return $this->sendResponse(false, 'Invoice Note Not Found');
        }
        return $this->sendResponse(true, 'Invoice Note details.', $PocomosPestOfficeDefaultInvoiceNote);
    }

    /**
     * API for create of Invoice Note
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
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $query = PocomosPestOfficeDefaultInvoiceNote::query();

        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $position = (($position = (clone ($query))->where('deleted', false)->where('office_configuration_id', $pestOfficeConfig->id)->orderBy('position', 'desc')->first()) ? $position->position + 1 : 1);

        $input_details = $request->only('name', 'description', 'active') + ['position' => $position, 'office_configuration_id' => $pestOfficeConfig->id];

        $PocomosPestOfficeDefaultInvoiceNote =  (clone ($query))->create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Invoice Note created successfully.', $PocomosPestOfficeDefaultInvoiceNote);
    }

    /**
     * API for update of Invoice Note
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'invoice_note_id' => 'required|exists:pocomos_pest_office_default_invoice_note,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'description' => 'nullable',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestOfficeDefaultInvoiceNote = PocomosPestOfficeDefaultInvoiceNote::findOrFail($request->invoice_note_id);

        if (!$PocomosPestOfficeDefaultInvoiceNote) {
            return $this->sendResponse(false, 'Invoice Note not found.');
        }

        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        $PocomosPestOfficeDefaultInvoiceNote = $PocomosPestOfficeDefaultInvoiceNote->update($request->only('name', 'description', 'active') + ['office_configuration_id' => $pestOfficeConfig->id]);

        return $this->sendResponse(true, 'Invoice Note updated successfully.', $PocomosPestOfficeDefaultInvoiceNote);
    }

    /**
     * API for delete of Invoice Note
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosPestOfficeDefaultInvoiceNote = PocomosPestOfficeDefaultInvoiceNote::findOrFail($id);
        if (!$PocomosPestOfficeDefaultInvoiceNote) {
            return $this->sendResponse(false, 'Invoice Note not found.');
        }

        $officeId = Session::get(config('constants.ACTIVE_OFFICE_ID'));
        $pestOfficeConfig = PocomosPestOfficeSetting::whereOfficeId($officeId)->firstOrFail();
        $nextRecord = PocomosPestOfficeDefaultInvoiceNote::where('deleted', false)->where('office_configuration_id', $pestOfficeConfig->id)->orderBy('position','asc')->pluck('id')->toArray();
        
        $PocomosPestOfficeDefaultInvoiceNote->update([
            'deleted' => 1
        ]);

        $deletePos = array_search($id,$nextRecord);
        $nextId = $nextRecord[$deletePos+1];

        $deletedPos = $PocomosPestOfficeDefaultInvoiceNote->position;
        
        return $this->sendResponse(true, 'Invoice Note deleted successfully.');
    }


    /**Chemical Templates APIs */

    /**
     * API for list of Chemical Templates
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function listCheTemplates(Request $request, $officeId)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeConfig = PocomosPestOfficeSetting::whereOfficeId($officeId)->firstOrFail();

        $pestOfficeDefaultChemsheets = PocomosPestOfficeDefaultChemsheetSettings::where('office_config_id', $officeConfig->id);

        if ($request->search) {
            $search = $request->search;

            if ($search == 'Yes' || $search == 'yes') {
                $search = 1;
            } elseif ($search == 'No' || $search == 'no') {
                $search = 0;
            }

            $pestOfficeDefaultChemsheets = $pestOfficeDefaultChemsheets->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
                $q->orWhere('active', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $count = $pestOfficeDefaultChemsheets->count();
        if ($request->page && $request->perPage) {
            $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
            $page    = $paginateDetails['page'];
            $perPage = $paginateDetails['perPage'];
            $pestOfficeDefaultChemsheets = $pestOfficeDefaultChemsheets->skip($perPage * ($page - 1))->take($perPage);
        }
        $pestOfficeDefaultChemsheets = $pestOfficeDefaultChemsheets->orderBy('id', 'desc')->get();

        $data = [
            'chemical_templates' => $pestOfficeDefaultChemsheets,
            'count' => $count
        ];
        return $this->sendResponse(true, __('strings.list', ['name' => 'Chemical Templates']), $data);
    }

    /**
     * API for create of Chemical Template
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function createCheTemplates(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'active' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeConfig = PocomosPestOfficeSetting::whereOfficeId($request->office_id)->firstOrFail();
        $input_details = $request->only('name', 'active');
        $input_details['office_config_id'] = $officeConfig->id;

        $res =  PocomosPestOfficeDefaultChemsheetSettings::create($input_details);

        /**End manage trail */
        return $this->sendResponse(true, __('strings.create', ['name' => 'Chemical Template']), $res);
    }

    /**
     * API for details of Invoice Note
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function getCheTemplate($id)
    {
        $res = PocomosPestOfficeDefaultChemsheetSettings::with('product_detail.job_product_detail.invoice_detail', 'product_detail.job_product_detail.application_detail', 'product_detail.job_product_detail.product', 'product_detail.job_product_detail.area_detail.area')->findOrFail($id);

        if (!$res) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Chemical Template']));
        }

        return $this->sendResponse(true, __('strings.details', ['name' => 'Chemical Template']), $res);
    }

    /**
     * API for update of Chemical Template
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function updateCheTemplate(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'name' => 'required',
            'active' => 'required|boolean',
            'master_service' => 'required|boolean',
            'unit' => 'nullable',
            'amount' => 'nullable',
            'products' => 'required|array',
            'products.*.product_id' => 'nullable|exists:pocomos_pest_products,id',
            'products.*.job_product_id' => 'nullable|exists:pocomos_jobs_products,id',
            'products.*.dilution_rate' => 'nullable',
            'products.*.dilution_unit' => 'nullable',
            'products.*.dilution_quantity' => 'nullable',
            'products.*.dilution_liquid_unit' => 'nullable',
            'products.*.action' => 'nullable|in:delete,add,edit',
            'products.*.area_applid' => 'nullable|exists:pocomos_areas,id',
            'products.*.service_id' => 'nullable|exists:pocomos_services,id',
            'products.*.amount' => 'nullable',
            'products.*.unit' => 'nullable',
            'products.*.application_rate' => 'nullable',
            'products.*.price' => 'nullable',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeConfig = PocomosPestOfficeSetting::whereOfficeId($request->office_id)->firstOrFail();
        $res = PocomosPestOfficeDefaultChemsheetSettings::findOrFail($id);

        if (!$res) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Chemical Template']));
        }

        $tax_code = PocomosTaxCode::where('office_id', $request->office_id)->inRandomOrder()->first();

        if (!$tax_code) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Office Tax']));
        }

        DB::beginTransaction();
        try {
            // $res->office_config_id = $request->office_config_id ?? null;
            $res->name = $request->name ?? null;
            $res->active = $request->active ?? 0;

            if (isset($request->master_service) && $request->master_service == 1) {
                $res->master_service = $request->master_service;
                $res->amount = $request->amount;
                $res->unit = $request->unit;
            }
            $res->save();

            $applidProducts = array();
            foreach ($request->products as $product) {
                $jobsProduct['product_id'] = $product['product_id'] ?? null;
                $jobsProduct['service_id'] = $product['service_id'] ?? null;
                $jobsProduct['dilution_rate'] = $product['dilution_rate'] ?? null;
                $jobsProduct['dilution_unit'] = $product['dilution_unit'] ?? null;
                $jobsProduct['dilution_quantity'] = $product['dilution_quantity'] ?? null;
                $jobsProduct['dilution_liquid_unit'] = $product['dilution_liquid_unit'] ?? null;
                $jobsProduct['application_rate'] = $product['application_rate'] ?? null;
                $jobsProduct['amount'] = $product['amount'] ?? 0;
                $jobsProduct['active'] = $request->active ?? 0;
                // $jobsProduct['invoice_item_id'] = null;

                $invoiceItems['description'] = 'Description';
                $invoiceItems['price'] = $product['price'] ?? 0;
                $invoiceItems['active'] = $request->active ?? 0;
                $invoiceItems['sales_tax'] = $tax_code->tax_rate ?? 0;
                $invoiceItems['tax_code_id'] = $tax_code->id ?? null;
                $invoiceItems['type'] = 'Type';

                if ($product['action'] == 'edit') {
                    $pocomosProduct = PocomosJobsProducts::findOrFail($product['job_product_id']);

                    if ($pocomosProduct) {
                        if ($pocomosProduct['invoice_item_id']) {
                            $pocomosInvoice = PocomosInvoiceItems::findOrFail($pocomosProduct['invoice_item_id']);

                            if ($pocomosInvoice) {
                                $pocomosInvoice->update($invoiceItems);
                            }
                        }
                        $pocomosProduct->update($jobsProduct);

                        PocomosPestOfficeDefaultChemsheetsProducts::where('configuration_id', $id)
                            ->where('product_id', $product['job_product_id'])
                            ->delete();

                        PocomosPestOfficeDefaultChemsheetsProducts::create(['configuration_id' => $id, 'product_id' => $product['job_product_id']]);

                        PocomosJobsProductsAreas::where('applied_product_id', $product['job_product_id'])
                            ->delete();

                        foreach ($product['area_applid'] as $value) {
                            PocomosJobsProductsAreas::create(['applied_product_id' => $product['job_product_id'], 'area_id' => $value]);
                        }
                    }
                } elseif ($product['action'] == 'add') {
                    $invoice = PocomosInvoiceItems::create($invoiceItems);

                    $jobsProduct['invoice_item_id'] = $invoice->id;

                    $jobProduct = PocomosJobsProducts::create($jobsProduct);

                    PocomosPestOfficeDefaultChemsheetsProducts::create(['configuration_id' => $id, 'product_id' => $jobProduct->id]);

                    foreach ($product['area_applid'] as $value) {
                        PocomosJobsProductsAreas::create(['applied_product_id' => $jobProduct->id, 'area_id' => $value]);
                    }
                } elseif ($product['action'] == 'delete') {
                    $pocomosProduct = PocomosJobsProducts::findOrFail($product['job_product_id']);

                    if ($pocomosProduct) {
                        if ($pocomosProduct['invoice_item_id']) {
                            $pocomosInvoice = PocomosInvoiceItems::findOrFail($pocomosProduct['invoice_item_id']);

                            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                            $pocomosInvoice->delete();
                            $pocomosProduct->delete();
                            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                        }

                        PocomosPestOfficeDefaultChemsheetsProducts::where('configuration_id', $id)
                            ->where('product_id', $product['job_product_id'])
                            ->delete();

                        PocomosJobsProductsAreas::where('applied_product_id', $product['job_product_id'])
                            ->delete();
                    }
                }
            }

            /**If need the all product delete then delete auto chem sheet then un comment below */
            // $isDelete = PocomosPestOfficeDefaultChemsheetsProducts::where('configuration_id', $id)->count();
            // if (!$isDelete) {
            //     PocomosPestOfficeDefaultChemsheetSettings::findOrFail($id)->delete();
            // }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception(__('strings.message', ['message' => $e->getMessage()]));
        }
        return $this->sendResponse(true, __('strings.update', ['name' => 'Chemical Template']));
    }

    /**
     * API for delete of Chemical Template
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function deleteCheTemplate($id)
    {
        $res = PocomosPestOfficeDefaultChemsheetSettings::findOrFail($id);
        if (!$res) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Chemical Template']));
        }

        $res->delete();

        return $this->sendResponse(true, __('strings.delete', ['name' => 'Chemical Template']));
    }

    /**
     * API for update of Template configuration
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function updateTemplateConfiguration(Request $request)
    {
        $v = validator($request->all(), [
            'show_application_rate_on_template' => 'required|boolean',
            'show_dilution_rate_on_template' => 'required|boolean',
            'show_technician_note_template' => 'required|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestInvoiceSetting = PocomosPestInvoiceSetting::where('office_id', $request->office_id)->first();
        if (!$PocomosPestInvoiceSetting) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Pest Invoice Setting']));
        }

        $update_details = $request->only('show_application_rate_on_template', 'show_dilution_rate_on_template', 'show_technician_note_template');

        if (count($update_details)) {
            $PocomosPestInvoiceSetting->update($update_details);
        }

        return $this->sendResponse(true, __('strings.update', ['name' => 'Pest Invoice Setting']), $PocomosPestInvoiceSetting);
    }

    /**
     * API for update of Template configuration
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function getTemplateConfiguration(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestInvoiceSetting = PocomosPestInvoiceSetting::where('office_id', $request->office_id)->select('show_application_rate_on_template', 'show_dilution_rate_on_template', 'show_technician_note_template')->first();

        if (!$PocomosPestInvoiceSetting) {
            return $this->sendResponse(false, __('strings.not_found', ['name' => 'Pest Invoice Setting']));
        }

        return $this->sendResponse(true, 'Pest Invoice Setting.', $PocomosPestInvoiceSetting);
    }

    /**
     * API for reorder of Technical Note Template
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function reorder(Request $request)
    {
        $v = validator($request->all(), [
            'all_ids.*' => 'required|exists:pocomos_pest_office_default_invoice_note,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $allIds = $request->all_ids;

        $i = 1;
        foreach ($allIds as $value) {
            DB::select(DB::raw("UPDATE pocomos_pest_office_default_invoice_note SET position = $i WHERE id = $value"));
            $i++;
        }

        return $this->sendResponse(true, __('strings.reorder', ['name' => 'Technician Note Template']));
    }

    public function reorderNoteTemplate($office_id, $pos, $id)
    {
        $pestOfficeConfig = PocomosPestOfficeSetting::where('office_id', $office_id)->first();

        $pocomosPestOfficeDefaultInvoiceNote = PocomosPestOfficeDefaultInvoiceNote::findOrFail($id);

        $is_reordered = false;
        $newPosition = $pos;
        $originalPosition = $pocomosPestOfficeDefaultInvoiceNote->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $videos = PocomosPestOfficeDefaultInvoiceNote::where('deleted', false)->where('office_configuration_id', $pestOfficeConfig->id)->orderBy('position', 'asc')->get();

            foreach ($videos as $key => $value) {
                $detail = PocomosPestOfficeDefaultInvoiceNote::findOrFail($value->id);
                if ($value->id == $id) {
                    $position = $newPosition;
                }else if(isset($videos[$key-1]) && ($videos[$key-1]['position'] > $value['position'])&& (($videos[$key-1]['position'] - $value['position']) > 1)){
                    $position = $videos[$key-1]['position'] + 1;
                } else {
                    $position = $detail->position;
                    if ($movedDown) {
                        if ($position > $originalPosition && $position <= $newPosition) {
                            $position--;
                        }
                    } elseif ($position <= $originalPosition && $position >= $newPosition) {
                        $position++;
                    }
                }
                $detail->position = $position;
                $detail->save();
            }
        }
        return true;
    }
    /**End Chemical Templates APIs */
}
