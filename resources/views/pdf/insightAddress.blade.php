<li class="row">
   <span class="f-right">{{ $office->name }}</span>
</li>
<li class="row">
   <span class="f-right">{{ $office->contact->primaryPhone->number }}</span>
</li>
<li class="row">
   <span class="f-right">{{ $office->contact->street }} {{ $office->contact->suite }}</span>
</li>
<li class="row">
   <span class="f-right">{{ $office->contact->city }}, {{ $office->contact->name }}, {{ $office->contact->postal_code }}</span>
</li>
