<?php 
namespace App\Libraries;

use Config;
use Crypt;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Models\Org\Organization;
use App\Models\Org\OrganizationAdministration;
use App\Models\Org\OrganizationAdministrationSession;
use App\Models\Org\OrganizationAdministrationLog;
use App\Models\Org\OrganizationAdministrationLogActionType;

class OrganizationAdministrationLoggingClass 
{
	private $userId = NULL;
	private $systemName = NULL;
	private $organizationId = NULL;
	private $organizationName = NULL;
	private $userName = NULL;
	private $userDetails = NULL;

	public function init($adminId)
	{
		$this->systemName = Config::get('app_config.system_name');

        $adminUser = OrganizationAdministration::active()->byId($adminId)->first();

        if(isset($adminUser))
        {
            $this->userDetails = $adminUser;
            $this->userId = $adminId;
            $this->userName = $adminUser->fullname;
            $this->organizationId = $adminUser->organization_id;
            $this->organizationName = $adminUser->organization->regd_name;
        }
	}
	
	public function createLogAdminLoggedIn()
	{
		if(isset($this->userId))
		{
			$actionType = OrganizationAdministrationLogActionType::forTypeLogIn()->first();
			if(isset($actionType))
			{
				$actionTypeId = $actionType->action_type_id;

				$logMsg = $this->userName.' has logged in.';

				$this->addAdminLogEntry($actionTypeId, $logMsg);
			}
		}
	}
	
	public function createLogAdminLoggedOut()
	{
		if(isset($this->userId))
		{
			$actionType = OrganizationAdministrationLogActionType::forTypeLogOut()->first();
			if(isset($actionType))
			{
				$actionTypeId = $actionType->action_type_id;

				$logMsg = $this->userName.' has logged out.';

				$this->addAdminLogEntry($actionTypeId, $logMsg);
			}
		}
	}

	private function addAdminLogEntry($actionTypeId, $logMsg)
	{
    	$userSessionLog = New OrganizationAdministrationLog;
        $userSessionLog->organization_id = $this->organizationId;
        $userSessionLog->org_admin_id = $this->userId;
        $userSessionLog->action_type_id = $actionTypeId;
        $userSessionLog->log_message = $logMsg;
        $userSessionLog->save();
	}
}
