<?php

/**
  * Payment tab for admin panel, AdminPayment.php
  * @category admin
  *
  * @author PrestaShop <support@prestashop.com>
  * @copyright PrestaShop
  * @license http://www.opensource.org/licenses/osl-3.0.php Open-source licence 3.0
  * @version 1.4
  *
  */

class AdminPayment extends AdminTab
{
	public $paymentModules = array();
	
	public function __construct()
	{
		/* Get all modules then select only payment ones*/
		$modules = Module::getModulesOnDisk();
		foreach ($modules AS $module)
			if ($module->tab == 'payments_gateways')
			{
				if($module->id)
				{
					$module->country = array();
					$countries = DB::getInstance()->ExecuteS('SELECT id_country FROM '._DB_PREFIX_.'module_country WHERE id_module = '.intval($module->id));
					foreach ($countries as $country)
						$module->country[] = $country['id_country'];
						
					$module->currency = array();
					$currencies = DB::getInstance()->ExecuteS('SELECT id_currency FROM '._DB_PREFIX_.'module_currency WHERE id_module = '.intval($module->id));
					foreach ($currencies as $currency)
						$module->currency[] = $currency['id_currency'];
						
					$module->group = array();
					$groups = DB::getInstance()->ExecuteS('SELECT id_group FROM '._DB_PREFIX_.'module_group WHERE id_module = '.intval($module->id));
					foreach ($groups as $group)
						$module->group[] = $group['id_group'];
				}
				else
				{
					$module->country = NULL;
					$module->currency = NULL;
					$module->group = NULL;
				}

				$this->paymentModules[] = $module;
			}
	
		parent::__construct();
	}
	
	public function postProcess()
	{
		if (Tools::isSubmit('submitModulecountry'))
			$this->saveRestrictions('country');
		elseif (Tools::isSubmit('submitModulecurrency'))
			$this->saveRestrictions('currency');
		elseif (Tools::isSubmit('submitModulegroup'))
			$this->saveRestrictions('group');
	}
	
