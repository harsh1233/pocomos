<p>{{ $user['first_name'] }} {{$user['last_name'] }}</p>
<br>
<p>A task has been has been assigned to you.</p>
<br>
<p>Priority: <b>{{$body['priority']}}</b></p>
<p>Name: <b>Task</b></p>
<p>Description: <b>{{$body['description']}}</b></p>
<br>
<p>This alert was assigned by <b>{{$sender['first_name']}} {{$sender['last_name']}}</b></p>
<br>
<p>Please login to your Pocomos Software1 Management Software to update this task.</p>