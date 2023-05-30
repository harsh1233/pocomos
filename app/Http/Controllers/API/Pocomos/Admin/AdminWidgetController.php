<?php

namespace App\Http\Controllers\API\Pocomos\Admin;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosAdminWidget;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;

class AdminWidgetController extends Controller
{
    use Functions;

    /**
     * API for list of The Admin Widget
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request)
    {
        $PocomosAdminWidget = PocomosAdminWidget::orderBy('id', 'desc')->where('active', 1);

        if ($request->enabled) {
            $PocomosAdminWidget->whereEnabled(true);
        }

        $PocomosAdminWidget = $PocomosAdminWidget->get();

        return $this->sendResponse(true, 'List of The Admin Widget.', $PocomosAdminWidget);
    }

    /**
     * API for details of The Admin Widget
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosAdminWidget = PocomosAdminWidget::find($id);
        if (!$PocomosAdminWidget) {
            return $this->sendResponse(false, 'The Admin Widget Not Found');
        }
        return $this->sendResponse(true, 'The Admin Widget details.', $PocomosAdminWidget);
    }

    /**
     * API for create of The Admin Widget
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required',
            'internal_name' => 'required',
            'description' => 'required',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('name', 'internal_name', 'description', 'enabled');
        $input_details['active'] = 1;

        $PocomosAdminWidget =  PocomosAdminWidget::create($input_details);


        /**End manage trail */
        return $this->sendResponse(true, 'The Admin Widget created successfully.', $PocomosAdminWidget);
    }

    /**
     * API for update of The Admin Widget
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'adminwidget_id' => 'required|exists:pocomos_admin_widgets,id',
            'name' => 'required',
            'internal_name' => 'required',
            'description' => 'required',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAdminWidget = PocomosAdminWidget::find($request->adminwidget_id);

        if (!$PocomosAdminWidget) {
            return $this->sendResponse(false, 'The Admin Widget not found.');
        }

        $PocomosAdminWidget->update(
            $request->only('name', 'internal_name', 'description', 'enabled')
        );

        return $this->sendResponse(true, 'The Admin Widget updated successfully.', $PocomosAdminWidget);
    }

    /* API for changeStatus of The Admin Widget */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'adminwidget_id' => 'required|exists:pocomos_admin_widgets,id',
            'enabled' => 'required|boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosAdminWidget = PocomosAdminWidget::find($request->adminwidget_id);

        if (!$PocomosAdminWidget) {
            return $this->sendResponse(false, 'Unable to find Admin Widget');
        }

        $PocomosAdminWidget->update([
            'active' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.', $PocomosAdminWidget);
    }
}
