<p>{{ $profile->customer->first_name }} {{ $profile->customer->last_name }},</p>

<p>Your {{ $job->contract->service_type_details->name }} service has been rescheduled to {{ date('m/d/y', strtotime($job->date_scheduled)) }}. If this does not work for you, please contact our office at {{ $config->company_details->coontact_address->primaryPhone->number }}.</p>
