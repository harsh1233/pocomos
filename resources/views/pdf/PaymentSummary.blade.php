<style>
th { font-weight: bold; text-align: center; }
td.center { text-align: center; }
</style>
<table>
    <tr>
        <td style="width: 75%"></td>
        <td style="width: 25%">
            <table cellpadding="0" cellspacing="0" >
                <tr>
                    <th>Date</th>
                </tr>
                <tr>
                    <td class="center">{{ "now"|date("m/d/y") }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<table>
    <tr>
        <td style="width: 70%; font-size: 8pt;"></td>
        <td style="width: 30%">
            <table cellpadding="2" cellspacing="0" >
                <tr>
                    <th>Details:</th>
                </tr>
                <tr>
                    <td style="font-size: 7pt;">
                    	{{ responsibleCustomer.billingName ? responsibleCustomer.billingName : responsibleCustomer }}<br>{{ responsibleCustomer.billingAddress.street }} {{ responsibleCustomer.billingAddress.suite }}<br>{{ responsibleCustomer.billingAddress.city }}, {{ responsibleCustomer.billingAddress.region.code }} {{ responsibleCustomer.billingAddress.postalCode }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<style>
th { font-weight: bold; text-align: center; }
td.center { text-align: center; }
td.small { font-size: 8pt; }
th { font-size: 8pt; }
</style>
<table class="table align-middle" >
	<thead>
		<tr>
		    <th>Invoice #</th>
		    <th>Date</th>
		    <th>Network</th>
		    <th>Amount</th>
		    <th>Type</th>
		    <th>Status</th>
		    <th>Description</th>
		    <th>
		        <abbr title="This is often the ID associated with your payment gateway.">
		            External Id
		        </abbr>
		    </th>
		    <th>Payment Initiator</th>
		    <th>Card Alias</th>
		    <th>Multiple Invoices</th>
		</tr>
	</thead>
	<tbody>

	</tbody>
</table>