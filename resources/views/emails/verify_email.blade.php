<?= $data['customer']['first_name'].' '.$data['customer']['last_name'] ?>,<br /><br />
<?php //$link = url('customer_verify_email?id='.$data['id'].'&hash='.$data['hash']); ?>
In order to ensure that you receive all pertinent account notifications, please confirm your email address.<br /><br />

<?= $data['html_link'] ?> <br /><br />

If you're having trouble clicking the link, please copy and paste the link below into your web browser.<br /><br />

{{ $data['url'] }}<br /><br />
<br />
Best regards,<br />
<?= $data['office']['name'] ?? '' ?><br />
<?= $data['office']['coontact_address']['primaryPhone']['number'] ?>