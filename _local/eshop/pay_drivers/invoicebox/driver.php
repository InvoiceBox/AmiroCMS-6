<?php

class Invoicebox_PaymentSystemDriver extends AMI_PaymentSystemDriver {

    protected $driverName = 'invoicebox';

    /**
     * Get checkout button HTML form
     *
     * @param array $aRes Will contain "error" (error description, 'Success by default') and "errno" (error code, 0 by default). "forms" will contain a created form
     * @param array $aData The data list for button generation
     * @param bool $bAutoRedirect If form autosubmit required (directly from checkout page)
     * @return bool true if form is generated, false otherwise
     */
    public function getPayButton(&$aRes, $aData, $bAutoRedirect = false) {
        $aRes['errno'] = 0;
        $aRes['error'] = 'Success';
        $aData['hiddens'] = $this->getScopeAsFormHiddenFields($aData);
        return parent::getPayButton($aRes, $aData, $bAutoRedirect);
    }

    /**
     * Get the form that will be autosubmitted to payment system. This step is required for some shooping cart actions.
     *
     * @param array $aData The data list for button generation
     * @param array $aRes Will contain "error" (error description, 'Success by default') and "errno" (error code, 0 by default). "forms" will contain a created form
     * @return bool true if form is generated, false otherwise
     */
    public function getPayButtonParams($aData, &$aRes) {
        $aData['url'] = 'https://go.invoicebox.ru/module_inbox_auto.u';
        $quantity = 0;
        $signatureValue = md5($this->object->invoicebox_participant_id .
                $aData['order_id'] .
                $aData['amount'] .
                $aData['driver_currency'] .
                $aData['im_invoicebox_api_key']
        );
		$orderId = (int)$aData['order_id'];
        $oOrder =
            AMI::getResourceModel('eshop_order/table')
            ->find($orderId);
        if($orderId != $oOrder->id){
            $message = "Invalid order id '" . $aData['order_id'] . "'";
            $this->reportError($message);
            $aRes['error'] = $message;
            $aRes['errno'] = self::ERROR_INVALID_ORDER_ID;

            return FALSE;
        }
		
		
        $itemNo = 0;
        $basketItemhtml = '';
        $measure = "шт.";
		$oOrderProductList =
            AMI::getResourceModel('eshop_order_item/table', array(array('doRemapListItems' => TRUE)))
            ->getList()
            ->addColumn('*')
            ->addSearchCondition(array('id_order' => $orderId))
            ->load();
        foreach ($oOrderProductList as $oItem) {
			$aProduct = $oItem->data;
            $aProduct = $aProduct['item_info'];
			
            $itemNo++;
            $quantity +=$oItem->qty;
            $name = $aProduct['name'];
            $price = $aProduct['price']+$aProduct['tax_item'];
			$tax_item_value = $aProduct['tax_item_value'];
            $qty = $oItem->qty;
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_name" value="' . $name . '" />' . "\n";
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_quantity" value="' . $qty . '" />' . "\n";
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_measure" value="' . $measure . '" />' . "\n";
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_price" value="' . $price . '" />' . "\n";
			$basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_vatrate" value="' . $aProduct['tax_item_value'] . '" />' . "\n";
			$basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_vat" value="' . $aProduct['tax_item'] . '" />' . "\n";
        }
		if($oOrder->shipping > 0){
			$itemNo++;
			$basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_name" value="Доставка" />' . "\n";
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_quantity" value="1" />' . "\n";
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_measure" value="' . $measure . '" />' . "\n";
            $basketItemhtml .= '<input type="hidden" name="itransfer_item' . $itemNo . '_price" value="' . $oOrder->shipping . '" />' . "\n";
			
		}
        $aData['basketItemhtml'] = $basketItemhtml;
        $aData['hiddens'] = $this->getScopeAsFormHiddenFields(array(
            'itransfer_participant_id' => $aData['im_invoicebox_participant_id'],
            'itransfer_participant_ident' => $aData['im_invoicebox_participant_ident'],
            'itransfer_testmode' => $aData['im_invoicebox_testmode'],
            'itransfer_participant_sign' => $signatureValue,
            'itransfer_cms_name' => 'AMIRO',
            'itransfer_order_id' => $aData['order_id'],
            'itransfer_order_amount' => $aData['amount'],
            'itransfer_order_quantity' => $quantity,
            'itransfer_order_currency_ident' => $aData['driver_currency'],
            'itransfer_order_description' => 'Оплата заказа №' . $aData['order'] . ' Сумма к оплате ' . $aData['amount_formatted'],
            'itransfer_body_type' => "PRIVATE",
            'itransfer_person_name' => $aData['firstname'] . ' ' . $aData['lastname'],
            'itransfer_person_email' => $aData['email'],
            'itransfer_person_phone' => $aData['contact'],
            'itransfer_url_return' => $aData['return'],
            'itransfer_url_cancel' => $aData['cancel'],
            'itransfer_url_notify' => $aData['callback'],
            'FinalStep' => '1'
        ));

        return parent::getPayButtonParams($aData, $aRes);
    }

