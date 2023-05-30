<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosOfficeWidget;
use DB;
use App\Models\Pocomos\PocomosAdminWidget;

class OfficeWidgetController extends Controller
{
    use Functions;

    /**
     * API for list of OfficeWidget
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list(Request $request, $id)
    {
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'search' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeWidgets = PocomosOfficeWidget::select(
            '*',
            'pocomos_office_widgets.id',
            'pocomos_office_widgets.enabled'
        )
                            ->join('pocomos_admin_widgets as paw', 'pocomos_office_widgets.admin_widget_id', 'paw.id')
                            ->where('pocomos_office_widgets.active', 1)
                            ->where('paw.active', 1)
                            ->where('paw.enabled', 1)
                            ->where('pocomos_office_widgets.office_id', $id);

        if ($request->enabled) {
            $officeWidgets->where('pocomos_office_widgets.enabled', true);
        }

        $officeWidgets->orderBy('pocomos_office_widgets.position');


        /* if ($request->search) {
            $search = $request->search;
            if ($search == 'Enabled' || $search == 'enabled') {
                $search = 1;
            } elseif ($search == 'Disabled' || $search == 'disabled') {
                $search = 0;
            }

            $officeWidgets = $officeWidgets->where(function ($q) use ($search) {
                $q->where('sa.name', 'like', '%' . $search . '%');
                $q->orWhere('sa.enabled', $search);
            });
        } */

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $officeWidgets->count();
        $allIds = $officeWidgets->orderBy('position', 'asc')->pluck('id');
        $officeWidgets = $officeWidgets->skip($perPage * ($page - 1))->take($perPage)->orderBy('pocomos_office_widgets.id', 'desc')->get();

        $data = [
            'office_widget' => $officeWidgets,
            'count' => $count,
            'all_ids' => $allIds
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Office widget']), $data);
    }

    /**
     * API for details of OfficeWidget
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosOfficeWidget = DB::table('pocomos_admin_widgets as sa')
                ->leftJoin('pocomos_office_widgets as ca', 'sa.id', '=', 'ca.admin_widget_id')
                ->where('ca.id', $id)->first();

        if (!$PocomosOfficeWidget) {
            return $this->sendResponse(false, 'OfficeWidget Not Found');
        }

        return $this->sendResponse(true, 'OfficeWidget details.', $PocomosOfficeWidget);
    }

    /**
     * API for create of OfficeWidget entities
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            // 'admin_widget_id' => 'required|exists:pocomos_admin_widgets,id',
            'admin_widget_id' => 'required|exists:pocomos_admin_widgets,id|unique:pocomos_office_widgets,admin_widget_id,null,office_id,office_id,'.$request->office_id,
            'enabled' => 'required|boolean',
        ], [
            'admin_widget_id.unique' => 'This widget is already assigned to the current office.'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /* $query = PocomosOfficeWidget::query();

        $position = (($position = (clone ($query))->orderBy('position', 'desc')->first()) ? $position->position + 1 : 1);

        $input_details = $request->only('office_id',  'admin_widget_id',  'enabled') + ['active' => true, 'position' => $position];

        $PocomosOfficeWidget =  (clone ($query))->create($input_details); */

        $officeWidget = PocomosOfficeWidget::whereActive(1)->whereOfficeId($request->office_id)
                                            ->orderBy('position', 'desc')->first();

        $position = $officeWidget ? $officeWidget->position + 1 : 1;

        PocomosOfficeWidget::create([
            'office_id' => $request->office_id,
            'admin_widget_id' => $request->admin_widget_id,
            'enabled' => $request->enabled,
            'position' => $position,
            'active' => 1,
        ]);

        return $this->sendResponse(true, 'Office widget created successfully.');
    }

    /**
     * API for update of SalesAlertConfiguration entities
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        $v = validator($request->all(), [
            'office_widget_id' => 'required|exists:pocomos_office_widgets,id',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'admin_widget_id' => 'required|exists:pocomos_admin_widgets,id|unique:pocomos_office_widgets,admin_widget_id,'.$request->office_widget_id.',id,office_id,'.$request->office_id,
            'active' => 'boolean',
            'enabled' => 'required|boolean',
        ], [
            'admin_widget_id.unique' => 'This widget is already assigned to the current office.'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeWidget = PocomosOfficeWidget::where('office_id', $request->office_id)->where('id', $request->office_widget_id)->first();

        if (!$PocomosOfficeWidget) {
            return $this->sendResponse(false, 'Unable to find the SalesAlertConfiguration.');
        }

        $PocomosOfficeWidget->update(
            $request->only('office_id', 'admin_widget_id', 'enabled', 'active')
        );

        return $this->sendResponse(true, 'OfficeWidget updated successfully.', $PocomosOfficeWidget);
    }

    /* API for changeStatus of  Email Type Setting */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'office_widget_id' => 'required|integer|min:1',
            'enabled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeWidget = PocomosOfficeWidget::find($request->office_widget_id);
        if (!$PocomosOfficeWidget) {
            return $this->sendResponse(false, 'OfficeWidget not found');
        }

        $PocomosOfficeWidget->update([
            'enabled' => $request->enabled,
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }

    /**
     * API for reorder of OfficeWidget
     .
     *
     * @param  \Illuminate\Http\Request  $request, integer $id
     * @return \Illuminate\Http\Response
     */

    public function reorder(Request $request)
    {
        $v = validator($request->all(), [
            'all_ids.*' => 'required|exists:pocomos_office_widgets,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $allIds = $request->all_ids;

        $i = 1;
        foreach ($allIds as $value) {
            DB::select(DB::raw("UPDATE pocomos_office_widgets SET position = $i WHERE id = $value"));
            $i++;
        }

        return $this->sendResponse(true, 'Office Widget reordered successfully.');
    }
}
