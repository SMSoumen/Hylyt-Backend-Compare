<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Api\Appuser;
use App\Models\Api\AppuserTag;
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
use App\Models\Org\Api\OrgEmployeeTag;
use App\Libraries\OrganizationClass;
use App\Libraries\ContentDependencyManagementClass;
use App\Models\Api\AppuserContentTag;
use App\Models\Org\Api\OrgEmployeeContentTag;
use DB;
use View;
use Illuminate\Support\Facades\Log;
use Illuminate\Encryption\Encrypter;



class TagController extends Controller
{	
	public function __construct()
    {
    	
    }
    
    public function getPhpInfo()
    {
		phpinfo();
	}
    
    /**
     * Add Tag.
     *
     * @return json array
     */
    public function saveTagDetails()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $id = (Input::get('id'));
        $name = (Input::get('name'));
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

                $id = sracDecryptNumberData($id, $userSession);
				             
                $status = 1;
                $msg = "";
                
                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $response = $depMgmtObj->addEditTag($id, $name);
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
     * Tag List.
     *
     * @return json array
     */
    public function tagList()
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
                $tagList = array();
                $arrForSorting = array();

                $userTags = AppuserTag::where('appuser_id','=',$userId)->get();
                foreach ($userTags as $tag) 
                {
                    $tagList[$i]['id'] = sracEncryptNumberData($tag->tag_id, $userSession);
                    $tagList[$i]['name'] = $tag->tag_name;
                    $arrForSorting[$i] = $tag->tag_name;

                    $i++;
                }
                array_multisort($arrForSorting, $tagList);   

                $status = 1;
                
                $resCnt = count($tagList); 
                if($resCnt == 0)
                    $msg = Config::get('app_config_notif.inf_no_tag_found');

                $response['tagCnt'] = $resCnt;
                $response['tagArr'] = $tagList;
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
     * Folder List.
     *
     * @return json array
     */
    public function loadSelectTagList()
    {
        $msg = "";
        $status = 0;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $selOrgId = Input::get('selOrgId');
        $loginToken = Input::get('loginToken');
        $searchStr = Input::get('searchStr');

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
                
                $tagArr = array();
                $arrForSorting = array();                
                $userTags = $depMgmtObj->getAllTagsModelObj();
                    
                if(isset($searchStr) && $searchStr != "")
                {       
                    $userTags = $userTags->where(function($query) use ($searchStr)
                                    {
                                        $query->where('tag_name','like',"%$searchStr%");
                                    });     
                }

                $userTagArr = $userTags->get(); 

                foreach ($userTagArr as $tag) 
                {
                	if($orgId > 0)					
						$tagId = $tag->employee_tag_id;
					else
						$tagId = $tag->appuser_tag_id;
						
					$tagName = $tag->tag_name;
					$tagObj = array();
					$tagObj["id"] = sracEncryptNumberData($tagId, $userSession);
					$tagObj["text"] = $tagName;
					array_push($tagArr, $tagObj);
					array_push($arrForSorting, $tagName);
                }
                array_multisort($arrForSorting, $tagArr);   

				$response = array('results' => $tagArr );
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
    public function appuserTagListDatatable()
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
                $modelObj = $depMgmtObj->getAllTagsModelObj();

                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);   
                $selArr = array();             
            	if($orgId > 0)					
				{
                    $idStr = 'employee_tag_id';
                    $selArr = [$idStr.' as tag_id', 'tag_name', 'rel_system_tag_id'];
                }
				else
				{
                    $idStr = 'appuser_tag_id';
                    $selArr = [$idStr.' as tag_id', 'tag_name' , \DB::raw("0 as rel_system_tag_id")];
                }

	            $tags = $modelObj->select($selArr);

	            return Datatables::of($tags)
	                    ->remove_column('tag_id')
                        ->remove_column('rel_system_tag_id')
	                    ->add_column('action', function($tag) use ($userSession) {
	                        return $this->getTagDatatableButton($tag->tag_id, $tag->rel_system_tag_id, $userSession);
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
    
    private function getTagDatatableButton($id, $relSystemTagId, $userSession)
    {
        $buttonHtml = "";  
        $id = sracEncryptNumberData($id, $userSession);

        if(!isset($relSystemTagId) || $relSystemTagId <= 0)
        {
            $buttonHtml .= '&nbsp;<button onclick="loadAddEditTagModal(\''.$id.'\');" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i>&nbsp;&nbsp;Edit</button>';
            $buttonHtml .= '&nbsp;<button onclick="checkAndDeleteTag(\''.$id.'\');" class="btn btn-xs btn-danger"><i class="fa fa-trash-o"></i>&nbsp;&nbsp;Delete</button>';
        }
      
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
                $tag = $depMgmtObj->getTagObject($id);

	            if(isset($tag))
	            {
	            	$pageName = "Edit";
	            	$tag->tag_id = sracEncryptNumberData($id, $userSession);
				}
	            
	            $data = array();
	            $data['tag'] = $tag;
	            $data['page_description'] = $pageName.' '.'Tag';
	           
	            $_viewToRender = View::make('content.supporting._addEditTagModal', $data);
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
     * Check Tag Can Be Deleted.
     *
     * @return json array
     */
    public function checkTagCanBeDeleted()
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

                $depMgmtObj = New ContentDependencyManagementClass;
                $depMgmtObj->withOrgKey($user, $encOrgId);
                $depMgmtObj->setCurrentLoginToken($loginToken);
                $tag = $depMgmtObj->getTagObject($id);

                $orgId = OrganizationClass::getOrgIdFromOrgKey($encOrgId);
                $orgDbConName = OrganizationClass::configureConnectionForOrganization($orgId);

                if(isset($tag))
                {
                    if(($orgId == 0) || ($orgId > 0 && (!isset($tag->rel_system_tag_id) || $tag->rel_system_tag_id <= 0)))
                    {
                        if($orgId > 0)
                        {
                            $modelObj = New OrgEmployeeContentTag;
                            $modelObj->setConnection($orgDbConName);
                        }
                        else
                        {
                            $modelObj = New AppuserContentTag;
                        }
                        
                        $modelObj = $modelObj->where('tag_id', '=', $id);
                        
                        $usedTags = $modelObj->first();
                        
                        if(!isset($usedTags))                
                            $status = 1;
                        else
                        {
                            $status = -1;
                            $msg = "Tag in use. Cannot be deleted.";
                        }
                    }
                    else
                    {
                        $status = -1;
                        $msg = "System Tag Cannot be deleted.";
                    }
                }
                else
                {
                    $status = -1;
                    $msg = Config::get('app_config_notif.err_invalid_data');
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
     * Delete Tag.
     *
     * @return json array
     */
    public function deleteTag()
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
                $depMgmtObj->deleteTag($id);              
                    
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
    
    public function validateTagName()
    {
        $msg = "";
        $status = 0;
        $isAvailable = FALSE;

        $encUserId = Input::get('userId');
        $encOrgId = Input::get('orgId');
        $loginToken = Input::get('loginToken');
        $id = (Input::get('id'));
        $name = (Input::get('name'));

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
                $modelObj = $depMgmtObj->getAllTagsModelObj();            
                
               	$modelObj = $modelObj->where('tag_name','=',$name);
               	if($id > 0) 
            	{
            		if($encOrgId == "")
            			$fieldname = "appuser";
            		else
            			$fieldname = "employee";
            			
            		$modelObj = $modelObj->where($fieldname."_tag_id", "<>", "$id");
				}
            		
            	$tag = $modelObj->first();
            	
            	if(!isset($tag))
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
