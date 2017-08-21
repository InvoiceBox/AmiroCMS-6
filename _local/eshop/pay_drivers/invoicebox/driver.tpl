%%include_language "_local/eshop/pay_drivers/invoicebox/driver.lng"%%

<!--#set var="settings_form" value="
    <tr>
        <td>%%im_invoicebox_participant_id%%:</td>
        <td><input type="text" name="im_invoicebox_participant_id" class="field" value="##im_invoicebox_participant_id##" size="40" autocomplete="off"></td>
    </tr>
    <tr>
        <td>%%im_invoicebox_participant_ident%%:</td>
        <td><input type="text" name="im_invoicebox_participant_ident" class="field" value="##im_invoicebox_participant_ident##" size="40" autocomplete="off"></td>
    </tr>
	<tr>
        <td>%%im_invoicebox_api_key%%:</td>
        <td><input type="text" name="im_invoicebox_api_key" class="field" value="##im_invoicebox_api_key##" size="40" autocomplete="off"></td>
    </tr>
	
	<tr>
        <td>%%im_invoicebox_testmode%%:</td>
        <td>
            <select name="im_invoicebox_testmode">
                <option value="0">%%im_invoicebox_testmode_off%%</option>
                <option value="1">%%im_invoicebox_testmode_on%%</option>
            </select>
            <script>
                AMI.$('[name=im_invoicebox_testmode]').val('##im_invoicebox_testmode##');
            </script>
        </td>
    </tr>
	
	

"-->

<!--#set var="checkout_form" value="
<form name="paymentform##billing_driver##" action="##process_url##" method="post">
##hiddens##
<input type="hidden" name="cms_name" value="amirocms" />
</form>
"-->

<!--#set var="pay_form" value="
	
    <form name="paymentform##billing_driver##" action="##url##" method="POST" target="_blank">
    ##hiddens##
	##basketItemhtml##
	Вы хотите оплатить через систему <b>ИнвойсБокс</b><br>
    Сумма к оплате: <b>##amount_formatted##</b>
            <p>
            <input type="submit" name="Submit" value="Оплатить">
            </p>
    </form>
    
"-->
