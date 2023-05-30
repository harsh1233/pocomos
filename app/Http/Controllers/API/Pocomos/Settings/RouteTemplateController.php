<?php

namespace App\Http\Controllers\API\Pocomos\Settings;

use DB;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Models\Pocomos\PocomosTeam;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosRoute;
use App\Models\Pocomos\PocomosTechnician;
use App\Models\Pocomos\PocomosOfficeWidget;
use App\Models\Pocomos\PocomosRouteTemplate;

use App\Models\Pocomos\PocomosPestOfficeSetting;

use function Ramsey\Uuid\v1;

class RouteTemplateController extends Controller
{
    use Functions;

    /**
     * API for list of OfficeWidget
     .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function list($id)
    {
        $PocomosOfficeWidget = DB::table('pocomos_office_widgets as sa')->join('pocomos_admin_widgets as ca', 'ca.id', '=', 'sa.admin_widget_id')->where('sa.office_id', $id)->where('sa.active', 1)->where('ca.active', 1)->where('ca.enabled', 1)->get();

        return $this->sendResponse(true, 'Lists all OfficeWidget entities.', $PocomosOfficeWidget);
    }

    /**
     * API for details of OfficeWidget
     .
     * @param  \Integer  $id
     * @return \Illuminate\Http\Response
     */

