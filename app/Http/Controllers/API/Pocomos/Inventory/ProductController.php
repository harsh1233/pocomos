<?php

namespace App\Http\Controllers\API\Pocomos\Inventory;

use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Pocomos\PocomosPestProduct;
use App\Models\Orkestra\PocomosPestInvoiceSetting;

class ProductController extends Controller
{
    use Functions;

    /**
     * API for list of Products.
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

        $Products = DB::table('pocomos_pest_products as sa')->leftJoin('pocomos_distributors as ca', 'ca.id', '=', 'sa.distributor_id')->leftJoin('orkestra_files as of', 'of.id', '=', 'sa.file_id')->where('sa.office_id', $request->office_id)->where('sa.active', 1)->orderBy('sa.position', 'asc')->select('sa.*', 'ca.name as DistributorName', 'of.path', 'of.filename');

        if ($request->search) {
            $search = $request->search;
            $Products->where(function ($query) use ($search) {
                $query->where('sa.name', 'like', '%' . $search . '%')
                    ->orWhere('sa.description', 'like', '%' . $search . '%')
                    ->orWhere('sa.epa_code', 'like', '%' . $search . '%')
                    ->orWhere('sa.position', 'like', '%' . $search . '%')
                    ->orWhere('ca.name', 'like', '%' . $search . '%');
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $Products->count();
        $Products->skip($perPage * ($page - 1))->take($perPage);

        $Products = $Products->get();

        return $this->sendResponse(true, 'List', [
            'Form_Letter' => $Products,
            'count' => $count,
        ]);
    }

    /**
     * API for get details of Product.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $Products = DB::table('pocomos_pest_products as sa')->join('pocomos_distributors as ca', 'ca.id', '=', 'sa.distributor_id')->where('sa.id', $id)->select('sa.*', 'ca.name as DistributorName')->get();

        if (!$Products) {
            return $this->sendResponse(false, 'Product Not Found');
        }

        return $this->sendResponse(true, 'Product details.', $Products);
    }

    /**
     * API for add details of Product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'unit' => 'nullable',
            'epa_code' => 'nullable',
            'threshold' => 'nullable|integer',
            'distributor_id' => 'nullable|exists:pocomos_distributors,id',
            'shows_on_estimates' => 'boolean',
            'enable_dilution_rate' => 'boolean',
            'default_dilution_rate' => 'nullable',
            'enable_application_rate' => 'boolean',
            'default_application_rate' => 'nullable',
            'shows_on_invoices' => 'boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'file' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'description', 'distributor_id', 'shows_on_estimates', 'enable_dilution_rate', 'default_dilution_rate', 'default_dilution_unit', 'default_dilution_quantity', 'default_dilution_liquid_unit', 'enable_application_rate', 'default_application_rate', 'shows_on_invoices', 'office_id');

        $input_details['unit'] =   $request->unit ?? '';
        $input_details['epa_code'] =   $request->epa_code ?? '';
        $input_details['threshold'] =   $request->threshold ?? '';
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

        $newPosition = PocomosPestProduct::orderBy('position', 'desc')->first();

        $input_details['position'] = (int)$newPosition->position + 1;
        $PocomosPestProduct =  PocomosPestProduct::create($input_details);

        return $this->sendResponse(true, 'Product created successfully.', $PocomosPestProduct);
    }

    /**
     * API for update details of Product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'product_id' => 'required|exists:pocomos_pest_products,id',
            'name' => 'required',
            'description' => 'required',
            'unit' => 'nullable',
            'epa_code' => 'nullable',
            'threshold' => 'nullable|integer',
            'distributor_id' => 'nullable|exists:pocomos_distributors,id',
            'shows_on_estimates' => 'boolean',
            'enable_dilution_rate' => 'boolean',
            'default_dilution_rate' => 'nullable',
            'enable_application_rate' => 'boolean',
            'default_application_rate' => 'nullable',
            'shows_on_invoices' => 'boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'file' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,jpeg,png,gif|max:20480',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestProduct = PocomosPestProduct::where('id', $request->product_id)->where('office_id', $request->office_id)->where('active', 1)->first();

        if (!$PocomosPestProduct) {
            return $this->sendResponse(false, 'Product not found.');
        }

        $input_details = $request->only('name', 'description', 'distributor_id', 'shows_on_estimates', 'enable_dilution_rate', 'default_dilution_rate', 'default_dilution_unit', 'default_dilution_quantity', 'default_dilution_liquid_unit', 'enable_application_rate', 'default_application_rate', 'shows_on_invoices', 'office_id');

        $input_details['unit'] =   $request->unit ?? $PocomosPestProduct->unit;
        $input_details['epa_code'] =   $request->epa_code ?? $PocomosPestProduct->epa_code;
        $input_details['threshold'] = $request->threshold ?? $PocomosPestProduct->threshold;

        // File Details
        if ($request->file('file')) {
            $OrkestraFile = OrkestraFile::find($PocomosPestProduct->file_id);

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

        $PocomosPestProduct->update($input_details);

        return $this->sendResponse(true, 'Product updated successfully.', $PocomosPestProduct);
    }

    /**
     * API for delete details of Product.
     *
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function delete($id)
    {
        $PocomosPestProduct = PocomosPestProduct::where('id', $id)->where('active', 1)->first();

        if (!$PocomosPestProduct) {
            return $this->sendResponse(false, 'Product not found.');
        }

        $PocomosPestProduct->update([
            'active' => 0
        ]);

        return $this->sendResponse(true, 'Product deleted successfully.');
    }

    /* API for changeStatus of product */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'product_id' => 'required|exists:pocomos_pest_products,id',
            'enabled' => 'required|boolean',
            'office_id' => 'required|exists:pocomos_company_offices,id',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosPestProduct = PocomosPestProduct::where('id', $request->product_id)->where('office_id', $request->office_id)->where('active', 1)->first();

        if (!$PocomosPestProduct) {
            return $this->sendResponse(false, 'Product not found');
        }

        $PocomosPestProduct->update([
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }

    /**Download SDS file */
    public function downlaodSDS($id)
    {
        $product = PocomosPestProduct::findOrFail($id);
        $file_detail = $product->file_detail;

        /**Get local storage file content */
        $file = Storage::disk('local')->get($file_detail->path);

        return (new Response($file, 200))
            ->header('Content-Type', $file_detail->mime_type);
    }

    /**Product reorder */
    public function reorder(Request $request, $id)
    {
        $v = validator($request->all(), [
            'pos' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $product = PocomosPestProduct::findOrFail($id);

        $is_reordered = false;
        $newPosition = $request->pos;
        $originalPosition = $product->position;

        if ($newPosition === $originalPosition) {
            $is_reordered = true;
        }

        if (!$is_reordered) {
            $movedDown = $newPosition > $originalPosition;
            $products = PocomosPestProduct::where('active', true)->orderBy('id', 'asc')->get();
            foreach ($products as $value) {
                $detail = PocomosPestProduct::find($value->id);
                if ($value->id == $id) {
                    $position = $newPosition;
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

        return $this->sendResponse(true, __('strings.reorder', ['name' => 'Product']));
    }
}
