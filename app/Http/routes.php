<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', 'UserController@login');

/* User Routes Begin */
Route::get('login',  ['as' => 'login', 'uses' => 'UserController@login']);
Route::post('authenticate', ['as' => 'authenticate', 'uses' => 'UserController@authenticate']);
Route::get('changePassword', ['as' => 'user.changePassword', 'uses' => 'UserController@changePassword']);
Route::post('validateCurrentPassword', ['as' => 'user.validateCurrentPassword', 'uses' => 'UserController@validateCurrentPassword']);
Route::post('updatePassword', ['as' => 'user.updatePassword', 'uses' => 'UserController@updatePassword']);
Route::get('logout', ['as' => 'logout', 'uses' => 'UserController@logout']);
/* User Routes End */

/* Permission Routes End */
Route::get('/permissionDenied', ['uses' => 'PermissionController@permissionDenied', 'as' => 'roleright.permissionDenied']);
/* Permission Routes End */

/* Dashboard Routes Begin */
Route::get('dashboard', ['as' => 'dashboard', 'uses' => 'DashboardController@index']);
Route::post('/loadDashboardStats', ['as' => 'dashboard.loadDashboardStats', 'uses' => 'DashboardController@loadDashboardStats']);
/* Dashboard Routes End */

/* Common Functions Routes Begin */
Route::post('/cfCompareDateValidation', ['uses' => 'CommonFunctionController@dateCompareValidation', 'as' => 'commonfunction.compareDates']);
Route::get('/sendTestNotif', ['uses' => 'CommonFunctionController@sendTestNotif', 'as' => 'commonfunction.sendTestNotif']);
/* Common Functions Routes End */

/* Department Routes Begin */
Route::get('/departmentDatatable', ['uses' => 'DepartmentController@departmentDatatable', 'as' => 'departmentDatatable']);
Route::get('/department', ['uses' => 'DepartmentController@index', 'as' => 'department']);
Route::get('/addDepartment', ['uses' => 'DepartmentController@create', 'as' => 'department.create']);
Route::post('/saveDepartment', ['uses' => 'DepartmentController@store', 'as' => 'department.store']);
Route::post('/departmentDetails', ['uses' => 'DepartmentController@show', 'as' => 'department.show']);
Route::post('/editDepartment', ['uses' => 'DepartmentController@edit', 'as' => 'department.edit']);
Route::post('/updateDepartment', ['uses' => 'DepartmentController@update', 'as' => 'department.update']);
Route::post('/deleteDepartment', ['uses' => 'DepartmentController@destroy', 'as' => 'department.delete']);
Route::post('/validateDepartmentName', ['uses' => 'DepartmentController@validateDepartmentName', 'as' => 'department.validateDepartmentName']);
Route::post('/checkDeptAvailForDelete', ['uses' => 'DepartmentController@checkAvailForDelete', 'as' => 'department.checkAvailForDelete']);
/* Department Routes End */

/* Roles Routes Begin */
Route::get('/roleDatatable', ['uses' => 'RoleController@roleDatatable', 'as' => 'roleDatatable']);
Route::get('/role', ['uses' => 'RoleController@index', 'as' => 'role']);
Route::get('/addRole', ['uses' => 'RoleController@create', 'as' => 'role.create']);
Route::post('/saveRole', ['uses' => 'RoleController@store', 'as' => 'role.store']);
Route::post('/roleDetails', ['uses' => 'RoleController@show', 'as' => 'role.show']);
Route::post('/editRole', ['uses' => 'RoleController@edit', 'as' => 'role.edit']);
Route::post('/updateRole', ['uses' => 'RoleController@update', 'as' => 'role.update']);
Route::post('/deleteRole', ['uses' => 'RoleController@destroy', 'as' => 'role.delete']);
Route::post('/validateRoleName', ['uses' => 'RoleController@validateRoleName', 'as' => 'role.validateRoleName']);
Route::post('/checkRoleAvailForDelete', ['uses' => 'RoleController@checkAvailForDelete', 'as' => 'role.checkAvailForDelete']);
/* Roles Routes End */

/* Modules Routes Begin */
Route::get('/moduleDatatable', ['uses' => 'ModuleController@moduleDatatable', 'as' => 'moduleDatatable']);
Route::get('/module', ['uses' => 'ModuleController@index', 'as' => 'module']);
Route::get('/addModule', ['uses' => 'ModuleController@create', 'as' => 'module.create']);
Route::post('/saveModule', ['uses' => 'ModuleController@store', 'as' => 'module.store']);
Route::post('/moduleDetails', ['uses' => 'ModuleController@show', 'as' => 'module.show']);
Route::post('/editModule', ['uses' => 'ModuleController@edit', 'as' => 'module.edit']);
Route::post('/updateModule', ['uses' => 'ModuleController@update', 'as' => 'module.update']);
Route::post('/deleteModule', ['uses' => 'ModuleController@destroy', 'as' => 'module.delete']);
Route::post('/validateModuleName', ['uses' => 'ModuleController@validateModuleName', 'as' => 'module.validateModuleName']);
Route::post('/checkModuleAvailForDelete', ['uses' => 'ModuleController@checkAvailForDelete', 'as' => 'module.checkAvailForDelete']);
/* Modules Routes End */

/* Rights Routes Begin */
Route::get('/modifyRoleRights', ['uses' => 'RoleRightController@roleRights', 'as' => 'roleRights']);
Route::post('/loadRightsForRole', ['uses' => 'RoleRightController@loadRightsForRole', 'as' => 'roleright.loadRightsForRole']);
Route::post('/validateRoleRight', ['uses' => 'RoleRightController@validateRoleRight', 'as' => 'roleright.validateRoleRight']);
Route::get('/modifyModuleRights', ['uses' => 'RoleRightController@moduleRights', 'as' => 'moduleRights']);
Route::post('/loadRightsForModule', ['uses' => 'RoleRightController@loadRightsForModule', 'as' => 'roleright.loadRightsForModule']);
Route::post('/validateModuleRight', ['uses' => 'RoleRightController@validateModuleRight', 'as' => 'roleright.validateModuleRight']);
/* Rights Routes End */

/* Employee Routes Begin */
Route::get('/employeeDatatable', ['uses' => 'EmployeeController@employeeDatatable', 'as' => 'employeeDatatable']);
Route::get('/employee', ['uses' => 'EmployeeController@index', 'as' => 'employee']);
Route::get('/addEmployee', ['uses' => 'EmployeeController@create', 'as' => 'employee.create']);
Route::post('/saveEmployee', ['uses' => 'EmployeeController@store', 'as' => 'employee.store']);
Route::post('/validateEmployeeNo', ['uses' => 'EmployeeController@validateEmployeeNo', 'as' => 'employee.validateEmployeeNo']);
Route::post('/validateUserName', ['uses' => 'EmployeeController@validateUserName', 'as' => 'employee.validateUserName']);
Route::post('/employeeDetails', ['uses' => 'EmployeeController@show', 'as' => 'employee.show']);
Route::post('/editEmployee', ['uses' => 'EmployeeController@edit', 'as' => 'employee.edit']);
Route::post('/updateEmployee', ['uses' => 'EmployeeController@update', 'as' => 'employee.update']);
Route::post('/deleteEmployee', ['uses' => 'EmployeeController@destroy', 'as' => 'employee.delete']);
Route::post('/checkEmpAvailForDelete', ['uses' => 'EmployeeController@checkAvailForDelete', 'as' => 'employee.checkAvailForDelete']);
Route::post('/changeEmployeeStatus', ['uses' => 'EmployeeController@changeStatus', 'as' => 'employee.changeStatus']);
/* Employee Routes End */

/* Appuser Server Routes Begin */
Route::get('/appuserDatatable', ['uses' => 'AppuserServerController@appuserDatatable', 'as' => 'appuserServerDatatable']);
Route::get('/appuser', ['uses' => 'AppuserServerController@index', 'as' => 'appuserServer']);
Route::post('/appuserSerDetails', ['uses' => 'AppuserServerController@show', 'as' => 'appuserServer.show']);
Route::post('/loadModifyAppuserQuotaModal', ['uses' => 'AppuserServerController@loadModifyAppuserQuotaModal', 'as' => 'appuserServer.loadModifyQuotaModal']);
Route::post('/saveAppuserQuotaDetails', ['uses' => 'AppuserServerController@saveAppuserQuotaDetails', 'as' => 'appuserServer.saveAppuserQuotaDetails']);
Route::post('/changeAppuserStatus', ['uses' => 'AppuserServerController@changeStatus', 'as' => 'appuserServer.changeStatus']);
Route::post('/changeAppuserPremiumStatus', ['uses' => 'AppuserServerController@changePremiumStatus', 'as' => 'appuserServer.changePremiumStatus']);
Route::get('/deletedAppuserDatatable', ['uses' => 'AppuserServerController@deletedAppuserDatatable', 'as' => 'deletedAppuserServerDatatable']);
Route::get('/deletedAppuser', ['uses' => 'AppuserServerController@deletedAppuserList', 'as' => 'deletedAppuserServer']);
Route::post('/loadModifyAppuserPremiumExpirationDateModal', ['uses' => 'AppuserServerController@loadModifyAppuserPremiumExpirationDateModal', 'as' => 'appuserServer.loadModifyPremiumExpirationDateModal']);
Route::post('/saveAppuserPremiumExpirationDateDetails', ['uses' => 'AppuserServerController@saveAppuserPremiumExpirationDateDetails', 'as' => 'appuserServer.savePremiumExpirationDateDetails']);
/* Appuser Server Routes End */

/* Notification Routes Begin */
Route::get('/notificationDatatable', ['uses' => 'NotificationController@notificationDatatable', 'as' => 'notificationDatatable']);
Route::get('/notification', ['uses' => 'NotificationController@index', 'as' => 'notification']);
Route::post('/notificationDetails', ['uses' => 'NotificationController@show', 'as' => 'notification.show']);
Route::post('/loadSendNotificationModal', ['uses' => 'NotificationController@loadSendAppuserNotificationModal', 'as' => 'notification.loadNotifModal']);
Route::post('/sendAppuserNotification', ['uses' => 'NotificationController@sendAppuserNotification', 'as' => 'notification.sendNotification']);
Route::post('/sendSelAppuserNotification', ['uses' => 'NotificationController@sendSelAppuserNotification', 'as' => 'notification.sendSelAppuserNotification']);
Route::post('/saveNotificationAsDraft', ['uses' => 'NotificationController@saveNotificationAsDraft', 'as' => 'notification.saveNotificationAsDraft']);
Route::post('/filterAppuserForNotification', ['uses' => 'NotificationController@filterAppuserListForSend', 'as' => 'notification.filterAppuserListForSend']);
Route::get('/appuserNotificationDatatable', ['uses' => 'NotificationController@appuserDatatable', 'as' => 'appuserNotificationDatatable']);
Route::get('/sendAppuserTestNotification', ['uses' => 'NotificationController@sendTestNotification', 'as' => 'sendTestNotification']);
/* Notification Routes End */

/* Content Addition Routes Begin */
Route::get('/contentAdditionDatatable', ['uses' => 'ContentAdditionController@contentAdditionDatatable', 'as' => 'contentAdditionDatatable']);
Route::get('/contentAddition', ['uses' => 'ContentAdditionController@index', 'as' => 'contentAddition']);
Route::post('/contentAdditionDetails', ['uses' => 'ContentAdditionController@show', 'as' => 'contentAddition.show']);
Route::post('/loadAddContentModal', ['uses' => 'ContentAdditionController@loadAddAppuserContentModal', 'as' => 'contentAddition.loadAddContentModal']);
Route::post('/addAppuserContent', ['uses' => 'ContentAdditionController@addAppuserContent', 'as' => 'contentAddition.addAppuserContent']);
Route::post('/addSelAppuserContent', ['uses' => 'ContentAdditionController@addSelAppuserContent', 'as' => 'contentAddition.addSelAppuserContent']);
Route::post('/filterAppuserForContent', ['uses' => 'ContentAdditionController@filterAppuserListForSend', 'as' => 'contentAddition.filterAppuserListForSend']);
Route::get('/appuserContentDatatable', ['uses' => 'ContentAdditionController@appuserDatatable', 'as' => 'appuserContentDatatable']);
/* Content Addition Routes End */

