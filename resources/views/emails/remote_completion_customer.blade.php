{{ $data->customer_name }},<br>
<br>
Welcome to {{ $data->office_name }}!<br>
<br>
<a target="_blank" href={{ $data->redirect_url }}>Click here to review and finalize</a> your {{ $data->agreement_name }} agreement.<br>
<br>
<br>
Having troubles opening the link in your browser? Copy and paste the link below:<br>
<br>
{{ $data->redirect_url }} <br>
<br><br>
We look forward to your business.<br>
<br>
Best regards,<br>
{{ $data->office_name }}<br>
{{ $data->office_fax }}