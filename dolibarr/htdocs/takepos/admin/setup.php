<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/admin/setup.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

// If socid provided by ajax company selector
if (!empty($_REQUEST['CASHDESK_ID_THIRDPARTY_id']))
{
	$_GET['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
}

// Security check
if (!$user->admin) accessforbidden();

$langs->loadLangs(array("admin", "cashdesk"));

global $db;

$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		array_push($paiements, $obj);
	}
}

/*
 * Actions
 */
if (GETPOST('action', 'alpha') == 'set')
{
	$db->begin();
	if (GETPOST('socid', 'int') < 0) $_POST["socid"] = '';

	$res = dolibarr_set_const($db, "CASHDESK_SERVICES", GETPOST('CASHDESK_SERVICES', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ROOT_CATEGORY_ID", GETPOST('TAKEPOS_ROOT_CATEGORY_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_BAR_RESTAURANT", GETPOST('TAKEPOS_BAR_RESTAURANT', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_TICKET_VAT_GROUPPED", GETPOST('TAKEPOS_TICKET_VAT_GROUPPED', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTERS", GETPOST('TAKEPOS_ORDER_PRINTERS', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_NOTES", GETPOST('TAKEPOS_ORDER_NOTES', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_PHONE_BASIC_LAYOUT", GETPOST('TAKEPOS_PHONE_BASIC_LAYOUT', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_SUPPLEMENTS", GETPOST('TAKEPOS_SUPPLEMENTS', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_SUPPLEMENTS_CATEGORY", GETPOST('TAKEPOS_SUPPLEMENTS_CATEGORY', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_NUMPAD", GETPOST('TAKEPOS_NUMPAD', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_SORTPRODUCTFIELD", GETPOST('TAKEPOS_SORTPRODUCTFIELD', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_COLOR_THEME", GETPOST('TAKEPOS_COLOR_THEME', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_NUM_TERMINALS", GETPOST('TAKEPOS_NUM_TERMINALS', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_DIRECT_PAYMENT", GETPOST('TAKEPOS_DIRECT_PAYMENT', 'int'), 'int', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_CUSTOM_RECEIPT", GETPOST('TAKEPOS_CUSTOM_RECEIPT', 'int'), 'int', 0, '', $conf->entity);
	//$res = dolibarr_set_const($db, "TAKEPOS_HEAD_BAR", GETPOST('TAKEPOS_HEAD_BAR', 'int'), 'int', 0, '', $conf->entity);
    $res = dolibarr_set_const($db, "TAKEPOS_EMAIL_TEMPLATE_INVOICE", GETPOST('TAKEPOS_EMAIL_TEMPLATE_INVOICE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!empty($conf->global->TAKEPOS_ENABLE_SUMUP)) {
		$res = dolibarr_set_const($db, "TAKEPOS_SUMUP_AFFILIATE", GETPOST('TAKEPOS_SUMUP_AFFILIATE', 'alpha'), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "TAKEPOS_SUMUP_APPID", GETPOST('TAKEPOS_SUMUP_APPID', 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if ($conf->global->TAKEPOS_ORDER_NOTES == 1)
	{
		$extrafields = new ExtraFields($db);
		$extrafields->addExtraField('order_notes', 'Order notes', 'varchar', 0, 255, 'facturedet', 0, 0, '', '', 0, '', 0, 1);
	}

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

	if (!$res > 0) $error++;

 	if (!$error)
    {
        $db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
	    setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_prepare_head();
dol_fiche_head($head, 'setup', 'TakePOS', -1);
print '<br>';


// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// Terminals
print '<tr class="oddeven"><td>';
print $langs->trans("NumberOfTerminals");
print '<td colspan="2">';
$array = array(1=>"1", 2=>"2", 3=>"3", 4=>"4", 5=>"5", 6=>"6", 7=>"7", 8=>"8", 9=>"9");
print $form->selectarray('TAKEPOS_NUM_TERMINALS', $array, (empty($conf->global->TAKEPOS_NUM_TERMINALS) ? '0' : $conf->global->TAKEPOS_NUM_TERMINALS), 0);
print "</td></tr>\n";

// Services
if (!empty($conf->service->enabled))
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("CashdeskShowServices");
	print '<td colspan="2">';
	print ajax_constantonoff("CASHDESK_SERVICES", array(), $conf->entity, 0, 0, 1, 0);
	//print $form->selectyesno("CASHDESK_SERVICES", $conf->global->CASHDESK_SERVICES, 1);
	print "</td></tr>\n";
}

// Root category for products
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("RootCategoryForProductsToSell"), $langs->trans("RootCategoryForProductsToSellDesc"));
print '<td colspan="2">';
print $form->select_all_categories(Categorie::TYPE_PRODUCT, $conf->global->TAKEPOS_ROOT_CATEGORY_ID, 'TAKEPOS_ROOT_CATEGORY_ID', 64, 0, 0);
print ajax_combobox('TAKEPOS_ROOT_CATEGORY_ID');
print "</td></tr>\n";

// VAT Grouped on ticket
print '<tr class="oddeven"><td>';
print $langs->trans('TicketVatGrouped');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_TICKET_VAT_GROUPPED", array(), $conf->entity, 0, 0, 1, 0);
//print $form->selectyesno("TAKEPOS_TICKET_VAT_GROUPPED", $conf->global->TAKEPOS_TICKET_VAT_GROUPPED, 1);
print "</td></tr>\n";

// Sort product
print '<tr class="oddeven"><td>';
print $langs->trans("SortProductField");
print '<td colspan="2">';
$prod = new Product($db);
$array = array('rowid' => 'ID', 'ref' => 'Ref', 'datec' => 'DateCreation', 'tms' => 'DateModification');
print $form->selectarray('TAKEPOS_SORTPRODUCTFIELD', $array, (empty($conf->global->TAKEPOS_SORTPRODUCTFIELD) ? 'rowid' : $conf->global->TAKEPOS_SORTPRODUCTFIELD), 0, 0, 0, '', 1);
print "</td></tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val)	$htmltext .= $key.'<br>';
$htmltext .= '</i>';

// Color theme
print '<tr class="oddeven"><td>';
print $langs->trans("ColorTheme");
print '<td colspan="2">';
$array = array(0=>"eldy", 1=>$langs->trans("Colorful"));
print $form->selectarray('TAKEPOS_COLOR_THEME', $array, (empty($conf->global->TAKEPOS_COLOR_THEME) ? '0' : $conf->global->TAKEPOS_COLOR_THEME), 0);
print "</td></tr>\n";

// Payment numpad
print '<tr class="oddeven"><td>';
print $langs->trans("Paymentnumpad");
print '<td colspan="2">';
$array = array(0=>$langs->trans("Numberspad"), 1=>$langs->trans("BillsCoinsPad"));
print $form->selectarray('TAKEPOS_NUMPAD', $array, (empty($conf->global->TAKEPOS_NUMPAD) ? '0' : $conf->global->TAKEPOS_NUMPAD), 0);
print "</td></tr>\n";

// Direct Payment
print '<tr class="oddeven"><td>';
print $langs->trans('DirectPaymentButton');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_DIRECT_PAYMENT", array(), $conf->entity, 0, 0, 1, 0);
//print $form->selectyesno("TAKEPOS_DIRECT_PAYMENT", $conf->global->TAKEPOS_DIRECT_PAYMENT, 1);
print "</td></tr>\n";

// Head Bar
/*print '<tr class="oddeven"><td>';
print $langs->trans('HeadBar');
print '<td colspan="2">';
print $form->selectyesno("TAKEPOS_HEAD_BAR", $conf->global->TAKEPOS_HEAD_BAR, 1);
print "</td></tr>\n";
*/

// Email template for send invoice
print '<tr class="oddeven"><td>';
print $langs->trans('EmailTemplate');
print '<td colspan="2">';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
$formmail = new FormMail($db);
$nboftemplates = $formmail->fetchAllEMailTemplate('facture_send', $user, null, -1); // We set lang=null to get in priority record with no lang
//$arraydefaultmessage = $formmail->getEMailTemplate($db, $tmp[1], $user, null, 0, 1, '');
$arrayofmessagename = array();
if (is_array($formmail->lines_model)) {
    foreach ($formmail->lines_model as $modelmail) {
        //var_dump($modelmail);
        $moreonlabel = '';
        if (!empty($arrayofmessagename[$modelmail->label])) {
            $moreonlabel = ' <span class="opacitymedium">('.$langs->trans("SeveralLangugeVariatFound").')</span>';
        }
        $arrayofmessagename[$modelmail->label] = $langs->trans(preg_replace('/\(|\)/', '', $modelmail->label)).$moreonlabel;
    }
}
//var_dump($arraydefaultmessage);
//var_dump($arrayofmessagename);
print $form->selectarray('TAKEPOS_EMAIL_TEMPLATE_INVOICE', $arrayofmessagename, $conf->global->TAKEPOS_EMAIL_TEMPLATE_INVOICE, 'None', 1, 0, '', 0, 0, 0, '', '', 1);
print "</td></tr>\n";

print '</table>';
print '</div>';

print '<br>';

// Bar Restaurant mode
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("Other").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("EnableBarOrRestaurantFeatures");
print '</td>';
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_BAR_RESTAURANT", array(), $conf->entity, 0, 0, 1, 0);
//print $form->selectyesno("TAKEPOS_BAR_RESTAURANT", $conf->global->TAKEPOS_BAR_RESTAURANT, 1);
print "</td></tr>\n";

if ($conf->global->TAKEPOS_BAR_RESTAURANT && $conf->global->TAKEPOS_PRINT_METHOD != "browser") {
	print '<tr class="oddeven value"><td>';
	print $langs->trans("OrderPrinters").' (<a href="'.DOL_URL_ROOT.'/takepos/admin/orderprinters.php?leftmenu=setup">'.$langs->trans("Setup").'</a>)';
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_ORDER_PRINTERS", array(), $conf->entity, 0, 0, 1, 0);
	//print $form->selectyesno("TAKEPOS_ORDER_PRINTERS", $conf->global->TAKEPOS_ORDER_PRINTERS, 1);
	print '</td></tr>';

	print '<tr class="oddeven value"><td>';
	print $langs->trans("OrderNotes");
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_ORDER_NOTES", array(), $conf->entity, 0, 0, 1, 0);
	//print $form->selectyesno("TAKEPOS_ORDER_NOTES", $conf->global->TAKEPOS_ORDER_NOTES, 1);
	print '</td></tr>';
}

if ($conf->global->TAKEPOS_BAR_RESTAURANT)
{
	print '<tr class="oddeven value"><td>';
	print $langs->trans("BasicPhoneLayout");
	print '<td colspan="2">';
	//print $form->selectyesno("TAKEPOS_PHONE_BASIC_LAYOUT", $conf->global->TAKEPOS_PHONE_BASIC_LAYOUT, 1);
	print ajax_constantonoff("TAKEPOS_PHONE_BASIC_LAYOUT", array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';

	print '<tr class="oddeven value"><td>';
	print $langs->trans("ProductSupplements");
	print '<td colspan="2">';
	//print $form->selectyesno("TAKEPOS_SUPPLEMENTS", $conf->global->TAKEPOS_SUPPLEMENTS, 1);
	print ajax_constantonoff("TAKEPOS_SUPPLEMENTS", array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';

	if ($conf->global->TAKEPOS_SUPPLEMENTS)
	{
		print '<tr class="oddeven"><td>';
		print $langs->trans("SupplementCategory");
		print '<td colspan="2">';
		print $form->select_all_categories(Categorie::TYPE_PRODUCT, $conf->global->TAKEPOS_SUPPLEMENTS_CATEGORY, 'TAKEPOS_SUPPLEMENTS_CATEGORY', 64, 0, 0);
		print ajax_combobox('TAKEPOS_SUPPLEMENTS_CATEGORY');
		print "</td></tr>\n";
	}
}
print '</table>';
print '</div>';


// Sumup options
if ($conf->global->TAKEPOS_ENABLE_SUMUP) {
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans("Sumup").'</td><td>'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	print '<tr class="oddeven"><td>';
	print $langs->trans("SumupAffiliate");
	print '<td colspan="2">';
	print '<input type="text" name="TAKEPOS_SUMUP_AFFILIATE" value="'.$conf->global->TAKEPOS_SUMUP_AFFILIATE.'"></input>';
	print "</td></tr>\n";
	print '<tr class="oddeven"><td>';
	print $langs->trans("SumupAppId");
	print '<td colspan="2">';
	print '<input type="text" name="TAKEPOS_SUMUP_APPID" value="'.$conf->global->TAKEPOS_SUMUP_APPID.'"></input>';
	print "</td></tr>\n";

	print '</table>';
	print '</div>';
}

print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";

llxFooter();
$db->close();
