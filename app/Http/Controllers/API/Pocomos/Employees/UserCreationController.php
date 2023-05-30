<?php

namespace App\Http\Controllers\API\Pocomos\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserCreationController extends Controller
{
    /* API for create of user */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'integer',
            'code' => 'required',
            'description' => 'required',
            'tax_rate' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'code', 'description', 'tax_rate');

        $PocomosTaxCode =  PocomosTaxCode::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'Tax code created successfully.', $PocomosTaxCode);
    }
}
