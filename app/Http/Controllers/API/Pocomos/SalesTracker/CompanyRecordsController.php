<?php

namespace App\Http\Controllers\API\Pocomos\SalesTracker;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\Pocomos\PocomosReportCompanyRecord;
use App\Models\Pocomos\PocomosSalesPeople;
use App\Models\Pocomos\Admin\PocomosCompanyOffice;
use DB;

class CompanyRecordsController extends Controller
{
    use Functions;

    public function getData(Request $request)
    {
        $v = validator($request->all(), [
            'office_id'         => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $officeId = $request->office_id;
        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($officeId)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $records = PocomosReportCompanyRecord::with([
            'first_sales_person.office_user_details.user_details:id,first_name,last_name',
            'second_sales_person.office_user_details.user_details:id,first_name,last_name',
            'third_sales_person.office_user_details.user_details:id,first_name,last_name'
            ])
        ->whereOfficeId($request->office_id)->get()->toArray();

        $calculatedRecords = $this->getAllTimeData($officeId);

        $data = array(
            'serviced_day'        => $this->getRecord($officeId, $records, $calculatedRecords, 'SERVICED_DAY'),
            'serviced_week'       => $this->getRecord($officeId, $records, $calculatedRecords, 'SERVICED_WEEK'),
            'serviced_month'      => $this->getRecord($officeId, $records, $calculatedRecords, 'SERVICED_MONTH'),
            'serviced_summer'     => $this->getRecord($officeId, $records, $calculatedRecords, 'SERVICED_SUMMER'),
            'scheduled_day'       => $this->getRecord($officeId, $records, $calculatedRecords, 'SCHEDULED_DAY'),
            'scheduled_week'      => $this->getRecord($officeId, $records, $calculatedRecords, 'SCHEDULED_WEEK'),
            'scheduled_month'     => $this->getRecord($officeId, $records, $calculatedRecords, 'SCHEDULED_MONTH'),
            'scheduled_summer'    => $this->getRecord($officeId, $records, $calculatedRecords, 'SCHEDULED_SUMMER'),
        );

        return $this->sendResponse(true, __('strings.list', ['name' => 'Company records']), [$data]);
    }

    public function getRecord($officeId, $records, $calculatedRecords, $recordType)
    {
        $record = array_filter($records, function ($record) use ($recordType) {
            return $record['name'] == $recordType;
        });
        // dd(count($record));
        $record = count($record) > 0 ? array_shift($record) : null;

        if (!$record) {
            // dd('aq');
            // $record = $this->createRecord($officeId, $recordType, $calculatedRecords);
        } elseif ($record['automatic']) {
            // dd('a');
            // $this->autoFillRecord($record, $recordType, $calculatedRecords);
        }

        // $record['first_salesperson_id'] = $record['first_salesperson_id'] != null ? $record['first_salesperson_id'] : '';
        // $record['second_salesperson_id'] = $record['second_salesperson_id'] != null ? $record['second_salesperson_id'] : '';
        // $record['third_salesperson_id'] = $record['third_salesperson_id'] != null ? $record['third_salesperson_id'] : '';

        // return $record;

        if (isset($record['first_salesperson_id'])) {
            $record['first_sales_person']['office_user_details']['user_details']['full_name'] = '-';
        }

        if (isset($record['second_salesperson_id'])) {
            $record['second_sales_person']['office_user_details']['user_details']['full_name'] = '-';
        }

        if (isset($record['third_salesperson_id'])) {
            $record['third_sales_person']['office_user_details']['user_details']['full_name'] = '-';
        }

        return $record;
    }

    public function createRecord($officeId, $recordType, $calculatedRecords)
    {
        $record = new PocomosReportCompanyRecord();
        // dd($officeId);
        $record->office_id = $officeId;
        $record->name = $recordType;
        $record->active = 1;
        $record->automatic = 1;
        $this->autoFillRecord($record, $recordType, $calculatedRecords);

        return $record;
    }

    public function autoFillRecord($record, $recordType, $calculatedRecords)
    {
        // $salespersonRepo = $this->getRepository(Salesperson::class);
        // dd($calculatedRecords);
        if (isset($record['id'])) {
            $record = PocomosReportCompanyRecord::whereId($record['id'])->first();
        }
        // dd($calculatedRecords);
        if (array_key_exists(0, $calculatedRecords[$recordType])) {
            // dd('a');
            $record0 = PocomosSalesPeople::whereId($calculatedRecords[$recordType][0]->salesperson_id)->first();
            $record->first_salesperson_id = $record0 ? $record0->id : null;
            $record->first_count = $calculatedRecords[$recordType][0]->count;
        } else {
            $record->first_salesperson_id = null;
            $record->first_count = 0;
        }

        if (array_key_exists(1, $calculatedRecords[$recordType])) {
            $record->second_salesperson_id = PocomosSalesPeople::whereId($calculatedRecords[$recordType][1]['salesperson_id'])->first()->id;
            $record->second_count = $calculatedRecords[$recordType][1]['count'];
        } else {
            $record->second_salesperson_id = null;
            $record->second_count = 0;
        }

        if (array_key_exists(2, $calculatedRecords[$recordType])) {
            $record->third_salesperson_id = PocomosSalesPeople::whereId($calculatedRecords[$recordType][2]['salesperson_id'])->first()->id;
            $record->third_count = $calculatedRecords[$recordType][2]['count'];
        } else {
            $record->third_salesperson_id = null;
            $record->third_count = 0;
        }

        $record->automatic = 1;

        $record->save();
    }

    public function getAllTimeData($officeId)
    {
        $parentId = PocomosCompanyOffice::whereId($officeId)->first()->parent_id;

        $officeId = $parentId ? $parentId : $officeId;

        $baseSql = 'SELECT salesperson_id, count FROM
                    (SELECT s.id AS salesperson_id, COUNT(*) as `count`
                      FROM pocomos_salesperson_profiles sp
                        JOIN pocomos_memberships m ON m.salesperson_profile_id = sp.id
                        JOIN pocomos_salespeople s ON m.salesperson_id = s.id
                        JOIN pocomos_company_office_users ou ON s.user_id = ou.id
                        JOIN orkestra_users u ON ou.user_id = u.id
                        JOIN pocomos_contracts c ON c.salesperson_id = s.id
                        JOIN pocomos_pest_contracts pc ON pc.contract_id = c.id
                        JOIN pocomos_company_offices o ON ou.office_id = o.id AND (o.id = '.$officeId.' OR o.parent_id = '.$officeId.')';

        $servicedCriteria = ' JOIN pocomos_jobs j on j.contract_id = pc.id
                         WHERE j.date_completed IS NOT NULL';

        $scheduledCriteria = ' JOIN pocomos_jobs j on j.contract_id = pc.id
                               WHERE j.date_completed IS NULL
                               AND j.type = \'Initial\'';

        $summerServicedRange = ' AND MONTH(j.date_completed) BETWEEN 5 AND 12';
        $summerScheduledRange = ' AND MONTH(j.date_scheduled) BETWEEN 5 AND 12';

        $groupBy = ' GROUP BY s.id, ';

        $servicedDay = 'CONCAT(YEAR(j.date_completed),DAYOFYEAR(j.date_completed)) ';
        $servicedWeek = 'CONCAT(YEAR(j.date_completed),WEEKOFYEAR(j.date_completed)) ';
        $servicedMonth = 'CONCAT(YEAR(j.date_completed),MONTH(j.date_completed)) ';
        $servicedYear = 'YEAR(j.date_completed) ';

        $scheduledDay = 'CONCAT(YEAR(j.date_scheduled),DAYOFYEAR(j.date_scheduled)) ';
        $scheduledWeek = 'CONCAT(YEAR(j.date_scheduled),WEEKOFYEAR(j.date_scheduled)) ';
        $scheduledMonth = 'CONCAT(YEAR(j.date_scheduled),MONTH(j.date_scheduled)) ';
        $scheduledYear = 'YEAR(j.date_scheduled) ';

        $orderBy = 'ORDER BY `count` DESC) z GROUP BY salesperson_id ORDER BY count DESC';

        $topServicedDaySql = $baseSql . $servicedCriteria . $groupBy . $servicedDay . $orderBy;
        $topServicedWeekSql = $baseSql . $servicedCriteria . $groupBy . $servicedWeek . $orderBy;
        $topServicedMonthSql = $baseSql . $servicedCriteria . $groupBy . $servicedMonth . $orderBy;
        $topServicedSummerSql = $baseSql . $servicedCriteria . $summerServicedRange . $groupBy . $servicedYear . $orderBy;

        $topScheduledDaySql = $baseSql . $servicedCriteria . $groupBy . $scheduledDay . $orderBy;
        $topScheduledWeekSql = $baseSql . $servicedCriteria . $groupBy . $scheduledWeek . $orderBy;
        $topScheduledMonthSql = $baseSql . $servicedCriteria . $groupBy . $scheduledMonth . $orderBy;
        $topScheduledSummerSql = $baseSql . $servicedCriteria . $summerScheduledRange . $groupBy . $scheduledYear . $orderBy;

        $servicedDay = DB::select(DB::raw($topServicedDaySql));
        $servicedWeek = DB::select(DB::raw($topServicedWeekSql));
        $servicedMonth = DB::select(DB::raw($topServicedMonthSql));
        $servicedSummer = DB::select(DB::raw($topServicedSummerSql));

        $scheduledDay = DB::select(DB::raw($topScheduledDaySql));
        $scheduledWeek = DB::select(DB::raw($topScheduledWeekSql));
        $scheduledMonth = DB::select(DB::raw($topScheduledMonthSql));
        $scheduledSummer = DB::select(DB::raw($topScheduledSummerSql));

        $data = array(
            'SERVICED_DAY'      => $servicedDay,
            'SERVICED_WEEK'     => $servicedWeek,
            'SERVICED_MONTH'    => $servicedMonth,
            'SERVICED_SUMMER'   => $servicedSummer,
            'SCHEDULED_DAY'     => $scheduledDay,
            'SCHEDULED_WEEK'    => $scheduledWeek,
            'SCHEDULED_MONTH'   => $scheduledMonth,
            'SCHEDULED_SUMMER'  => $scheduledSummer
        );

        return $data;
    }

    public function get(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id'     => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosReportCompanyRecord = PocomosReportCompanyRecord::with([
                'first_sales_person.office_user_details.user_details:id,first_name,last_name',
                'second_sales_person.office_user_details.user_details:id,first_name,last_name',
                'third_sales_person.office_user_details.user_details:id,first_name,last_name'
            ])
            ->whereId($id)->whereActive(true)->whereOfficeId($request->office_id)->get();

        if (!$PocomosReportCompanyRecord->count()) {
            return $this->sendResponse(false, 'Company Record Not Found');
        }

        $sql = 'SELECT s.id, CONCAT(u.first_name, \' \', u.last_name) as name
                                FROM pocomos_salespeople s
                                JOIN pocomos_company_office_users ou ON s.user_id = ou.id AND ou.office_id = ' . $request->office_id . '
                                JOIN orkestra_users u ON ou.user_id = u.id WHERE 1 = 1 ';

        if ($request->search_term) {
            $searchTerm = "'".$request->search_term."'";
            $sql .= ' AND (u.first_name LIKE '.$searchTerm.' OR u.last_name LIKE '.$searchTerm.' OR u.username LIKE '.$searchTerm.' OR CONCAT(u.first_name, \' \', u.last_name) LIKE '.$searchTerm.')';
        }

        $sql .= ' ORDER BY u.first_name, u.last_name';

        $salesPeople = DB::select(DB::raw($sql));

        return $this->sendResponse(
            true,
            'Company Record',
            [
                'data'        =>  $PocomosReportCompanyRecord,
                'salespeople' =>  $salesPeople
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $v = validator($request->all(), [
            'office_id'     => 'required',
            'automatic'     => 'required|boolean'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $pocomosCompanyOffice = PocomosCompanyOffice::whereId($request->office_id)->first();

        if (!$pocomosCompanyOffice) {
            return $this->sendResponse(false, 'Company Office not found.');
        }

        $PocomosReportCompanyRecord = PocomosReportCompanyRecord::whereId($id)->whereActive(true)->whereOfficeId($request->office_id)->first();
        if (!$PocomosReportCompanyRecord) {
            return $this->sendResponse(false, 'Company Record Not Found');
        }

        if ($request->automatic == 0) {
            $PocomosReportCompanyRecord->first_salesperson_id   = $request->first_salesperson_id ;
            $PocomosReportCompanyRecord->first_count            = $request->first_count ?: 0;
            $PocomosReportCompanyRecord->second_salesperson_id  = $request->second_salesperson_id ;
            $PocomosReportCompanyRecord->second_count           = $request->second_count ?: 0;
            $PocomosReportCompanyRecord->third_salesperson_id   = $request->third_salesperson_id ;
            $PocomosReportCompanyRecord->third_count            = $request->third_count ?: 0;
            $PocomosReportCompanyRecord->automatic              = 0;

            $PocomosReportCompanyRecord->save();
        } elseif ($request->automatic == 1) {
            $record     = $PocomosReportCompanyRecord;
            $recordType = $PocomosReportCompanyRecord->name;
            $calculatedRecords = $this->getAllTimeData($request->office_id);

            $this->autoFillRecord($record, $recordType, $calculatedRecords);
        }


        return $this->sendResponse(true, 'Company Record updated successfully.');
    }
}
