<?php

namespace App\Jobs;

use PDF;
use Mail;
use Illuminate\Bus\Queueable;
use App\Mail\RecruitmentAgreement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class RecruitAgreementGenerate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Functions;

    public $id;
    public $recruit;
    public $emails;
    public $office_id;
    public $recruit_user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($recruit, $emails, $office_id, $recruit_user, $id)
    {
        $this->recruit = $recruit;
        $this->emails = $emails;
        $this->office_id = $office_id;
        $this->recruit_user = $recruit_user;
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("RecruitAgreementGenerate Job Started For Recruitement: ". $this->id);

        $data = DB::select(DB::raw("SELECT co.name, CONCAT(ud.first_name, ' ' , ud.last_name ) as 'recruiter_name', ra.agreement_body, ra.name as 'agreement_name'
        FROM pocomos_recruits AS pr
        JOIN pocomos_recruiting_offices AS ro ON pr.recruiting_office_id = ro.id
        JOIN pocomos_recruiting_office_configurations AS roc ON ro.office_configuration_id = roc.id
        JOIN pocomos_company_offices AS co ON roc.office_id = co.id
        JOIN pocomos_recruiters AS ru ON pr.recruiter_id = ru.id
        JOIN pocomos_company_office_users AS ou ON ru.user_id = ou.id
        JOIN orkestra_users AS ud ON ou.user_id = ud.id
        JOIN pocomos_recruit_contracts AS rc ON pr.recruit_contract_id = rc.id
        JOIN pocomos_recruit_agreements AS ra ON rc.agreement_id = ra.id
        WHERE (roc.office_id = '$this->office_id' OR co.parent_id = '$this->office_id') AND pr.active = 1
        GROUP BY co.id
        ORDER BY co.id ASC"));

        if (!count($data)) {
            return $this->sendResponse(false, __('strings.something_went_wrong'));
        }

        $agreement = $data[0]->agreement_name ?? '';
        $agreement_body = $data[0]->agreement_body ?? '';

        $agreement_body = $this->generateAgreement($agreement_body, $this->id, $this->office_id);

        $path = 'public/pdf/agreement_'.$this->id.'.pdf';

        $pdf = PDF::loadView('emails.dynamic_email_render', compact('agreement_body'));
        Storage::put($path, $pdf->output());

        Log::info("RecruitAgreementGenerate Job End For Recruitement: ". $this->id);
        $email = Mail::to($this->emails);
        $email->send(new RecruitmentAgreement($agreement, $path, $this->id));

        // unlink(storage_path('app/public').'/../'.$path);
    }
}
