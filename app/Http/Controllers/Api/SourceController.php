<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserSource;
use App\Models\Org\Api\OrgEmployeeSource;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;
use Datatables;
use Illuminate\Support\Facades\Input;
use Hash;
use Redirect;
use Config;
use Response;
use Crypt;
use App\Libraries\CommonFunctionClass;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use DB;
use View;

class SourceController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    /**
     * Add Source.
     *
     * @return json array
     */
    public function saveSourceDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = (Input::get('id'));
        $name = Input::get('name');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $name != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}

                $id = sracDecryptNumberData($id, $userSession);
				             
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $response = $depMgmtObj->addEditSource($id, $name);
                $response['syncId'] = sracEncryptNumberData($response['syncId'], $userSession);
                
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Source List.
     *
     * @return json array
     */
    public function sourceList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::findOrFail($userId);
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
				             
                $i = 0;
                $sourceList = array();
                $arrForSorting = array();

                $userSources = AppuserSource::ofUser($userId)->get();
                foreach ($userSources as $source) 
                {
                    $sourceList[$i]['id'] = sracEncryptNumberData($source->appuser_source_id, $userSession);
                    $sourceList[$i]['name'] = $source->source_name;
                    $arrForSorting[$i] = $source->source_name;

                    $i++;
                }
                array_multisort($arrForSorting, $sourceList);   

                $status = 1;

                $resCnt = count($sourceList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_source_found');

                $response['sourceCnt'] = $resCnt;
                $response['sourceArr'] = $sourceList;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Source List.
     *
     * @return json array
     */
    public function loadSelectSourceList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $selOrgId = Input::get('selOrgId');
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                
                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);	
                
                $sourceArr = array();
                $arrForSorting = array();                
                $userSources = $depMgmtObj->getAllSources();
                foreach ($userSources as $source) 
                {
                	if($orgId > 0)					
						$sourceId = $source->employee_source_id;
					else
						$sourceId = $source->appuser_source_id;
						
					$sourceName = $source->source_name;
					$sourceObj = array();
					$sourceObj["id"] = sracEncryptNumberData($sourceId, $userSession);
					$sourceObj["text"] = $sourceName;
					array_push($sourceArr, $sourceObj);
					array_push($arrForSorting, $sourceName);
                }
                array_multisort($arrForSorting, $sourceArr);   

				$response = array('results' => $sourceArr );
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }

    /**
     * Display a listing of the resource via datatables.
     *
     * @return void
     */
    public function appuserSourceListDatatable()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $modelObj = $depMgmtObj->getAllSourcesModelObj();

                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);                
            	if($orgId > 0)					
					$idStr = 'employee_source_id';
				else
					$idStr = 'appuser_source_id';
						
	            $sources = $modelObj->select([$idStr.' as source_id', 'source_name']);

	            return Datatables::of($sources)
	                    ->remove_column('source_id')
	                    ->add_column('action', function($source) use ($userSession) {
	                        return $this->getSourceDatatableButton($source->source_id, $userSession);
	                    })
	                    ->make();
			}
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
		}
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    private function getSourceDatatableButton($id, $userSession)
    {
        $id = sracEncryptNumberData($id, $userSession);
        
		$buttonHtml = "";        
        $buttonHtml .= '&nbsp;<button onclick="loadAddEditSourceModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
        $buttonHtml .= '&nbsp;<button onclick="checkAndDeleteSource(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
        return $buttonHtml;
	}

    /**
     * Load add or edit details modal
     *
     * @param  int  $id
     *
     * @return void
     */
    public function loadAddEditModal()
    {
        $encUserId = Input::get('userId');
        $id = (Input::get('id'));
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $status = 0;
        $msg = "";

        if($encUserId != "" && $id != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            {
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}

                $id = sracDecryptNumberData($id, $userSession);
				             
                $status = 1;	            
	            $pageName = 'Add'; 
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $source = $depMgmtObj->getSourceObject($id);

	            if(isset($source))
	            {
	            	$pageName = "Edit";
	            	$source->source_id = sracEncryptNumberData($id, $userSession);
				}
	            
	            $data = array();
	            $data['source'] = $source;
	            $data['page_description'] = $pageName.' '.'Source';
	           
	            $_viewToRender = View::make('content.supporting._addEditSourceModal', $data);
	            $_viewToRender = $_viewToRender->render();
	            
	            $response['view'] = $_viewToRender;             
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }
        
        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Check Source Can Be Deleted.
     *
     * @return json array
     */
    public function checkSourceCanBeDeleted()
    {
        $msg = "";
        $status = 0;
        $isDeletable = 1;

        $encUserId = Input::get('userId');
        $id = (Input::get('id'));
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        
        $response = array();
        $response = array();

        if($encUserId != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}

                $id = sracDecryptNumberData($id, $userSession);
                
                $isFolder = TRUE;
				
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
				$contentModelObj = $depMgmtObj->getAllContentModelObj($isFolder);
				$tableName = $depMgmtObj->getContentTablename($isFolder);
				
				$contentModelObj = $contentModelObj->where($tableName.'.source_id', '=', $id);
				
				$usedSources = $contentModelObj->first();
				
				if(!isset($usedSources))                
                	$status = 1;
                else
                {
					$status = -1;
					$msg = "Source in use. Cannot be deleted.";
				}                
			}
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
		}
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    /**
     * Delete Source.
     *
     * @return json array
     */
    public function deleteSource()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = (Input::get('id'));
        $loginToken = Input::get('loginToken');

        $response = array();

        if($encUserId != "" && $id != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken); 
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}

                $id = sracDecryptNumberData($id, $userSession);
				             
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $depMgmtObj->deleteSource($id);
                    
                CommonFunctionClass::setLastSyncTs($userId, $loginToken);
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
    }
    
    public function validateSourceName()
    {
        $msg = "";
        $status = 0;
        $isAvailable = FALSE;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $id = (Input::get('id'));
        $name = Input::get('name');

        $response = array();

        if($encUserId != "" && $id != "")
        {
        	if(!isset($loginToken) || $loginToken == "")
        	{
		        $response['status'] = -1;
		        $response['msg'] = Config::get('app_config_notif.err_login_token_unavailable');
		        $response['tokenStatus'] = Config::get('app_config_notif.err_login_token_unavailable_status_id');

		        return Response::json($response);
			}
			
            $userId = Crypt::decrypt($encUserId);
            $user = Appuser::byId($userId)->first();
            
            if(isset($user) )
            { 
            	$userSession = CommonFunctionClass::getUserSession($userId, $loginToken);
            	if(!isset($userSession))
	        	{
			        $response['status'] = -1;
			        $response['msg'] = Config::get('app_config_notif.err_login_token_incorrect');
		        	$response['tokenStatus'] = Config::get('app_config_notif.err_login_token_incorrect_status_id');

			        return Response::json($response);
				}

                $id = sracDecryptNumberData($id, $userSession);
				             
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $modelObj = $depMgmtObj->getAllSourcesModelObj();            
                
               	$modelObj = $modelObj->where('source_name','=',$name);
               	if($id > 0) 
            	{
            		if($encOrgId == "")
            			$fieldname = "appuser";
            		else
            			$fieldname = "employee";
            			
            		$modelObj = $modelObj->where($fieldname."_source_id", "<>", "$id");
				}
            		
            	$source = $modelObj->first();
            	
            	if(!isset($source))
		            $isAvailable = TRUE;
            }
            else
            {
                $status = -1;
                $msg = Config::get('app_config_notif.err_invalid_user');       
            }
        }
        else
        {
            $status = -1;
            $msg = Config::get('app_config_notif.err_invalid_data');
        }      

        $response['valid'] = $isAvailable;
        $response['status'] = $status;
        $response['msg'] = "$msg";

        return Response::json($response);
	}
}
