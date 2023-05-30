<?php

namespace App\Http\Controllers\API\Pocomos\PestRoutes;

use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosPestRoutesConfig;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use App\Http\Controllers\Functions;

use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    use Functions;


    /**
     * API for Pest Route Setting Edit
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'     => 'integer|min:1',
            'auth_key'      => 'required|string',
            'auth_token'    => 'required|string',
            'sub_domain'    => 'required|string',
            'enabled'       => 'required|in:0,1',
            'active'        => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $office =  PocomosCompanyOffice::findOrFail($request->office_id);

        $PocomosPestRoute = PocomosPestRoutesConfig::updateOrCreate(
            [
            'office_id'   => $office->id
        ],
            [   'auth_key'    => $request->auth_key,
                'auth_token'  => $request->auth_token,
                'sub_domain'  => $request->sub_domain,
                'enabled'     => $request->enabled,
                'active'      => $request->active
            ]
        );

        return $this->sendResponse(true, 'Updated successfully.', $PocomosPestRoute);
    }


    public function get($id)
    {
        $PocomosPestRoute = PocomosPestRoutesConfig::whereOfficeId($id)->first();
        if (!$PocomosPestRoute) {
            return $this->sendResponse(false, ' Not Found');
        }
        return $this->sendResponse(true, 'Details.', $PocomosPestRoute);
    }
}
