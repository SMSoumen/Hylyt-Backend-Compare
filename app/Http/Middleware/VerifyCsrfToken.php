<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        
        /* For Common Functions Starts */        
        '/cfCompareDateValidation',
        /* For Common Functions Ends */

        /* For User Starts */
        '/validateCurrentPassword',
        /* For User Ends */
        
        /* For Dashboard Starts */
        '/loadDashboardStats',
        /* For Dashboard Ends */
        
        /* For Department Starts */
        '/validateDepartmentName',
        '/checkDeptAvailForDelete',
        /* For Department Ends */
        
        /* For Role Starts */
        '/validateRoleName',
        '/checkRoleAvailForDelete',
        /* For Role Ends */

        /* For Module Starts */
        '/validateModuleName',
        '/checkModuleAvailForDelete',
        /* For Module Ends */

        /* For Rights Starts */
        '/loadRightsForRole',
        '/loadRightsForModule',
        /* For Rights Ends */

        /* For Employee Starts */
        '/validateEmployeeNo',
        '/validateUserName',
        '/checkEmpAvailForDelete',
        /* For Employee Ends */

        /* For Appuser Server Starts */
        '/loadModifyAppuserQuotaModal',
        '/saveAppuserQuotaDetails',
        '/changeAppuserStatus',
        '/loadSendNotificationModal',
        '/sendAppuserNotification',
        '/loadAddContentModal',
        '/addAppuserContent',
        '/sendSelAppuserNotification',
        '/addSelAppuserContent',
        '/appuserContentDatatable',
        '/appuserContentDatatable',
        '/loadModifyAppuserPremiumExpirationDateModal',
        '/saveAppuserPremiumExpirationDateDetails',
        /* For Appuser Server Ends */
        
        /* For Department Starts */
        '/validateThoughtTipDate',
        '/checkThoughtTipAvailForDelete',
        /* For Department Ends */

         /* For API Starts */
            /* For Appuser Admin Starts */
            '/admAuthenticate',
            '/admValidateCurrPassword',
            '/admUpdatePassword',
            '/loadAdminUserMenu',
            '/loadAdminDashboardStats',
            '/resendAdminCredentials',
            '/loadOrgInformationModal',
            '/saveOrgAppInformation',
            '/admValidateOtherAdminEmail',
            '/admValidateOtherAdminPassword',
            '/admUserLogout',
            '/loadOrgStackReferralCodeModal',
            '/saveOrgStackReferralCode',
            /* For Appuser Admin Ends */

            /* For Organization Department Starts */
            '/loadOrgDeptView',
            '/orgDeptDatatable',
            '/loadOrgDeptAddEditModal',
            '/saveOrgDeptDetails',
            '/deleteOrgDept',
            '/validateOrgDeptName',
            '/checkOrgDeptAvailForDelete',
            /* For Organization Department Ends */
            
            /* For Organization SystemTag Starts */
            '/loadOrgSystemTagView',
            '/orgSystemTagDatatable',
            '/loadOrgSystemTagAddEditModal',
            '/saveOrgSystemTagDetails',
            '/sendOrgSystemTagModifiedNotification',
            '/deleteOrgSystemTag',
            '/validateOrgSystemTagName',
            '/checkOrgSystemTagAvailForDelete',
            /* For Organization SystemTag Ends */

            /* For Organization Designation Starts */
            '/loadOrgDesigView',
            '/orgDesigDatatable',
            '/loadOrgDesigAddEditModal',
            '/saveOrgDesigDetails',
            '/deleteOrgDesig',
            '/validateOrgDesigName',
            '/checkOrgDesigAvailForDelete',
            /* For Organization Designation Ends */

            /* For Organization Badge Starts */
            '/loadOrgBadgeView',
            '/orgBadgeDatatable',
            '/loadOrgBadgeAddEditModal',
            '/saveOrgBadgeDetails',
            '/deleteOrgBadge',
            '/validateOrgBadgeName',
            '/checkOrgBadgeAvailForDelete',
            /* For Organization Badge Ends */

            /* For Organization Group Starts */
            '/loadOrgGroupView',
            '/orgGroupDatatable',
            '/loadOrgGroupAddEditModal',
            '/saveOrgGroupDetails',
            '/loadOrgGroupModifyRightModal',
            '/saveOrgGroupRightDetails',
            '/deleteOrgGroup',
            '/validateOrgGroupName',
            '/checkOrgGroupAvailForDelete',
            '/loadOrgGroupInfoModal',
            '/invertOrgGroupFavoritedStatus',
            '/changeOrgGroupStatus',
            '/invertOrgGroupLockedStatus',
            '/loadOrgGroupMembershipShareModal',
            '/sendOrgGroupMembershipInvitation',
            '/usrJoinGroup',
            /* For Organization Group Ends */

            /* For Organization Employee Starts */
            '/loadOrgEmployeeView',
            '/orgEmployeeDatatable',
            '/loadOrgEmployeeAddEditModal',
            '/saveOrgEmployeeDetails',
            '/deleteOrgEmployee',
            '/validateOrgEmployeeNo',
            '/validateOrgEmployeeEmail',
            '/checkOrgEmployeeAvailForDelete',
            '/loadOrgEmployeeUploadExcel',
            '/loadOrgNotificationView',
            '/changeOrgEmployeeStatus',
            '/loadOrgEmployeeModifyQuotaModal',
            '/saveOrgEmployeeQuotaDetails',
            '/loadOrgEmployeeModifyShareRightModal',
            '/loadOrgEmployeeRestoreContentModal',
            '/performOrgEmployeeContentRestore',
            '/saveOrgEmployeeShareRightDetails',
            '/sendOrgEmployeeCredentialMail',
            '/loadOrgEmployeeCredentialModal',
            '/loadOrgEmployeeModifyEmailModal',
            '/changeOrgEmployeeEmail',
            '/changeOrgEmployeeWebAccessStatus',
            '/checkOrgEmployeeAvailForDetachment',
            '/detachOrgEmployee',
            '/changeOrgEmployeeFileSaveShareEnabledStatus',
            '/changeOrgEmployeeScreenShareEnabledStatus',
            /* For Organization Employee Ends */

            /* For Organization Template Starts */
            '/loadOrgTemplateView',
            '/loadOrgTemplateAddEditModal',
            '/orgTemplateDatatable',
            '/saveOrgTemplateDetails',
            '/deleteOrgTemplate',
            '/checkOrgTemplateAvailForDelete',
            '/validateOrgTemplateName',
            /* For Organization Template Ends */

            /* For Appuser Starts */
            '/usageagreementDetails',
            '/appuserAuthenticate',
            '/appuserRegister',
            '/appuserVerify',
            '/resendAppuserVerificationCode',
            '/sendAppuserOtp',
            '/verifyAppuserOtp',
            '/sendLoggedInAppuserOtp',
            '/changeAppuserForgotPassword',
            '/appuserDetails',
            '/saveAppuserDetails',
            '/saveAppuserReferralCode',
            '/loadAppuserChangePasswordModal',
            '/validateAppuserPassword',
            '/changeAppuserPassword',
            '/appuserSocialAuthenticate',
            '/sendAppuserForgotPin',
            '/sendAppuserForgotFolderPin',
            '/appuserDelete',
            '/appuserLogout',
            '/appuserRequestAccountType',
            '/appuserAccountTypeSubscribed',
            '/getThoughtTipForConsideredDate',
            '/validateAppuserAppPin',
            '/checkAppuserAppPinEnabled',
            '/appuserSocialAppleAuthenticate',
            '/tempSetAppuserDefaultParamsForAlreadyVerified',
            '/checkAppuserCloudStorageAccessTokenValidity',
            '/registerAppuserCloudStorageAccessToken',
            '/deregisterAppuserCloudStorageAccessToken',
            '/fetchAndRegisterAppuserCloudStorageAccessToken',
            '/fetchAndRefreshAppuserCloudStorageAccessToken',
            '/loadAppuserSessionManagementModal',
            '/removeExistingAppuserSession',
            '/checkAppuserCloudCalendarAccessTokenValidity',
            '/registerAppuserCloudCalendarAccessToken',
            '/deregisterAppuserCloudCalendarAccessToken',
            '/setupAppuserCalendarIdSelectionForCloudCalendar',
            '/fetchAndRegisterAppuserCloudCalendarAccessToken',
            '/fetchAndRefreshAppuserCloudCalendarAccessToken',
            '/checkAppuserCloudStorageAndCalendarAccessTokenValidities',
            '/checkAppuserCloudMailBoxAccessTokenValidity',
            '/registerAppuserCloudMailBoxAccessToken',
            '/deregisterAppuserCloudMailBoxAccessToken',
            '/fetchAndRegisterAppuserCloudMailBoxAccessToken',
            '/fetchAndRefreshAppuserCloudMailBoxAccessToken',
            '/checkAppuserAssociatedCloudDependencyAccessTokenValidity',
            '/getAppuserLinkedCloudDependencies',
            /* For Appuser Ends */

            /* For Messaging Starts */
            '/registerAppuserMessagingToken',
            /* For Messaging Ends */

            /* For Feedback Starts */
            '/saveAppuserFeedbackDetails',
            /* For Feedback Ends */

            /* For Sync Starts */
            '/appuserLoginDataSync',
            '/appuserConstantSync',
            '/appuserPrimaryDataReSync',
            '/appuserSecondaryDataReSync',
            '/appuserQuotaReSync',
            '/appuserShareRightsReSync',
            '/appuserPeriodicDataReSync',
            /* For Sync Ends */

            /* For Folder Starts */
            '/saveAppuserFolderDetails',
            '/saveAppuserVirtualFolderDetails',
            '/appuserFolderList',
            '/loadSelectFolderList',
            '/checkAppuserFolderIsDeletable',
            '/deleteAppuserFolder',
            '/appuserFolderListDatatable',
            '/loadAppuserFolderAddEditModal',
            '/validateAppuserFolderName',
            '/validateAppuserFolderPin',
            '/checkAppuserFolderPinEnabled',
            '/appuserFavoriteList',
            '/invertFolderFavoritedStatus',
            '/appuserFolderGroupListView',
            '/appuserAllNotesFolderGroupId',
            /* For Folder Ends */

            /* For Source Starts */
            '/saveAppuserSourceDetails',
            '/appuserSourceList',
            '/loadSelectSourceList',
            '/checkAppuserSourceIsDeletable',
            '/deleteAppuserSource',
            '/appuserSourceListDatatable',
            '/loadAppuserSourceAddEditModal',
            '/validateAppuserSourceName',
            /* For Source Ends */

            /* For Tag Starts */
            '/saveAppuserTagDetails',
            '/appuserTagList',
            '/loadSelectTagList',
            '/appuserTagListDatatable',
            '/loadAppuserTagAddEditModal',
            '/checkAppuserTagIsDeletable',
            '/deleteAppuserTag',
            '/validateAppuserTagName',
            /* For Tag Ends */

            /* For Content Starts */
            '/saveAppuserContentDetails',
            '/uploadAppuserContentImage',
            '/uploadAppuserContentAttachment',
            '/appuserContentList',
            '/appuserContentDetailsModal',
            '/appuserContentDetails',
            '/restoreDeletedAppuserContent',
            '/restoreMultiDeletedAppuserContent',
            '/deleteAppuserContent',
            '/checkAppuserContentCanBeDeleted',
            '/deleteMultiAppuserContent',
            '/appuserContentMarkToggle',
            '/appuserContentMoveToFolder',
            '/appuserContentCopyToFolder',
            '/appuserContentMerge',
            '/appuserContentAddTags',
            '/appuserContentCopyToOrganization',
            '/sortAppuserContentModal',
            '/filterAppuserContentModal',
            '/appuserContentPrintView',
            '/usrRemoveContent',
            '/appuserDueContentList',
            '/appuserDueContentListNew',
            '/appuserContentDependencyModal',
            '/appuserContentModifyDateTimeModal',
            '/performAppuserContentDateTimeModification',
            '/appuserContentInfoModal',
            '/appuserContentAttachmentInfoModal',
            '/appuserFolderOrGroupContentInfoModal',
            '/modifyPartialAppuserContentDetails',
            '/checkContentCanBeShared',
            '/saveAppuserOneLineChatContent',
            '/appuserDashboardMetrics',
            '/appuserContentListView',
            '/appuserContentDetailsModalNew',
            '/appuserContentDetailsLoadSubView',
            '/performContentConversationPartOperation',
            '/performContentModificationPushOperation',
            '/performContentSharingPushOperation',
            '/checkContentCanBePrinted',
            '/loadWebVideoTutorialModal',
            '/loadAppuserShareRecipientSelectionModal',
            '/loadAppuserPartContentShareRecipientSelectionModal',
            '/loadAppuserPartContentShareConversationSelectionModal',
            '/performAppuserPartContentShareToConversation',
            '/loadAppuserProfileDefaultFolderGroupDetails',
            '/loadAppuserContentCloudAttachmentSelectionModal',
            '/appuserGroupContentCopyToFolder',
            '/appuserContentDependencyGlobalSearchListView',
            '/appuserMultiContentMarkAsCompleted',
            '/appuserMultiContentMarkAsPending',
            '/appuserContentMarkAsSnoozed',
            '/checkAppuserCalendarContentForOverLapping',
            /* For Content Ends */

            /* For Attachment Starts */
            '/uploadContentAttachment',
            '/removeContentAttachment',
            '/uploadGroupContentAttachment',
            '/removeGroupContentAttachment',
            '/downloadContentAttachment',
            '/downloadEncContentAttachment',
            '/removeSelContentAttachment',
            '/getTemporaryContentAttachmentUrl',
            /* For Attachment Ends */

            /* For Cloud Attachment Starts */
            '/loadCloudAttachmentRelevantFolderFileList',
            '/loadCloudAttachmentRelevantFolderFileContinuedList',
            '/loadCloudAttachmentRelevantFolderFileFilteredList',
            '/loadCloudAttachmentRelevantFolderFileContinuedFilteredList',
            '/loadCloudAttachmentRelevantFileMappedDetails',
            '/uploadCloudAttachmentRelevantFile',
            '/checkCloudAttachmentRelevantFileCanBeDeleted',
            '/deleteCloudAttachmentRelevantFile',
            '/checkCloudAttachmentRelevantFolderCanBeDeleted',
            '/deleteCloudAttachmentRelevantFolder',
            '/addNewCloudAttachmentRelevantFolder',
            '/performCloudStorageAttachmentImportAsAppuserContent',
            '/loadCloudStorageTypeAuthenticationDependencies',
            /* For Cloud Attachment Ends */

            /* For Cloud Calendar Starts */
            '/loadCloudCalendarRelevantCalendarList',
            '/performCloudCalendarRelevantPrimaryAutoSync',
            '/loadCloudCalendarRelevantEventList',
            '/loadCloudCalendarRelevantEventContinuedList',
            '/loadCloudCalendarRelevantEventDetails',
            '/addNewCloudCalendarRelevantEvent',
            '/updateCloudCalendarRelevantEvent',
            '/checkCloudCalendarRelevantEventCanBeDeleted',
            '/deleteCloudCalendarRelevantEvent',
            /* For Cloud Calendar Ends */

            /* For Cloud MailBox Starts */
            '/loadCloudMailBoxRelevantMailBoxList',
            '/loadCloudMailBoxRelevantMessageList',
            '/loadCloudMailBoxRelevantMessageContinuedList',
            '/loadCloudMailBoxRelevantMessageDetails',
            '/loadCloudMailBoxRelevantMessageAttachmentDetails',
            '/loadCloudMailBoxMultiRelevantMessageAttachmentDetails',
            '/loadCloudMailBoxMultiRelevantMessageCompleteDetails',
            '/addNewCloudMailBoxRelevantMessage',
            '/addNewCloudMailBoxRelevantMessageDialog',
            '/checkCloudMailBoxRelevantMessageCanBeDeleted',
            '/deleteCloudMailBoxRelevantMessage',
            /* For Cloud MailBox Ends */

            /* For Email Handler Starts */
            // '/uploadUserContent',
            // '/sendReminderMail',
            // '/checkAndResyncAppuserCloudCalendarAutoSyncChanges',
            /* For Email Handler Ends */
            
            /* For Appuser Contact Starts */
            '/syncAppuserContacts',
            '/appuserContactList',
            '/shareAppContent',
            '/blockAppuserContact',
            '/unblockAppuserContact',
            '/inviteAppuserContact',
            '/appuserBroadcastList',
            '/saveAppuserBroadcast',
            '/blockAppuserEmail',
            
            '/loadBroadcastDetails',
            '/renameBroadcast',
            '/removeBroadcastUser',
            '/deleteBroadcast',
            '/addBroadcastMember',
            /* For Appuser Contact Ends */
            
            /* For Group Starts */
            '/shareAppGroupContent',
            '/appuserGroupList',
            '/saveGroupDetails',
            '/loadGroupDetails',
            '/saveGroupContentDetails',
            '/renameGroup',
            '/toggleOpenGroupStatus',
            '/loadAppuserJoinOpenGroupModal',
            '/joinOpenGroupAsMember',
            '/removeGroupUser',
            '/exitGroup',
            '/deleteGroup',
            '/shareGroupContent',
            '/addGroupMember',
            '/deleteGroupContent',
            '/syncGroupContent',
            '/resyncGroupData',
            '/modifyGroupQuotaDetails',
            '/uploadGroupPhoto',
            '/invertGroupFavoritedStatus',
            '/performGroupRestore',
            '/checkGroupBackupCanBeRestored',
            '/groupRestoreProcessCompleted',
            '/groupRestoreProcessFailed',
            /* For Group Ends */
            
            /* For Organization Group Starts */
            '/saveOrgGroupContentDetails',
            '/loadOrgGroupDetails',
            '/syncOrgGroupContent',
            '/deleteOrgGroupContent',
            '/resyncOrgGroupData',
            '/loadSelectGroupList',
            '/loadAppuserGroupInfo',
            '/validateAppuserGroupName',
            /* For Organization Group Ends */
            
            /* For Appuser Constant Starts */
            '/appuserSyncDefaultFolder',
            '/appuserSyncAppPin',
            '/appuserSyncAppFolderPin',
            '/appuserSyncAttachmentRetainDay',
            '/appuserSyncPrintPreference',
            /* For Appuser Constant Ends */
            
            /* For Organization Content Addition Starts */
            '/orgContentAdditionDatatable',
            '/orgContentAddition',
            '/orgContentAdditionDetails',
            '/loadAddOrgContentModal',
            '/addAppuserOrgContent',
            '/addSelOrgAppuserContent',
            '/filterOrgAppuserForContent',
            '/orgAppuserContentDatatable',
            '/checkOrgContentAdditionAvailForDelete',
            '/deleteOrgContentAddition',
            /* For Organization Content Addition Ends */
            
            /* For Organization Notification Starts */
            '/orgNotificationDatatable',
            '/orgNotification',
            '/orgNotificationDetails',
            '/loadAddOrgNotificationModal',
            '/addAppuserOrgNotification',
            '/addSelOrgAppuserNotification',
            '/filterOrgAppuserForNotification',
            '/orgAppuserNotificationDatatable',
            '/checkOrgNotificationAvailForDelete',
            '/deleteOrgNotification',
            /* For Organization Notification Ends */
            
            /* For Organization Backup Management Starts */
            '/orgBackupDatatable',
            '/orgBackup',
            '/orgBackupDetails',
            '/loadAddOrgBackupModal',
            '/checkOrgForBackupCreate',
            '/saveOrgBackupDetails',
            '/checkOrgBackupAvailForDelete',
            '/deleteOrgBackup',
            '/checkOrgBackupAvailForRestore',
            '/loadConfirmOrgRestoreModal',
            '/restoreOrgBackup',
            '/downloadOrgBackup',
            /* For Organization Backup Management Ends */
            
            /* For Organization Admin Log Starts */
            '/orgAdminLogDatatable',
            '/loadOrgAdminLogView',
            /* For Organization Admin Log Ends */
            
            /* For Organization Video Conference Starts */
            '/orgVideoConferenceDatatable',
            '/loadOrgVideoConferenceView',
            '/orgOrgVideoConferenceInfoModal',
            '/loadAddOrgVideoConferenceModal',
            /* For Organization Video Conference Ends */
            
            /* For Organization Administration Starts */
            '/loadOrganizationAdministrationView',
            /* For Organization Administration Ends */
            
            /* For Appuser Organization Starts */
            '/appuserSubscribeOrganization',
            '/appuserOrganizationPrimarySync',
            '/appuserOrganizationGroupList',
            '/appuserOrganizationUserList',
            '/shareAppOrganizationContent',
            '/appuserOrganizationTemplateList',
            '/getOrganizationTemplateDetails',
            '/organizationAppuserDetails',
            '/loadAppuserSelectProfileModal',
            '/loadAppuserProfileSettings',
            '/loadSelectProfileList',
            '/loadAppuserInformationModal',
            '/loadAppuserOrgInformationModal',
            '/appuserOrgReportAbuse',
            '/appuserOrganizationLeave',
            '/appuserOrgContactAdmin',
            '/appuserOrgScreenshotTaken',
            '/appuserOrgSenderEmailMappedList',
            '/appuserOrganizationProfileSubscribe',
            '/loadOrganizationDetailsForCnameMapping',
            '/loadWLAppDetailsForCnameMapping',
            /* For Appuser Organization Ends */
            
            /* For Sys Video Conference Starts */
            '/saveVideoConferenceDetails',
            '/checkVideoConferenceCanBeStarted',
            '/markVideoConferenceUserJoined',
            '/markVideoConferenceUserExited',
            '/checkVideoConferenceCanBeDeleted',
            '/deleteVideoConference',
            '/getUpcomingVideoConferenceList',
            '/getPastVideoConferenceList',
            '/getAttendedVideoConferenceList',
            '/loadVideoConferenceAddEditModal',
            '/loadVideoConferenceRelevantParticipants',
            '/loadVideoConferenceJoinModal',
            '/loadVideoConferenceConductModal',
            '/loadVideoConferenceInformation',
            '/loadVideoConferenceInformationModal',
            '/saveVideoConferenceInformationAsUserContent',
            '/loadVideoConferenceShareModal',
            '/sendVideoConferenceEmailInvitation',
            '/loadVideoConferenceShareWithinHyLytModal',
            '/sendVideoConferenceInvitationWithinHyLyt',
            /* For Sys Video Conference Ends */
        /* For API Ends */

        /* For Admin QPI Starts */

            /* For Organization Starts */
            '/organizationDatatable',
            '/organizationDetails',
            '/loadOrgRegistrationDetails',
            '/saveOrgRegistrationDetails',
            '/loadOrgSubscriptionDetails',
            '/saveOrgSubscriptionDetails',
            '/loadOrgAdministrationDetails',
            '/organizationAdminDatatable',
            '/addOrgAdministrator',
            '/orgAdministratorDetails',
            '/changeOrgAdministratorStatus',
            '/resendOrgAdministratorCredentials',
            '/loadOrgAdministratorCredentialsModal',
            '/deleteOrgAdministrator',
            '/changeOrganizationStatus',
            '/validateAdminEmailForOrg',
            '/validateOrganizationCode',
            '/validateOrganizationDatabaseName',
            '/loadOrgServerDetails',
            '/saveOrgServerDetails',
             '/loadOrgEmployeeUpload',
             '/checkOrgAvailForDelete',
             '/deleteOrganization',
             '/registerOrganizationWithReferral',
            /* For Organization Ends */
            
            /* Enterp Admin Starts */
            '/serAuthenticate',
            '/serValidateCurrPassword',
            '/serUpdatePassword',
            '/loadServerUserMenu',
            /* Enterp Admin Ends */
            
            /* Organization Referral Code Starts */
            '/loadOrgReferralCodeView',
            '/loadOrgReferralCodeAddEditModal',
            '/orgReferralCodeDatatable',
            '/saveOrgReferralCodeDetails',
            '/changeOrgReferralCodeStatus',
            '/deleteOrgReferralCode',
            '/checkOrgReferralCodeAvailForDelete',
            '/validateOrgReferralCode',
            /* Organization Referral Code Ends */
            
            /* Premium Referral Code Starts */
            '/loadPremiumReferralCodeView',
            '/loadPremiumReferralCodeAddEditModal',
            '/premiumReferralCodeDatatable',
            '/savePremiumReferralCodeDetails',
            '/changePremiumReferralCodeStatus',
            '/deletePremiumReferralCode',
            '/checkPremiumReferralCodeAvailForDelete',
            '/validatePremiumReferralCode',
            /* Premium Referral Code Ends */

            /* Premium Coupon Starts */
            '/loadPremiumCouponView',
            '/loadPremiumCouponAddEditModal',
            '/premiumCouponDatatable',
            '/loadPremiumCouponDetailView',
            '/premiumCouponCodeDatatable',
            '/savePremiumCouponDetails',
            '/changePremiumCouponStatus',
            '/deletePremiumCoupon',
            '/checkPremiumCouponAvailForDelete',
            '/validatePremiumCouponCodePrefix',
            '/generatePremiumCoupon',
            '/checkPremiumCouponAvailForGenerate',
            /* Premium Coupon Ends */

            /* Enterprise Coupon Starts */
            '/loadEnterpriseCouponView',
            '/loadEnterpriseCouponAddEditModal',
            '/enterpriseCouponDatatable',
            '/loadEnterpriseCouponDetailView',
            '/enterpriseCouponCodeDatatable',
            '/saveEnterpriseCouponDetails',
            '/changeEnterpriseCouponStatus',
            '/deleteEnterpriseCoupon',
            '/checkEnterpriseCouponAvailForDelete',
            '/validateEnterpriseCouponCodePrefix',
            '/generateEnterpriseCoupon',
            '/checkEnterpriseCouponAvailForGenerate',
            /* Enterprise Coupon Ends */
            
        /* For Admin QPI Ends */
    ];
}
