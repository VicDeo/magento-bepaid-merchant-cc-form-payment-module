<?php


class Mage_Ecomcharge_Block_Standard_Redirect extends Mage_Core_Block_Template
{
		

	
    public function  GetHtml()
    {

		$action_url = 'https://checkout.ecomcharge.com/ctp/payments';
        $standard = Mage::getModel('ecomcharge/standard');
        $form = new Varien_Data_Form();
        $form->setAction($action_url)
            ->setId('ecomcharge_standard_checkout')
            ->setName('ecomcharge_standard_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);		
        foreach ($standard->setOrder($this->getOrder())->getStandardCheckoutFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $html= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("ecomcharge_standard_checkout").submit();</script>';
        return $html;
    }	
}