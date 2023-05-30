<?= $data['customer']['first_name'].''.$data['customer']['last_name'] ?>,
<br/>
You will find a file sent to by <?= $data['user']['first_name'].''.$data['user']['last_name'] ?> at
<?= $data['office']['name'] ?>.<br/>
<a href="<?= $data['file']['path'] ?>">Download <?= $data['file']['filename'] ?></a>