    public function get($id)
    {
        $PocomosOfficeWidget = DB::table('pocomos_office_widgets as sa')->join('pocomos_admin_widgets as ca', 'ca.id', '=', 'sa.admin_widget_id')->where('sa.id', $id)->first();

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
            'admin_widget_id' => 'required|integer|min:1',
            'active' => 'boolean',
            'enabled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('office_id', 'admin_widget_id', 'active', 'enabled');

        $PocomosOfficeWidget =  PocomosOfficeWidget::create($input_details);

        return $this->sendResponse(true, 'OfficeWidget created successfully.', $PocomosOfficeWidget);
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
            'office_widget_id' => 'required|integer|min:1',
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'admin_widget_id' => 'required|integer|min:1',
            'active' => 'boolean',
            'enabled' => 'boolean',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $PocomosOfficeWidget = PocomosOfficeWidget::find($request->office_widget_id);

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
            'enabled' => $request->enabled
        ]);

        return $this->sendResponse(true, 'Status changed successfully.');
    }

    /**List route templates */
    public function listRouteTemplates(Request $request)
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

        $templates = PocomosRouteTemplate::with('technician_detail.user_detail.user_details')->where('office_id', $request->office_id)->where('active', true);

        if ($request->search) {
            $search = $request->search;

            $tempTechIds = DB::select(DB::raw("SELECT pt.id
            FROM pocomos_technicians AS pt
            JOIN pocomos_company_office_users AS cou ON pt.user_id = cou.id
            JOIN orkestra_users AS ou ON cou.user_id = ou.id
            WHERE (ou.first_name LIKE '%$search%' OR ou.last_name LIKE '%$search%')"));

            $techIds = array_map(function ($value) {
                return $value->id;
            }, $tempTechIds);

            $templates = $templates->where(function ($q) use ($search, $techIds) {
                $q->where('id', 'like', '%'.$search.'%');
                $q->orWhere('name', 'like', '%'.$search.'%');
                $q->orWhere('frequency_days', 'like', '%'.$search.'%');
                $q->orWhereIn('technician_id', $techIds);
            });
        }

        /**For pagination */
        $paginateDetails = $this->getPaginationDetails($request->page, $request->perPage);
        $page    = $paginateDetails['page'];
        $perPage = $paginateDetails['perPage'];
        $count = $templates->count();
        $templates = $templates->skip($perPage * ($page - 1))->take($perPage)->orderBy('id', 'desc')->get();

        $data = [
            'templates' => $templates,
            'count' => $count
        ];

        return $this->sendResponse(true, __('strings.list', ['name' => 'Route templates']), $data);
    }

    /**Update routes templates configuration */
    public function updateRouteTemplatesConfigurations(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'route_template_days' => 'integer|required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeConfiguration = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();
        $officeConfiguration->route_template_days = $request->route_template_days;
        $officeConfiguration->save();

        return $this->sendResponse(true, __('strings.update', ['name' => 'Route Templates Configurations has been']));
    }

    /**Delete route template */
    public function deleteRouteTemplate($id)
    {
        $template = PocomosRouteTemplate::findOrFail($id);
        $template->active = false;
        $template->save();

        return $this->sendResponse(true, __('strings.delete', ['name' => 'The Route Template has been']));
    }

    /**Create route template */
    public function createRouteTemplate(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'frequency_days' => 'nullable|array',
            'template' => 'nullable',
            'template.date_scheduled' => 'required',
            'template.name' => 'required',
            'template.slots' => 'nullable|array',
            'template.technician' => 'nullable',
            'template.assignments' => 'nullable|array',
            'template.begin_time' => 'nullable',
            'template.end_time' => 'nullable',
            'template.day_group' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $routeTemplate = new PocomosRouteTemplate();
        $routeTemplate = $this->processRouteTemplate($request, $routeTemplate);

        return $this->sendResponse(true, __('strings.create', ['name' => 'Route Template']));
    }

    /**Route template create */
    public function processRouteTemplate($request, $routeTemplate)
    {
        $office = $request->office_id;

        $routeDetails = $request->template;
        $routeTemplate->name = $routeDetails['name'];
        $routeTemplate->template = json_encode($request->template);

        if (isset($routeDetails['technician']['id']) && is_numeric($routeDetails['technician']['id'])) {
            $technician_id = $routeDetails['technician']['id'];
            $office_id = $request->office_id;

            $technician = DB::select(DB::raw("SELECT t.*
            FROM pocomos_technicians AS t
            JOIN pocomos_company_office_users AS u ON t.user_id = u.id
            WHERE t.id = '$technician_id' AND u.office_id = $office_id"));

            if ($technician) {
                $routeTemplate->technician_id = $technician[0]->id;
            }
        } else {
            $routeTemplate->technician_id = null;
        }
        $routeTemplate->office_id = $office;
        $routeTemplate->frequency_days = serialize($request->frequency_days);
        $routeTemplate->save();
        return $routeTemplate;
    }

    /**Create route template */
    public function updateRouteTemplate(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id',
            'frequency_days' => 'required|array',
            'template' => 'required',
            'template.date_scheduled' => 'required',
            'template.name' => 'required',
            'template.slots' => 'required|array',
            'template.technician' => 'required',
            'template.assignments' => 'required|array',
            'template.begin_time' => 'nullable',
            'template.end_time' => 'nullable',
            'template.day_group' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $routeTemplate = PocomosRouteTemplate::findOrFail($id);
        $routeTemplate = $this->processRouteTemplate($request, $routeTemplate);

        return $this->sendResponse(true, __('strings.update', ['name' => 'Route Template']));
    }

    /**Routes list */
    public function routesList(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $routes = PocomosRoute::where('date_scheduled', date('Y-m-d'))->whereOfficeId($request->office_id)->get();

        return $this->sendResponse(true, __('strings.list', ['name' => 'Route templates']), $routes);
    }

    /**Get route template detail */
    public function getRouteTemplate($id)
    {
        $routeTemplate = PocomosRouteTemplate::findOrFail($id);

        return $this->sendResponse(true, __('strings.details', ['name' => 'Route Template']), $routeTemplate);
    }

    /**Get routes templates configuration */
    public function getRouteTemplateConfigurations(Request $request)
    {
        $v = validator($request->all(), [
            'office_id' => 'required|exists:pocomos_company_offices,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $officeConfiguration = PocomosPestOfficeSetting::where('office_id', $request->office_id)->firstOrFail();

        return $this->sendResponse(true, __('strings.details', ['name' => 'Route templates configurations']), $officeConfiguration);
    }
}