    /**
     * Verify the order from user back link. In success case 'accepted' status will be setup for order.
     *
     * @param array $aGet $_GET data
     * @param array $aPost $_POST data
     * @param array $aRes reserved array reference
     * @param array $aCheckData Data that provided in driver configuration
     * @return bool true if order is correct and false otherwise
     * @see AMI_PaymentSystemDriver::payProcess(...)
     */
    public function payProcess($aGet, $aPost, &$aRes, $aCheckData, $aOrderData) {
        // See implplementation of this method in parent class

        return parent::payProcess($aGet, $aPost, $aRes, $aCheckData, $aOrderData);
    }

    /**
     * Verify the order by payment system background responce. In success case 'confirmed' status will be setup for order.
     *
     * @param array $aGet $_GET data
     * @param array $aPost $_POST data
     * @param array $aRes reserved array reference
     * @param array $aCheckData Data that provided in driver configuration
     * @return int -1 - ignore post, 0 - reject(cancel) order, 1 - confirm order
     * @see AMI_PaymentSystemDriver::payCallback(...)
     */
    public function payCallback($aGet, $aPost, &$aRes, $aCheckData, $aOrderData) {
        if (!is_array($aGet)) {
            $aGet = Array();
        }
        if (!is_array($aPost)) {
            $aPost = Array();
        }
        $aParams = array_merge($aGet, $aPost);
        $participantId = IntVal($aParams["participantId"]);
        $participantOrderId = trim($aParams["participantOrderId"]);
        $participant_apikey = $aCheckData['im_invoicebox_api_key'];
        $ucode = trim($aParams["ucode"]);
        $timetype = trim($aParams["timetype"]);
        $time = str_replace(' ', '+', trim($aParams["time"]));
        $amount = trim($aParams["amount"]);
        $currency = trim($aParams["currency"]);
        $agentName = trim($aParams["agentName"]);
        $agentPointName = trim($aParams["agentPointName"]);
        $testMode = trim($aParams["testMode"]);
        $sign = trim($aParams["sign"]);

        $sign_strC = $participantId .
                $participantOrderId .
                $ucode .
                $timetype .
                $time .
                $amount .
                $currency .
                $agentName .
                $agentPointName .
                $testMode .
                $participant_apikey;

        $sign_strC = md5($sign_strC);

        if ($sign != $sign_strC) {
            die('failed');
        }

        if (($amount - $aCheckData['amount']) == 0) {
            global $cms, $oEshop, $oOrder;

            $oEshop->initByOwnerName('eshop');
            $oOrder->updateStatus($cms, $participantOrderId, 'auto', 'confirmed_done');
            die('OK');
        } else {

            die('failed');
        }
    }

    public function getProcessOrder(array $aGet, array $aPost, array &$aRes, array $aAdditionalParams) {
        $orderId = 0;
        if (!empty($aGet["participantOrderId"])) {
            $orderId = $aGet["participantOrderId"];
        }
        if (!empty($aPost["participantOrderId"])) {
            $orderId = $aPost["participantOrderId"];
        }
        return intval($orderId);
    }

    public static function getOrderIdVarName() {
        return 'participantOrderId';
    }

}