	private function saveRestrictions($type)
	{
		global $currentIndex;
		
		Db::getInstance()->Execute('TRUNCATE '._DB_PREFIX_.'module_'.$type.'');
		foreach ($this->paymentModules as $module)
			if ($module->active AND isset($_POST[$module->name.'_'.$type.'']))
				foreach ($_POST[$module->name.'_'.$type.''] as $selected)
					$values[] = '('.intval($module->id).', '.intval($selected).')';
		Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'module_'.$type.' (`id_module`, `id_'.$type.'`) VALUES '.implode(',', $values));
		Tools::redirectAdmin($currentIndex.'&conf=4'.'&token='.$this->token);
	}

	public function display()
	{
		global $cookie;
		
		/*
foreach ($this->paymentModules AS $module)
			if ($module->active AND $module->warning)
				$this->displayWarning($module->displayName.' - '.stripslashes(pSQL($module->warning)));
*/
		
		$currencies = Currency::getCurrencies();
		$countries = Country::getCountries(intval($cookie->id_lang));
		$groups = Group::getGroups(intval($cookie->id_lang));
		
		$tokenModules = Tools::getAdminToken('AdminModules'.intval(Tab::getIdFromClassName('AdminModules')).intval($cookie->id_employee));
		echo '<h2 class="space">'.$this->l('Payment modules list').'</h2>';
		echo '<input type="button" class="button" onclick="document.location=\'index.php?tab=AdminModules&token='.$tokenModules.'&module_name='.$this->paymentModules[0]->name.'&tab_module=payments_gateways\'" value="'.$this->l('Click to see the list of payment modules.').'" />';
		
		echo '<br /><h2 class="space">'.$this->l('Payment module restrictions').'</h2>';
		$textCurrencies = $this->l('Please mark the checkbox(es) for the currency or currencies in which you want the payment module(s) available.');
		$textCountries = $this->l('Please mark the checkbox(es) for the country or countries in which you want the payment module(s) available.');
		$textGroups = $this->l('Please mark the checkbox(es) for the groups in which you want the payment module(s) available.');
		$this->displayModuleRestrictions($currencies, $this->l('Currencies restrictions'), 'currency', $textCurrencies, 'dollar');
		echo '<br />';
		$this->displayModuleRestrictions($groups, $this->l('Groups restrictions'), 'group', $textGroups, 'group');
		echo '<br />';
		$this->displayModuleRestrictions($countries, $this->l('Countries restrictions'), 'country', $textCountries, 'world');
	}
	
	public function displayModuleRestrictions($items, $title, $nameId, $desc, $icon)
	{
		global $currentIndex;
		$irow = 0;
		
		echo '
		<form action="'.$currentIndex.'&token='.$this->token.'" method="post" id="form_'.$nameId.'">
			<fieldset>
				<legend><img src="../img/admin/'.$icon.'.gif" />'.$title.'</legend>
				<p>'.$desc.'<p>
				<table cellpadding="0" cellspacing="0" class="table">
					<tr>
						<th style="width: 200px">'.$title.'</th>';
		foreach ($this->paymentModules as $module)
		{
			if ($module->active)
			{
				echo '
						<th>';
				if ($nameId != 'currency' OR ($nameId == 'currency' AND $module->currencies_mode == 'checkbox'))
					echo '
							<input type="hidden" id="checkedBox_'.$nameId.'_'.$module->name.'" value="checked">
							<a href="javascript:checkPaymentBoxes(\''.$nameId.'\', \''.$module->name.'\')" style="text-decoration:none;">';
				echo '
							&nbsp;<img src="'.__PS_BASE_URI__.'modules/'.$module->name.'/logo.gif" alt="'.$module->name.'" title="'.$module->displayName.'" />';
				if ($nameId != 'currency' OR ($nameId == 'currency' AND $module->currencies_mode == 'checkbox'))
					echo '
							</a>';
				echo '
						</th>';
			}
		}
		echo '
					</tr>';
		foreach ($items as $item)
		{
			echo '
					<tr'.($irow++ % 2 ? ' class="alt_row"' : '').'>
						<td>'.$item['name'].'</td>';
			foreach ($this->paymentModules as $module)
			{
				if ($module->active)
				{
					echo '
						<td style="text-align: center">';
					if ($nameId != 'currency' OR ($nameId == 'currency' AND $module->currencies AND $module->currencies_mode == 'checkbox'))
						echo '
							<input type="checkbox" name="'.$module->name.'_'.$nameId.'[]" value="'.$item['id_'.$nameId].'"'.(in_array($item['id_'.$nameId.''], $module->{$nameId}) ? ' checked="checked"' : '').' />';
					elseif ($nameId == 'currency' AND $module->currencies AND $module->currencies_mode == 'radio')
						echo '
							<input type="radio" name="'.$module->name.'_'.$nameId.'[]" value="'.$item['id_'.$nameId].'"'.(in_array($item['id_'.$nameId.''], $module->{$nameId}) ? ' checked="checked"' : '').' />';
					elseif ($nameId == 'currency')
						echo '--';
					echo '
						</td>';
				}
			}
			echo '
					</tr>';
		}
		if ($nameId == 'currency')
		{
			echo '
				<tr'.($irow++ % 2 ? ' class="alt_row"' : '').'>
					<td>'.$this->l('Customer currency').'</td>';
			foreach ($this->paymentModules as $module)
				if ($module->active)
					echo '
					<td style="text-align: center">'.(($module->currencies AND $module->currencies_mode == 'radio') ? '<input type="radio" name="'.$module->name.'_'.$nameId.'[]" value="-1"'.(in_array(-1, $module->{$nameId}) ? ' checked="checked"' : '').' />' : '--').'</td>';
			echo '
				</tr>';
			echo '
				<tr'.($irow++ % 2 ? ' class="alt_row"' : '').'>
					<td>'.$this->l('Shop default currency').'</td>';
			foreach ($this->paymentModules as $module)
				if ($module->active)
					echo '
					<td style="text-align: center">'.(($module->currencies AND $module->currencies_mode == 'radio') ? '<input type="radio" name="'.$module->name.'_'.$nameId.'[]" value="-2"'.(in_array(-2, $module->{$nameId}) ? ' checked="checked"' : '').' />' : '--').'</td>';
			echo '
				</tr>';
		}
		echo '
				</table>
				<div style="text-align:center;"><input type="submit" class="button space" name="submitModule'.$nameId.'" value="'.$this->l('Save restrictions').'" /></div>
			</fieldset>
		</form>';
	}
	
}

?>