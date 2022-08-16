<?php 
namespace App\Libraries;

use Config;
use Image;
use Crypt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class FolderFilterUtilClass 
{	
	protected $encFilterStr = '';
	protected $decFilterJsonObj = NULL;

	private $FILTER_KEY_START_DATE_TS = 'START_DATE_TS';
	private $FILTER_KEY_END_DATE_TS = 'END_DATE_TS';
	private $FILTER_KEY_CONTENT_TYPE = 'CONTENT_TYPE_ARR';
	private $FILTER_KEY_GROUP = 'GROUP_ARR';
	private $FILTER_KEY_FOLDER = 'FOLDER_ARR';
	private $FILTER_KEY_TAG = 'TAG_ARR';
	private $FILTER_KEY_SOURCE = 'SOURCE_ARR';
	private $FILTER_KEY_ATTACHMENT_TYPE = 'ATTACHMENT_TYPE_ARR';
	private $FILTER_KEY_IS_CONVERSATION = 'IS_CONVERSATION';
	private $FILTER_KEY_IS_UNTAGGED = 'IS_UNTAGGED';
	private $FILTER_KEY_IS_UNREAD = 'IS_UNREAD';
	private $FILTER_KEY_IS_LOCKED = 'IS_LOCKED';
	private $FILTER_KEY_IS_STARRED = 'IS_STARRED';
	private $FILTER_KEY_IS_RESTRICTED = 'IS_RESTRICTED';
	private $FILTER_KEY_DOWNLOAD_STATUS = 'DOWNLOAD_STATUS';
	private $FILTER_KEY_IS_SHOW_FOLDER = 'SHOW_FOLDER';
	private $FILTER_KEY_IS_SHOW_GROUP = 'SHOW_GROUP';
    private $FILTER_KEY_ATTACHMENT_STATUS = 'ATTACHMENT_STATUS';
    private $FILTER_KEY_REPEAT_STATUS = 'REPEAT_STATUS';
    private $FILTER_KEY_COMPLETED_STATUS = 'COMPLETED_STATUS';
    private $FILTER_KEY_SENDER_EMAIL = 'SENDER_EMAIL';
    private $FILTER_KEY_DATE_FILTER_TYPE = 'DATE_FILTER_TYPE';
    private $FILTER_KEY_DATE_FILTER_TYPE_DAY_COUNT = 'DATE_FILTER_TYPE_DAY_COUNT';


	private $FILTER_VAL_START_DATE_TS = 0;
	private $FILTER_VAL_END_DATE_TS = 0;
	private $FILTER_VAL_IS_CONVERSATION = 0;
	private $FILTER_VAL_IS_UNTAGGED = 0;
	private $FILTER_VAL_IS_UNREAD = 0;
	private $FILTER_VAL_IS_LOCKED = 0;
	private $FILTER_VAL_IS_STARRED = 0;
	private $FILTER_VAL_IS_RESTRICTED = 0;
	private $FILTER_VAL_DOWNLOAD_STATUS = -1;
    private $FILTER_VAL_ATTACHMENT_STATUS = -1;
    private $FILTER_VAL_REPEAT_STATUS = -1;
    private $FILTER_VAL_COMPLETED_STATUS = -1;
	private $FILTER_VAL_IS_SHOW_FOLDER = 0;
	private $FILTER_VAL_IS_SHOW_GROUP = 0;
    private $FILTER_VAL_SENDER_EMAIL = '';
    private $FILTER_VAL_DATE_FILTER_TYPE = -1;
    private $FILTER_VAL_DATE_FILTER_TYPE_DAY_COUNT = 0;

	private $FILTER_VAL_CONTENT_TYPE = [];
	private $FILTER_VAL_GROUP = [];
	private $FILTER_VAL_FOLDER = [];
	private $FILTER_VAL_TAG = [];
	private $FILTER_VAL_SOURCE = [];
	private $FILTER_VAL_ATTACHMENT_TYPE = [];
		
	public function __construct()
    {   
    	
    }
    
    public function setFilterStr($filterStr)
    {
    	$this->encFilterStr = $filterStr;
    	$this->segregateFilterStr();
	}
    
    private function segregateFilterStr()
    {
    	$tempEncFilterStr = $this->encFilterStr;

    	try
    	{
    		$this->decFilterJsonObj = json_decode($tempEncFilterStr, true);

    		$this->setFilterForStartDate();
    		$this->setFilterForEndDate();
    		$this->setFilterForIsConversation();
    		$this->setFilterForIsUntagged();
    		$this->setFilterForIsUnread();
    		$this->setFilterForIsLocked();
    		$this->setFilterForIsStarred();
    		$this->setFilterForIsRestricted();
    		$this->setFilterForDownloadStatus();
            $this->setFilterForAttachmentStatus();
            $this->setFilterForRepeatStatus();
            $this->setFilterForCompletedStatus();
    		$this->setFilterForIsShowFolder();
    		$this->setFilterForIsShowGroup();
    		$this->setFilterForContentType();
    		$this->setFilterForGroup();
    		$this->setFilterForFolder();
    		$this->setFilterForTag();
    		$this->setFilterForSource();
            $this->setFilterForAttachmentType();
    		$this->setFilterForSenderEmail();
            $this->setFilterForDateFilterType();
            $this->setFilterForDateFilterTypeDayCount();
    	}
		catch(Exception $e)
		{

		}
	}
    
    public function compileFilterStr()
    {
    	try
    	{
            $filtersObj = array();
            $filtersObj[$this->FILTER_KEY_IS_CONVERSATION] = $this->FILTER_VAL_IS_CONVERSATION;
            $filtersObj[$this->FILTER_KEY_IS_UNTAGGED] = $this->FILTER_VAL_IS_UNTAGGED;
            $filtersObj[$this->FILTER_KEY_IS_UNREAD] = $this->FILTER_VAL_IS_UNREAD;
            $filtersObj[$this->FILTER_KEY_IS_LOCKED] = $this->FILTER_VAL_IS_LOCKED;
            $filtersObj[$this->FILTER_KEY_IS_STARRED] = $this->FILTER_VAL_IS_STARRED;
            $filtersObj[$this->FILTER_KEY_IS_RESTRICTED] = $this->FILTER_VAL_IS_RESTRICTED;
            $filtersObj[$this->FILTER_KEY_DOWNLOAD_STATUS] = $this->FILTER_VAL_DOWNLOAD_STATUS;
            $filtersObj[$this->FILTER_KEY_ATTACHMENT_STATUS] = $this->FILTER_VAL_ATTACHMENT_STATUS;
            $filtersObj[$this->FILTER_KEY_REPEAT_STATUS] = $this->FILTER_VAL_REPEAT_STATUS;
            $filtersObj[$this->FILTER_KEY_COMPLETED_STATUS] = $this->FILTER_VAL_COMPLETED_STATUS;
            $filtersObj[$this->FILTER_KEY_IS_SHOW_FOLDER] = $this->FILTER_VAL_IS_SHOW_FOLDER;
            $filtersObj[$this->FILTER_KEY_IS_SHOW_GROUP] = $this->FILTER_VAL_IS_SHOW_GROUP;
            $filtersObj[$this->FILTER_KEY_IS_CONVERSATION] = $this->FILTER_VAL_IS_CONVERSATION;
            $filtersObj[$this->FILTER_KEY_START_DATE_TS] = $this->FILTER_VAL_START_DATE_TS;
            $filtersObj[$this->FILTER_KEY_END_DATE_TS] = $this->FILTER_VAL_END_DATE_TS;
            $filtersObj[$this->FILTER_KEY_CONTENT_TYPE] = $this->FILTER_VAL_CONTENT_TYPE;
            $filtersObj[$this->FILTER_KEY_GROUP] = $this->FILTER_VAL_GROUP;
            $filtersObj[$this->FILTER_KEY_FOLDER] = $this->FILTER_VAL_FOLDER;
            $filtersObj[$this->FILTER_KEY_TAG] = $this->FILTER_VAL_TAG;
            $filtersObj[$this->FILTER_KEY_SOURCE] = $this->FILTER_VAL_SOURCE;
            $filtersObj[$this->FILTER_KEY_ATTACHMENT_TYPE] = $this->FILTER_VAL_ATTACHMENT_TYPE;
            $filtersObj[$this->FILTER_KEY_SENDER_EMAIL] = $this->FILTER_VAL_SENDER_EMAIL;
            $filtersObj[$this->FILTER_KEY_DATE_FILTER_TYPE] = $this->FILTER_VAL_DATE_FILTER_TYPE;
            $filtersObj[$this->FILTER_KEY_DATE_FILTER_TYPE_DAY_COUNT] = $this->FILTER_VAL_DATE_FILTER_TYPE_DAY_COUNT;

            return $consEncFilterStr = json_encode($filtersObj, true);
    	}
		catch(Exception $e)
		{

		}
	}
    
    private function setFilterForIsConversation()
    {
    	$isConversationFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_CONVERSATION);
    	$this->FILTER_VAL_IS_CONVERSATION = $isConversationFlag;
    	Log::info('IS_CONVERSATION : '.$isConversationFlag);
    	return $isConversationFlag;
    }
    
    private function setFilterForIsUntagged()
    {
    	$isUntaggedFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_UNTAGGED);
    	$this->FILTER_VAL_IS_UNTAGGED = $isUntaggedFlag;
    	Log::info('IS_UNTAGGED : '.$isUntaggedFlag);
    	return $isUntaggedFlag;
    }
    
    private function setFilterForIsUnread()
    {
    	$isUnreadFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_UNREAD);
    	$this->FILTER_VAL_IS_UNREAD = $isUnreadFlag;
    	Log::info('IS_UNREAD : '.$isUnreadFlag);
    	return $isUnreadFlag;
    }
    
    private function setFilterForIsLocked()
    {
    	$isLockedFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_LOCKED);
    	$this->FILTER_VAL_IS_LOCKED = $isLockedFlag;
    	Log::info('IS_LOCKED : '.$isLockedFlag);
    	return $isLockedFlag;
    }
    
    private function setFilterForIsStarred()
    {
    	$isStarredFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_STARRED);
    	$this->FILTER_VAL_IS_STARRED = $isStarredFlag;
    	Log::info('IS_STARRED : '.$isStarredFlag);
    	return $isStarredFlag;
    }
    
    private function setFilterForIsRestricted()
    {
    	$isRestrictedFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_RESTRICTED);
    	$this->FILTER_VAL_IS_RESTRICTED = $isRestrictedFlag;
    	Log::info('IS_RESTRICTED : '.$isRestrictedFlag);
    	return $isRestrictedFlag;
    }
    
    private function setFilterForDownloadStatus()
    {
    	$downloadStatusFlag = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_DOWNLOAD_STATUS, -1);
    	$this->FILTER_VAL_DOWNLOAD_STATUS = $downloadStatusFlag;
    	Log::info('DOWNLOAD_STATUS : '.$downloadStatusFlag);
    	return $downloadStatusFlag;
    }
    
    private function setFilterForAttachmentStatus()
    {
        $attachmentStatusFlag = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_ATTACHMENT_STATUS, -1);
        $this->FILTER_VAL_ATTACHMENT_STATUS = $attachmentStatusFlag;
        Log::info('ATTACHMENT_STATUS : '.$attachmentStatusFlag);
        return $attachmentStatusFlag;
    }
    
    private function setFilterForRepeatStatus()
    {
        $repeatStatusFlag = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_REPEAT_STATUS, -1);
        $this->FILTER_VAL_REPEAT_STATUS = $repeatStatusFlag;
        Log::info('REPEAT_STATUS : '.$repeatStatusFlag);
        return $repeatStatusFlag;
    }
    
    private function setFilterForCompletedStatus()
    {
        $completedStatusFlag = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_COMPLETED_STATUS, -1);
        $this->FILTER_VAL_COMPLETED_STATUS = $completedStatusFlag;
        Log::info('COMPLETED_STATUS : '.$completedStatusFlag);
        return $completedStatusFlag;
    }
    
    private function setFilterForDateFilterType()
    {
        $dateFilterType = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_DATE_FILTER_TYPE, -1);
        $this->FILTER_VAL_DATE_FILTER_TYPE = $dateFilterType;
        Log::info('DATE_FILTER_TYPE : '.$dateFilterType);
        return $dateFilterType;
    }
    
    private function setFilterForDateFilterTypeDayCount()
    {
        $dateFilterTypeDayCount = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_DATE_FILTER_TYPE_DAY_COUNT);
        $this->FILTER_VAL_DATE_FILTER_TYPE_DAY_COUNT = $dateFilterTypeDayCount;
        Log::info('DATE_FILTER_TYPE_DAY_COUNT : '.$dateFilterTypeDayCount);
        return $dateFilterTypeDayCount;
    }
    
    private function setFilterForIsShowFolder()
    {
    	$isShowFolderFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_SHOW_FOLDER);
    	$this->FILTER_VAL_IS_SHOW_FOLDER = $isShowFolderFlag;
    	Log::info('IS_SHOW_FOLDER : '.$isShowFolderFlag);
    	return $isShowFolderFlag;
    }
    
    private function setFilterForIsShowGroup()
    {
    	$isShowGroupFlag = $this->parseFilterStrForValueTypeIntFlag($this->FILTER_KEY_IS_SHOW_GROUP);
    	$this->FILTER_VAL_IS_SHOW_GROUP = $isShowGroupFlag;
    	Log::info('IS_SHOW_GROUP : '.$isShowGroupFlag);
    	return $isShowGroupFlag;
    }
    
    private function setFilterForStartDate()
    {
    	$startDateTs = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_START_DATE_TS);
    	$this->FILTER_VAL_START_DATE_TS = $startDateTs;
    	Log::info('START_DATE_TS : '.$startDateTs);
    	return $startDateTs;
    }
    
    private function setFilterForEndDate()
    {
    	$endDateTs = $this->parseFilterStrForValueTypeInt($this->FILTER_KEY_END_DATE_TS);
    	$this->FILTER_VAL_END_DATE_TS = $endDateTs;
    	Log::info('END_DATE_TS : '.$endDateTs);
    	return $endDateTs;
    }
    
    private function setFilterForSenderEmail()
    {
        $senderEmail = $this->parseFilterStrForValueTypeEmail($this->FILTER_KEY_SENDER_EMAIL);
        $this->FILTER_VAL_SENDER_EMAIL = $senderEmail;
        Log::info('SENDER_EMAIL : '.$senderEmail);
        return $senderEmail;
    }
    
    private function setFilterForContentType()
    {
    	$contentTypeIdArr = $this->parseFilterStrForValueTypeIntArray($this->FILTER_KEY_CONTENT_TYPE);
    	$this->FILTER_VAL_CONTENT_TYPE = $contentTypeIdArr;
    	Log::info('CONTENT_TYPE : ');
    	Log::info($contentTypeIdArr);
    	return $contentTypeIdArr;
    }
    
    private function setFilterForGroup()
    {
    	$groupIdArr = $this->parseFilterStrForValueTypeIntArray($this->FILTER_KEY_GROUP);
    	$this->FILTER_VAL_GROUP = $groupIdArr;
    	Log::info('GROUP : ');
    	Log::info($groupIdArr);
    	return $groupIdArr;
    }
    
    private function setFilterForFolder()
    {
    	$folderIdArr = $this->parseFilterStrForValueTypeIntArray($this->FILTER_KEY_FOLDER);
    	$this->FILTER_VAL_FOLDER = $folderIdArr;
    	Log::info('FOLDER : ');
    	Log::info($folderIdArr);
    	return $folderIdArr;
    }
    
    private function setFilterForTag()
    {
    	$tagIdArr = $this->parseFilterStrForValueTypeIntArray($this->FILTER_KEY_TAG);
    	$this->FILTER_VAL_TAG = $tagIdArr;
    	Log::info('TAG : ');
    	Log::info($tagIdArr);
    	return $tagIdArr;
    }
    
    private function setFilterForSource()
    {
    	$sourceIdArr = $this->parseFilterStrForValueTypeIntArray($this->FILTER_KEY_SOURCE);
    	$this->FILTER_VAL_SOURCE = $sourceIdArr;
    	Log::info('SOURCE : ');
    	Log::info($sourceIdArr);
    	return $sourceIdArr;
    }
    
    private function setFilterForAttachmentType()
    {
    	$attachmentTypeArr = $this->parseFilterStrForValueTypeStrArray($this->FILTER_KEY_ATTACHMENT_TYPE);
    	$this->FILTER_VAL_ATTACHMENT_TYPE = $attachmentTypeArr;
    	Log::info('ATTACHMENT_TYPE : ');
    	Log::info($attachmentTypeArr);
    	return $attachmentTypeArr;
    }
    
    private function parseFilterStrForValueTypeInt($filterKey, $defValue = 0)
    {
    	$intVal = $defValue;
    	if(isset($this->decFilterJsonObj) && isset($this->decFilterJsonObj[$filterKey]))
    	{
    		$intJsonVal = $this->decFilterJsonObj[$filterKey];

    		if(isset($intJsonVal) && $intJsonVal != "" && is_numeric($intJsonVal * 1))
    		{
    			$intVal = $intJsonVal * 1;
    		}
    	}
    	return $intVal;
    }
    
    private function parseFilterStrForValueTypeIntFlag($filterKey)
    {
    	$flagVal = 0;
    	if(isset($this->decFilterJsonObj) && isset($this->decFilterJsonObj[$filterKey]))
    	{
    		$flagJsonVal = $this->decFilterJsonObj[$filterKey];

    		if(isset($flagJsonVal) && $flagJsonVal != "")
    		{
    			$flagVal = $flagJsonVal * 1; 

    			if($flagVal != 1)
    			{
    				$flagVal = 0;
    			}
    		}
    	}
    	return $flagVal;
    }
    
    private function parseFilterStrForValueTypeEmail($filterKey)
    {
        $emailVal = '';
        if(isset($this->decFilterJsonObj) && isset($this->decFilterJsonObj[$filterKey]))
        {
            $emailJsonVal = $this->decFilterJsonObj[$filterKey];

            if(isset($emailJsonVal) && $emailJsonVal != "" && filter_var($emailJsonVal, FILTER_VALIDATE_EMAIL))
            {
                $emailVal = $emailJsonVal;
            }
        }
        return $emailVal;
    }

    private function parseFilterStrForValueTypeIntArray($filterKey)
    {
    	$valArr = [];
    	if(isset($this->decFilterJsonObj) && isset($this->decFilterJsonObj[$filterKey]))
    	{
    		$flagJsonValArr = $this->decFilterJsonObj[$filterKey];

    		if(isset($flagJsonValArr) && is_array($flagJsonValArr) && count($flagJsonValArr) > 0)
    		{
    			foreach ($flagJsonValArr as $flagKey => $flagJsonVal)
    			{
	    			$flagVal = $flagJsonVal != "" ? $flagJsonVal * 1 : 0; 

	    			if($flagVal < 0)
	    			{
	    				$flagVal = 0;
	    			}

	    			array_push($valArr, $flagVal);
    			}
    		}
    	}
    	return $valArr;
    }

    private function parseFilterStrForValueTypeStrArray($filterKey)
    {
    	$valArr = [];
    	if(isset($this->decFilterJsonObj) && isset($this->decFilterJsonObj[$filterKey]))
    	{
    		$flagJsonValArr = $this->decFilterJsonObj[$filterKey];

    		if(isset($flagJsonValArr) && is_array($flagJsonValArr) && count($flagJsonValArr) > 0)
    		{
    			foreach ($flagJsonValArr as $flagKey => $flagJsonVal)
    			{
	    			$flagVal = $flagJsonVal != "" ? $flagJsonVal : "";

	    			array_push($valArr, $flagVal);
    			}
    		}
    	}
    	return $valArr;
    }
    
    public function setFilterValueIsConversation($isConversationFlag)
    {
    	$isConversationFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isConversationFlag);
    	$this->FILTER_VAL_IS_CONVERSATION = $isConversationFlag;
    }

    public function setFilterValueIsUntagged($isUntaggedFlag)
    {
    	$isUntaggedFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isUntaggedFlag);
    	$this->FILTER_VAL_IS_UNTAGGED = $isUntaggedFlag;
    }
    
    public function setFilterValueIsUnread($isUnreadFlag)
    {
    	$isUnreadFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isUnreadFlag);
    	$this->FILTER_VAL_IS_UNREAD = $isUnreadFlag;
    }
    
    public function setFilterValueIsLocked($isLockedFlag)
    {
    	$isLockedFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isLockedFlag);
    	$this->FILTER_VAL_IS_LOCKED = $isLockedFlag;
    }
    
    public function setFilterValueIsStarred($isStarredFlag)
    {
    	$isStarredFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isStarredFlag);
    	$this->FILTER_VAL_IS_STARRED = $isStarredFlag;
    }
    
    public function setFilterValueIsRestricted($isRestrictedFlag)
    {
    	$isRestrictedFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isRestrictedFlag);
    	$this->FILTER_VAL_IS_RESTRICTED = $isRestrictedFlag;
    }
    
    public function setFilterValueDownloadStatus($downloadStatusFlag)
    {
    	$downloadStatusFlag = $this->sanitizeFilterValueForValueTypeInt($downloadStatusFlag, -1);
    	$this->FILTER_VAL_DOWNLOAD_STATUS = $downloadStatusFlag;
    }
    
    public function setFilterValueAttachmentStatus($attachmentStatusFlag)
    {
        $attachmentStatusFlag = $this->sanitizeFilterValueForValueTypeInt($attachmentStatusFlag, -1);
        $this->FILTER_VAL_ATTACHMENT_STATUS = $attachmentStatusFlag;
    }
    
    public function setFilterValueRepeatStatus($repeatStatusFlag)
    {
        $repeatStatusFlag = $this->sanitizeFilterValueForValueTypeInt($repeatStatusFlag, -1);
        $this->FILTER_VAL_REPEAT_STATUS = $repeatStatusFlag;
    }
    
    public function setFilterValueCompletedStatus($completedStatusFlag)
    {
        $completedStatusFlag = $this->sanitizeFilterValueForValueTypeInt($completedStatusFlag, -1);
        $this->FILTER_VAL_COMPLETED_STATUS = $completedStatusFlag;
    }
    
    public function setFilterValueDateFilterType($dateFilterTypeFlag)
    {
        $dateFilterTypeFlag = $this->sanitizeFilterValueForValueTypeInt($dateFilterTypeFlag, -1);
        $this->FILTER_VAL_DATE_FILTER_TYPE = $dateFilterTypeFlag;
    }
    
    public function setFilterValueDateFilterTypeDayCount($dateFilterTypeDayCount)
    {
        $dateFilterTypeDayCount = $this->sanitizeFilterValueForValueTypeInt($dateFilterTypeDayCount);
        $this->FILTER_VAL_DATE_FILTER_TYPE_DAY_COUNT = $dateFilterTypeDayCount;
    }
    
    public function setFilterValueIsShowFolder($isShowFolderFlag)
    {
    	$isShowFolderFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isShowFolderFlag);
    	$this->FILTER_VAL_IS_SHOW_FOLDER = $isShowFolderFlag;
    }
    
    public function setFilterValueIsShowGroup($isShowGroupFlag)
    {
    	$isShowGroupFlag = $this->sanitizeFilterValueForValueTypeIntFlag($isShowGroupFlag);
    	$this->FILTER_VAL_IS_SHOW_GROUP = $isShowGroupFlag;
    }
    
    public function setFilterValueStartDateTs($startDateTs)
    {
        $startDateTs = $this->sanitizeFilterValueForValueTypeInt($startDateTs);
        $this->FILTER_VAL_START_DATE_TS = $startDateTs;
    }
    
    public function setFilterValueEndDateTs($endDateTs)
    {
        $endDateTs = $this->sanitizeFilterValueForValueTypeInt($endDateTs);
        $this->FILTER_VAL_END_DATE_TS = $endDateTs;
    }
    
    public function setFilterValueSenderEmail($senderEmail)
    {
        $senderEmail = $this->sanitizeFilterValueForValueTypeEmail($senderEmail);
        $this->FILTER_VAL_SENDER_EMAIL = $senderEmail;
    }
    
    public function setFilterValueContentType($contentTypeIdArr)
    {
    	$contentTypeIdArr = $this->sanitizeFilterValueForValueTypeIntArray($contentTypeIdArr);
    	$this->FILTER_VAL_CONTENT_TYPE = $contentTypeIdArr;
    }
    
    public function setFilterValueGroup($groupIdArr)
    {
    	$groupIdArr = $this->sanitizeFilterValueForValueTypeIntArray($groupIdArr);
    	$this->FILTER_VAL_GROUP = $groupIdArr;
    }
    
    public function setFilterValueFolder($folderIdArr)
    {
    	$folderIdArr = $this->sanitizeFilterValueForValueTypeIntArray($folderIdArr);
    	$this->FILTER_VAL_FOLDER = $folderIdArr;
    }
    
    public function setFilterValueTag($tagIdArr)
    {
    	$tagIdArr = $this->sanitizeFilterValueForValueTypeIntArray($tagIdArr);
    	$this->FILTER_VAL_TAG = $tagIdArr;
    }
    
    public function setFilterValueSource($sourceIdArr)
    {
    	$sourceIdArr = $this->sanitizeFilterValueForValueTypeIntArray($sourceIdArr);
    	$this->FILTER_VAL_SOURCE = $sourceIdArr;
    }
    
    public function setFilterValueAttachmentType($attachmentTypeArr)
    {
    	$attachmentTypeArr = $this->sanitizeFilterValueForValueTypeStrArray($attachmentTypeArr);
    	$this->FILTER_VAL_ATTACHMENT_TYPE = $attachmentTypeArr;
    }
    
    private function sanitizeFilterValueForValueTypeIntFlag($ipFlagVal)
    {
    	$flagVal = 0;
    	if(isset($ipFlagVal) && $ipFlagVal != "" && $ipFlagVal * 1 == 1)
    	{
    		$flagVal = 1;
    	}
    	return $flagVal;
    }
    
    private function sanitizeFilterValueForValueTypeInt($ipVal, $defValue = 0)
    {
        $intVal = $defValue;
        if(isset($ipVal) && $ipVal != "" && is_numeric($ipVal * 1))
        {
            $intVal = $ipVal * 1;
        }
        return $intVal;
    }
    
    private function sanitizeFilterValueForValueTypeEmail($ipVal)
    {
        $emailVal = '';
        if(isset($ipVal) && $ipVal != "" && filter_var($ipVal, FILTER_VALIDATE_EMAIL))
        {
            $emailVal = $ipVal;
        }
        return $emailVal;
    }
    
    private function sanitizeFilterValueForValueTypeIntArray($ipArrVal)
    {
    	$valArr = [];
    	if(isset($ipArrVal) && is_array($ipArrVal) && count($ipArrVal) > 0)
    	{
    		foreach ($ipArrVal as $ipVal) 
    		{
    			$intVal = $ipVal * 1;
    			array_push($valArr, $intVal);
    		}
    	}
    	return $valArr;
    }
    
    private function sanitizeFilterValueForValueTypeStrArray($ipArrVal)
    {
    	$valArr = [];
    	if(isset($ipArrVal) && is_array($ipArrVal) && count($ipArrVal) > 0)
    	{
    		$valArr = $ipArrVal;
    	}
    	return $valArr;
    }
    
    public function getFilterValueIsConversation()
    {
    	return $this->FILTER_VAL_IS_CONVERSATION;
    }
    
    public function getFilterValueIsUntagged()
    {
    	return $this->FILTER_VAL_IS_UNTAGGED;
    }
    
    public function getFilterValueIsLocked()
    {
    	return $this->FILTER_VAL_IS_LOCKED;
    }
    
    public function getFilterValueIsStarred()
    {
    	return $this->FILTER_VAL_IS_STARRED;
    }
    
    public function getFilterValueIsRestricted()
    {
    	return $this->FILTER_VAL_IS_RESTRICTED;
    }
    
    public function getFilterValueIsUnread()
    {
        return $this->FILTER_VAL_IS_UNREAD;
    }
    
    public function getFilterValueDownloadStatus()
    {
    	return $this->FILTER_VAL_DOWNLOAD_STATUS;
    }
    
    public function getFilterValueAttachmentStatus()
    {
        return $this->FILTER_VAL_ATTACHMENT_STATUS;
    }
    
    public function getFilterValueRepeatStatus()
    {
        return $this->FILTER_VAL_REPEAT_STATUS;
    }
    
    public function getFilterValueCompletedStatus()
    {
        return $this->FILTER_VAL_COMPLETED_STATUS;
    }
    
    public function getFilterValueDateFilterType()
    {
        return $this->FILTER_VAL_DATE_FILTER_TYPE;
    }
    
    public function getFilterValueDateFilterTypeDayCount()
    {
        return $this->FILTER_VAL_DATE_FILTER_TYPE_DAY_COUNT;
    }
    
    public function getFilterValueIsShowFolder()
    {
    	return $this->FILTER_VAL_IS_SHOW_FOLDER;
    }
    
    public function getFilterValueIsShowGroup()
    {
    	return $this->FILTER_VAL_IS_SHOW_GROUP;
    }
    
    public function getFilterValueStartDateTs()
    {
        return $this->FILTER_VAL_START_DATE_TS;
    }
    
    public function getFilterValueEndDateTs()
    {
        return $this->FILTER_VAL_END_DATE_TS;
    }
    
    public function getFilterValueSenderEmail()
    {
        return $this->FILTER_VAL_SENDER_EMAIL;
    }
    
    public function getFilterValueContentType()
    {
    	return $this->FILTER_VAL_CONTENT_TYPE;
    }
    
    public function getFilterValueFolder()
    {
    	return $this->FILTER_VAL_FOLDER;
    }
    
    public function getFilterValueGroup()
    {
    	return $this->FILTER_VAL_GROUP;
    }
    
    public function getFilterValueTag()
    {
    	return $this->FILTER_VAL_TAG;
    }
    
    public function getFilterValueSource()
    {
    	return $this->FILTER_VAL_SOURCE;
    }
    
    public function getFilterValueAttachmentType()
    {
    	return $this->FILTER_VAL_ATTACHMENT_TYPE;
    }
}
