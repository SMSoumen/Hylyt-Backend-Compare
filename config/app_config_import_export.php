<?php
use Illuminate\Support\Facades\Config;

$configVar = array();
$configVar['employee_template_name'] = 'employee_template.xlsx';
$configVar['url_path_employee_template']='/templates/';

/*$config['employee_template']  = array('Sr No*','Employee ID*','Employee Name*','Department*','Designation*','Gender','Address','Contact Number','Email*','Date of Joining(dd-mm-yyyy)*','Date of Leaving(dd-mm-yyyy)','Manager(Employee ID)*','CTC*');
*/

return $configVar;