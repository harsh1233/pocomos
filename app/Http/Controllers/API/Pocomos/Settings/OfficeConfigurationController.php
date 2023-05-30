<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Orkestra\OrkestraCredential;
use App\Models\Pocomos\PocomosOfficeSetting;
use App\Models\Pocomos\PocomosPestOfficeSetting;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class OfficeConfigurationController extends Controller
{
    use Functions;

    /**
     * API for update Form Letter
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'theme' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeSetting = PocomosOfficeSetting::where('office_id', $request->office_id)
            ->first();

        if (!$PocomosOfficeSetting) {
            return $this->sendResponse(false, 'Office not found.');
        }

        $PocomosOfficeSetting->update(
            $request->only('theme')
        );

        return $this->sendResponse(true, 'Office updated successfully.', $PocomosOfficeSetting);
    }

    /**Get available themes */
    public function getAvailableThemes()
    {
        /**Get themes */
        $themes = config('themes');

        return $this->sendResponse(true, __('strings.list', ['name' => 'Available themes']), $themes);
    }

    /**
     * Edits an existing OfficeConfiguration entity.
     * @Route("/update-gateway-credentials/{officeId}", name="office_gateway_credentials_update", defaults={"_format"="json"})
     * @Secure(roles="ROLE_OFFICE_WRITE")
     */
    public function updateGatewayCredentials(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'card_credentials.transactor' => "required",
            'card_credentials.username' => "required",
            'card_credentials.password' => "required",
            'card_credentials.account_id' => "required",
            'ach_credentials.transactor' => "required",
            'ach_credentials.username' => "required",
            'ach_credentials.password' => "required",
            'ach_credentials.account_id' => "required",
            'ach_credentials.sec_code' => "required",
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeSetting= PocomosOfficeSetting::whereOfficeId($request->office_id)->firstOrFail();

        $card_credentials = $request->card_credentials;
        $ach_credentials = $request->ach_credentials;

        $cardCredentials['username'] = $card_credentials['username'] ?? '';
        $cardCredentials['password'] = $card_credentials['password'] ?? '';
        $cardCredentials['account_id'] = $card_credentials['account_id'] ?? '';
        $cardCredentials = serialize($cardCredentials);

        $cardUpdateDetails['credentials'] = $cardCredentials;
        $cardUpdateDetails['transactor'] = $card_credentials['transactor'] ?? '';
        $cardUpdateDetails['active'] = true;

        if ($officeSetting->card_credentials_id) {
            $cardDetail = OrkestraCredential::find($officeSetting->card_credentials_id)->update($cardUpdateDetails);
            $cardDetail = OrkestraCredential::find($officeSetting->card_credentials_id);
        } else {
            $cardDetail = OrkestraCredential::create($cardUpdateDetails);
        }

        $achCredentials['username'] = $ach_credentials['username'] ?? '';
        $achCredentials['password'] = $ach_credentials['password'] ?? '';
        $achCredentials['account_id'] = $ach_credentials['account_id'] ?? '';
        $achCredentials['sec_code'] = $ach_credentials['sec_code'] ?? '';
        $achCredentials = serialize($achCredentials);

        $achUpdateDetails['credentials'] = $achCredentials;
        $achUpdateDetails['transactor'] = $ach_credentials['transactor'] ?? '';
        $achUpdateDetails['active'] = true;

        if ($officeSetting->ach_credentials_id) {
            $achDetail = OrkestraCredential::find($officeSetting->ach_credentials_id)->update($achUpdateDetails);
            $achDetail = OrkestraCredential::find($officeSetting->ach_credentials_id);
        } else {
            $achDetail = OrkestraCredential::create($achUpdateDetails);
        }

        $officeSetting->card_credentials_id = $cardDetail->id;
        $officeSetting->ach_credentials_id = $achDetail->id;
        $officeSetting->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Office configuration']));
    }

    /**Get office credentials details */
    public function getOffceCredentialsDetails($officeId)
    {
        $officeSetting= PocomosOfficeSetting::with('ach_cred_details', 'cash_cred_details', 'check_cred_details', 'card_cred_details', 'points_cred_details', 'external_cred_details')->whereOfficeId($officeId)->firstOrFail();
        return $this->sendResponse(true, __('strings.details', ['name' => 'Office configuration']), $officeSetting);
    }

    /**
     * Displays a form to edit an existing OfficeConfiguration entity.
     */
    public function clearTokens($officeId)
    {
        $sql = " UPDATE orkestra_accounts SET account_token = NULL WHERE id IN (SELECT id FROM (
                 SELECT oa.id FROM pocomos_customers pc JOIN pocomos_customer_sales_profiles pcsp on pc.id = pcsp.customer_id
                 JOIN pocomos_customers_accounts pca on pcsp.id = pca.profile_id
                 JOIN orkestra_accounts oa on pca.account_id = oa.id 
                 WHERE office_id = $officeId and pc.status = 'Active' AND account_token IS NOT NULL AND oa.active = 1) as x )";

        DB::select(DB::raw($sql));
        return $this->sendResponse(true, __('strings.sucess', ['name' => 'Tokens cleared']));
    }
}