/* ThoughtTip Routes Begin */
Route::get('/thoughtTipDatatable', ['uses' => 'ThoughtTipController@thoughtTipDatatable', 'as' => 'thoughtTipDatatable']);
Route::get('/thoughtTip', ['uses' => 'ThoughtTipController@index', 'as' => 'thoughtTip']);
Route::get('/addThoughtTip', ['uses' => 'ThoughtTipController@create', 'as' => 'thoughtTip.create']);
Route::post('/saveThoughtTip', ['uses' => 'ThoughtTipController@store', 'as' => 'thoughtTip.store']);
Route::post('/thoughtTipDetails', ['uses' => 'ThoughtTipController@show', 'as' => 'thoughtTip.show']);
Route::post('/editThoughtTip', ['uses' => 'ThoughtTipController@edit', 'as' => 'thoughtTip.edit']);
Route::post('/updateThoughtTip', ['uses' => 'ThoughtTipController@update', 'as' => 'thoughtTip.update']);
Route::post('/deleteThoughtTip', ['uses' => 'ThoughtTipController@destroy', 'as' => 'thoughtTip.delete']);
Route::post('/validateThoughtTipDate', ['uses' => 'ThoughtTipController@validateThoughtTipDate', 'as' => 'thoughtTip.validateThoughtTipDate']);
Route::post('/checkThoughtTipAvailForDelete', ['uses' => 'ThoughtTipController@checkAvailForDelete', 'as' => 'thoughtTip.checkAvailForDelete']);
/* ThoughtTip Routes End */

/* Subscription Routes Begin */
Route::get('/usVer', ['uses' => 'SubscriptionController@unsubscribeVerificationPending', 'as' => 'unsubscribeVerificationPending']);
Route::get('/usInac', ['uses' => 'SubscriptionController@unsubscribeInactivity', 'as' => 'unsubscribeInactivity']);
/* Subscription Routes End */

/* API Routes Begin */
	/* Appuser Admin Routes Begin */
	Route::post('/admAuthenticate',  ['middleware' => ['cors'], 'as' => 'admAuthenticate', 'uses' => 'AdminApi\AppAdminController@authenticate']);
	Route::post('/admValidateCurrPassword',  ['middleware' => ['cors'], 'as' => 'admValidateCurrPassword', 'uses' => 'AdminApi\AppAdminController@validateCurrPassword']);
	Route::post('/admUpdatePassword',  ['middleware' => ['cors'], 'as' => 'admUpdatePassword', 'uses' => 'AdminApi\AppAdminController@updatePassword']);
	Route::post('/loadAdminUserMenu', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@loadAdminUserMenu', 'as' => 'loadAdmSysMenu']);
	Route::post('/loadAdminDashboardStats', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@loadDashboardStats', 'as' => 'loadAdmDashboardStats']);
	Route::post('/resendAdminCredentials', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@resendCredentials', 'as' => 'resendAdmCredentials']);
	Route::post('/loadOrgInformationModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@loadOrgInformationModal', 'as' => 'loadOrgInformationModal']);
	Route::post('/saveOrgAppInformation', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@saveOrgAppInformation', 'as' => 'saveOrgAppInformation']);
	Route::post('/admValidateOtherAdminEmail',  ['middleware' => ['cors'], 'as' => 'admValidateOtherAdminEmail', 'uses' => 'AdminApi\AppAdminController@validateOtherAdminEmail']);
	Route::post('/admValidateOtherAdminPassword',  ['middleware' => ['cors'], 'as' => 'admValidateOtherAdminPassword', 'uses' => 'AdminApi\AppAdminController@validateOtherAdminPassword']);
	Route::post('/admUserLogout',  ['middleware' => ['cors'], 'as' => 'admUserLogout', 'uses' => 'AdminApi\AppAdminController@userLogout']);
	Route::post('/loadOrgStackReferralCodeModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@loadOrgStackReferralCodeModal', 'as' => 'loadOrgStackReferralCodeModal']);
	Route::post('/saveOrgStackReferralCode', ['middleware' => ['cors'], 'uses' => 'AdminApi\AppAdminController@saveOrgStackReferralCode', 'as' => 'saveOrgStackReferralCode']);
	/* Appuser Admin Routes End */

	/* Organization Department Routes Begin */
	Route::post('/loadOrgDeptView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@loadDepartmentView', 'as' => 'orgDepartment.loadView']);
	Route::post('/loadOrgDeptAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@loadAddEditModal', 'as' => 'orgDepartment.loadAddEditModal']);
	Route::post('/orgDeptDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@departmentDatatable', 'as' => 'orgDeptDatatable']);
	Route::post('/saveOrgDeptDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@saveDetails', 'as' => 'orgDepartment.saveDetails']);
	Route::post('/deleteOrgDept', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@destroy', 'as' => 'orgDepartment.delete']);
	Route::post('/validateOrgDeptName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@validateDepartmentName', 'as' => 'orgDepartment.validateDepartmentName']);
	Route::post('/checkOrgDeptAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDepartmentController@checkAvailForDelete', 'as' => 'orgDepartment.checkAvailForDelete']);
	/* Organization Department Routes End */

	/* Organization SystemTag Routes Begin */
	Route::post('/loadOrgSystemTagView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@loadSystemTagView', 'as' => 'orgSystemTag.loadView']);
	Route::post('/loadOrgSystemTagAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@loadAddEditModal', 'as' => 'orgSystemTag.loadAddEditModal']);
	Route::post('/orgSystemTagDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@systemTagDatatable', 'as' => 'orgSystemTagDatatable']);
	Route::post('/saveOrgSystemTagDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@saveDetails', 'as' => 'orgSystemTag.saveDetails']);
	Route::post('/sendOrgSystemTagModifiedNotification', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@sendTagModifiedNotification', 'as' => 'orgSystemTag.sendTagModifiedNotification']);
	Route::post('/deleteOrgSystemTag', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@destroy', 'as' => 'orgSystemTag.delete']);
	Route::post('/validateOrgSystemTagName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@validateSystemTagName', 'as' => 'orgSystemTag.validateSystemTagName']);
	Route::post('/checkOrgSystemTagAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgSystemTagController@checkAvailForDelete', 'as' => 'orgSystemTag.checkAvailForDelete']);
	/* Organization SystemTag Routes End */

	/* Organization Designation Routes Begin */
	Route::post('/loadOrgDesigView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@loadDesignationView', 'as' => 'orgDesignation.loadView']);
	Route::post('/loadOrgDesigAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@loadAddEditModal', 'as' => 'orgDesignation.loadAddEditModal']);
	Route::post('/orgDesigDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@designationDatatable', 'as' => 'orgDesigDatatable']);
	Route::post('/saveOrgDesigDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@saveDetails', 'as' => 'orgDesignation.saveDetails']);
	Route::post('/deleteOrgDesig', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@destroy', 'as' => 'orgDesignation.delete']);
	Route::post('/validateOrgDesigName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@validateDesignationName', 'as' => 'orgDesignation.validateDesignationName']);
	Route::post('/checkOrgDesigAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgDesignationController@checkAvailForDelete', 'as' => 'orgDesignation.checkAvailForDelete']);
	/* Organization Designation Routes End */

	/* Organization Badge Routes Begin */
	Route::post('/loadOrgBadgeView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@loadBadgeView', 'as' => 'orgBadge.loadView']);
	Route::post('/loadOrgBadgeAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@loadAddEditModal', 'as' => 'orgBadge.loadAddEditModal']);
	Route::post('/orgBadgeDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@badgeDatatable', 'as' => 'orgBadgeDatatable']);
	Route::post('/saveOrgBadgeDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@saveDetails', 'as' => 'orgBadge.saveDetails']);
	Route::post('/deleteOrgBadge', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@destroy', 'as' => 'orgBadge.delete']);
	Route::post('/validateOrgBadgeName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@validateBadgeName', 'as' => 'orgBadge.validateBadgeName']);
	Route::post('/checkOrgBadgeAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBadgeController@checkAvailForDelete', 'as' => 'orgBadge.checkAvailForDelete']);
	/* Organization Badge Routes End */

	/* Organization Employee Routes Begin */
	Route::post('/loadOrgEmployeeView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadEmployeeView', 'as' => 'orgEmployee.loadView']);
	Route::post('/loadOrgEmployeeAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadAddEditModal', 'as' => 'orgEmployee.loadAddEditModal']);
	Route::post('/orgEmployeeDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@employeeDatatable', 'as' => 'orgEmployeeDatatable']);
	Route::post('/saveOrgEmployeeDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@saveDetails', 'as' => 'orgEmployee.saveDetails']);
	Route::post('/deleteOrgEmployee', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@destroy', 'as' => 'orgEmployee.delete']);
	Route::post('/validateOrgEmployeeNo', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@validateEmployeeNo', 'as' => 'orgEmployee.validateEmployeeNo']);
	Route::post('/validateOrgEmployeeEmail', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@validateEmployeeEmail', 'as' => 'orgEmployee.validateEmployeeEmail']);
	Route::post('/checkOrgEmployeeAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@checkAvailForDelete', 'as' => 'orgEmployee.checkAvailForDelete']);
	Route::post('/loadOrgEmployeeUploadExcel', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadUploadExcel', 'as' => 'orgEmployee.loadUploadExcel']);
	Route::post('/loadOrgEmployeeUpload', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@import', 'as' => 'orgEmployee.import']);
	Route::post('/loadOrgNotificationView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadNotificationView', 'as' => 'orgEmployee.loadView']);
	Route::post('/changeOrgEmployeeStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@changeStatus', 'as' => 'orgEmployee.changeStatus']);
	Route::post('/loadOrgEmployeeModifyQuotaModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadModifyQuotaModal', 'as' => 'orgEmployee.loadModifyQuotaModal']);
	Route::post('/saveOrgEmployeeQuotaDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@saveQuotaDetails', 'as' => 'orgEmployee.saveQuotaDetails']);
	Route::post('/loadOrgEmployeeRestoreContentModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadRestoreContentModal', 'as' => 'orgEmployee.loadRestoreContentModal']);
	Route::post('/performOrgEmployeeContentRestore', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@performContentRestore', 'as' => 'orgEmployee.performContentRestore']);
	Route::post('/loadOrgEmployeeModifyShareRightModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadModifyShareRightModal', 'as' => 'orgEmployee.loadModifyShareRightModal']);
	Route::post('/saveOrgEmployeeShareRightDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@saveShareRightDetails', 'as' => 'orgEmployee.saveShareRightDetails']);
	Route::post('/sendOrgEmployeeCredentialMail', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@sendCredentialMail', 'as' => 'orgEmployee.sendCredentialMail']);
	Route::post('/loadOrgEmployeeCredentialModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadCredentialModal', 'as' => 'orgEmployee.loadCredentialModal']);
	Route::post('/loadOrgEmployeeModifyEmailModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@loadEditEmailModal', 'as' => 'orgEmployee.loadEditEmailModal']);
	Route::post('/changeOrgEmployeeEmail', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@changeEmail', 'as' => 'orgEmployee.changeEmail']);
	Route::post('/changeOrgEmployeeWebAccessStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@changeWebAccessStatus', 'as' => 'orgEmployee.changeWebAccessStatus']);
	Route::post('/checkOrgEmployeeAvailForDetachment', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@checkAvailForDetachment', 'as' => 'orgEmployee.checkAvailForDetachment']);
	Route::post('/detachOrgEmployee', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@detachEmployee', 'as' => 'orgEmployee.detach']);
	Route::post('/changeOrgEmployeeFileSaveShareEnabledStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@changeFileSaveShareEnabledStatus', 'as' => 'orgEmployee.changeFileSaveShareEnabledStatus']);
	Route::post('/changeOrgEmployeeScreenShareEnabledStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgEmployeeController@changeScreenShareEnabledStatus', 'as' => 'orgEmployee.changeScreenShareEnabledStatus']);
	/* Organization Employee Routes End */

	/* Organization Template Routes Begin */
	Route::post('/loadOrgTemplateView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@loadTemplateView', 'as' => 'orgTemplate.loadView']);
	Route::post('/loadOrgTemplateAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@loadAddEditModal', 'as' => 'orgTemplate.loadAddEditModal']);
	Route::post('/orgTemplateDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@templateDatatable', 'as' => 'orgTemplateDatatable']);
	Route::post('/saveOrgTemplateDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@saveDetails', 'as' => 'orgTemplate.saveDetails']);
	Route::post('/deleteOrgTemplate', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@destroy', 'as' => 'orgTemplate.delete']);
	Route::post('/checkOrgTemplateAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@checkAvailForDelete', 'as' => 'orgTemplate.checkAvailForDelete']);
	Route::post('/validateOrgTemplateName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgTemplateController@validateTemplateName', 'as' => 'orgTemplate.validateTemplateName']);
	/* Organization Template Routes End */

	/* Organization Content Addition Routes Begin */
	Route::post('/orgContentAdditionDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@contentAdditionDatatable', 'as' => 'orgContentAdditionDatatable']);
	Route::post('/orgContentAddition', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@loadContentAdditionView', 'as' => 'orgContentAddition']);
	Route::post('/orgContentAdditionDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@show', 'as' => 'orgContentAddition.show']);
	Route::post('/loadAddOrgContentModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@loadAddContentModal', 'as' => 'orgContentAddition.loadAddContentModal']);
	Route::post('/addAppuserOrgContent', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@addAppuserContent', 'as' => 'orgContentAddition.addAppuserContent']);
	Route::post('/addSelOrgAppuserContent', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@addSelAppuserContent', 'as' => 'orgContentAddition.addSelAppuserContent']);
	Route::post('/filterOrgAppuserForContent', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@filterAppuserListForSend', 'as' => 'orgContentAddition.filterAppuserListForSend']);
	Route::post('/orgAppuserContentDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@appuserDatatable', 'as' => 'orgAppuserContentDatatable']);
	Route::post('/checkOrgContentAdditionAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@checkAvailForDelete', 'as' => 'orgContentAddition.checkAvailForDelete']);
	Route::post('/deleteOrgContentAddition', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgContentAdditionController@destroy', 'as' => 'orgContentAddition.delete']);
	/* Organization Content Addition Routes End */

	/* Organization Notification Routes Begin */
	Route::post('/orgNotificationDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@notificationDatatable', 'as' => 'orgNotificationDatatable']);
	Route::post('/orgNotification', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@loadNotificationView', 'as' => 'orgNotification']);
	Route::post('/orgNotificationDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@show', 'as' => 'orgNotification.show']);
	Route::post('/loadAddOrgNotificationModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@loadAddNotifModal', 'as' => 'orgNotification.loadAddNotificationModal']);
	Route::post('/addAppuserOrgNotification', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@addAppuserNotification', 'as' => 'orgNotification.addAppuserNotification']);
	Route::post('/addSelOrgAppuserNotification', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@addSelAppuserNotification', 'as' => 'orgNotification.addSelAppuserNotification']);
	Route::post('/filterOrgAppuserForNotification', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@filterAppuserListForSend', 'as' => 'orgNotification.filterAppuserListForSend']);
	Route::post('/orgAppuserNotificationDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@appuserDatatable', 'as' => 'orgAppuserNotificationDatatable']);
	Route::post('/checkOrgNotificationAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@checkAvailForDelete', 'as' => 'orgNotification.checkAvailForDelete']);
	Route::post('/deleteOrgNotification', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgNotificationController@destroy', 'as' => 'orgNotification.delete']);
	/* Organization Notification Routes End */

	/* Organization Group Routes Begin */
	Route::post('/loadOrgGroupView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@loadGroupView', 'as' => 'orgGroup.loadView']);
	Route::post('/loadOrgGroupAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@loadAddEditModal', 'as' => 'orgGroup.loadAddEditModal']);
	Route::post('/orgGroupDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@groupDatatable', 'as' => 'orgGroupDatatable']);
	Route::post('/saveOrgGroupDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@saveDetails', 'as' => 'orgGroup.saveDetails']);
	Route::post('/loadOrgGroupModifyRightModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@loadModifyRightModal', 'as' => 'orgGroup.loadModifyRightModal']);
	Route::post('/saveOrgGroupRightDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@saveRightDetails', 'as' => 'orgGroup.saveRightDetails']);
	Route::post('/deleteOrgGroup', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@destroy', 'as' => 'orgGroup.delete']);
	Route::post('/validateOrgGroupName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@validateGroupName', 'as' => 'orgGroup.validateGroupName']);
	Route::post('/checkOrgGroupAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@checkAvailForDelete', 'as' => 'orgGroup.checkAvailForDelete']);
	Route::post('/loadOrgGroupInfoModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@loadGroupDetailsModal', 'as' => 'orgGroup.loadOrgGroupDetailsModal']);
	Route::post('/changeOrgGroupStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgGroupController@changeStatus', 'as' => 'orgGroup.changeStatus']);

	/* Organization Group Routes End */

	/* Organization Backup Management Routes Begin */
	Route::post('/orgBackupDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@backupDatatable', 'as' => 'orgBackupDatatable']);
	Route::post('/orgBackup', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@loadBackupView', 'as' => 'orgBackup']);
	Route::post('/orgBackupDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@show', 'as' => 'orgBackup.show']);
	Route::post('/loadAddOrgBackupModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@loadAddBackupModal', 'as' => 'orgBackup.loadAddBackupModal']);
	Route::post('/checkOrgForBackupCreate', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@checkAvailForBackup', 'as' => 'orgBackup.checkAvailForBackup']);
	Route::post('/saveOrgBackupDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@addBackup', 'as' => 'orgBackup.addBackup']);
	Route::post('/checkOrgBackupAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@checkAvailForDelete', 'as' => 'orgBackup.checkAvailForDelete']);
	Route::post('/deleteOrgBackup', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@destroy', 'as' => 'orgBackup.delete']);
	Route::post('/checkOrgBackupAvailForRestore', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@checkAvailForRestore', 'as' => 'orgBackup.checkAvailForRestore']);
	Route::post('/loadConfirmOrgRestoreModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@loadConfirmRestoreModal', 'as' => 'orgBackup.loadConfirmRestoreModal']);
	Route::post('/restoreOrgBackup', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@performRestore', 'as' => 'orgBackup.restore']);
	Route::post('/downloadOrgBackup', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgBackupManagementController@performDownload', 'as' => 'orgBackup.download']);
	/* Organization Backup Management Routes End */

	/* Organization Admin Log Routes Begin */
	Route::post('/orgAdminLogDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgAdminLogController@adminLogDatatable', 'as' => 'orgAdminLogDatatable']);
	Route::post('/loadOrgAdminLogView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgAdminLogController@loadAdminLogView', 'as' => 'orgAdminLog']);
	/* Organization Admin Log Routes End */

	/* Organization Video Conference Routes Begin */
	Route::post('/orgVideoConferenceDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgVideoConferenceController@orgVideoConferenceDatatable', 'as' => 'orgVideoConferenceDatatable']);
	Route::post('/loadOrgVideoConferenceView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgVideoConferenceController@loadVideoConferenceView', 'as' => 'orgVideoConference']);
	Route::post('/orgOrgVideoConferenceInfoModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgVideoConferenceController@loadInfoModal', 'as' => 'orgVideoConference.loadInfoModal']);
	Route::post('/loadAddOrgVideoConferenceModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgVideoConferenceController@loadAddBackupModal', 'as' => 'orgVideoConference.loadAddBackupModal']);
	Route::post('/checkOrgVideoConferenceAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgVideoConferenceController@checkAvailForDelete', 'as' => 'orgVideoConference.checkAvailForDelete']);
	Route::post('/deleteOrgVideoConference', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgVideoConferenceController@destroy', 'as' => 'orgVideoConference.delete']);
	/* Organization Video Conference Routes End */

	/* Organization Administration Routes Begin */
	Route::post('/loadOrganizationAdministrationView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationAdministrationController@loadOrganizationAdministrationView', 'as' => 'organizationAdministration']);
	/* Organization Administration Routes End */
	
	/* Appuser Organization Routes Begin */
	Route::post('/appuserSubscribeOrganization', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@subscribeOrganization', 'as' => 'orgApp.subscribeOrganization']);
	Route::post('/appuserOrganizationPrimarySync', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@organizationPrimarySync', 'as' => 'orgApp.organizationPrimarySync']);
	Route::post('/appuserOrganizationGroupList', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@getAppuserGroupList', 'as' => 'orgApp.getAppuserGroupList']);
	Route::post('/appuserOrganizationUserList', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@getAppuserEmployeeList', 'as' => 'orgApp.getAppuserEmployeeList']);
	Route::post('/shareAppOrganizationContent', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@shareOrgContent', 'as' => 'orgApp.shareOrgContent']);
	Route::post('/appuserOrganizationTemplateList', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@getAppuserTemplateList', 'as' => 'orgApp.getAppuserTemplateList']);
	Route::post('/getOrganizationTemplateDetails', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@getTemplateDetails', 'as' => 'orgApp.getTemplateDetails']);
	Route::post('/organizationAppuserDetails', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@getEmployeeDetails', 'as' => 'orgApp.getEmployeeDetails']);
	Route::post('/loadAppuserSelectProfileModal', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadSelectProfileModal', 'as' => 'orgApp.loadSelectProfileModal']);
	Route::post('/loadAppuserProfileSettings', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadProfileSettingsModal', 'as' => 'orgApp.loadProfileSettingsModal']);
	Route::post('/loadSelectProfileList', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadSelectProfileList', 'as' => 'orgApp.loadSelectProfileList']);	
	Route::post('/loadAppuserInformationModal', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadAppuserInformationModal', 'as' => 'orgApp.loadAppuserInformationModal']);	
	Route::post('/loadAppuserOrgInformationModal', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadAppuserOrgInformationModal', 'as' => 'orgApp.loadAppuserOrgInformationModal']);	
	Route::post('/appuserOrgReportAbuse', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@appuserOrgReportAbuse', 'as' => 'orgApp.appuserOrgReportAbuse']);	
	Route::post('/appuserOrganizationLeave', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@appuserOrgLeave', 'as' => 'orgApp.appuserOrgLeave']);	
	Route::post('/appuserOrgContactAdmin', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@appuserOrgContactAdmin', 'as' => 'orgApp.appuserOrgContactAdmin']);
	Route::post('/appuserOrgScreenshotTaken', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@appuserOrgScreenshotTaken', 'as' => 'orgApp.appuserOrgScreenshotTaken']);
	Route::post('/appuserOrgSenderEmailMappedList', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@getAppuserSenderEmailMappedList', 'as' => 'orgApp.getAppuserSenderEmailMappedList']);
	Route::get('/appuserOrganizationProfileSubscribe', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@subscribeAppuserOrganizationViaLink', 'as' => 'orgApp.subscribeAppuserOrganizationViaLink']);
	Route::post('/loadOrganizationDetailsForCnameMapping', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadOrganizationDetailsForCnameMapping', 'as' => 'orgApp.loadOrganizationDetailsForCnameMapping']);
	Route::post('/loadWLAppDetailsForCnameMapping', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationAppController@loadWLAppDetailsForCnameMapping', 'as' => 'orgApp.loadWLAppDetailsForCnameMapping']);
	/* Appuser Organization Routes End */
	
	/* Appuser Routes Begin */
	Route::post('/appuserRegister', ['uses' => 'Api\AppuserController@register', 'as' => 'appuser.register']);
	Route::post('/appuserAuthenticate', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@authenticate', 'as' => 'appuser.authenticate']);
	Route::post('/appuserVerify', ['uses' => 'Api\AppuserController@verify', 'as' => 'appuser.verify']);
	Route::post('/resendAppuserVerificationCode', ['uses' => 'Api\AppuserController@resendVerificationCode', 'as' => 'appuser.resendVerificationCode']);
	Route::post('/sendAppuserOtp', ['uses' => 'Api\AppuserController@sendOtp', 'as' => 'appuser.sendOtp']);
	Route::post('/verifyAppuserOtp', ['uses' => 'Api\AppuserController@verifyOtp', 'as' => 'appuser.verifyOtp']);
	Route::post('/sendLoggedInAppuserOtp', ['uses' => 'Api\AppuserController@sendLoggedInAppUserOtp', 'as' => 'appuser.sendLoggedInAppUserOtp']);
	Route::post('/appuserDetails', ['uses' => 'Api\AppuserController@userDetails', 'as' => 'appuser.show']);
	Route::post('/saveAppuserDetails', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@saveUserDetails', 'as' => 'appuser.update']);
	Route::post('/saveAppuserReferralCode', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@saveUserReferralCode', 'as' => 'appuser.updateReferralCode']);
	Route::post('/loadAppuserChangePasswordModal', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@loadChangePasswordModal', 'as' => 'appuser.loadChangePasswordModal']);
	Route::post('/validateAppuserPassword', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@validatePassword', 'as' => 'appuser.validatePassword']);
	Route::post('/changeAppuserPassword', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@changePassword', 'as' => 'appuser.changePassword']);
	Route::post('/appuserSocialAuthenticate', ['uses' => 'Api\AppuserController@authenticateSocialLogin', 'as' => 'appuser.authenticateSocialLogin']);
	Route::post('/sendAppuserForgotPin', ['uses' => 'Api\AppuserController@sendPin', 'as' => 'appuser.sendPin']);
	Route::post('/sendAppuserForgotFolderPin', ['uses' => 'Api\AppuserController@sendFolderPin', 'as' => 'appuser.sendFolderPin']);
	Route::post('/appuserDelete', ['uses' => 'Api\AppuserController@deleteUser', 'as' => 'appuser.delete']);
	Route::post('/appuserLogout', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@userLogout', 'as' => 'appuser.logout']);
	Route::get('/usrVerify', ['uses' => 'Api\AppuserController@verifyAppuserViaLink', 'as' => 'appuser.verifyViaLink']);
	Route::post('/appuserRequestAccountType', ['uses' => 'Api\AppuserController@saveUserAccountRequest', 'as' => 'appuser.saveUserAccountRequest']);
	Route::post('/appuserAccountTypeSubscribed', ['uses' => 'Api\AppuserController@saveUserAccountSubscribed', 'as' => 'appuser.saveUserAccountSubscribed']);
	Route::post('/getThoughtTipForConsideredDate', ['uses' => 'Api\AppuserController@getThoughtTipForConsideredDate', 'as' => 'appuser.getThoughtTipForConsideredDate']);
	Route::post('/validateAppuserAppPin', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@validateAppPin', 'as' => 'appuser.validateAppPin']);
	Route::post('/checkAppuserAppPinEnabled', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@checkAppPinEnabled', 'as' => 'appuser.checkAppPinEnabled']);
	Route::post('/appuserSocialAppleAuthenticate', ['uses' => 'Api\AppuserController@authenticateAppleLogin', 'as' => 'appuser.authenticateAppleLogin']);
	Route::post('/tempSetAppuserDefaultParamsForAlreadyVerified', ['uses' => 'Api\AppuserController@tempSetUserDefaultParamsForAlreadyVerified', 'as' => 'appuser.tempSetUserDefaultParamsForAlreadyVerified']);
	Route::post('/checkAppuserCloudStorageAccessTokenValidity', ['uses' => 'Api\AppuserController@checkAppuserCloudStorageAccessTokenValidity', 'as' => 'appuser.checkCloudStorageAccessTokenValidity']);
	Route::post('/registerAppuserCloudStorageAccessToken', ['uses' => 'Api\AppuserController@registerAppuserCloudStorageAccessToken', 'as' => 'appuser.registerCloudStorageAccessToken']);
	Route::post('/deregisterAppuserCloudStorageAccessToken', ['uses' => 'Api\AppuserController@deregisterAppuserCloudStorageAccessToken', 'as' => 'appuser.deregisterCloudStorageAccessToken']);
	Route::post('/fetchAndRegisterAppuserCloudStorageAccessToken', ['uses' => 'Api\AppuserController@fetchAndRegisterAppuserCloudStorageAccessToken', 'as' => 'appuser.fetchAndRegisterCloudStorageAccessToken']);
	Route::post('/fetchAndRefreshAppuserCloudStorageAccessToken', ['uses' => 'Api\AppuserController@fetchAndRefreshAppuserCloudStorageAccessToken', 'as' => 'appuser.fetchAndRefreshAppuserCloudStorageAccessToken']);
	Route::post('/loadAppuserSessionManagementModal', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@loadAppuserSessionManagementModal', 'as' => 'appuser.loadAppuserSessionManagementModal']);
	Route::post('/removeExistingAppuserSession', ['middleware' => ['cors'], 'uses' => 'Api\AppuserController@removeExistingAppuserSession', 'as' => 'appuser.removeExistingAppuserSession']);
	Route::post('/checkAppuserCloudCalendarAccessTokenValidity', ['uses' => 'Api\AppuserController@checkAppuserCloudCalendarAccessTokenValidity', 'as' => 'appuser.checkCloudCalendarAccessTokenValidity']);
	Route::post('/registerAppuserCloudCalendarAccessToken', ['uses' => 'Api\AppuserController@registerAppuserCloudCalendarAccessToken', 'as' => 'appuser.registerCloudCalendarAccessToken']);
	Route::post('/deregisterAppuserCloudCalendarAccessToken', ['uses' => 'Api\AppuserController@deregisterAppuserCloudCalendarAccessToken', 'as' => 'appuser.deregisterCloudCalendarAccessToken']);
	Route::post('/setupAppuserCalendarIdSelectionForCloudCalendar', ['uses' => 'Api\AppuserController@setupAppuserCalendarIdSelectionForCloudCalendar', 'as' => 'appuser.setupAppuserCalendarIdSelectionForCloudCalendar']);
	Route::post('/fetchAndRegisterAppuserCloudCalendarAccessToken', ['uses' => 'Api\AppuserController@fetchAndRegisterAppuserCloudCalendarAccessToken', 'as' => 'appuser.fetchAndRegisterCloudCalendarAccessToken']);
	Route::post('/fetchAndRefreshAppuserCloudCalendarAccessToken', ['uses' => 'Api\AppuserController@fetchAndRefreshAppuserCloudCalendarAccessToken', 'as' => 'appuser.fetchAndRefreshAppuserCloudCalendarAccessToken']);
	Route::post('/checkAppuserCloudStorageAndCalendarAccessTokenValidities', ['uses' => 'Api\AppuserController@checkAppuserCloudStorageAndCalendarAccessTokenValidities', 'as' => 'appuser.checkClouddStorageAndCalendarAccessTokenValidities']);
	Route::post('/checkAppuserCloudMailBoxAccessTokenValidity', ['uses' => 'Api\AppuserController@checkAppuserCloudMailBoxAccessTokenValidity', 'as' => 'appuser.checkCloudMailBoxAccessTokenValidity']);
	Route::post('/registerAppuserCloudMailBoxAccessToken', ['uses' => 'Api\AppuserController@registerAppuserCloudMailBoxAccessToken', 'as' => 'appuser.registerCloudMailBoxAccessToken']);
	Route::post('/deregisterAppuserCloudMailBoxAccessToken', ['uses' => 'Api\AppuserController@deregisterAppuserCloudMailBoxAccessToken', 'as' => 'appuser.deregisterCloudMailBoxAccessToken']);
	Route::post('/fetchAndRegisterAppuserCloudMailBoxAccessToken', ['uses' => 'Api\AppuserController@fetchAndRegisterAppuserCloudMailBoxAccessToken', 'as' => 'appuser.fetchAndRegisterCloudMailBoxAccessToken']);
	Route::post('/fetchAndRefreshAppuserCloudMailBoxAccessToken', ['uses' => 'Api\AppuserController@fetchAndRefreshAppuserCloudMailBoxAccessToken', 'as' => 'appuser.fetchAndRefreshAppuserCloudMailBoxAccessToken']);
	Route::post('/checkAppuserAssociatedCloudDependencyAccessTokenValidity', ['uses' => 'Api\AppuserController@checkAppuserAssociatedCloudDependencyAccessTokenValidity', 'as' => 'appuser.checkAssociatedCloudDependencyAccessTokenValidity']);
	Route::post('/getAppuserLinkedCloudDependencies', ['uses' => 'Api\AppuserController@getAppuserLinkedCloudDependencies', 'as' => 'appuser.getAppuserLinkedCloudDependencies']);
	/* Appuser Routes End */

	/* Messaging Routes Begin */
	Route::post('/registerAppuserMessagingToken', ['uses' => 'Api\MessagingController@registerAppuserToken', 'as' => 'messaging.registerToken']);
	//Route::post('/appuserConstantSync', ['uses' => 'Api\MessagingController@userConstantDataSync', 'as' => 'messaging.constantsData']);
	/* Messaging Routes End */

	/* Sync Routes Begin */
	Route::post('/appuserLoginDataSync', ['uses' => 'Api\SyncController@loginDataSync', 'as' => 'sync.loginData']);
	Route::post('/appuserConstantSync', ['uses' => 'Api\SyncController@userConstantDataSync', 'as' => 'sync.constantsData']);
	Route::post('/appuserPrimaryDataReSync', ['uses' => 'Api\SyncController@userDataPrimaryReSync', 'as' => 'sync.primaryReSync']);
	Route::post('/appuserSecondaryDataReSync', ['uses' => 'Api\SyncController@userDataSecondaryReSync', 'as' => 'sync.secondaryReSync']);
	Route::post('/appuserQuotaReSync', ['uses' => 'Api\SyncController@userQuotaSync', 'as' => 'sync.userQuotaSync']);
	Route::post('/appuserShareRightsReSync', ['uses' => 'Api\SyncController@userShareRightsSync', 'as' => 'sync.userShareRightsSync']);
	Route::post('/appuserPeriodicDataReSync', ['uses' => 'Api\SyncController@periodicDataSync', 'as' => 'sync.periodicDataSync']);

	/* Sync Routes End */

	/* Feedback Routes Begin */
	Route::post('/saveAppuserFeedbackDetails', ['uses' => 'Api\FeedbackController@saveFeedbackDetails', 'as' => 'feedback.save']);
	/* Feedback Routes End */

	/* Folder Routes Begin */
	Route::post('/saveAppuserFolderDetails', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@saveFolderDetails', 'as' => 'folder.save']);
	Route::post('/saveAppuserVirtualFolderDetails', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@saveVirtualFolderDetails', 'as' => 'folder.saveVirtual']);
	Route::post('/appuserFolderList', ['uses' => 'Api\FolderController@folderList', 'as' => 'folder']);
	Route::post('/loadSelectFolderList', ['uses' => 'Api\FolderController@loadSelectFolderList', 'as' => 'folder.loadSelectFolderList']);
	Route::post('/checkAppuserFolderIsDeletable', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@checkFolderCanBeDeleted', 'as' => 'folder.checkForDelete']);
	Route::post('/deleteAppuserFolder', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@deleteFolder', 'as' => 'folder.delete']);
	Route::post('/appuserFolderListDatatable', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@appuserFolderListDatatable', 'as' => 'folder.appuserFolderListDatatable']);
	Route::post('/loadAppuserFolderAddEditModal', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@loadAddEditModal', 'as' => 'folder.loadAddEditModal']);
	Route::post('/validateAppuserFolderName', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@validateFolderName', 'as' => 'folder.validateFolderName']);
	Route::post('/validateAppuserFolderPin', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@validateFolderPin', 'as' => 'folder.validateFolderPin']);
	Route::post('/checkAppuserFolderPinEnabled', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@checkFolderPinEnabled', 'as' => 'folder.checkFolderPinEnabled']);
	Route::post('/appuserFavoriteList', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@favoriteList', 'as' => 'folder.favoriteList']);
	Route::post('/invertFolderFavoritedStatus', ['middleware' => ['cors'], 'uses' => 'Api\FolderController@invertFavoritedStatus', 'as' => 'folder.invertFavoritedStatus']);
	Route::post('/appuserFolderGroupListView', ['uses' => 'Api\FolderController@folderGroupListView', 'as' => 'folder.folderGroupListView']);
	Route::post('/appuserAllNotesFolderGroupId', ['uses' => 'Api\FolderController@allNotesFolderGroupId', 'as' => 'folder.allNotesFolderGroupId']);
	/* Folder Routes End */

	/* Source Routes Begin */
	Route::post('/saveAppuserSourceDetails', ['middleware' => ['cors'], 'uses' => 'Api\SourceController@saveSourceDetails', 'as' => 'source.save']);
	Route::post('/appuserSourceList', ['uses' => 'Api\SourceController@sourceList', 'as' => 'source']);
	Route::post('/loadSelectSourceList', ['uses' => 'Api\SourceController@loadSelectSourceList', 'as' => 'source.loadSelectSourceList']);
	Route::post('/checkAppuserSourceIsDeletable', ['middleware' => ['cors'], 'uses' => 'Api\SourceController@checkSourceCanBeDeleted', 'as' => 'source.checkForDelete']);
	Route::post('/deleteAppuserSource', ['middleware' => ['cors'], 'uses' => 'Api\SourceController@deleteSource', 'as' => 'source.delete']);
	Route::post('/appuserSourceListDatatable', ['middleware' => ['cors'], 'uses' => 'Api\SourceController@appuserSourceListDatatable', 'as' => 'source.appuserSourceListDatatable']);
	Route::post('/loadAppuserSourceAddEditModal', ['middleware' => ['cors'], 'uses' => 'Api\SourceController@loadAddEditModal', 'as' => 'source.loadAddEditModal']);
	Route::post('/validateAppuserSourceName', ['middleware' => ['cors'], 'uses' => 'Api\SourceController@validateSourceName', 'as' => 'source.validateSourceName']);
	/* Source Routes End */

	/* Tag Routes Begin */
	Route::post('/saveAppuserTagDetails', ['middleware' => ['cors'], 'uses' => 'Api\TagController@saveTagDetails', 'as' => 'tag.save']);
	Route::post('/appuserTagList', ['uses' => 'Api\TagController@tagList', 'as' => 'tag']);
	Route::post('/loadSelectTagList', ['uses' => 'Api\TagController@loadSelectTagList', 'as' => 'tag.loadSelectTagList']);
	Route::post('/checkAppuserTagIsDeletable', ['middleware' => ['cors'], 'uses' => 'Api\TagController@checkTagCanBeDeleted', 'as' => 'tag.checkForDelete']);
	Route::post('/deleteAppuserTag', ['middleware' => ['cors'], 'uses' => 'Api\TagController@deleteTag', 'as' => 'tag.delete']);
	Route::post('/appuserTagListDatatable', ['middleware' => ['cors'], 'uses' => 'Api\TagController@appuserTagListDatatable', 'as' => 'tag.appuserTagListDatatable']);
	Route::post('/loadAppuserTagAddEditModal', ['middleware' => ['cors'], 'uses' => 'Api\TagController@loadAddEditModal', 'as' => 'tag.loadAddEditModal']);
	Route::post('/validateAppuserTagName', ['middleware' => ['cors'], 'uses' => 'Api\TagController@validateTagName', 'as' => 'tag.validateTagName']);
	Route::get('/testPhpInfo', ['uses' => 'Api\TagController@getPhpInfo', 'as' => 'tag.phpinfo']);
	/* Tag Routes End */

	/* Content Routes Begin */
	Route::post('/saveAppuserContentDetails', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@saveContentDetails', 'as' => 'content.save']);
	Route::post('/uploadAppuserContentImage', ['uses' => 'Api\ContentController@addContentImage', 'as' => 'content.addImage']);
	Route::post('/uploadAppuserContentAttachment', ['uses' => 'Api\ContentController@addContentAttachment', 'as' => 'content.addAttachment']);
	Route::post('/appuserContentList', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@contentList', 'as' => 'content']);
	Route::post('/appuserContentDetailsModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@contentDetailsModal', 'as' => 'content.contentDetailsModal']);
	Route::post('/appuserContentDetails', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@contentDetails', 'as' => 'content.show']);
	Route::post('/restoreDeletedAppuserContent', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@restoreDeletedContent', 'as' => 'content.restoreDeleted']);
	Route::post('/restoreMultiDeletedAppuserContent', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@restoreMultiDeletedContent', 'as' => 'content.multiRestoreDeleted']);
	Route::post('/deleteAppuserContent', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@deleteContent', 'as' => 'content.delete']);
	Route::post('/checkAppuserContentCanBeDeleted', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@checkContentCanBeDeleted', 'as' => 'content.checkForDelete']);
	Route::post('/deleteMultiAppuserContent', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@deleteMultiContent', 'as' => 'content.multiDelete']);
	Route::post('/appuserContentMarkToggle', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@toggleContentMarking', 'as' => 'content.toggleMark']);
	Route::post('/appuserContentMoveToFolder', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@moveContentToFolder', 'as' => 'content.moveToFolder']);
	Route::post('/appuserContentCopyToFolder', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@copyContentToFolder', 'as' => 'content.copyToFolder']);
	Route::post('/appuserContentMerge', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@mergeContents', 'as' => 'content.merge']);
	Route::post('/appuserContentAddTags', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@addTagsToContent', 'as' => 'content.addTags']);
	Route::post('/appuserContentCopyToOrganization', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@copyContentToOrganization', 'as' => 'content.copyToOrg']);
	Route::post('/sortAppuserContentModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@sortContentModal', 'as' => 'content.sortContentModal']);
	Route::post('/filterAppuserContentModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@filterContentModal', 'as' => 'content.filterContentModal']);
	Route::post('/appuserContentPrintView', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@printMultiContent', 'as' => 'content.printMultiContent']);
	Route::get('/usrRemoveContent', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@deleteAppuserContentViaLink', 'as' => 'content.deleteAppuserContentViaLink']);
	Route::post('/appuserDueContentList', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@getDueAppuserContents', 'as' => 'content.getDueAppuserContents']);
	Route::post('/appuserDueContentListNew', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@getDueAppuserContentsNew', 'as' => 'content.getDueAppuserContentsNew']);
	Route::post('/appuserContentDependencyModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@appuserContentDependencyModal', 'as' => 'content.appuserContentDependencyModal']);
	Route::post('/appuserContentModifyDateTimeModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@appuserContentModifyDateTimeModal', 'as' => 'content.appuserContentModifyDateTimeModal']);
	Route::post('/performAppuserContentDateTimeModification', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@performContentDateTimeModification', 'as' => 'content.performContentDateTimeModification']);
	Route::post('/appuserContentInfoModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@appuserContentInfoModal', 'as' => 'content.appuserContentInfoModal']);
	Route::post('/appuserContentAttachmentInfoModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@appuserContentAttachmentInfoModal', 'as' => 'content.appuserContentAttachmentInfoModal']);
	Route::post('/appuserFolderOrGroupContentInfoModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@appuserFolderOrGroupContentInfoModal', 'as' => 'content.appuserFolderOrGroupContentInfoModal']);
	Route::post('/modifyPartialAppuserContentDetails', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@modifyContentDetails', 'as' => 'content.modifyContentDetails']);
	Route::post('/checkContentCanBeShared', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@checkContentCanBeShared', 'as' => 'content.checkContentCanBeShared']);
	Route::post('/saveAppuserOneLineChatContent', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@saveOneLineChatContent', 'as' => 'content.saveOneLineChatContent']);
	Route::post('/appuserDashboardMetrics', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@getDueAppuserDashboardMetrics', 'as' => 'content.getDueAppuserDashboardMetrics']);
	Route::post('/appuserContentListView', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@contentListView', 'as' => 'content.contentListView']);
	Route::post('/appuserContentDetailsModalNew', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@contentDetailsModalNew', 'as' => 'content.contentDetailsModalNew']);
	Route::post('/appuserContentDetailsLoadSubView', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@loadContentDetailsSubView', 'as' => 'content.loadContentDetailsSubView']);
	Route::post('/performContentConversationPartOperation', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@performContentConversationPartOperation', 'as' => 'content.performContentConversationPartOperation']);
	Route::post('/performContentModificationPushOperation', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@performContentModificationRespectivePush', 'as' => 'content.performContentModificationRespectivePush']);
	Route::post('/performContentSharingPushOperation', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@performContentSharingRespectivePush', 'as' => 'content.performContentSharingRespectivePush']);
	Route::post('/checkContentCanBePrinted', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@checkContentCanBePrinted', 'as' => 'content.checkContentCanBePrinted']);
	Route::post('/loadWebVideoTutorialModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@webVideoTutorialModal', 'as' => 'content.webVideoTutorialModal']);
	Route::post('/loadAppuserShareRecipientSelectionModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@contentShareRecipientSelectionModal', 'as' => 'content.shareRecipientSelectionModal']);
	Route::post('/loadAppuserPartContentShareRecipientSelectionModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@partContentShareRecipientSelectionModal', 'as' => 'content.sharePartContentRecipientSelectionModal']);
	Route::post('/loadAppuserPartContentShareConversationSelectionModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@partContentShareConverationSelectionModal', 'as' => 'content.sharePartContentConversationSelectionModal']);
	Route::post('/performAppuserPartContentShareToConversation', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@performAppuserPartContentShareToConversation', 'as' => 'content.performAppuserPartContentShareToConversation']);
	Route::post('/loadAppuserProfileDefaultFolderGroupDetails', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@profileDefaultFolderGroupDetails', 'as' => 'content.profileDefaultFolderGroupDetails']);
	Route::post('/loadAppuserContentCloudAttachmentSelectionModal', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@loadContentCloudAttachmentSelectionModal', 'as' => 'content.loadContentCloudAttachmentSelectionModal']);
	Route::post('/appuserGroupContentCopyToFolder', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@copyGroupContentToFolder', 'as' => 'content.copyGroupContentToFolder']);
	Route::post('/appuserContentDependencyGlobalSearchListView', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@appuserContentDependencyGlobalSearchListView', 'as' => 'content.appuserContentDependencyGlobalSearchListView']);
	Route::post('/appuserMultiContentMarkAsCompleted', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@setMultiContentCompletionStatusAsCompleted', 'as' => 'content.setMultiAsCompleted']);
	Route::post('/appuserMultiContentMarkAsPending', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@setMultiContentCompletionStatusAsPending', 'as' => 'content.setMultiAsPending']);
	Route::post('/appuserContentMarkAsSnoozed', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@markContentReminderStatusAsSnoozed', 'as' => 'content.markAsSnoozed']);
	Route::post('/checkAppuserCalendarContentForOverLapping', ['middleware' => ['cors'], 'uses' => 'Api\ContentController@checkCalendarContentTimingForOverlapping', 'as' => 'content.checkCalendarContentTimingForOverlapping']);
	/* Content Routes End */

	/* Attachment Routes Begin */
	Route::post('/uploadContentAttachment', ['uses' => 'Api\AttachmentController@uploadAttachment', 'as' => 'attachment.upload']);
	Route::post('/removeContentAttachment', ['uses' => 'Api\AttachmentController@removeAttachment', 'as' => 'attachment.remove']);
	Route::post('/uploadGroupContentAttachment', ['uses' => 'Api\AttachmentController@uploadGroupAttachment', 'as' => 'attachment.uploadToGroup']);
	Route::post('/removeGroupContentAttachment', ['uses' => 'Api\AttachmentController@removeGroupAttachment', 'as' => 'attachment.removeFromGroup']);
	Route::post('/downloadContentAttachment', ['uses' => 'Api\AttachmentController@downloadAttachment', 'as' => 'attachment.download']);
	Route::post('/downloadEncContentAttachment', ['uses' => 'Api\AttachmentController@downloadEncAttachment', 'as' => 'attachment.downloadEnc']);
	Route::post('/removeSelContentAttachment', ['uses' => 'Api\AttachmentController@removeContentAttachment', 'as' => 'attachment.removeContentAttachment']);
	Route::post('/getTemporaryContentAttachmentUrl', ['uses' => 'Api\AttachmentController@decryptAttachmentForTempUrl', 'as' => 'attachment.decryptAttachmentForTempUrl']);
	/* Attachment Routes End */

	/* Cloud Attachment Routes Begin */
	Route::post('/loadCloudAttachmentRelevantFolderFileList', ['uses' => 'Api\CloudAttachmentController@loadRelevantFolderFileList', 'as' => 'cloudAttachment.loadRelevantFolderFileList']);
	Route::post('/loadCloudAttachmentRelevantFolderFileContinuedList', ['uses' => 'Api\CloudAttachmentController@loadRelevantFolderFileContinuedList', 'as' => 'cloudAttachment.loadRelevantFolderFileContinuedList']);
	Route::post('/loadCloudAttachmentRelevantFolderFileFilteredList', ['uses' => 'Api\CloudAttachmentController@loadRelevantFolderFileFilteredList', 'as' => 'cloudAttachment.loadRelevantFolderFileFilteredList']);
	Route::post('/loadCloudAttachmentRelevantFolderFileContinuedFilteredList', ['uses' => 'Api\CloudAttachmentController@loadRelevantFolderFileContinuedFilteredList', 'as' => 'cloudAttachment.loadRelevantFolderFileContinuedFilteredList']);
	Route::post('/loadCloudAttachmentRelevantFileMappedDetails', ['uses' => 'Api\CloudAttachmentController@loadRelevantSelectedFileMappedDetails', 'as' => 'cloudAttachment.loadRelevantSelectedFileMappedDetails']);
	Route::post('/uploadCloudAttachmentRelevantFile', ['uses' => 'Api\CloudAttachmentController@uploadRelevantFile', 'as' => 'cloudAttachment.uploadRelevantFile']);
	Route::post('/checkCloudAttachmentRelevantFileCanBeDeleted', ['uses' => 'Api\CloudAttachmentController@checkRelevantFileCanBeDeleted', 'as' => 'cloudAttachment.checkRelevantFileCanBeDeleted']);
	Route::post('/deleteCloudAttachmentRelevantFile', ['uses' => 'Api\CloudAttachmentController@removeRelevantFile', 'as' => 'cloudAttachment.removeRelevantFile']);
	Route::post('/checkCloudAttachmentRelevantFolderCanBeDeleted', ['uses' => 'Api\CloudAttachmentController@checkRelevantFolderCanBeDeleted', 'as' => 'cloudAttachment.checkRelevantFolderCanBeDeleted']);
	Route::post('/deleteCloudAttachmentRelevantFolder', ['uses' => 'Api\CloudAttachmentController@removeRelevantFolder', 'as' => 'cloudAttachment.removeRelevantFolder']);
	Route::post('/addNewCloudAttachmentRelevantFolder', ['uses' => 'Api\CloudAttachmentController@addNewRelevantFolder', 'as' => 'cloudAttachment.addNewRelevantFolder']);
	Route::post('/performCloudStorageAttachmentImportAsAppuserContent', ['uses' => 'Api\CloudAttachmentController@performCloudStorageAttachmentImportAsContent', 'as' => 'cloudAttachment.performCloudStorageAttachmentImportAsContent']);
	Route::post('/loadCloudStorageTypeAuthenticationDependencies', ['uses' => 'Api\CloudAttachmentController@loadCloudStorageTypeAuthenticationDependencies', 'as' => 'cloudAttachment.loadCloudStorageTypeAuthenticationDependencies']);
	/* Cloud Attachment Routes End */

	/* Cloud Calendar Routes Begin */
	Route::post('/loadCloudCalendarRelevantCalendarList', ['uses' => 'Api\CloudCalendarController@loadRelevantCalendarList', 'as' => 'cloudCalendar.loadRelevantCalendarList']);
	Route::post('/performCloudCalendarRelevantPrimaryAutoSync', ['uses' => 'Api\CloudCalendarController@performLinkedCalendarPrimarySync', 'as' => 'cloudCalendar.performLinkedCalendarPrimarySync']);
	Route::post('/loadCloudCalendarRelevantEventList', ['uses' => 'Api\CloudCalendarController@loadRelevantCalendarEventList', 'as' => 'cloudCalendar.loadRelevantCalendarEventList']);
	Route::post('/loadCloudCalendarRelevantEventContinuedList', ['uses' => 'Api\CloudCalendarController@loadRelevantCalendarEventContinuedList', 'as' => 'cloudCalendar.loadRelevantCalendarEventContinuedList']);
	Route::post('/loadCloudCalendarRelevantEventDetails', ['uses' => 'Api\CloudCalendarController@loadRelevantCalendarEventDetails', 'as' => 'cloudCalendar.loadRelevantCalendarEventDetails']);
	Route::post('/addNewCloudCalendarRelevantEvent', ['uses' => 'Api\CloudCalendarController@addNewEvent', 'as' => 'cloudCalendar.addNewEvent']);
	Route::post('/updateCloudCalendarRelevantEvent', ['uses' => 'Api\CloudCalendarController@updateExistingEvent', 'as' => 'cloudCalendar.updateExistingEvent']);
	Route::post('/checkCloudCalendarRelevantEventCanBeDeleted', ['uses' => 'Api\CloudCalendarController@checkRelevantEventCanBeDeleted', 'as' => 'cloudCalendar.checkRelevantEventCanBeDeleted']);
	Route::post('/deleteCloudCalendarRelevantEvent', ['uses' => 'Api\CloudCalendarController@removeRelevantEvent', 'as' => 'cloudCalendar.removeRelevantEvent']);
	/* Cloud Calendar Routes End */

	/* Cloud MailBox Routes Begin */
	Route::post('/loadCloudMailBoxRelevantMailBoxList', ['uses' => 'Api\CloudMailBoxController@loadRelevantMailBoxList', 'as' => 'cloudMailBox.loadRelevantMailBoxList']);
	Route::post('/loadCloudMailBoxRelevantMessageList', ['uses' => 'Api\CloudMailBoxController@loadRelevantMailBoxMessageList', 'as' => 'cloudMailBox.loadRelevantMailBoxMessageList']);
	Route::post('/loadCloudMailBoxRelevantMessageContinuedList', ['uses' => 'Api\CloudMailBoxController@loadRelevantMailBoxMessageContinuedList', 'as' => 'cloudMailBox.loadRelevantMailBoxMessageContinuedList']);
	Route::post('/loadCloudMailBoxRelevantMessageDetails', ['uses' => 'Api\CloudMailBoxController@loadRelevantMailBoxMessageDetails', 'as' => 'cloudMailBox.loadRelevantMailBoxMessageDetails']);
	Route::post('/loadCloudMailBoxRelevantMessageAttachmentDetails', ['uses' => 'Api\CloudMailBoxController@loadRelevantMailBoxMessageAttachmentDetails', 'as' => 'cloudMailBox.loadRelevantMailBoxMessageAttachmentDetails']);
	Route::post('/loadCloudMailBoxMultiRelevantMessageAttachmentDetails', ['uses' => 'Api\CloudMailBoxController@loadMultiRelevantMailBoxMessageAttachmentDetails', 'as' => 'cloudMailBox.loadMultiRelevantMailBoxMessageAttachmentDetails']);
	Route::post('/loadCloudMailBoxMultiRelevantMessageCompleteDetails', ['uses' => 'Api\CloudMailBoxController@loadMultiRelevantMailBoxMessageCompleteDetails', 'as' => 'cloudMailBox.loadMultiRelevantMailBoxMessageCompleteDetails']);
	Route::post('/addNewCloudMailBoxRelevantMessage', ['uses' => 'Api\CloudMailBoxController@addNewMessage', 'as' => 'cloudMailBox.addNewMessage']);
	Route::post('/addNewCloudMailBoxRelevantMessageDialog', ['uses' => 'Api\CloudMailBoxController@addNewMessageDialog', 'as' => 'cloudMailBox.addNewMessageDialog']);
	Route::post('/checkCloudMailBoxRelevantMessageCanBeDeleted', ['uses' => 'Api\CloudMailBoxController@checkRelevantMessageCanBeDeleted', 'as' => 'cloudMailBox.checkRelevantMessageCanBeDeleted']);
	Route::post('/deleteCloudMailBoxRelevantMessage', ['uses' => 'Api\CloudMailBoxController@removeRelevantMessage', 'as' => 'cloudMailBox.removeRelevantMessage']);
	/* Cloud MailBox Routes End */

	/* Email Handler Routes Begin */
	Route::post('/uploadUserContent', ['uses' => 'Api\EmailHandlerController@saveContentDetails', 'as' => 'emailHandler.saveContent']);
	Route::post('/sendReminderMail', ['uses' => 'Api\EmailHandlerController@sendReminderMail', 'as' => 'emailHandler.sendReminderMail']);
	Route::post('/sendReminderMailNew', ['uses' => 'Api\EmailHandlerController@sendReminderMailAndNotification', 'as' => 'emailHandler.sendReminderMailNew']);
	Route::post('/sendInactivityMail', ['uses' => 'Api\EmailHandlerController@sendInactivityReminderMail', 'as' => 'emailHandler.sendInactivityMail']);
	Route::post('/verPendMail', ['uses' => 'Api\EmailHandlerController@sendVerificationPendingMail', 'as' => 'emailHandler.verPendMail']);
	Route::post('/sendBirthdayMail', ['uses' => 'Api\EmailHandlerController@sendBirthdayReminderMail', 'as' => 'emailHandler.sendBirthdayMail']);
	Route::post('/sendQuotaExhaustWarningMail', ['uses' => 'Api\EmailHandlerController@sendQuotaExhaustWarningMail', 'as' => 'emailHandler.sendQuotaExhaustWarningMail']);
	Route::post('/sendAnalyticsMail', ['uses' => 'Api\EmailHandlerController@sendAnalyticsMail', 'as' => 'emailHandler.sendAnalyticsMail']);
	Route::post('/removeTemporaryDecryptedContentAttachments', ['uses' => 'Api\EmailHandlerController@removeTempDecryptedAttachments', 'as' => 'emailHandler.removeTempDecryptedAttachments']);
	Route::post('/setOrganizationEmployeeInactivityByDefinedPeriod', ['uses' => 'Api\EmailHandlerController@setOrganizationEmployeeInactivityByDefinedPeriod', 'as' => 'emailHandler.setOrganizationEmployeeInactivityByDefinedPeriod']);
	Route::post('/checkAndDeleteAppuserRemovedContent', ['uses' => 'Api\EmailHandlerController@checkAndDeleteAppuserRemovedContent', 'as' => 'emailHandler.checkAndDeleteAppuserRemovedContent']);
	Route::post('/sendEnterprisePremiumSubscriptionExpiryDueMail', ['uses' => 'Api\EmailHandlerController@sendEnterprisePremiumSubscriptionExpiryDueMail', 'as' => 'emailHandler.sendEnterprisePremiumSubscriptionExpiryDueMail']);
	Route::post('/sendEnterpriseRelevantWarningMails', ['uses' => 'Api\EmailHandlerController@sendEnterpriseRelevantWarningMails', 'as' => 'emailHandler.sendEnterpriseRelevantWarningMails']);
	Route::post('/sendAppuserSracContactPendingHiMails', ['uses' => 'Api\EmailHandlerController@sendAppuserSracContactPendingHiMails', 'as' => 'emailHandler.sendAppuserSracContactPendingHiMails']);
	Route::post('/checkAndRefreshAppuserCloudStorageTokens', ['uses' => 'Api\EmailHandlerController@checkAndRefreshAppuserCloudStorageTokens', 'as' => 'emailHandler.checkAndRefreshAppuserCloudStorageTokens']);
	Route::post('/checkAndResyncAppuserCloudCalendarAutoSyncChanges', ['uses' => 'Api\EmailHandlerController@checkAndResyncAppuserCloudCalendarAutoSyncChanges', 'as' => 'emailHandler.checkAndResyncAppuserCloudCalendarAutoSyncChanges']);
	/* Email Handler Routes End */

	/* Contact Routes Begin */
	Route::post('/syncAppuserContacts', ['uses' => 'Api\AppuserContactController@syncContacts', 'as' => 'contact.sync']);
	Route::post('/appuserContactList', ['uses' => 'Api\AppuserContactController@getAppuserContactList', 'as' => 'contact.getAppuserContactList']);
	Route::post('/shareAppContent', ['uses' => 'Api\AppuserContactController@shareContent', 'as' => 'contact.shareContent']);
	Route::post('/blockAppuserContact', ['uses' => 'Api\AppuserContactController@blockContact', 'as' => 'contact.blockContact']);
	Route::post('/unblockAppuserContact', ['uses' => 'Api\AppuserContactController@unblockContact', 'as' => 'contact.unblockContact']);
	Route::post('/inviteAppuserContact', ['uses' => 'Api\AppuserContactController@inviteContact', 'as' => 'contact.inviteContact']);
	Route::post('/appuserBroadcastList', ['uses' => 'Api\AppuserContactController@getAppuserBroadcastList', 'as' => 'contact.getAppuserBroadcastList']);
	Route::post('/saveAppuserBroadcast', ['uses' => 'Api\AppuserContactController@saveBroadcastDetails', 'as' => 'contact.saveBroadcastDetails']);
	Route::post('/blockAppuserEmail', ['uses' => 'Api\AppuserContactController@blockEmail', 'as' => 'contact.blockEmail']);
	
	Route::post('/loadBroadcastDetails', ['uses' => 'Api\AppuserContactController@loadAddEditBroadcastDetails', 'as' => 'broadcast.loadDetails']);
	Route::post('/renameBroadcast', ['uses' => 'Api\AppuserContactController@performBroadcastRename', 'as' => 'broadcast.rename']);
	Route::post('/removeBroadcastUser', ['uses' => 'Api\AppuserContactController@removeBroadcastUser', 'as' => 'broadcast.removeUser']);
	Route::post('/deleteBroadcast', ['uses' => 'Api\AppuserContactController@deleteBroadcast', 'as' => 'broadcast.delete']);
	Route::post('/addBroadcastMember', ['uses' => 'Api\AppuserContactController@addBroadcastMember', 'as' => 'broadcast.addMember']);	
	/* Contact Routes End */

	/* Group Routes Begin */
	Route::post('/shareAppGroupContent', ['uses' => 'Api\GroupController@shareContent', 'as' => 'group.shareContent']);
	Route::post('/appuserGroupList', ['uses' => 'Api\GroupController@getAppuserGroupList', 'as' => 'group.getAppuserGroupList']);
	Route::post('/saveGroupDetails', ['uses' => 'Api\GroupController@saveGroupDetails', 'as' => 'group.saveGroupDetails']);
	Route::post('/loadGroupDetails', ['uses' => 'Api\GroupController@loadAddEditGroupDetails', 'as' => 'group.loadAddEditGroupDetails']);
	Route::post('/saveGroupContentDetails', ['uses' => 'Api\GroupController@saveContentDetails', 'as' => 'group.saveContentDetails']);
	Route::post('/renameGroup', ['uses' => 'Api\GroupController@performRename', 'as' => 'group.rename']);
	Route::post('/toggleOpenGroupStatus', ['uses' => 'Api\GroupController@toggleOpenGroupStatus', 'as' => 'group.toggleOpenGroupStatus']);
	Route::post('/loadAppuserJoinOpenGroupModal', ['uses' => 'Api\GroupController@loadJoinOpenGroupModal', 'as' => 'group.loadJoinOpenGroupModal']);
	Route::post('/joinOpenGroupAsMember', ['uses' => 'Api\GroupController@joinOpenGroupAsMember', 'as' => 'group.joinOpenGroupAsMember']);
	Route::post('/removeGroupUser', ['uses' => 'Api\GroupController@removeUser', 'as' => 'group.removeUser']);
	Route::post('/exitGroup', ['uses' => 'Api\GroupController@exitGroup', 'as' => 'group.exit']);
	Route::post('/deleteGroup', ['uses' => 'Api\GroupController@deleteGroup', 'as' => 'group.delete']);
	Route::post('/shareGroupContent', ['uses' => 'Api\GroupController@shareContent', 'as' => 'group.shareContent']);
	Route::post('/addGroupMember', ['uses' => 'Api\GroupController@addGroupMember', 'as' => 'group.addMember']);
	Route::post('/deleteGroupContent', ['uses' => 'Api\GroupController@deleteContent', 'as' => 'group.deleteContent']);
	Route::post('/syncGroupContent', ['uses' => 'Api\GroupController@syncContent', 'as' => 'group.syncContent']);
	Route::post('/resyncGroupData', ['uses' => 'Api\GroupController@resyncData', 'as' => 'group.resyncData']);
	Route::post('/modifyGroupQuotaDetails', ['uses' => 'Api\GroupController@modifyQuotaDetails', 'as' => 'group.modifyQuotaDetails']);
	Route::post('/uploadGroupPhoto', ['uses' => 'Api\GroupController@uploadGroupPhoto', 'as' => 'group.uploadGroupPhoto']);	
	Route::post('/invertGroupFavoritedStatus', ['middleware' => ['cors'], 'uses' => 'Api\GroupController@invertFavoritedStatus', 'as' => 'group.invertFavoritedStatus']);
	Route::post('/performGroupRestore', ['middleware' => ['cors'], 'uses' => 'Api\GroupController@performGroupDataRestore', 'as' => 'group.performGroupDataRestore']);
	Route::post('/checkGroupBackupCanBeRestored', ['middleware' => ['cors'], 'uses' => 'Api\GroupController@checkGroupBackupCanBeRestored', 'as' => 'group.checkBackupCanBeRestored']);
	Route::post('/groupRestoreProcessCompleted', ['middleware' => ['cors'], 'uses' => 'Api\GroupController@groupRestoreProcessCompleted', 'as' => 'group.restoreProcessCompleted']);
	Route::post('/groupRestoreProcessFailed', ['middleware' => ['cors'], 'uses' => 'Api\GroupController@groupRestoreProcessFailed', 'as' => 'group.restoreProcessFailed']);
	/* Group Routes End */

	/* Group Routes Begin */
	//Route::post('/shareAppGroupContent', ['uses' => 'Api\OrganizationGroupController@shareContent', 'as' => 'orgGroup.shareContent']);
	Route::post('/saveOrgGroupContentDetails', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@saveContentDetails', 'as' => 'orgGroup.saveContentDetails']);
	Route::post('/loadOrgGroupDetails', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@loadAddEditGroupDetails', 'as' => 'orgGroup.loadAddEditGroupDetails']);
	//Route::post('/shareGroupContent', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@shareContent', 'as' => 'orgGroup.shareContent']);
	Route::post('/deleteOrgGroupContent', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@deleteContent', 'as' => 'orgGroup.deleteContent']);
	Route::post('/syncOrgGroupContent', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@syncContent', 'as' => 'orgGroup.syncContent']);
	Route::post('/resyncOrgGroupData', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@resyncData', 'as' => 'orgGroup.resyncData']);	
	Route::post('/loadSelectGroupList', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@loadSelectGroupList', 'as' => 'orgGroup.loadSelectGroupList']);
	Route::post('/loadAppuserGroupInfo', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@loadGroupDetailsModal', 'as' => 'orgGroup.loadGroupDetailsModal']);
	Route::post('/validateAppuserGroupName', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@validateGroupName', 'as' => 'orgGroup.validateGroupName']);
	Route::post('/invertOrgGroupFavoritedStatus', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@invertFavoritedStatus', 'as' => 'orgGroup.invertFavoritedStatus']);
	Route::post('/invertOrgGroupLockedStatus', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@invertLockedStatus', 'as' => 'orgGroup.invertLockedStatus']);
	Route::post('/loadOrgGroupMembershipShareModal', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@getGroupMembershipShareModal', 'as' => 'orgGroup.getMembershipShareModal']);
	Route::post('/sendOrgGroupMembershipInvitation', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@sendGroupJoinInvitation', 'as' => 'orgGroup.sendGroupJoinInvitation']);
	Route::get('/usrJoinGroup', ['middleware' => ['cors'], 'uses' => 'Api\OrganizationGroupController@joinOrgGroupAsMemberViaLink', 'as' => 'orgGroup.joinOrgGroupAsMemberViaLink']);
	/* Group Routes End */

	/* Appuser Constant Routes Begin */
	Route::post('/appuserSyncDefaultFolder', ['uses' => 'Api\AppuserConstantController@syncDefaultFolder', 'as' => 'userConstant.syncDefaultFolder']);
	Route::post('/appuserSyncAppPin', ['uses' => 'Api\AppuserConstantController@syncAppPin', 'as' => 'userConstant.syncAppPin']);
	Route::post('/appuserSyncAppFolderPin', ['uses' => 'Api\AppuserConstantController@syncAppFolderPin', 'as' => 'userConstant.syncAppFolderPin']);
	Route::post('/appuserSyncAttachmentRetainDay', ['uses' => 'Api\AppuserConstantController@syncAttachmentRetainDay', 'as' => 'userConstant.syncAttachmentRetainDay']);
	Route::post('/appuserSyncPrintPreference', ['uses' => 'Api\AppuserConstantController@syncPrintPreference', 'as' => 'userConstant.syncPrintPreference']);
	/* Appuser Constant Routes End */

	/* Sys Video Conference Routes Begin */
	Route::post('/saveVideoConferenceDetails', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@saveConferenceDetails', 'as' => 'sysVideoConference.save']);
	Route::post('/checkVideoConferenceCanBeStarted', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@checkConferenceCanBeStarted', 'as' => 'sysVideoConference.canBeStarted']);
	Route::post('/markVideoConferenceUserJoined', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@markConferenceJoinedByUser', 'as' => 'sysVideoConference.markJoinedByUser']);
	Route::post('/markVideoConferenceUserExited', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@markConferenceExitedByUser', 'as' => 'sysVideoConference.markExitedByUser']);
	Route::post('/checkVideoConferenceCanBeDeleted', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@checkConferenceCanBeDeleted', 'as' => 'sysVideoConference.canBeDeleted']);
	Route::post('/deleteVideoConference', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@deleteConference', 'as' => 'sysVideoConference.delete']);
	Route::post('/getUpcomingVideoConferenceList', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getUpcomingVideoConferences', 'as' => 'sysVideoConference.getUpcomingVideoConferences']);
	Route::post('/getPastVideoConferenceList', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getPastVideoConferences', 'as' => 'sysVideoConference.getPastVideoConferences']);
	Route::post('/getAttendedVideoConferenceList', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getAttendedVideoConferences', 'as' => 'sysVideoConference.getAttendedVideoConferences']);
	Route::post('/loadVideoConferenceAddEditModal', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@conferenceDetailsModal', 'as' => 'sysVideoConference.conferenceDetailsModal']);
	Route::post('/loadVideoConferenceRelevantParticipants', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@loadRelevantConferenceParticipants', 'as' => 'sysVideoConference.loadRelevantConferenceParticipants']);
	Route::post('/loadVideoConferenceJoinModal', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@conferenceJoinModal', 'as' => 'sysVideoConference.joinConferenceModal']);
	Route::post('/loadVideoConferenceConductModal', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@conferenceConductModal', 'as' => 'sysVideoConference.conductConferenceModal']);
	Route::post('/loadVideoConferenceInformation', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getConferenceInformation', 'as' => 'sysVideoConference.getInformation']);
	Route::post('/loadVideoConferenceInformationModal', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getConferenceInformationModal', 'as' => 'sysVideoConference.getInformation']);
	Route::post('/saveVideoConferenceInformationAsUserContent', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@saveConferenceInformationAsUserContent', 'as' => 'sysVideoConference.saveConferenceInformationAsUserContent']);
	Route::post('/loadVideoConferenceShareModal', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getConferenceShareModal', 'as' => 'sysVideoConference.getConferenceShareModal']);
	Route::post('/sendVideoConferenceEmailInvitation', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@sendConferenceEmailInvitation', 'as' => 'sysVideoConference.sendEmailInvitation']);
	Route::post('/loadVideoConferenceShareWithinHyLytModal', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@getConferenceShareWithinHyLytModal', 'as' => 'sysVideoConference.getConferenceShareWithinHyLytModal']);
	Route::post('/sendVideoConferenceInvitationWithinHyLyt', ['middleware' => ['cors'], 'uses' => 'Api\SysVideoConferenceController@sendConferenceInvitationWithinHyLyt', 'as' => 'sysVideoConference.sendConferenceInvitationWithinHyLyt']);
	/* Sys Video Conference Routes End */

/* API Routes End */

/* Admin API Routes Begin */

	/* Organization Routes Begin */
	Route::post('/organizationDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@organizationDatatable', 'as' => 'organizationDatatable']);
	Route::get('/organization', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@index', 'as' => 'organization']);
	Route::post('/organizationDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@create', 'as' => 'organization.create']);
	Route::post('/loadOrgRegistrationDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@loadRegDetailsView', 'as' => 'organization.loadRegDetailsView']);
	Route::post('/saveOrgRegistrationDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@saveRegDetails', 'as' => 'organization.saveRegDetails']);
	Route::post('/loadOrgSubscriptionDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@loadSubscriptionDetailsView', 'as' => 'organization.loadSubscriptionDetailsView']);
	Route::post('/saveOrgSubscriptionDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@saveSubscriptionDetails', 'as' => 'organization.saveSubscriptionDetails']);
	Route::post('/loadOrgAdministrationDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@loadAdministrationDetailsView', 'as' => 'organization.loadAdministrationDetailsView']);
	Route::post('/organizationAdminDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@organizationAdminDatatable', 'as' => 'organizationAdminDatatable']);
	Route::post('/addOrgAdministrator', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@addAdministrator', 'as' => 'organization.addAdministrator']);
	Route::post('/orgAdministratorDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@viewAdministrator', 'as' => 'organization.viewAdministrator']);
	Route::post('/deleteOrgAdministrator', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@deleteAdministrator', 'as' => 'organization.deleteAdministrator']);
	Route::post('/changeOrganizationStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@changeStatus', 'as' => 'organization.changeStatus']);
	Route::post('/changeOrgAdministratorStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@changeAdministratorStatus', 'as' => 'organization.changeAdministratorStatus']);
	Route::post('/resendOrgAdministratorCredentials', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@resendAdministratorCredentials', 'as' => 'organization.resendAdministratorCredentials']);
	Route::post('/loadOrgAdministratorCredentialsModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@loadAdministratorCredentialsModal', 'as' => 'organization.loadAdministratorCredentialsModal']);
	Route::post('/validateAdminEmailForOrg', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@validateAdminEmailForOrg', 'as' => 'organization.validateAdminEmail']);
	Route::post('/validateOrganizationCode', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@validateOrganizationCode', 'as' => 'organization.validateCode']);
	Route::post('/validateOrganizationDatabaseName', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@validateOrganizationDatabaseName', 'as' => 'organization.validateDatabaseName']);
	Route::post('/loadOrgServerDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@loadServerDetailsView', 'as' => 'organization.loadServerDetailsView']);
	Route::post('/saveOrgServerDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@saveServerDetails', 'as' => 'organization.saveServerDetails']);
	Route::post('/checkOrgAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@checkAvailForDelete', 'as' => 'organization.checkAvailForDelete']);
	Route::post('/deleteOrganization', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@destroy', 'as' => 'organization.delete']);
	Route::post('/registerOrganizationWithReferral', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrganizationController@registerOrganizationWithReferral', 'as' => 'organization.registerWithReferral']);
	/* Organization Routes End */
	
	/* Enterp Admin Routes Begin */
	Route::post('serAuthenticate',  ['middleware' => ['cors'], 'as' => 'serAuthenticate', 'uses' => 'AdminApi\EnterpAdminController@authenticate']);
	Route::post('serValidateCurrPassword',  ['middleware' => ['cors'], 'as' => 'serValidateCurrPassword', 'uses' => 'AdminApi\EnterpAdminController@validateCurrPassword']);
	Route::post('serUpdatePassword',  ['middleware' => ['cors'], 'as' => 'serUpdatePassword', 'uses' => 'AdminApi\EnterpAdminController@updatePassword']);
	Route::post('loadServerUserMenu', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpAdminController@loadServerUserMenu', 'as' => 'serUser.loadServerUserMenu']);
	/* Enterp Admin Routes End */

	/* Organization Referral Code Routes Begin */
	Route::post('/loadOrgReferralCodeView', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@loadReferralCodeView', 'as' => 'orgReferralCode.loadView']);
	Route::post('/loadOrgReferralCodeAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@loadAddEditModal', 'as' => 'orgReferralCode.loadAddEditModal']);
	Route::post('/orgReferralCodeDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@orgReferralCodeDatatable', 'as' => 'orgReferralCodeDatatable']);
	Route::post('/saveOrgReferralCodeDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@saveDetails', 'as' => 'orgReferralCode.saveDetails']);
	Route::post('/changeOrgReferralCodeStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@changeStatus', 'as' => 'orgReferralCode.changeStatus']);
	Route::post('/deleteOrgReferralCode', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@destroy', 'as' => 'orgReferralCode.delete']);
	Route::post('/checkOrgReferralCodeAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@checkAvailForDelete', 'as' => 'orgReferralCode.checkAvailForDelete']);
	Route::post('/validateOrgReferralCode', ['middleware' => ['cors'], 'uses' => 'AdminApi\OrgReferralCodeController@validateOrgReferralCode', 'as' => 'orgReferralCode.validateReferralCode']);
	/* Organization Referral Code Routes End */

	/* Premium Referral Code Routes Begin */
	Route::post('/loadPremiumReferralCodeView', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@loadReferralCodeView', 'as' => 'premiumReferralCode.loadView']);
	Route::post('/loadPremiumReferralCodeAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@loadAddEditModal', 'as' => 'premiumReferralCode.loadAddEditModal']);
	Route::post('/premiumReferralCodeDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@premiumReferralCodeDatatable', 'as' => 'premiumReferralCodeDatatable']);
	Route::post('/savePremiumReferralCodeDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@saveDetails', 'as' => 'premiumReferralCode.saveDetails']);
	Route::post('/changePremiumReferralCodeStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@changeStatus', 'as' => 'premiumReferralCode.changeStatus']);
	Route::post('/deletePremiumReferralCode', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@destroy', 'as' => 'premiumReferralCode.delete']);
	Route::post('/checkPremiumReferralCodeAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@checkAvailForDelete', 'as' => 'premiumReferralCode.checkAvailForDelete']);
	Route::post('/validatePremiumReferralCode', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumReferralCodeController@validatePremiumReferralCode', 'as' => 'premiumReferralCode.validateReferralCode']);
	/* Premium Referral Code Routes End */

	/* Premium Coupon Routes Begin */
	Route::post('/loadPremiumCouponView', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@loadCouponView', 'as' => 'premiumCoupon.loadView']);
	Route::post('/loadPremiumCouponAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@loadAddEditModal', 'as' => 'premiumCoupon.loadAddEditModal']);
	Route::post('/premiumCouponDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@premiumCouponDatatable', 'as' => 'premiumCouponDatatable']);
	Route::post('/loadPremiumCouponDetailView', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@loadCouponDetailView', 'as' => 'premiumCoupon.loadDetailView']);
	Route::post('/premiumCouponCodeDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@premiumCouponCodeDatatable', 'as' => 'premiumCouponCodeDatatable']);
	Route::post('/savePremiumCouponDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@saveDetails', 'as' => 'premiumCoupon.saveDetails']);
	Route::post('/changePremiumCouponStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@changeStatus', 'as' => 'premiumCoupon.changeStatus']);
	Route::post('/deletePremiumCoupon', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@destroy', 'as' => 'premiumCoupon.delete']);
	Route::post('/checkPremiumCouponAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@checkAvailForDelete', 'as' => 'premiumCoupon.checkAvailForDelete']);
	Route::post('/validatePremiumCouponCodePrefix', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@validateCouponCodePrefix', 'as' => 'premiumCoupon.validateCouponCodePrefix']);
	Route::post('/generatePremiumCoupon', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@generate', 'as' => 'premiumCoupon.generate']);
	Route::post('/checkPremiumCouponAvailForGenerate', ['middleware' => ['cors'], 'uses' => 'AdminApi\PremiumCouponController@checkAvailForGenerate', 'as' => 'premiumCoupon.checkAvailForGenerate']);
	/* Premium Coupon Routes End */

	/* Enterprise Coupon Routes Begin */
	Route::post('/loadEnterpriseCouponView', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@loadCouponView', 'as' => 'enterpriseCoupon.loadView']);
	Route::post('/loadEnterpriseCouponAddEditModal', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@loadAddEditModal', 'as' => 'enterpriseCoupon.loadAddEditModal']);
	Route::post('/enterpriseCouponDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@enterpriseCouponDatatable', 'as' => 'enterpriseCouponDatatable']);
	Route::post('/loadEnterpriseCouponDetailView', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@loadCouponDetailView', 'as' => 'enterpriseCoupon.loadDetailView']);
	Route::post('/enterpriseCouponCodeDatatable', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@enterpriseCouponCodeDatatable', 'as' => 'enterpriseCouponCodeDatatable']);
	Route::post('/saveEnterpriseCouponDetails', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@saveDetails', 'as' => 'enterpriseCoupon.saveDetails']);
	Route::post('/changeEnterpriseCouponStatus', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@changeStatus', 'as' => 'enterpriseCoupon.changeStatus']);
	Route::post('/deleteEnterpriseCoupon', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@destroy', 'as' => 'enterpriseCoupon.delete']);
	Route::post('/checkEnterpriseCouponAvailForDelete', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@checkAvailForDelete', 'as' => 'enterpriseCoupon.checkAvailForDelete']);
	Route::post('/validateEnterpriseCouponCodePrefix', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@validateCouponCodePrefix', 'as' => 'enterpriseCoupon.validateCouponCodePrefix']);
	Route::post('/generateEnterpriseCoupon', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@generate', 'as' => 'enterpriseCoupon.generate']);
	Route::post('/checkEnterpriseCouponAvailForGenerate', ['middleware' => ['cors'], 'uses' => 'AdminApi\EnterpriseCouponController@checkAvailForGenerate', 'as' => 'enterpriseCoupon.checkAvailForGenerate']);
	/* Enterprise Coupon Routes End */

	Route::get('/tempTestEnc', ['uses' => 'TempController@testEncryption', 'as' => 'temp.testEncryption']);
	Route::get('/tempMapOrgEmpKey', ['uses' => 'TempController@mapOrgEmpKey', 'as' => 'temp.mapOrgEmpKey']);
	Route::get('/tempMapOrgSelfUsers', ['uses' => 'TempController@mapOrgSelfEnrolledUsers', 'as' => 'temp.mapOrgSelfEnrolledUsers']);
	Route::get('/recalGrpQuota', ['uses' => 'TempController@recalGrpQuota', 'as' => 'temp.recalGrpQuota']);
	Route::get('/recalUserQuota', ['uses' => 'TempController@recalUserQuota', 'as' => 'temp.recalUserQuota']);
	Route::get('/encPass', ['uses' => 'TempController@encPass', 'as' => 'temp.encPass']);
	Route::get('/encryptUserPins', ['uses' => 'TempController@encryptUserPins', 'as' => 'temp.encryptUserPins']);
	Route::get('/encryptUserAttachments', ['uses' => 'TempController@encryptAttachmentAndCreateThumb', 'as' => 'temp.encryptUserAttachments']);
	Route::get('/tempFormatAttachmentName', ['uses' => 'TempController@formatAttachmentName', 'as' => 'temp.formatAttachmentName']);
	Route::get('/tempMapAppuserLastSyncTs', ['uses' => 'TempController@mapAppuserLastSyncTsTTT', 'as' => 'temp.mapAppuserLastSyncTs']);
	Route::get('/tempMapAppuserSocialLoginPassword', ['uses' => 'TempController@mapAppuserSocialLoginPassword', 'as' => 'temp.mapAppuserSocialLoginPassword']);
	Route::get('/tempSanitizeAppuserContactDetails', ['uses' => 'TempController@sanitizeAppuserContactDetails', 'as' => 'temp.sanitizeAppuserContactDetails']);
	Route::get('/tempModifyAllOrganizationDatabase', ['uses' => 'TempController@modifyAllOrganizationDatabase', 'as' => 'temp.modifyAllOrganizationDatabase']);
	Route::get('/tempSetupAllOrganizationContentReminderTimeStamp', ['uses' => 'TempController@setupAllOrganizationContentReminderTimeStamp', 'as' => 'temp.setupAllOrganizationContentReminderTimeStamp']);
	Route::get('/tempCreateOrganizationMissingAttachmentUploadFolders', ['uses' => 'TempController@createOrganizationMissingAttachmentUploadFolders', 'as' => 'temp.createOrganizationMissingAttachmentUploadFolders']);

/* Admin API Routes End */