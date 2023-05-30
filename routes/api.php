<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**Login route */
Route::post('login', 'API\AuthController@login');
Route::post('forgotPassword', 'API\AuthController@forgotPassword');
Route::post('resetPassword', 'API\AuthController@resetPassword');
Route::post('loginRemoteCompletionUser', 'API\AuthController@loginRemoteCompletionUser');
Route::post('createVersionSwitchDependencies', 'API\AuthController@createVersionSwitchDependencies');
Route::get('exportCustomersDownload/{slug}', 'API\Pocomos\Admin\OfficeExportController@exportCustomersDownload');
Route::get('exportLeadsDownload/{slug}', 'API\Pocomos\Admin\OfficeExportController@exportLeadsDownload');
Route::get('exportLeadsDownloadProcess/{slug}', 'API\Pocomos\Admin\OfficeExportController@exportLeadsDownloadProcess');
Route::post('createChargeTest', 'API\AuthController@createChargeTest');

// Webhook for receive sms
Route::post('receive-sms', 'API\Pocomos\Lead\LeadController@receiveSms');

Route::group(['middleware' => ['auth:sanctum', 'api-sessions']], function () {
    // Route::middleware('auth:sanctum')->group(function () {

    Route::get('checkVersionSwitchedUser', 'API\AuthController@checkVersionSwitchedUser');

    Route::namespace('API\Pocomos\Admin')->prefix('admin')->group(function () {
        /** company routes */
        Route::prefix('company')->group(function () {
            Route::post('create', 'OfficeController@create')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
            Route::post('list', 'OfficeController@list')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
            Route::get('{id}', 'OfficeController@get')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
            Route::post('update', 'OfficeController@update')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
            Route::post('reactivateallusers/{officeId}', 'OfficeController@reactivateUsers')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
            Route::post('deactivateAllUsers/{officeId}', 'OfficeController@deactivateAllUsers')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
            Route::post('countryregionlist', 'OfficeController@countryregionlist')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
            Route::post('countrylist', 'OfficeController@countrylist')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
            Route::post('countrywithregion', 'OfficeController@countrywithregion')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
            Route::post('switchBranch', 'OfficeController@switchBranch')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
            Route::post('cloneBranch', 'OfficeController@cloneBranch')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
            Route::post('runAutoPay', 'OfficeController@runAutoPay')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        });

        /** Import Routes  */
        Route::prefix('import')->group(function () {
            Route::get('getOffices', 'ImportController@getOffices')->middleware('role:ROLE_ADMIN');
        });
        Route::get('getUserOffices/{id}', 'OfficeController@getUserOffices')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
        Route::post('companies/{id}/offices', 'OfficeController@companies_offices')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
        Route::post('optimization/edit', 'OfficeController@optimization')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('optimization/get', 'OfficeController@optimizationget')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('zipcode/edit', 'OfficeController@zipcode')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('zipcode/get', 'OfficeController@zipcodeget')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('configuration/edit', 'OfficeController@configuration')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('configuration/get', 'OfficeController@configurationGet')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('companies/security/get', 'OfficeController@securityget')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('companies/security/update', 'OfficeController@security')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('salestax/salestaxupdate', 'OfficeController@salestaxupdate')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
        Route::post('salestax/salestaxget', 'OfficeController@salestaxget')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
        Route::post('companies/sms/smsUpdate', 'OfficeController@smsUpdate')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
        Route::post('companies/sms/smsget', 'OfficeController@smsget')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_READ');
        Route::post('companies/email/editUpdate', 'OfficeController@emailUpdate')->middleware('role:ROLE_ADMIN');
        Route::post('companies/email/editGet', 'OfficeController@emailGet')->middleware('role:ROLE_ADMIN');
        Route::post('companies/vtp/vtpUpdate', 'OfficeController@vtpUpdate')->middleware('role:ROLE_ADMIN');
        Route::post('companies/vtp/vtpget', 'OfficeController@vtpget')->middleware('role:ROLE_ADMIN');
        Route::post('custom-agreements/office/upd', 'OfficeController@updAgreement')->middleware('role:ROLE_ADMIN');
        Route::post('custom-agreements/office/getupd', 'OfficeController@getupdAgreement')->middleware('role:ROLE_ADMIN');
        Route::post('getDetails', 'OfficeController@getDetails')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');
        Route::get('getLoggedinUserDetails', 'OfficeController@getLoggedinUserDetails')->middleware('role:ROLE_ADMIN');
        Route::post('companies/export/authenticate', 'OfficeController@authenticate')->middleware('role:ROLE_ADMIN');
        Route::post('zipcode/checkValid', 'OfficeController@zipcodeCheckValid')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('zip-code/validate', 'OfficeController@validateActionRequest')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_WRITE');
        Route::post('getOfficeSettig', 'OfficeController@getOfficeSettig')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_READ');

        /* This all routes are define for export CSV file which applicable in admin panel */
        Route::post('companies/{id}/exportAccount', 'OfficeExportController@exportAccount')->middleware('role:ROLE_ADMIN');
        Route::post('companies/{id}/exportNote', 'OfficeExportController@exportNote')->middleware('role:ROLE_ADMIN');
        Route::post('companies/{id}/exportContract', 'OfficeExportController@exportContract')->middleware('role:ROLE_ADMIN');
        Route::post('companies/{id}/exportPhone', 'OfficeExportController@exportPhone')->middleware('role:ROLE_ADMIN');
        Route::post('companies/{id}/exportRecruitement', 'OfficeExportController@exportRecruitement')->middleware('role:ROLE_ADMIN');
        // Route::post('companies/{id}/exportTransactions', 'OfficeExportController@exportTransactions');

        /** Line Subscription Purchase routes */
        Route::prefix('companies/line-sub-purchasing')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('get', 'LineSubscriptionPurchasingController@getData');
            Route::post('create', 'LineSubscriptionPurchasingController@create');
            Route::post('list', 'LineSubscriptionPurchasingController@list');
            Route::post('update', 'LineSubscriptionPurchasingController@update');
            Route::post('delete', 'LineSubscriptionPurchasingController@delete');
        });

        /** Webhook routes */
        Route::prefix('companies/webhook')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'WebhookConfigureController@create');
            Route::post('list', 'WebhookConfigureController@list');
            Route::post('delete', 'WebhookConfigureController@delete');
        });

        Route::prefix('companies')->middleware('role:ROLE_ADMIN')->group(function () {
            /** Quickbooks routes */
            Route::prefix('quickbooks')->group(function () {
                Route::post('edit', 'QucikBooksAdminOfficeConfigurationController@createUpdate');
                Route::post('list', 'QucikBooksAdminOfficeConfigurationController@list');
            });
            /** Pricing routes */
            Route::prefix('pricing')->group(function () {
                Route::post('update', 'OfficeBillingProfileController@createUpdate');
                Route::post('list', 'OfficeBillingProfileController@list');
            });
            /** Offices  */
            Route::post('offices', 'OfficeController@all_branches');

            //* Import */
            Route::post('addImport', 'ImportController@importBatch');
            Route::post('list/{id}', 'ImportController@list');
            Route::post('getBatchRecords', 'ImportController@getBatchRecords');
            Route::get('getBatchDetails/{id}', 'ImportController@getBatchDetails');
            Route::get('finishBatch/{id}', 'ImportController@finishBatch');
            Route::post('updateImportCustomer/{id}', 'ImportController@updateImportCustomer');
            Route::get('downloadImportTemplate', 'ImportController@downloadImportTemplate');
            Route::get('getBatchRecordDetail/{id}', 'ImportController@getBatchRecordDetail');
        });

        /** sms/senders routes */
        Route::prefix('sms/senders')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'AdminSenderController@create');
            Route::post('list', 'AdminSenderController@list');
            Route::get('{id}', 'AdminSenderController@get');
            Route::post('update', 'AdminSenderController@update');
            Route::post('changeStatus', 'AdminSenderController@changeStatus');

            /** admin SMS Usage route */
            Route::post('adminSMSUsage', 'AdminSenderController@adminSMSUsage');

            /** admin received-message-log route */
            Route::post('received-message-log', 'AdminSenderController@receivedmessagelog');
        });

        /** email/senders routes */
        Route::prefix('email/senders')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'AdminEmailSenderController@create');
            Route::post('list', 'AdminEmailSenderController@list');
            Route::get('{id}', 'AdminEmailSenderController@get');
            Route::post('update', 'AdminEmailSenderController@update');
        });

        /** system/custom-agreement routes */
        Route::prefix('system/custom-agreement')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'CustomAgreementController@create');
            Route::post('list', 'CustomAgreementController@list');
            Route::get('{id}', 'CustomAgreementController@get');
            Route::post('update', 'CustomAgreementController@update');
            Route::post('changeStatus', 'CustomAgreementController@changeStatus');
        });

        /** system/form-variables routes */
        Route::prefix('system/form-variable')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'FormVariableController@create');
            Route::post('list', 'FormVariableController@list');
            Route::get('{id}', 'FormVariableController@get');
            Route::post('update', 'FormVariableController@update');
            Route::post('changeStatus', 'FormVariableController@changeStatus');
            Route::post('variableList', 'FormVariableController@variableList');
        });

        /** system/timezones routes */
        Route::prefix('system/timezones')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'TimeZoneController@create');
            Route::post('list', 'TimeZoneController@list');
            Route::get('{id}', 'TimeZoneController@get');
            Route::post('update', 'TimeZoneController@update');
            Route::post('changeStatus', 'TimeZoneController@changeStatus');
        });

        /** system/error log */
        Route::prefix('system/errorlog')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('list', 'ErrorLogController@list');
            Route::get('{id}', 'ErrorLogController@get');
        });

        /** billing/line subscrition routes */
        Route::prefix('billing/linesubscription')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'LineSubscriptionController@create');
            Route::post('list', 'LineSubscriptionController@list');
            Route::get('{id}', 'LineSubscriptionController@get');
            Route::post('update', 'LineSubscriptionController@update');
            Route::get('delete/{id}', 'LineSubscriptionController@delete');
        });

        /** Office Billing Reports route */
        Route::post('billing/officeBillingReports', 'OfficeBillingReportsController@officeBillingReports')->middleware('role:ROLE_ADMIN');
        Route::post('billing/regenerateReportDate', 'OfficeBillingReportsController@regenerateReportDate')->middleware('role:ROLE_ADMIN');

        /** billing/standard-pricing routes */
        Route::prefix('billing/standard-pricing')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'StandardPriceController@create');
            Route::post('list', 'StandardPriceController@list');
            Route::get('{id}', 'StandardPriceController@get');
            Route::post('update', 'StandardPriceController@update');
            Route::post('changeStatus', 'StandardPriceController@changeStatus');
        });

        /** WDO/state_form routes */
        Route::prefix('WDO/state_form')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'TermiteAdminConfigurationController@create');
            Route::post('list', 'TermiteAdminConfigurationController@list');
            Route::get('{id}', 'TermiteAdminConfigurationController@get');
            Route::post('update', 'TermiteAdminConfigurationController@update');
            Route::get('delete/{id}', 'TermiteAdminConfigurationController@delete');
        });

        /** WDO/state_form_finding_type routes */
        Route::prefix('WDO/state_form_finding_type')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'TermiteAdminConfigurationController@findingTypeCreate');
            Route::post('list', 'TermiteAdminConfigurationController@findingTypeList');
            Route::get('{id}', 'TermiteAdminConfigurationController@findingTypeGet');
            Route::post('update', 'TermiteAdminConfigurationController@findingTypeUpdate');
            Route::get('delete/{id}', 'TermiteAdminConfigurationController@findingTypedelete');
        });

        /** Release Notes( Blog post) routes */
        Route::prefix('blog/post')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'AdminBlogController@create');
            Route::post('list', 'AdminBlogController@list');
            Route::get('{id}', 'AdminBlogController@get');
            Route::post('update', 'AdminBlogController@update');
            Route::get('delete/{id}', 'AdminBlogController@delete');
            Route::post('messageboardpost', 'AdminBlogController@messageboardpost');
        });

        /** Queued customers for Pestpac Export routes */
        Route::prefix('pestpac_export_customer')->middleware('role:ROLE_ADMIN')->group(function () {
            //PestPacExportController@search do not pass office_ids

            // Route::post('list/{officeid}', 'PestpacExportCustomerController@list');
            Route::get('{id}', 'PestpacExportCustomerController@get');
            Route::post('changePestpacCustomerStatus', 'PestpacExportCustomerController@changePestpacCustomerStatus');
            Route::post('tryExporting', 'PestpacExportCustomerController@tryExporting');
            Route::post('updateServiceOrder', 'PestpacExportCustomerController@updateServiceOrder');
        });

        /** admin-widget routes */
        Route::prefix('admin-widget')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'AdminWidgetController@create');
            Route::post('list', 'AdminWidgetController@list');
            Route::get('{id}', 'AdminWidgetController@get');
            Route::post('update', 'AdminWidgetController@update');
            Route::post('changeStatus', 'AdminWidgetController@changeStatus');
        });

        /** Mass Notifications routes */
        Route::prefix('mass-notification')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'MassNotificationController@create');
            Route::post('list', 'MassNotificationController@list');
        });

        /** Emergency News routes */
        Route::prefix('emergency-news')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'EmergencyNewsController@create');
            Route::post('list', 'EmergencyNewsController@list');
            Route::get('{id}', 'EmergencyNewsController@get');
            Route::post('update', 'EmergencyNewsController@update');
            Route::post('changeStatus', 'EmergencyNewsController@changeStatus');
        });

        /** Terms and Conditions routes */
        Route::prefix('terms-and-conditions')->middleware('role:ROLE_ADMIN')->group(function () {
            Route::post('create', 'TermsAndConditionsController@create');
            Route::post('list', 'TermsAndConditionsController@list');
            Route::get('{id}', 'TermsAndConditionsController@get');
            Route::post('update', 'TermsAndConditionsController@update');
            Route::post('changeStatus', 'TermsAndConditionsController@changeStatus');
        });

        /** Profile routes */
        Route::post('edit-profile/{id}', 'ProfileController@editProfile')->middleware('role:ROLE_ADMIN');
        Route::post('update-profile/{id}', 'ProfileController@updateProfile')->middleware('role:ROLE_ADMIN');
        Route::post('change-password/{id}', 'ProfileController@changePassword')->middleware('role:ROLE_ADMIN');
        Route::post('update-signature/{profile_id}', 'ProfileController@updateSignature')->middleware('role:ROLE_ADMIN');
        Route::post('whats-new', 'ProfileController@whatsNew')->middleware('role:ROLE_ADMIN');
    });

    Route::namespace('API\Pocomos\MessageBoard')->prefix('message-board')->group(function () {
        /** todo routes */
        Route::prefix('todo')->group(function () {
            Route::post('create', 'ToDoController@create')->middleware('role:ROLE_ADMIN');
            Route::post('taskSomeone', 'ToDoController@taskSomeone')->middleware('role:ROLE_ADMIN');
            Route::post('taskTeam', 'ToDoController@taskTeam')->middleware('role:ROLE_ADMIN');
            Route::post('taskBranch', 'ToDoController@taskBranch')->middleware('role:ROLE_ADMIN');
            Route::post('taskEveryone', 'ToDoController@taskEveryone')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'ToDoController@list')->middleware('role:ROLE_ADMIN');
            Route::post('changeTaskStatus', 'ToDoController@changeTaskStatus')->middleware('role:ROLE_ADMIN');
            Route::post('history', 'ToDoController@completedTaskHistory')->middleware('role:ROLE_ADMIN');
            Route::post('teamlist', 'ToDoController@teamlist')->middleware('role:ROLE_ADMIN');
            Route::post('branchlist', 'ToDoController@branchlist')->middleware('role:ROLE_ADMIN');
            Route::post('someonelist', 'ToDoController@someonelist')->middleware('role:ROLE_ADMIN');
        });
    });

    Route::namespace('API\Pocomos\MessageBoard')->prefix('message-board')->group(function () {
        /** todo routes */
        Route::prefix('todo')->group(function () {
            Route::post('create', 'ToDoController@create')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('taskSomeone', 'ToDoController@taskSomeone')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_SOMEONE');
            Route::post('taskTeam', 'ToDoController@taskTeam')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_TEAM');
            Route::post('taskBranch', 'ToDoController@taskBranch')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_BRANCH');
            Route::post('taskEveryone', 'ToDoController@taskEveryone')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_EVERYONE');
            Route::post('list', 'ToDoController@list')->middleware('role:ROLE_ADMIN|ROLE_TACKBOARD_READ|ROLE_USER');
            Route::post('changeTaskStatus', 'ToDoController@changeTaskStatus')->middleware('role:ROLE_ADMIN|ROLE_USER');
        });

        /** todo routes */
        Route::prefix('alert')->group(function () {
            Route::post('create', 'AlertController@create')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('message-branch/create', 'AlertController@messageBranch')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_BRANCH');
            Route::post('alertSomeone', 'AlertController@alertSomeone')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_SOMEONE');
            Route::post('alertTeam', 'AlertController@alertTeam')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_TEAM');
            Route::post('alertBranch', 'AlertController@alertBranch')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_BRANCH');
            Route::post('alertEveryone', 'AlertController@alertEveryone')->middleware('role:ROLE_ADMIN|ROLE_MESSAGE_EVERYONE');
            Route::post('list', 'AlertController@list')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('changealertStatusComplete', 'AlertController@changealertStatusComplete')->middleware('role:ROLE_ADMIN|ROLE_USER');
        });

        Route::prefix('notes')->group(function () {
            Route::post('create', 'NoteController@create')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('list', 'NoteController@list')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::get('delete/{id}', 'NoteController@delete')->middleware('role:ROLE_ADMIN|ROLE_USER');
        });

        Route::post('releaseNew', 'NoteController@releaseNew')->middleware('role:ROLE_ADMIN');
    });

    Route::namespace('API\Pocomos\Lead')->group(function () {
        /** lead routes */
        Route::prefix('leads')->group(function () {
            Route::post('list', 'LeadController@list')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('create', 'LeadController@create')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('update', 'LeadController@update')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::get('delete/{id}', 'LeadController@leaddelete')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('UpdateStatus', 'LeadController@UpdateStatus')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('SendEmail', 'LeadController@SendEmail')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('SendSMS', 'LeadController@SendSMS')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('SmsHistory', 'LeadController@SmsHistory')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('createNote', 'LeadController@createNote')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('getNote', 'LeadController@getNote')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('editNote', 'LeadController@editNote')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('deleteNote', 'LeadController@deleteNote')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('addPhoneNumber', 'LeadController@addPhoneNumber')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('editPhoneNumber', 'LeadController@editPhoneNumber')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('deletePhoneNumber', 'LeadController@deletePhoneNumber')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('getPhoneNumber', 'LeadController@getPhoneNumber')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('sendLeadFormLetterAction', 'LeadController@sendLeadFormLetterAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('sendLeadFormSmsAction', 'LeadController@sendLeadFormSmsAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('last-persons', 'LeadController@lastPersons')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('advance-search', 'LeadController@leadDataAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('leadKnockingReport', 'LeadController@leadKnockingReport')->middleware('role:ROLE_ADMIN|ROLE_LEAD_READ');
            Route::post('leadReport', 'LeadController@leadReport')->middleware('role:ROLE_ADMIN|ROLE_LEAD_READ');
            Route::get('lastPersonToModify/{id}', 'LeadController@lastPersonToModify')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('EditBillingInfo', 'LeadController@EditBillingInfo')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('EditServiceInfo', 'LeadController@EditServiceInfo')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('leadMapDetails', 'LeadController@leadMapDetails')->middleware('role:ROLE_ADMIN|ROLE_LEAD_READ');
            Route::post('leadMapUsersDetails', 'LeadController@leadMapUsersDetails')->middleware('role:ROLE_ADMIN|ROLE_LEAD_READ');
            Route::post('handleCustomerStatusAction', 'LeadController@handleCustomerStatusAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('deactivateAllLeads', 'LeadController@deactivateAllLeads')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('export', 'LeadController@export')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('getLeadDetails', 'LeadController@getLeadDetails')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('addreminder', 'LeadController@addreminder')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('get-salespeople/{office_id}', 'LeadController@getSalesPeopleByOffice')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('exportStartLeads', 'LeadController@exportLeads')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('sendEmailExportedDetails', 'LeadController@sendEmailExportedDetails')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
        });
        /** estimate routes */
        Route::prefix('estimate')->group(function () {
            Route::post('create', 'EstimateController@create')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('list', 'EstimateController@list')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('updateStatus', 'EstimateController@updateStatus')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('deleteEstimateAction', 'EstimateController@deleteEstimateAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('update', 'EstimateController@updateAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('sendEstimateAction', 'EstimateController@sendEstimateAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::get('estimateDownloadleadAction/{lead_id}/{estimate_id}/{print}', 'EstimateController@estimateDownloadleadAction')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
        });

        /** salesArea routes */
        Route::prefix('salesArea')->middleware('role:ROLE_ADMIN|ROLE_SALES_MANAGER|ROLE_SECRETARY|ROLE_ROUTE_MANAGER')->group(function () {
            Route::post('createSalesArea', 'LeadController@createSalesArea');
            Route::post('updateSalesArea/{id}', 'LeadController@updateSalesArea');
            Route::post('areaEnabledAction', 'LeadController@areaEnabledAction');
            Route::post('areaBlockAction', 'LeadController@areaBlockAction');
            Route::post('mapAreasList', 'SalesAreaController@mapAreasList');
            Route::post('list', 'SalesAreaController@list');
            Route::post('delete/{id}', 'SalesAreaController@delete');
        });
    });

    Route::namespace('API\Pocomos\Routing')->prefix('routes')->group(function () {
        //Calender routes

        Route::post('get', 'AssignRouteController@getAssignRouteFilters')->middleware('role:ROLE_ADMIN');
        Route::post('search', 'AssignRouteController@search')->middleware('role:ROLE_ADMIN');
        Route::post('send-sms/{custId}', 'AssignRouteController@sendFormLetterAction')->middleware('role:ROLE_ADMIN');

        /* for job pool > action :
        reschedule = ReminderController@rescheduleJob
        Hard-schedule = ServiceController@updateHardScheduleAction
        job note =  ReminderController@jobNote, updateNote
        job commission = ProcessJobsController@updateCommission
        cancel job = ReminderController@cancelJobs
        */
        Route::post('edit-cust-id/{custId}', 'AssignRouteController@editCustomerId')->middleware('role:ROLE_ADMIN');
        // to check duplicate and update id = CustomerController@checkAccountIdDuplicate, updateCustomerAccountId

        Route::post('edit-route-slot/{id}', 'AssignRouteController@editRouteSlot')->middleware('role:ROLE_ADMIN');

        //schedule customer
        Route::post('search-customer', 'AssignRouteController@searchCustomer')->middleware('role:ROLE_ADMIN');
        Route::post('customer-contracts/{customer_id}', 'AssignRouteController@getCustomerContracts')->middleware('role:ROLE_ADMIN');
        Route::post('contract-jobs/{contractId}', 'AssignRouteController@contractWiseJobs')->middleware('role:ROLE_ADMIN');
        Route::post('create-contract-job', 'AssignRouteController@createContractJob')->middleware('role:ROLE_ADMIN');
        Route::post('job/{jobid}/info', 'AssignRouteController@scheduledCustomerInfo')->middleware('role:ROLE_ADMIN');


        // for route action
        Route::post('print-summary/{route_id}', 'AssignRouteController@printSummary')->middleware('role:ROLE_ADMIN');
        Route::get('print-invoice/{route_id}', 'AssignRouteController@printInvoice')->middleware('role:ROLE_ADMIN');
        Route::post('optimize-route/{route_id}', 'AssignRouteController@optimizeRoute')->middleware('role:ROLE_ADMIN');
        Route::post('reschedule-route/{route_id}', 'AssignRouteController@rescheduleRoute')->middleware('role:ROLE_ADMIN');
        Route::post('modify-commission/{route_id}', 'AssignRouteController@modifyCommission')->middleware('role:ROLE_ADMIN');
        Route::post('confirm-jobs/{route_id}', 'AssignRouteController@confirmJobs')->middleware('role:ROLE_ADMIN');
        Route::post('view-map/{route_id}', 'AssignRouteController@viewMap')->middleware('role:ROLE_ADMIN');

        // for view map
        Route::post('geocode/{custId}', 'AssignRouteController@geocode')->middleware('role:ROLE_ADMIN');

        // for slot action
        Route::post('update-job-color/{job_id}', 'AssignRouteController@updateJobColor')->middleware('role:ROLE_ADMIN');

        Route::post('save-changes', 'AssignRouteController@saveChanges')->middleware('role:ROLE_ADMIN');


        /** Route map routes */
        Route::prefix('map')->group(function () {
            Route::post('get', 'RouteMapController@getFilters')->middleware('role:ROLE_ADMIN');
            Route::post('zip-codes', 'RouteMapController@getAllZipCodes')->middleware('role:ROLE_ADMIN');
            Route::post('map-codes', 'RouteMapController@getAllMapCodes')->middleware('role:ROLE_ADMIN');
            Route::post('jobs', 'RouteMapController@jobs')->middleware('role:ROLE_ADMIN');
            // to edit address/geocode also
            Route::post('unresolved-markers', 'RouteMapController@unresolvedMarkers')->middleware('role:ROLE_ADMIN');
            Route::post('update-address/{custId}', 'RouteMapController@updateAddress')->middleware('role:ROLE_ADMIN');
            Route::post('update-geocode/{custId}', 'RouteMapController@updateGeocode')->middleware('role:ROLE_ADMIN');

            //for action
            Route::post('reschedule-jobs', 'RouteMapController@scheduleAction')->middleware('role:ROLE_ADMIN');

            Route::post('preferred-tech-list', 'RouteMapController@preferredTechnicianList')->middleware('role:ROLE_ADMIN');
            Route::post('update-technician', 'RouteMapController@updateTechnician')->middleware('role:ROLE_ADMIN');

            Route::post('get-route-technician', 'RouteMapController@getRouteAndTechnicianByDate')->middleware('role:ROLE_ADMIN');
            Route::post('create-route', 'RouteMapController@createRouteAction')->middleware('role:ROLE_ADMIN');
        });

        /** Monthly Calendar routes */
        Route::post('calendar/{year}/{month}', 'MonthlyCalendarController@get')->middleware('role:ROLE_ADMIN');

        /** Reminder routes */
        Route::prefix('reminder')->group(function () {
            Route::post('get', 'ReminderController@getFilters')->middleware('role:ROLE_ADMIN');
            Route::post('search', 'ReminderController@search')->middleware('role:ROLE_ADMIN');
            Route::post('available-routes', 'ReminderController@availableRoutes')->middleware('role:ROLE_ADMIN');
            Route::post('available-slots', 'ReminderController@availableTimeSlotsAction')->middleware('role:ROLE_ADMIN');
            Route::post('reschedule/{job_id}', 'ReminderController@rescheduleJob')->middleware('role:ROLE_ADMIN');
            // for download invoice - DownloadController@invoiceAction
            Route::post('job-note/{customer_id}', 'ReminderController@jobNote')->middleware('role:ROLE_ADMIN');
            Route::post('update-note/{job_id}', 'ReminderController@updateNote')->middleware('role:ROLE_ADMIN');
            Route::post('bulk-charge', 'ReminderController@bulkCharge')->middleware('role:ROLE_ADMIN');
            // to confirm single job also
            Route::post('confirm-jobs', 'ReminderController@confirmJobs')->middleware('role:ROLE_ADMIN');
            //rescheduleJobWithOptions
            Route::post('reschedule-jobs', 'ReminderController@rescheduleJobs')->middleware('role:ROLE_ADMIN');
            Route::post('cancel-jobs', 'ReminderController@cancelJobs')->middleware('role:ROLE_ADMIN');
            Route::post('add-note', 'ReminderController@addNoteForCustomers')->middleware('role:ROLE_ADMIN');
        });

        /** Process jobs routes */
        Route::prefix('process')->group(function () {
            Route::post('get', 'ProcessJobsController@get')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'ProcessJobsController@list')->middleware('role:ROLE_ADMIN');
            //reschedule - ReminderController@rescheduleJobs
            Route::post('update-commission/{job_id}', 'ProcessJobsController@updateCommission')->middleware('role:ROLE_ADMIN');
            Route::post('update-timeslot-label/{job_id}', 'ProcessJobsController@updateTimeSlotLabel')->middleware('role:ROLE_ADMIN');
            Route::post('technicians', 'ProcessJobsController@getTechnicians')->middleware('role:ROLE_ADMIN');
            Route::post('complete-jobs', 'ProcessJobsController@completeJobs')->middleware('role:ROLE_ADMIN');
            Route::post('export-pestpac', 'ProcessJobsController@exportToPestpac')->middleware('role:ROLE_ADMIN');
            Route::post('send-mails', 'ProcessJobsController@sendMails')->middleware('role:ROLE_ADMIN');
            Route::post('send-sms', 'ProcessJobsController@sendSmss')->middleware('role:ROLE_ADMIN');
            // sendFormLetterAction
        });

        /** Reschedule routes */
        Route::prefix('reschedule')->group(function () {
            Route::post('get', 'RescheduleController@getFilters')->middleware('role:ROLE_ADMIN');
            //reschedule selected - ReminderController@rescheduleJobs
            Route::post('search', 'RescheduleController@search')->middleware('role:ROLE_ADMIN');
            Route::post('check-credit-status/{custId}', 'RescheduleController@checkCreditStatus')->middleware('role:ROLE_ADMIN');
        });
    });

    Route::namespace('API\Pocomos\Inventory')->prefix('inventory')->group(function () {
        /** product routes */
        Route::prefix('product')->group(function () {
            Route::post('create', 'ProductController@create')->middleware('role:ROLE_ADMIN|ROLE_FULL_PEST_PRODUCT');
            Route::post('list', 'ProductController@list')->middleware('role:ROLE_ADMIN|ROLE_VIEW_PEST_PRODUCT|ROLE_FULL_PEST_PRODUCT|ROLE_ARRANGE_ORDER_PEST_PRODUCT');
            Route::get('{id}', 'ProductController@get')->middleware('role:ROLE_ADMIN|ROLE_VIEW_PEST_PRODUCT');
            Route::post('update', 'ProductController@update')->middleware('role:ROLE_ADMIN|ROLE_FULL_PEST_PRODUCT');
            Route::get('delete/{id}', 'ProductController@delete')->middleware('role:ROLE_ADMIN|ROLE_FULL_PEST_PRODUCT');
            Route::post('changeStatus', 'ProductController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::get('downlaodSDS/{id}', 'ProductController@downlaodSDS')->middleware('role:ROLE_ADMIN|ROLE_VIEW_PEST_PRODUCT|ROLE_FULL_PEST_PRODUCT|ROLE_ARRANGE_ORDER_PEST_PRODUCT');
            Route::post('reorder/{id}', 'ProductController@reorder')->middleware('role:ROLE_ADMIN|ROLE_VIEW_PEST_PRODUCT|ROLE_FULL_PEST_PRODUCT|ROLE_ARRANGE_ORDER_PEST_PRODUCT');
        });

        /** inventory routes */
        Route::prefix('vehicle')->group(function () {
            Route::post('create', 'VehicleController@create')->middleware('role:ROLE_ADMIN|ROLE_VEHICLE_WRITE');
            Route::post('list', 'VehicleController@list')->middleware('role:ROLE_ADMIN|ROLE_VEHICLE_READ');
            Route::get('{id}', 'VehicleController@get')->middleware('role:ROLE_ADMIN|ROLE_VEHICLE_READ');
            Route::post('update', 'VehicleController@update')->middleware('role:ROLE_ADMIN|ROLE_VEHICLE_WRITE');
            Route::get('delete/{id}', 'VehicleController@delete')->middleware('role:ROLE_ADMIN|ROLE_VEHICLE_WRITE');
        });

        /** distributor routes */
        Route::prefix('distributor')->group(function () {
            Route::post('create', 'DistributorController@create')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'DistributorController@list')->middleware('role:ROLE_ADMIN');
            Route::get('{id}', 'DistributorController@get')->middleware('role:ROLE_ADMIN');
            Route::post('update', 'DistributorController@update')->middleware('role:ROLE_ADMIN');
            Route::get('delete/{id}', 'DistributorController@delete')->middleware('role:ROLE_ADMIN');
        });
    });

    /**
     * Mission Config Routes
     */
    Route::namespace('API\Pocomos\MissionSetting')->prefix('mission-config')->group(function () {
        Route::post('update', 'MissionConfigurationController@update')->middleware('role:ROLE_ADMIN');
        Route::get('get/{office_id}', 'MissionConfigurationController@get')->middleware('role:ROLE_ADMIN');
    });

    /**
     * Mission Export Routes
     */
    Route::namespace('API\Pocomos\MissionSetting')->prefix('mission-export')->group(function () {
        Route::post('get', 'MissionExportController@getFilters')->middleware('role:ROLE_ADMIN');
        Route::post('search', 'MissionExportController@search')->middleware('role:ROLE_ADMIN');
        Route::post('show/{id}', 'MissionExportController@show')->middleware('role:ROLE_ADMIN');
        Route::post('pause-reschedule/{id}', 'MissionExportController@changeMissionExportContractStatus')->middleware('role:ROLE_ADMIN');
        Route::post('try-exporting/{id}', 'MissionExportController@tryExporting')->middleware('role:ROLE_ADMIN');
    });

    /**
     * Pest Routes Config Routes
     */
    Route::namespace('API\Pocomos\PestRoutes')->prefix('pest-route')->group(function () {
        /** settings routes */
        Route::prefix('config')->group(function () {
            Route::post('update', 'ConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('{id}', 'ConfigurationController@get')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
        });

        /** pest routes exports */
        Route::prefix('export-contracts')->group(function () {
            Route::post('get', 'ExportContractsController@get')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('list', 'ExportContractsController@list')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('{id}', 'ExportContractsController@get')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
        });

        /** pest routes import */
        Route::prefix('import-customers')->group(function () {
            Route::post('list', 'ImportCustomerController@list')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            // start process
            Route::post('try-import', 'ImportCustomerController@tryImport')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
        });
    });

    Route::namespace('API\Pocomos\Settings')->prefix('settings')->group(function () {
        /** pest routes */
        Route::prefix('pest')->group(function () {
            Route::post('create', 'PestController@create')->middleware('role:ROLE_ADMIN|ROLE_PEST_WRITE');
            Route::post('list', 'PestController@list')->middleware('role:ROLE_ADMIN|ROLE_PEST_READ');
            Route::get('{id}', 'PestController@get')->middleware('role:ROLE_ADMIN|ROLE_PEST_READ');
            Route::post('update', 'PestController@update')->middleware('role:ROLE_ADMIN|ROLE_PEST_WRITE');
            Route::post('delete/{id}', 'PestController@delete')->middleware('role:ROLE_ADMIN|ROLE_PEST_WRITE');
            Route::post('reorder/{id}', 'PestController@reorder')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
        });

        /** area routes */
        Route::prefix('area')->group(function () {
            Route::post('create', 'AreaController@create')->middleware('role:ROLE_ADMIN|ROLE_AREA_WRITE');
            Route::post('list', 'AreaController@list')->middleware('role:ROLE_ADMIN|ROLE_AREA_READ');
            Route::get('{id}', 'AreaController@get')->middleware('role:ROLE_ADMIN|ROLE_AREA_READ');
            Route::post('update', 'AreaController@update')->middleware('role:ROLE_ADMIN|ROLE_AREA_WRITE');
            Route::post('delete/{id}', 'AreaController@delete')->middleware('role:ROLE_ADMIN|ROLE_AREA_WRITE');
            Route::post('reorder/{id}', 'AreaController@reorder')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
        });

        /** technician routes */
        Route::prefix('technician')->group(function () {
            Route::post('create', 'TechnicianChecklistController@create')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('list', 'TechnicianChecklistController@list')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::get('{id}', 'TechnicianChecklistController@get')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('update', 'TechnicianChecklistController@update')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('delete/{id}', 'TechnicianChecklistController@delete')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('updateChecklist', 'TechnicianChecklistController@updateChecklist')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            // Route::post('reorder/{id}', 'TechnicianChecklistController@reorder')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('reorder', 'TechnicianChecklistController@reorder')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('getTechnicianChecklistConfiguration', 'TechnicianChecklistController@getTechnicianChecklistConfiguration')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        /** tag routes */
        Route::prefix('tag')->group(function () {
            Route::post('create', 'TagController@create')->middleware('role:ROLE_ADMIN|ROLE_TAG_WRITE');
            Route::post('list', 'TagController@list')->middleware('role:ROLE_ADMIN|ROLE_TAG_READ');
            Route::get('{id}', 'TagController@get')->middleware('role:ROLE_ADMIN|ROLE_TAG_READ');
            Route::post('update', 'TagController@update')->middleware('role:ROLE_ADMIN|ROLE_TAG_WRITE');
            Route::get('delete/{id}', 'TagController@delete')->middleware('role:ROLE_ADMIN|ROLE_TAG_WRITE');
        });

        /** county routes */
        Route::prefix('county')->group(function () {
            Route::post('create', 'CountyController@create')->middleware('role:ROLE_ADMIN|ROLE_COUNTY_WRITE');
            Route::post('list', 'CountyController@list')->middleware('role:ROLE_ADMIN|ROLE_COUNTY_READ');
            Route::get('{id}', 'CountyController@get')->middleware('role:ROLE_ADMIN|ROLE_COUNTY_READ');
            Route::post('update', 'CountyController@update')->middleware('role:ROLE_ADMIN|ROLE_COUNTY_WRITE');
            Route::get('delete/{id}', 'CountyController@delete')->middleware('role:ROLE_ADMIN|ROLE_COUNTY_WRITE');
        });

        /** CustomFields routes */
        Route::prefix('CustomFields')->group(function () {
            Route::post('create', 'CustomFieldConfigController@create')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMFIELD_WRITE');
            Route::post('list', 'CustomFieldConfigController@list')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMFIELD_READ');
            Route::get('{id}', 'CustomFieldConfigController@get')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMFIELD_READ');
            Route::post('update', 'CustomFieldConfigController@update')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMFIELD_WRITE');
            Route::get('delete/{id}', 'CustomFieldConfigController@delete')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMFIELD_WRITE');
        });

        /** sales Status routes */
        Route::prefix('salesStatus')->group(function () {
            Route::post('create', 'SalesStatusController@create')->middleware('role:ROLE_ADMIN|ROLE_SALES_STATUS_WRITE');
            Route::post('list', 'SalesStatusController@list')->middleware('role:ROLE_ADMIN|ROLE_SALES_STATUS_READ');
            Route::get('{id}', 'SalesStatusController@get')->middleware('role:ROLE_ADMIN|ROLE_SALES_STATUS_READ');
            Route::post('update', 'SalesStatusController@update')->middleware('role:ROLE_ADMIN|ROLE_SALES_STATUS_WRITE');
            Route::post('delete/{id}', 'SalesStatusController@delete')->middleware('role:ROLE_ADMIN|ROLE_SALES_STATUS_WRITE');
            Route::post('reorder/{id}', 'SalesStatusController@reorder')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
        });

        /** status Reason routes */
        Route::prefix('statusReason')->group(function () {
            Route::post('create', 'StatusReasonController@create')->middleware('role:ROLE_ADMIN|ROLE_STATUS_REASON_WRITE');
            Route::post('list', 'StatusReasonController@list')->middleware('role:ROLE_ADMIN|ROLE_STATUS_REASON_READ');
            Route::get('{id}', 'StatusReasonController@get')->middleware('role:ROLE_ADMIN|ROLE_STATUS_REASON_READ');
            Route::post('update', 'StatusReasonController@update')->middleware('role:ROLE_ADMIN|ROLE_STATUS_REASON_WRITE');
            Route::get('delete/{id}', 'StatusReasonController@delete')->middleware('role:ROLE_ADMIN|ROLE_STATUS_REASON_WRITE');
        });

        /** threshold routes */
        Route::prefix('threshold')->group(function () {
            Route::post('create', 'ThresholdController@create')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_WRITE');
            Route::post('list', 'ThresholdController@list')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_READ');
            Route::get('{id}', 'ThresholdController@get')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_READ');
            Route::post('update', 'ThresholdController@update')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_WRITE');
            Route::post('delete', 'ThresholdController@delete')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_WRITE');
        });

        /** lead Not Interested Reason routes */
        Route::prefix('leadNotInterestedReason')->group(function () {
            Route::post('create', 'LeadNotInterestedReasonController@create')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('list', 'LeadNotInterestedReasonController@list')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::get('{id}', 'LeadNotInterestedReasonController@get')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('update', 'LeadNotInterestedReasonController@update')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('changeStatus', 'LeadNotInterestedReasonController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
        });

        /** service Type routes */
        Route::prefix('serviceType')->group(function () {
            Route::post('create', 'ServiceTypeController@create')->middleware('role:ROLE_ADMIN|ROLE_FULL_SERVICE_TYPE');
            Route::post('list', 'ServiceTypeController@list')->middleware('role:ROLE_ADMIN|ROLE_FULL_SERVICE_TYPE|ROLE_VIEW_SERVICE_TYPE|ROLE_ARRANGE_ORDER_SERVICE_TYPE');
            Route::get('{id}', 'ServiceTypeController@get')->middleware('role:ROLE_ADMIN|ROLE_FULL_SERVICE_TYPE|ROLE_VIEW_SERVICE_TYPE|ROLE_ARRANGE_ORDER_SERVICE_TYPE');
            Route::post('update', 'ServiceTypeController@update')->middleware('role:ROLE_ADMIN|ROLE_FULL_SERVICE_TYPE');
            Route::post('changeStatus', 'ServiceTypeController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_FULL_SERVICE_TYPE');
            Route::post('reorder/{id}', 'ServiceTypeController@reorder')->middleware('role:ROLE_ADMIN|ROLE_FULL_SERVICE_TYPE|ROLE_ARRANGE_ORDER_SERVICE_TYPE');
        });

        /** service routes (application types) */
        Route::prefix('service')->group(function () {
            Route::post('create', 'AppTypeController@create')->middleware('role:ROLE_ADMIN|ROLE_SERVICE_WRITE');
            Route::post('list', 'AppTypeController@list')->middleware('role:ROLE_ADMIN|ROLE_SERVICE_READ');
            Route::get('{id}', 'AppTypeController@get')->middleware('role:ROLE_ADMIN|ROLE_SERVICE_READ');
            Route::post('update', 'AppTypeController@update')->middleware('role:ROLE_ADMIN|ROLE_SERVICE_WRITE');
            Route::post('delete/{id}', 'AppTypeController@delete')->middleware('role:ROLE_ADMIN|ROLE_SERVICE_WRITE');
            Route::post('reorder/{id}', 'AppTypeController@reorder')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');

            Route::post('update-invoice-setting/{id}', 'AppTypeController@updateInvoiceSetting')->middleware('role:ROLE_ADMIN|ROLE_SERVICE_WRITE');
        });

        /** marketing Type routes */
        Route::prefix('marketingType')->group(function () {
            Route::post('create', 'FoundByTypeController@create')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('list', 'FoundByTypeController@list')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::get('{id}', 'FoundByTypeController@get')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('update', 'FoundByTypeController@update')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::get('delete/{id}', 'FoundByTypeController@delete')->middleware('role:ROLE_ADMIN|ROLE_USER');
            Route::post('getOtherMarketingTypes', 'FoundByTypeController@getOtherMarketingTypes')->middleware('role:ROLE_ADMIN|ROLE_USER');
        });

        /** discountype */
        Route::prefix('discountype')->group(function () {
            Route::post('create', 'DiscountController@create')->middleware('role:ROLE_ADMIN|ROLE_DISCOUNT_TYPE_WRITE');
            Route::post('list', 'DiscountController@list')->middleware('role:ROLE_ADMIN|ROLE_DISCOUNT_TYPE_READ');
            Route::get('{id}', 'DiscountController@get')->middleware('role:ROLE_ADMIN|ROLE_DISCOUNT_TYPE_WRITE');
            Route::post('update', 'DiscountController@update')->middleware('role:ROLE_ADMIN|ROLE_DISCOUNT_TYPE_WRITE');
            Route::post('delete/{id}', 'DiscountController@delete')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
            Route::post('reorder/{id}', 'DiscountController@reorder')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
            Route::post('changeStatus', 'DiscountController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
            Route::post('updateDiscountTypeAction', 'DiscountController@updateDiscountTypeAction')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
            Route::post('getDiscountTypeAction', 'DiscountController@getDiscountTypeAction')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
        });


        /** Communications route*/
        /** Auto Communications */
        Route::prefix('acsjobevent')->group(function () {
            Route::post('list', 'ACSJobEventController@list')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('create', 'ACSJobEventController@create')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('update', 'ACSJobEventController@update')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('changeStatus', 'ACSJobEventController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('delete', 'ACSJobEventController@delete')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('acsjobeventmail', 'ACSJobEventController@acsjobeventmail');
        });

        /** acs invoice event (invoice alerts) */
        Route::prefix('acsinvoiceevent')->group(function () {
            Route::post('list', 'ACSInvoiceEventController@list')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('create', 'ACSInvoiceEventController@create')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('update', 'ACSInvoiceEventController@update')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('changeStatus', 'ACSInvoiceEventController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
            Route::post('delete', 'ACSInvoiceEventController@delete')->middleware('role:ROLE_ADMIN|ROLE_BRANCH_MANAGER');
        });

        /** acs-notification routes */
        Route::prefix('acsNotification')->group(function () {
            Route::post('list/{id}', 'ACSNotificationController@list')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::get('delete/{officeid}/{notificationid}', 'ACSNotificationController@delete')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        /** form Letter routes */
        Route::prefix('formLetter')->group(function () {
            Route::post('create', 'FormLetterController@create')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_WRITE');
            Route::post('list', 'FormLetterController@list')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_READ');
            Route::get('{id}', 'FormLetterController@get')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_READ');
            Route::post('update', 'FormLetterController@update')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_WRITE');
            Route::get('delete/{id}', 'FormLetterController@delete')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_WRITE');
        });

        /** SMS form Letter routes */
        Route::prefix('SMSformLetter')->group(function () {
            Route::post('create', 'SmsFormLetterController@create')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
            Route::post('list', 'SmsFormLetterController@list')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_READ');
            Route::get('{id}', 'SmsFormLetterController@get')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_READ');
            Route::post('update', 'SmsFormLetterController@update')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
            Route::get('delete/{id}', 'SmsFormLetterController@delete')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
        });

        /** voice form Letter routes */
        Route::prefix('voiceformLetter')->group(function () {
            Route::post('create', 'VoiceFormLetterController@create')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
            Route::post('list', 'VoiceFormLetterController@list')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_READ');
            Route::get('{id}', 'VoiceFormLetterController@get')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_READ');
            Route::post('update', 'VoiceFormLetterController@update')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
            Route::get('delete/{id}', 'VoiceFormLetterController@delete')->middleware('role:ROLE_ADMIN|ROLE_SETTINGS_SMS_WRITE');
        });

        /** email type setting routes */
        Route::prefix('email_type_setting')->group(function () {
            Route::post('list', 'EmailTypeSettingController@list')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::get('{id}', 'EmailTypeSettingController@get')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('changeStatus', 'EmailTypeSettingController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('pestOfficeconfigurationEditEmail', 'EmailTypeSettingController@pestOfficeconfigurationEditEmail')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('ListpestOfficeconfigurationEmail/{id}', 'EmailTypeSettingController@ListpestOfficeconfigurationEmail')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('getCreditHoldSetting', 'EmailTypeSettingController@getCreditHoldSetting')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('creditHoldSetting', 'EmailTypeSettingController@creditHoldSetting')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
        });

        /** Configuration route  */
        /** pest ofice Configuration route Edit Update setting routes */
        Route::post('pestoficeConfigurationrouteEdit', 'RouteMapColorController@pestoficeConfigurationrouteEdit')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
        Route::post('pestoficeConfigurationrouteget', 'RouteMapColorController@pestoficeConfigurationrouteget')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');

        /** office-configuration optimization Edit Update setting routes */
        Route::post('officeConfigurationOptimization', 'DefaultOptimizationConfigController@officeConfigurationOptimization')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
        Route::post('officeConfigurationOptimizationget', 'DefaultOptimizationConfigController@officeConfigurationOptimizationget')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');

        /** Invoice Configuration Update setting routes */
        Route::post('invoice-settings', 'InvoiceConfigurationController@invoicesettings')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
        Route::post('getinvoicesettings/{id}', 'InvoiceConfigurationController@getinvoicesettings')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');

        /** Technician Note Templates */
        Route::prefix('createnote')->group(function () {
            Route::post('create', 'ChemSheetConfigurationController@create')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('list', 'ChemSheetConfigurationController@list')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::get('{id}', 'ChemSheetConfigurationController@get')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('update', 'ChemSheetConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::get('delete/{id}', 'ChemSheetConfigurationController@delete')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('reorder', 'ChemSheetConfigurationController@reorder')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
        });

        /** Sales Alert Configuration  */
        Route::prefix('salesalert')->group(function () {
            Route::post('create', 'SalesAlertConfigurationController@create')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_WRITE');
            Route::post('list/{id}', 'SalesAlertConfigurationController@list')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_READ');
            Route::get('{id}', 'SalesAlertConfigurationController@get')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_READ');
            Route::post('update', 'SalesAlertConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_WRITE');
            Route::get('delete/{id}', 'SalesAlertConfigurationController@delete')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_WRITE');
            Route::post('alertconfiguration', 'SalesAlertConfigurationController@alertconfiguration')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_WRITE');
            Route::post('alertconfigurationList/{id}', 'SalesAlertConfigurationController@alertconfigurationList')->middleware('role:ROLE_ADMIN|ROLE_SALES_ALERT_CONFIG_WRITE');
            Route::post('messageboardconfigs', 'AlertConfigurationController@messageboardconfigs')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('getmessageboardconfigs', 'AlertConfigurationController@getmessageboardconfigs')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
        });

        /** OfficeWidget routes  */
        Route::prefix('officewidgets')->group(function () {
            Route::post('create', 'OfficeWidgetController@create')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('list/{id}', 'OfficeWidgetController@list')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::get('{id}', 'OfficeWidgetController@get')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('update', 'OfficeWidgetController@update')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('changeStatus', 'OfficeWidgetController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('reorder', 'OfficeWidgetController@reorder')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
        });

        /** Office Opiniion Setting routes  */
        Route::prefix('integrations')->group(function () {
            Route::post('listopiniion/{id}', 'OfficeOpiniionSettingController@listopiniion')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('listdocusend/{id}', 'OfficeOpiniionSettingController@listdocusend')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('listfreshlime/{id}', 'OfficeOpiniionSettingController@listfreshlime')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('listbirdeye/{id}', 'OfficeOpiniionSettingController@listbirdeye')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('opiniion_integration', 'OfficeOpiniionSettingController@opiniion_integration')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('docusend_config', 'OfficeOpiniionSettingController@docusend_config')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('freshlime_config', 'OfficeOpiniionSettingController@freshlime_config')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('birdeye_config', 'OfficeOpiniionSettingController@birdeye_config')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
        });

        /** reports setting routes */
        Route::prefix('reports')->group(function () {
            Route::post('summerTotal', 'SummerTotalConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_REPORT_SUMMER_TOTAL');
            Route::get('listSalesStatus/{officeid}', 'SummerTotalConfigurationController@listSalesStatus')->middleware('role:ROLE_ADMIN');
        });

        /** Automated Reports */
        Route::prefix('automated')->group(function () {
            Route::post('create', 'AutomatedReportController@create')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('list', 'AutomatedReportController@list')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::get('{id}', 'AutomatedReportController@get')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('update', 'AutomatedReportController@update')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::get('delete/{id}', 'AutomatedReportController@delete')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('changeStatus', 'AutomatedReportController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
        });

        /** qr-code batch routes */
        Route::prefix('qr-code/batch')->group(function () {
            Route::post('create', 'BatchController@create')->middleware('role:ROLE_ADMIN|ROLE_QR_CODE_BATCH_WRITE');
            Route::post('list/{id}', 'BatchController@list')->middleware('role:ROLE_ADMIN|ROLE_QR_CODE_BATCH_READ');
            Route::post('assignQRCode', 'BatchController@assignQRCode')->middleware('role:ROLE_ADMIN|ROLE_QR_CODE_BATCH_READ');
        });

        /** agreement module */
        Route::prefix('agreement')->group(function () {
            Route::get('testNewAgreementLayoutAction/{agreement_id}/{office_id}', 'AgreementController@testNewAgreementLayoutAction')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::post('create', 'AgreementController@create')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT');
            Route::post('list', 'AgreementController@list')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_VIEW_AGREEMENT|ROLE_ARRANGE_ORDER_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::post('listNew', 'AgreementController@listNew')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_VIEW_AGREEMENT|ROLE_ARRANGE_ORDER_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::post('update', 'AgreementController@update')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::post('duplicateAgreement', 'AgreementController@duplicateAgreement')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::post('sendTestEmail', 'AgreementController@sendTestEmail')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::post('reorder', 'AgreementController@reorder')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_EDIT_AGREEMENT');
            Route::get('getAgreement/{id}', 'AgreementController@getAgreement')->middleware('role:ROLE_ADMIN|ROLE_FULL_AGREEMENT|ROLE_VIEW_AGREEMENT|ROLE_ARRANGE_ORDER_AGREEMENT|ROLE_EDIT_AGREEMENT');
        });

        /** appearance Update setting routes */
        Route::post('appearanceUpdate', 'OfficeConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
        Route::get('getAvailableThemes', 'OfficeConfigurationController@getAvailableThemes')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');

        /** company setting routes */
        Route::prefix('company')->group(function () {
            Route::post('getbulletin', 'BulletinController@getbulletin')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('bulletinUpdate', 'BulletinController@update')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('currentofficeUpdate', 'PestOfficeController@update')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::get('getcurrentoffice/{id}', 'PestOfficeController@get')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('updateGatewayCredentials', 'OfficeConfigurationController@updateGatewayCredentials')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
            Route::get('getOffceCredentialsDetails/{officeId}', 'OfficeConfigurationController@getOffceCredentialsDetails')->middleware('role:ROLE_ADMIN|ROLE_OFFICE_WRITE');
            Route::get('clearTokens/{officeId}', 'OfficeConfigurationController@clearTokens')->middleware('role:ROLE_ADMIN');
        });

        /** Taxcode routes*/
        Route::prefix('taxCode')->group(function () {
            Route::post('create', 'TaxCodeController@create')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
            Route::post('list/{id}', 'TaxCodeController@list')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
            Route::post('replaceTaxCodelist', 'TaxCodeController@replaceTaxCodelist')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
            Route::post('update', 'TaxCodeController@update')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
            Route::post('delete', 'TaxCodeController@delete')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
            Route::post('recalculate', 'TaxCodeController@recalculate')->middleware('role:ROLE_ADMIN|ROLE_TAXCODES_EDIT');
        });

        /** zipcode Reports */
        Route::prefix('zipcode')->group(function () {
            Route::post('create', 'ZipCodeController@create')->middleware('role:ROLE_ADMIN|ROLE_ALLOW_ADD_ZIPCODE');
            Route::post('list', 'ZipCodeController@list')->middleware('role:ROLE_ADMIN');
            Route::get('{id}', 'ZipCodeController@get')->middleware('role:ROLE_ADMIN|ROLE_ALLOW_ADD_ZIPCODE');
            Route::post('update', 'ZipCodeController@update')->middleware('role:ROLE_ADMIN|ROLE_ALLOW_ADD_ZIPCODE');
            Route::get('delete/{id}', 'ZipCodeController@delete')->middleware('role:ROLE_ADMIN|ROLE_ALLOW_ADD_ZIPCODE');
            Route::post('changeStatus', 'ZipCodeController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_ALLOW_ADD_ZIPCODE');
            Route::post('getBranches', 'ZipCodeController@getBranches')->middleware('role:ROLE_ADMIN|ROLE_ALLOW_ADD_ZIPCODE');
        });

        /** online-booking-configuration Reports */
        Route::prefix('online-booking-configurations')->group(function () {
            Route::post('create', 'OnlineBookingConfigurationController@create')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('list', 'OnlineBookingConfigurationController@list')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::get('{id}', 'OnlineBookingConfigurationController@get')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('update', 'OnlineBookingConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::get('delete/{id}', 'OnlineBookingConfigurationController@delete')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('changeStatus', 'OnlineBookingConfigurationController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
        });

        /** Technician Note Templates */
        Route::prefix('chemical-templates')->group(function () {
            Route::post('list/{id}', 'ChemSheetConfigurationController@listCheTemplates')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('create', 'ChemSheetConfigurationController@createCheTemplates')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::get('{id}', 'ChemSheetConfigurationController@getCheTemplate')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('update/{id}', 'ChemSheetConfigurationController@updateCheTemplate')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::get('delete/{id}', 'ChemSheetConfigurationController@deleteCheTemplate')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');

            Route::post('updateTemplateConfiguration', 'ChemSheetConfigurationController@updateTemplateConfiguration')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('getTemplateConfiguration', 'ChemSheetConfigurationController@getTemplateConfiguration')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
        });

        /** Route template routes */
        Route::prefix('route-template')->group(function () {
            Route::post('list', 'RouteTemplateController@listRouteTemplates')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('updateRouteTemplatesConfigurations', 'RouteTemplateController@updateRouteTemplatesConfigurations')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('deleteRouteTemplate/{id}', 'RouteTemplateController@deleteRouteTemplate')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('routesList', 'RouteTemplateController@routesList')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('createRouteTemplate', 'RouteTemplateController@createRouteTemplate')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('updateRouteTemplate/{id}', 'RouteTemplateController@updateRouteTemplate')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('getRouteTemplate/{id}', 'RouteTemplateController@getRouteTemplate')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('getRouteTemplateConfigurations', 'RouteTemplateController@getRouteTemplateConfigurations')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        /** Office Schedule routes */
        Route::prefix('schedule')->group(function () {
            Route::post('techs', 'OfficeScheduleController@getTechs')->middleware('role:ROLE_ADMIN');
            Route::post('get', 'OfficeScheduleController@getSchedule')->middleware('role:ROLE_ADMIN');
            Route::post('create', 'OfficeScheduleController@createOrUpdateSchedule')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('service-durations')->group(function () {
            Route::post('get', 'OfficeScheduleController@getServiceDurations')->middleware('role:ROLE_ADMIN');
            Route::post('update/{id}', 'OfficeScheduleController@updateServiceDurations')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('job-color-rules')->group(function () {
            Route::post('list', 'OfficeScheduleController@listJobColorRules')->middleware('role:ROLE_ADMIN');
            Route::post('create', 'OfficeScheduleController@createJobColorRule')->middleware('role:ROLE_ADMIN');
            Route::post('update/{id}', 'OfficeScheduleController@updateJobColorRule')->middleware('role:ROLE_ADMIN');
            Route::post('delete/{id}', 'OfficeScheduleController@deleteJobColorRule')->middleware('role:ROLE_ADMIN');
            Route::post('reorder', 'OfficeScheduleController@reorderJobColorRules')->middleware('role:ROLE_ADMIN');
        });
    });

    /** Sales Tracker routes */
    Route::namespace('API\Pocomos\SalesTracker')->prefix('SalesTracker')->group(function () {
        /** Commission Report routes */
        Route::prefix('commission-report')->group(function () {
            Route::post('salespeople-by-office/{id}', 'CommissionReportController@salesPeopleByOffice')->middleware('role:ROLE_ADMIN|ROLE_COMMISSION_REPORT|ROLE_SALESTRACKER_MANAGER');
            Route::post('get', 'CommissionReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_COMMISSION_REPORT|ROLE_SALESTRACKER_MANAGER');
            Route::post('update/{id}', 'CompanyRecordsController@update')->middleware('role:ROLE_ADMIN|ROLE_COMMISSION_REPORT|ROLE_SALESTRACKER_MANAGER');
        });

        Route::prefix('office-bonus')->group(function () {
            Route::post('list', 'OfficeBonusController@list')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('create', 'OfficeBonusController@create')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('get/{id}', 'OfficeBonusController@get')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('update/{id}', 'OfficeBonusController@update')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('delete/{id}', 'OfficeBonusController@delete')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
        });

        Route::prefix('company-records')->group(function () {
            Route::post('get-data', 'CompanyRecordsController@getData')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_RECORD_READ');
            Route::post('get/{id}', 'CompanyRecordsController@get')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_RECORD_READ');
            Route::post('update/{id}', 'CompanyRecordsController@update')->middleware('role:ROLE_ADMIN|ROLE_COMPANY_RECORD_WRITE');
        });

        Route::prefix('salespeople')->group(function () {
            Route::post('list', 'SalesPeopleController@list')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('form-data', 'SalesPeopleController@getFormData')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('create', 'SalesPeopleController@create')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('delete/{ou_id}', 'SalesPeopleController@delete')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('edit/{profile_id}', 'SalesPeopleController@edit')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('update/{profile_id}', 'SalesPeopleController@update')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('show/{uid}', 'SalesPeopleController@show')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('show-alerts/{id}', 'SalesPeopleController@showAlerts')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('add-alert', 'SalesPeopleController@addAlert')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('update-alert/{id}', 'SalesPeopleController@updateAlert')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('show-tasks/{id}', 'SalesPeopleController@showTasks')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('add-task', 'SalesPeopleController@addTask')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('update-task/{alert_id}', 'SalesPeopleController@updateTaskStatus')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('show-notes/{id}', 'SalesPeopleController@showNotes')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('add-note', 'SalesPeopleController@addNote')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('delete-note/{id}', 'SalesPeopleController@deleteNote')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('show-history/{id}', 'SalesPeopleController@completedAlertTaskHistory')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');

            Route::post('commissionDeduction/create', 'SalesPeopleController@createCommissionDeduction')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
            Route::post('commissionDeduction/list', 'SalesPeopleController@listCommissionDeduction')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
            Route::post('commissionDeduction/update/{id}', 'SalesPeopleController@updateCommissionDeduction')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
            Route::post('commissionDeduction/delete/{id}', 'SalesPeopleController@deleteCommissionDeduction')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
        });

        Route::prefix('commission-report')->group(function () {
            Route::post('calculate', 'CommissionReportController@calculate')->middleware('role:ROLE_ADMIN|ROLE_COMMISSION_REPORT');
        });

        Route::post('top-twenty', 'TopTwentyController@list')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON|ROLE_SECRETARY');

        Route::prefix('summer-total')->group(function () {
            Route::post('get', 'SummerTotalController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('search', 'SummerTotalController@search')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        Route::prefix('sales-status-modifier')->group(function () {
            Route::post('get', 'SalesStatusModifierController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');

            Route::post('get-technicians', 'SalesStatusModifierController@findTechnicianByOfficeAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('search', 'SalesStatusModifierController@search')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            // for modyfy selected
            Route::post('update', 'SalesStatusModifierController@update')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE|ROLE_SALES_STATUS_WRITE');
            // export to pestpac = ProcessJobsController@exportToPestpac

            Route::post('get-view-setting/{officeId}', 'SalesStatusModifierController@getViewSetting')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('update-view-setting/{id}', 'SalesStatusModifierController@updateViewSetting')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
        });

        /** Teams routes */
        Route::prefix('Teams')->group(function () {
            Route::post('addTeam', 'TeamsController@addTeam')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('get-members', 'TeamsController@getMembers')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('addMember', 'TeamsController@addMember')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('list/{id}', 'TeamsController@list')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('removeMember/{id}', 'TeamsController@removeMember')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('editTeam', 'TeamsController@editTeam')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('removeTeam/{id}', 'TeamsController@removeTeam')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
            Route::post('teamsList', 'TeamsController@teamsList')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER_MANAGER');
        });
    });

    Route::namespace('API\Pocomos\Recruitement')->prefix('recruitement')->group(function () {
        /** recruit Status routes */
        Route::prefix('recruitStatus')->group(function () {
            Route::post('create', 'RecruitStatusController@create')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_STATUS_WRITE');
            Route::post('list', 'RecruitStatusController@list')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_STATUS_READ');
            Route::get('{id}', 'RecruitStatusController@get')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_STATUS_READ');
            Route::post('update', 'RecruitStatusController@update')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_STATUS_WRITE');
            Route::get('delete/{id}', 'RecruitStatusController@delete')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_STATUS_WRITE');
            Route::post('checkDefaultStatusExist', 'RecruitStatusController@checkDefaultStatusExist')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_STATUS_WRITE');
        });

        /** recruiting Regions routes */
        Route::prefix('recruitingRegions')->group(function () {
            Route::post('create', 'RegionController@create')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_WRITE');
            Route::post('list', 'RegionController@list')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_READ');
            Route::get('{id}', 'RegionController@get')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_READ');
            Route::post('update', 'RegionController@update')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_WRITE');
            Route::get('delete/{id}', 'RegionController@delete')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_WRITE');
        });

        /** recruiting Offices routes */
        Route::prefix('recruitingOffices')->group(function () {
            Route::post('create', 'OfficeController@create')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_OFFICE_WRITE');
            Route::post('list', 'OfficeController@list')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_OFFICE_READ');
            Route::get('{id}', 'OfficeController@get')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_OFFICE_READ');
            Route::post('update', 'OfficeController@update')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_OFFICE_WRITE');
            Route::get('delete/{id}', 'OfficeController@delete')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_OFFICE_WRITE');
        });

        /** recruiting Agreements routes */
        Route::prefix('recruitAgreements')->group(function () {
            Route::post('create', 'RecruitAgreementController@create')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_WRITE');
            Route::post('list', 'RecruitAgreementController@list')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_READ');
            Route::get('{id}', 'RecruitAgreementController@get')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_READ');
            Route::post('update', 'RecruitAgreementController@update')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_AGREEMENT_WRITE');
        });

        /** recruiting CustomFields routes */
        Route::prefix('recruitCustomFields')->group(function () {
            Route::post('create', 'CustomFieldConfigurationController@create')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_CUSTOM_FIELD_WRITE');
            Route::post('list', 'CustomFieldConfigurationController@list')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_CUSTOM_FIELD_READ');
            Route::get('{id}', 'CustomFieldConfigurationController@get')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_CUSTOM_FIELD_READ');
            Route::post('update', 'CustomFieldConfigurationController@update')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_CUSTOM_FIELD_WRITE');
            Route::get('delete/{id}', 'CustomFieldConfigurationController@delete')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_CUSTOM_FIELD_WRITE');
        });

        /** recruiting Creation routes */
        Route::prefix('recruitCreation')->group(function () {
            Route::post('create', 'RecruitCreationController@create')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'RecruitCreationController@list')->middleware('role:ROLE_ADMIN');
            Route::get('{id}', 'RecruitCreationController@get')->middleware('role:ROLE_ADMIN');
            Route::get('delete/{id}', 'RecruitCreationController@delete')->middleware('role:ROLE_ADMIN');
        });

        /** recruit Status routes */
        Route::prefix('recruit')->group(function () {
            Route::post('create', 'RecruitController@create')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'RecruitController@list')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_READ');
            Route::post('update_status', 'RecruitController@update_status')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('delete/{id}', 'RecruitController@delete')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_DELETE');
            Route::post('quickEdit/{id}', 'RecruitController@quick_edit')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('uploadProfile/{id}', 'RecruitController@upload_profile')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('uploadAttachment/{id}', 'RecruitController@upload_attachment')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('update/{id}', 'RecruitController@update')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('get/{id}', 'RecruitController@get')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_READ');
            Route::post('remoteCompletionEmail/{id}', 'RecruitController@remote_completion_email')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('getRemoteCompletionDetails', 'RecruitController@getRemoteCompletionDetails')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('sendAgreement/{id}', 'RecruitController@send_agreement')->middleware('role:ROLE_ADMIN');
            Route::post('downloadAgreement/{id}', 'RecruitController@download_agreement')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('regenerateAgreement/{id}', 'RecruitController@regenerate_agreement')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::get('previewRecruitAgreement', 'RecruitController@previewRecruitAgreement')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('generateRecruitementSalesContract', 'RecruitController@generateRecruitementSalesContract')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('deleteAttachment', 'RecruitController@deleteAttachment')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('updateUser/{id}', 'RecruitController@updateUser')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('convertToEmployeeUser/{id}', 'RecruitController@convertToEmployeeUser')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('linkSelectData', 'RecruitController@linkSelectData')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
            Route::post('createW9Save', 'RecruitController@createW9')->middleware('role:ROLE_ADMIN|ROLE_RECRUIT_WRITE');
        });
    });

    /** employee routes */
    Route::prefix('employee')->group(function () {
        Route::post('create', 'API\UserController@create')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('update', 'API\UserController@update')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('check-username', 'API\UserController@checkUsername')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('upload-photo/{profile_id}', 'API\UserController@uploadPhoto')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('capture-signature/{profile_id}', 'API\UserController@captureSignature')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('deactivate/{profile_id}', 'API\UserController@deactivate')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::get('delete/{id}', 'API\UserController@delete')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('resetpassword', 'API\UserController@resetpassword')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('changeStatus', 'API\UserController@changeStatus')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('list', 'API\UserController@list')->middleware('role:ROLE_ADMIN|ROLE_USER_READ');
        Route::post('editTechnicianProfile/{user_id}', 'API\UserController@editTechnicianProfile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('changeTechnicianProfile', 'API\UserController@changeTechnicianProfile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::get('editSalesPerson/{ou_id}', 'API\UserController@editSalesPersonProfile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('changeSalesPersonProfile/{ou_id}', 'API\UserController@changeSalesPersonProfile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('editRecruiterPersonProfile/{id}', 'API\UserController@editRecruiterPersonProfile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('changeRecruiterProfile', 'API\UserController@changeRecruiterProfile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('edit-assigned-office/{profile_id}', 'API\UserController@editAssignedOffice')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('update-assigned-office', 'API\UserController@updateAssignedOffice')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('techniciansAndSalesPerson', 'API\UserController@techniciansAndSalesPerson')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('addAlert', 'API\UserController@addAlert')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('alertListing', 'API\UserController@alertListing')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('edit-permission/{user_id}', 'API\UserController@editPermission')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('update-permission/{user_id}', 'API\UserController@updatePermission')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('update-permission-new/{user_id}', 'API\UserController@updatePermissionNew')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('alertHistoryListing', 'API\UserController@alertHistoryListing')->middleware('role:ROLE_ADMIN|ROLE_HISTORY_READ');
        Route::post('salesPeopleList', 'API\UserController@salesPeopleList')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('getUserPermission/{user_id}', 'API\UserController@getUserPermission')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');

        Route::post('technicianUsersList', 'API\UserController@technicianUsersList')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('email-history/{id}', 'API\UserController@emailHistory')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('view-email/{id}', 'API\UserController@viewEmail')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('activity-history/{profileId}', 'API\UserController@activityHistory')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('message-board/{id}', 'API\UserController@messageBoard')->middleware('role:ROLE_ADMIN|ROLE_TACKBOARD_HISTORY');
        Route::post('update-message-board/{id}', 'API\UserController@updateMessageBoard')->middleware('role:ROLE_ADMIN|ROLE_TACKBOARD_HISTORY');
        Route::post('managerList', 'API\UserController@managerList')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');

        Route::post('switchUser', 'API\UserController@switchUser')->middleware('role:ROLE_ADMIN|ROLE_ALLOWED_TO_SWITCH');
        Route::post('exitImpersonateMode', 'API\UserController@exitImpersonateMode')->middleware('role:ROLE_ADMIN|ROLE_ALLOWED_TO_SWITCH');
        Route::post('recruiterUsersList', 'API\UserController@recruiterUsersList')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('getTechnicianLicensesDetails', 'API\Pocomos\Settings\TechnicianChecklistController@getTechnicianLicensesDetails')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');

        Route::post('check-office-user/{id}', 'API\UserController@checkofficeuser')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('check-office-userprofile', 'API\UserController@checkofficeuserprofile')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
        Route::post('check-tech/{id}', 'API\UserController@checktech')->middleware('role:ROLE_ADMIN|ROLE_USER_WRITE');
    });

    // Route::middleware('api-sessions')->group(function () {
    //     Route::namespace('API\Pocomos\Customer')->group(function () {
    //         /** Customer routes */
    //         Route::prefix('customer')->group(function () {
    //             Route::post('saveDefaultContract', 'CustomerController@saveDefaultContract');
    //             Route::get('profile/{id}', 'CustomerController@profile');
    //         });
    //     });
    // });

    /** Financial routes */
    Route::namespace('API\Pocomos\Financial')->prefix('financial')->group(function () {
        /** Transaction routes */
        Route::prefix('transaction')->group(function () {
            Route::post('get', 'TransactionController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_CREATE');
            Route::post('search', 'TransactionController@search')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_CREATE');
        });

        /** Closeout Month routes */
        Route::prefix('closeout-month')->group(function () {
            Route::post('list', 'CloseOutMonthController@list')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('close', 'CloseOutMonthController@closeMonth')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            // for download invoice - DownloadController@invoiceAction
            // for download service record - DownloadController@serviceRecordAction
        });

        /** Invoice search routes */
        Route::prefix('invoice')->group(function () {
            Route::post('search', 'InvoiceSearchController@search')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');
        });

        /** Unpaid Invoices routes */
        Route::prefix('unpaid-invoice')->group(function () {
            Route::post('get', 'UnpaidInvoiceController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('search', 'UnpaidInvoiceController@search')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('more-info/{job_id}', 'UnpaidInvoiceController@moreInfo')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('get-accounts/{profile_id}', 'UnpaidInvoiceController@getAccounts')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('charge/{invoice_id}', 'UnpaidInvoiceController@createChargeAction')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            // for download invoice - DownloadController@invoiceAction
            Route::get('download', 'UnpaidInvoiceController@downloadInvoice')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('add-discount/{invoice_id}', 'UnpaidInvoiceController@addDiscount')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('send-to-collections/{invoice_id}', 'UnpaidInvoiceController@sendToCollections')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            // for bulk charge - ReminderController@bulkCharge
            Route::post('cancel-invoices', 'UnpaidInvoiceController@cancelInvoices')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('received-by-collections', 'UnpaidInvoiceController@inCollectionsActionInvoiceController')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('received-by-collections-bulk', 'UnpaidInvoiceController@receivedByCollectionsBulkByInvoiceAction')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('modify-selected', 'UnpaidInvoiceController@salesByInvoicesAction')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('send-mails', 'UnpaidInvoiceController@sendActionFormLetterController')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('send-sms', 'UnpaidInvoiceController@sendAction_smsFormLetterController')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
            Route::post('mail-invoice/{custId}', 'UnpaidInvoiceController@mailInvoice')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE|ROLE_COLLECTIONS');
        });

        /** Prepaid Invoices routes */
        Route::prefix('prepaid-invoice')->group(function () {
            Route::post('search', 'PrepaidInvoiceController@search')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE');
        });

        /** Scheduled Payments routes */
        Route::prefix('schedule-payment')->group(function () {
            Route::post('get', 'SchedulePaymentController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE');
            Route::post('search', 'SchedulePaymentController@search')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE');
            Route::post('charge-payments', 'SchedulePaymentController@bulkChargePayments')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE');
            Route::post('cancel-payments', 'SchedulePaymentController@bulkCancelPayments')->middleware('role:ROLE_ADMIN|ROLE_ROUTE_WRITE');
        });

        /** Card management routes */
        Route::prefix('card-management')->group(function () {
            Route::post('get', 'CardManagementController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');
            Route::post('search', 'CardManagementController@search')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');            
            Route::post('edit-card/{customer_id}', 'CardManagementController@editCard')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');
            Route::post('update-card/{account_id}', 'CardManagementController@updateCard')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');
            Route::post('bulk-charge', 'CardManagementController@enqueueCustomersAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');
            Route::post('send-letter', 'CardManagementController@sendCustomerSms')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_TRANSACTIONS');
            // charge = quick invoice/quickchargeCreate
        });

        /** Payment report routes */
        Route::prefix('payment-report')->group(function () {
            Route::post('get', 'PaymentReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'PaymentReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Accounts with Credit routes */
        Route::prefix('account-credit')->group(function () {
            Route::post('get', 'AccountWithCreditController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('search', 'AccountWithCreditController@search')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            //ReceiveMoneyController
            Route::post('receive-money-form/{customer_id}', 'AccountWithCreditController@receiveMoneyForm')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('apply-bulk-credit', 'AccountWithCreditController@bulkApplyAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
        });

        /** Revenue Report routes */
        Route::prefix('revenue-report')->group(function () {
            Route::post('get', 'RevenueReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'RevenueReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });
    });


    /** Reports routes */
    Route::namespace('API\Pocomos\Reports')->prefix('reports')->group(function () {
        /** Dashboard Admin Only routes */
        Route::prefix('dashboard-admin-only')->group(function () {
            Route::post('get', 'DashboardAdminOnlyController@get')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Owner dashboard routes */
        Route::prefix('owner-dashboard')->group(function () {
            Route::post('get', 'OwnerDashboardController@getData')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Technician Report routes */
        Route::prefix('tech-report')->group(function () {
            Route::post('get', 'TechnicianReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
            // Route::post('technicians', 'TechnicianReportController@technicians')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
            Route::post('search', 'TechnicianReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
        });

        /** Detailed Tech Report routes */
        Route::prefix('detailed-tech-report')->group(function () {
            Route::post('get', 'DetailedTechReportController@getForm')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
            Route::post('technicians', 'DetailedTechReportController@technicians')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
            Route::post('search', 'DetailedTechReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');

            Route::post('getTechnicians', 'DetailedTechReportController@getTechnicians')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
            Route::post('getTechnicianReportDetails', 'DetailedTechReportController@getTechnicianReportDetails')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
            Route::post('downloadTechnicianReportDetails', 'DetailedTechReportController@downloadTechnicianReportDetails')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ|ROLE_TECHNICIAN');
        });

        /** Contract Report routes */
        Route::prefix('contract-report')->group(function () {
            Route::post('get', 'ContractReportController@salesStatus')->middleware('role:ROLE_ADMIN|ROLE_REPORT_CONTRACT');
            Route::post('get-salespeople', 'ContractReportController@getSalesPeopleByOffice')->middleware('role:ROLE_ADMIN|ROLE_REPORT_CONTRACT');
            Route::post('summary/{id}', 'ContractReportController@summary')->middleware('role:ROLE_ADMIN|ROLE_REPORT_CONTRACT');
        });

        /** contract-report routes */
        Route::post('reportcontractsummary', 'ContractReportController@reportcontractsummary')->middleware('role:ROLE_ADMIN');

        /** Account Status routes */
        Route::namespace('AccountStatus')->prefix('account-status')->group(function () {
            Route::post('sales-report', 'AccountStatusReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('tags', 'AccountStatusReportController@findTagsByOfficeAction')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('sales-report/search', 'AccountStatusReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');

            /** Account Status Totals routes */
            Route::post('totals', 'AccountStatusTotalsController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');
            Route::post('totals/salespeople', 'AccountStatusTotalsController@salespeopleByBranchesAction')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');
            Route::post('totals/marketing-types', 'AccountStatusTotalsController@maketingTypeByBranchesAction')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');
            Route::post('totals/update', 'AccountStatusTotalsController@update')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');

            /** Production Report routes */
            Route::post('production-report', 'ProductionReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');
            Route::post('production-report/search', 'ProductionReportController@search')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');

            /** Ranked Sales Report routes */
            Route::post('ranked-sales-report', 'RankedSalesReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');
            Route::post('ranked-sales-report/update', 'RankedSalesReportController@update')->middleware('role:ROLE_ADMIN|ROLE_SALESTRACKER');
        });

        /** Marketing Report routes */
        Route::prefix('marketing-report')->group(function () {
            Route::post('get', 'MarketingReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'MarketingReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Usage Report routes */
        Route::prefix('usage-report')->group(function () {
            Route::post('get', 'UsageReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'UsageReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** NY Usage Report routes */
        Route::prefix('ny-usage-report')->group(function () {
            Route::post('get', 'NyUsageReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'NyUsageReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Delinquent Report routes */
        Route::prefix('cancelled-report')->group(function () {
            Route::post('get', 'DelinquentReportController@getFilters')->middleware('role:ROLE_ADMIN');
            Route::post('get-salespeople', 'DelinquentReportController@getSalespeopleByOffices')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('get-technicians', 'DelinquentReportController@findTechniciansWithOfficeAction')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('search', 'DelinquentReportController@search')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('sendCustomerEmail', 'DelinquentReportController@sendCustomerEmail')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('sendCustomerSms', 'DelinquentReportController@sendCustomerSms')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        Route::post('credit-hold-report', 'CreditHoldReportController@list')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');

        /** Email Report routes */
        Route::prefix('email-report')->group(function () {
            Route::post('get', 'EmailReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_HISTORY_READ');
            Route::post('search', 'EmailReportController@search')->middleware('role:ROLE_ADMIN|ROLE_HISTORY_READ');
            Route::post('view/{id}', 'EmailReportController@view')->middleware('role:ROLE_ADMIN|ROLE_HISTORY_READ');
        });

        /** Unanswered Message routes */
        Route::prefix('unanswered-message')->group(function () {
            Route::post('list', 'UnansweredMessageController@list')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('mark-as-read', 'UnansweredMessageController@markMessageAsRead')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('customer/{id}/message-history/{phoneId}', 'UnansweredMessageController@viewFullConversation')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Tax Detail Report routes */
        Route::prefix('tax-detail')->group(function () {
            Route::post('get', 'TaxDetailReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'TaxDetailReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('view/{id}', 'TaxDetailReportController@view')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Tax Summary Report routes */
        Route::prefix('tax-summary')->group(function () {
            Route::post('get', 'TaxSummaryReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('search', 'TaxSummaryReportController@search')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
            Route::post('view/{id}', 'TaxSummaryReportController@view')->middleware('role:ROLE_ADMIN|ROLE_DASHBOARD_READ');
        });

        /** Notes Report routes */
        Route::prefix('office-note')->group(function () {
            Route::post('get', 'NotesReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('search', 'NotesReportController@search')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('view/{id}', 'NotesReportController@view')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        /** Estimate Report routes */
        Route::prefix('estimate')->group(function () {
            Route::post('find-customer', 'EstimateReportController@findCustomer')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('find-lead', 'EstimateReportController@findLead')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('get', 'EstimateReportController@getFilters')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('create', 'EstimateReportController@create')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('list', 'EstimateReportController@list')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('indexAction', 'EstimateReportController@indexAction')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('update/{id}', 'EstimateReportController@update')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('update-status/{id}', 'EstimateReportController@updateStatus')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('delete/{id}', 'EstimateReportController@delete')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('download/{id}', 'EstimateReportController@downloadPdf')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
        });

        /** Total sold routes */
        Route::prefix('total-sold')->group(function () {
            Route::post('get', 'TotalSoldReportController@getFilters')->middleware('role:ROLE_ADMIN');
            Route::post('search', 'TotalSoldReportController@search')->middleware('role:ROLE_ADMIN');
        });

        /** Personal report routes */
        Route::prefix('personal-report')->group(function () {
            Route::post('search', 'PersonalReportController@search')->middleware('role:ROLE_ADMIN');
        });

        /** All Office's Notes routes */
        /* Route::post('allnotes', 'NoteController@allnotes')->middleware('role:ROLE_ADMIN');

        Route::post('taxSummaryReport', 'TaxSummaryController@taxSummaryReport')->middleware('role:ROLE_ADMIN');

        Route::post('nyUsageReport', 'NyUsageController@nyUsageReport')->middleware('role:ROLE_ADMIN'); */
    });

    /** PestPac routes */
    Route::namespace('API\Pocomos\PestPac')->prefix('pestpac')->group(function () {
        /** Settings routes routes */
        Route::prefix('setting')->group(function () {
            Route::post('api-credential', 'SettingController@getApiCredential')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('update-api-credential/{id}', 'SettingController@updateApiCredential')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');

            Route::post('setting', 'SettingController@getData')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');
            Route::post('update-setting', 'SettingController@updateSetting')->middleware('role:ROLE_ADMIN|ROLE_PESTCONFIG_WRITE');

            Route::post('list-service-type', 'SettingController@listServiceType')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('get', 'SettingController@getPestContractServiceType')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('create-service-type', 'SettingController@createServiceType')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('edit-service-type/{id}', 'SettingController@editPestpacServiceType')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('update-service-type/{id}', 'SettingController@updatePestpacServiceType')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('toggle-service-type/{id}', 'SettingController@togglePestpacServiceType')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
        });

        /** PestPac Export routes */
        Route::prefix('export')->group(function () {
            Route::post('get', 'PestPacExportController@getOffices')->middleware('role:ROLE_ADMIN|ROLE_SALES_ADMIN|ROLE_SECRETARY|ROLE_OWNER');
            Route::post('search', 'PestPacExportController@search')->middleware('role:ROLE_ADMIN|ROLE_SALES_ADMIN|ROLE_SECRETARY|ROLE_OWNER');
            // show particular record = PestpacExportCustomerController@get
            // pause/reschedule customer export = PestpacExportCustomerController@changePestpacCustomerStatus
            // try exporting = PestpacExportCustomerController@tryExporting
        });
    });
    Route::namespace('API\Pocomos\Vtp')->group(function () {
        /** training and videos routes */
        Route::prefix('vtp')->group(function () {
            Route::post('watched-videos', 'VideosController@watchedVideos')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN|ROLE_VTP_USER');
            Route::post('watch', 'VideosController@watchAction')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN|ROLE_VTP_USER');
            Route::post('watch/video/{id}', 'VideosController@videoWatched')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN|ROLE_VTP_USER');
        });

        /** manage videos routes */
        Route::prefix('videos')->group(function () {
            Route::post('list', 'VideosController@list')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('create', 'VideosController@create')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::get('{id}', 'VideosController@get')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('edit/{id}', 'VideosController@edit')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::get('delete/{id}', 'VideosController@delete')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('reorder/{id}', 'VideosController@reorder')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
        });

        /** certification level routes */
        Route::prefix('certification-level')->group(function () {
            Route::post('list', 'CertificationLevelController@list')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('list-all', 'CertificationLevelController@listAll')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('create', 'CertificationLevelController@create')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::get('{id}', 'CertificationLevelController@get')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('update/{id}', 'CertificationLevelController@update')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::get('delete/{id}', 'CertificationLevelController@delete')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
        });

        /** certification report routes */
        Route::prefix('certification-report')->group(function () {
            Route::post('list', 'CertificationReportController@list')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('get/{id}', 'CertificationReportController@get')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
            Route::post('update/{id}', 'CertificationReportController@update')->middleware('role:ROLE_ADMIN|ROLE_VTP_ADMIN');
        });
    });

    Route::namespace('API\Pocomos\Customer')->group(function () {
        /** Customer routes */
        Route::prefix('customer')->group(function () {
            Route::post('create', 'CustomerController@create')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_CREATE');
            Route::post('available-slots', 'CustomerController@getSlotsAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_CREATE');
            Route::post('list', 'CustomerController@list')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('customerlookup', 'CustomerController@customerlookup')->middleware('role:ROLE_ADMIN');
            Route::post('uploadCustomerFile', 'AttachmentController@uploadCustomerFile')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('editCustomerFile', 'AttachmentController@editCustomerFile')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('listCustomerFile', 'AttachmentController@listCustomerFile')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('deleteCustomerfile', 'AttachmentController@deleteCustomerfile')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('resendAction', 'AttachmentController@resendAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::get('profile/{id}', 'CustomerController@profile')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE|ROLE_CUSTOMER_SHOW');
            Route::post('quickchargeCreate', 'QuickChargeController@quickchargeCreate')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('getcreatedBy', 'CustomerController@getcreatedBy')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('addAccountNote', 'CustomerController@addAccountNote')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('editAccountNote', 'CustomerController@editAccountNote')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('listAccountNote', 'CustomerController@listAccountNote')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('deleteAccountNote', 'CustomerController@deleteAccountNote')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('techNoteList', 'CustomerController@techNoteList')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('{custId}/tech-notes', 'CustomerController@techNotes')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('addNumber', 'CustomerController@addNumber')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('listNumber', 'CustomerController@listNumber')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('editNumber', 'CustomerController@editNumber')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('deleteNumber', 'CustomerController@deleteNumber')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');

            Route::post('upcomingInvoices', 'UpcomingInvoiceController@upcomingInvoices')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('discountAction', 'InvoiceItemController@discountAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('editduedate', 'UpcomingInvoiceController@editduedate')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');

            Route::post('saveAsLead', 'CustomerController@saveAsLead')->middleware('role:ROLE_ADMIN|ROLE_LEAD_WRITE');
            Route::post('paymentSchedule', 'CustomerController@paymentSchedule')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('remoteCompletionEmail', 'CustomerController@remoteCompletionEmail')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('getRemoteCompletionDetails', 'CustomerController@getRemoteCompletionDetails')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('getRemoteAgreementBody', 'CustomerController@getRemoteAgreementBody')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('remoteUpdateCustomer', 'CustomerController@remoteUpdateCustomer')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('customerRemoteCompletionSuccess', 'CustomerController@customerRemoteCompletionSuccess')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('downlaodAgreement', 'CustomerController@downlaodAgreement')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');

            Route::post('exportStartCustomers', 'CustomerController@exportCustomers')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('sendEmailExportedDetails', 'CustomerController@sendEmailExportedDetails')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateAccountStatus', 'CustomerController@updateAccountStatus')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('captureSignature', 'CustomerController@captureSignature')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('viewAgreement', 'CustomerController@viewAgreement')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('regenerateAgreement', 'CustomerController@regenerate_agreement')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateGeocodeDetails', 'CustomerController@updateGeocodeDetails')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('cancelContract', 'CustomerController@confirmCancelContract')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateServiceFrequency', 'CustomerController@updateServiceFrequency')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateContractPricing', 'CustomerController@updateContractPricing')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateContractCommission', 'CustomerController@updateContractCommission')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateServiceType', 'CustomerController@updateContractServiceType')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('createCreditAction', 'CustomerController@createCreditAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::get('downloadQrReportCsv', 'CustomerController@downloadQrReportCsv')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('sendCustomerFormLetter', 'CustomerController@sendCustomerFormLetter')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateServiceAddress', 'CustomerController@updateServiceAddress')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateBillingAddress', 'CustomerController@updateBillingAddress')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateServiceInformation', 'CustomerController@updateServiceInformation')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('customerUploadAttachment', 'CustomerController@customerUploadAttachment')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateCustomerAccountId', 'CustomerController@updateCustomerAccountId')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('checkAccountIdDuplicate', 'CustomerController@checkAccountIdDuplicate')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('updateFirstYearContractValueAction', 'CustomerController@updateFirstYearContractValueAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('addCustomerToExportAction', 'CustomerController@addCustomerToExportAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('apply-discounts/{cust_id}', 'CustomerController@applyDiscounts')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('given-discounts', 'CustomerController@givenDiscounts')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('update-discount/{pdtId}', 'CustomerController@updateDiscount')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('toggle-discount-status/{pdtId}', 'CustomerController@toggleDiscountStatus')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('delete-discount/{pdtId}', 'CustomerController@deleteDiscount')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('addCustomerToExport', 'CustomerController@addCustomerToExport')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('createLocation', 'LocationController@create')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('parentContracts', 'LocationController@parentContracts')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('listLocation', 'LocationController@listLocation')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('resendEmail', 'CustomerController@resendEmail')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('resendEmailBulk', 'CustomerController@resendEmailBulk')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('verifyEmail', 'CustomerController@verifyEmail')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('forceContractRenew', 'CustomerController@forceContractRenew')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('forceStateUpdate', 'CustomerController@forceStateUpdate')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('showAgreementDetails', 'CustomerController@showAgreementDetails')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('createNewContract', 'CustomerController@createNewContract')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('customerAdvanceSearch/filters', 'CustomerController@customerAdvanceSearchFilters')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('customerAdvanceSearch', 'CustomerController@customerAdvanceSearch')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('sendFormLetterAction', 'CustomerController@sendFormLetterAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('mapDetails', 'CustomerController@mapDetails')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('saveDefaultContract', 'CustomerController@saveDefaultContract')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('forceAutoPay', 'CustomerController@forceAutoPay')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('findDuplicateCustomers', 'CustomerController@findDuplicateCustomers')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('showWelcomeLetterDetails', 'CustomerController@showWelcomeLetterDetails')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_ACTIONS');
            Route::post('customerSearch', 'CustomerController@customerSearch')->middleware('role:ROLE_ADMIN');
            Route::post('sendFormSmsAction', 'CustomerController@sendFormSmsAction')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('sendCustomerEmployeeSms', 'CustomerController@sendCustomerEmployeeSms')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('getUnansweredTextMessages', 'CustomerController@getUnansweredTextMessages')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('{custId}/phone-numbers', 'CustomerController@getCustomerPhoneNumbers')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('employee/{couId}/phone-numbers', 'CustomerController@getEmployeePhoneNumbers')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('getCustomerTextMessagesList', 'CustomerController@getCustomerTextMessagesList')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('getEmployeeTextMessagesList', 'CustomerController@getEmployeeTextMessagesList')->middleware('role:ROLE_ADMIN|ROLE_SECRETARY');
            Route::post('markMessageAsReadAction', 'CustomerController@markMessageAsReadAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('{custId}/mark-all-as-read-particular', 'CustomerController@markAsReadAction')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('changeMessageStatus', 'CustomerController@changeMessageStatus')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');

            Route::post('sendBulkEmailToCustomers', 'CustomerController@sendBulkEmailToCustomers')->middleware('role:ROLE_ADMIN');
            Route::post('bulkSaveCustomerTaxCode', 'CustomerController@bulkSaveCustomerTaxCode')->middleware('role:ROLE_ADMIN');
            Route::post('bulkUpdateRecurringPrice', 'CustomerController@bulkUpdateRecurringPrice')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('updateResponsibleAction', 'SubCustomerController@updateResponsibleAction')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('getResponsibleAction', 'SubCustomerController@getResponsibleAction')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('getContractChoiceList', 'SubCustomerController@getContractChoiceList')->middleware('role:ROLE_ADMIN|ROLE_OWNER');
            Route::post('activateCustomerStatus', 'CustomerController@activateCustomerStatus')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('{custId}/job/{jobid}/complete', 'InvoiceController@completeJobAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('payment-account')->group(function () {
            Route::post('create', 'AccountController@create')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'AccountController@list')->middleware('role:ROLE_ADMIN');
            Route::post('edit', 'AccountController@update')->middleware('role:ROLE_ADMIN');
            Route::post('delete', 'AccountController@delete')->middleware('role:ROLE_ADMIN');
            Route::post('creditCreate', 'AccountController@creditCreate')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('public')->group(function () {
            Route::post('{custId}', 'PublicPaymentController@quickPaymentAction')->middleware('role:ROLE_ADMIN');
            Route::post('{custId}/payment-account/create', 'PublicPaymentController@createPublicPaymentAction')->middleware('role:ROLE_ADMIN');
            Route::post('{custId}/submit-payment', 'PublicPaymentController@submitPayment')->middleware('role:ROLE_ADMIN');
            Route::post('{custId}/misc-invoice/{invoiceId}/download', 'PublicPaymentController@miscInvoiceDownloadAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('transaction-history')->group(function () {
            Route::post('transactions', 'TransactionHistoryController@transactions')->middleware('role:ROLE_ADMIN');
            Route::post('customerInvoiceShow', 'InvoiceController@customerInvoiceShow')->middleware('role:ROLE_ADMIN');
            Route::post('refund', 'InvoiceController@refund')->middleware('role:ROLE_ADMIN|ROLE_INVOICE_REFUND');
            Route::post('paymentFailed', 'InvoiceController@paymentFailed')->middleware('role:ROLE_ADMIN');
            Route::post('edit-charge/{transaction_id}', 'InvoiceController@editCharge')->middleware('role:ROLE_ADMIN');
            Route::post('Paymentprocess', 'InvoiceController@Paymentprocess')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
            Route::post('process', 'ReceiveMoneyController@receiveMoney')->middleware('role:ROLE_ADMIN');
            Route::post('verifyPayments', 'InvoiceController@verifyPayments')->middleware('role:ROLE_ADMIN');
            Route::post('recalculateAction', 'InvoiceController@recalculateAction')->middleware('role:ROLE_ADMIN');
            Route::post('cancelAction', 'InvoiceController@cancelAction')->middleware('role:ROLE_ADMIN');
            Route::post('invoicelookup', 'InvoiceController@invoicelookup')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('bills')->group(function () {
            Route::post('list', 'BillController@list')->middleware('role:ROLE_ADMIN');
            Route::post('billinfo', 'BillController@billinfo')->middleware('role:ROLE_ADMIN');
            Route::post('createAction', 'BillController@createAction')->middleware('role:ROLE_ADMIN');
            Route::post('editAction', 'BillController@editAction')->middleware('role:ROLE_ADMIN');
            Route::post('validItemsAction', 'BillController@validItemsAction')->middleware('role:ROLE_ADMIN');
            Route::post('listjobs', 'BillController@listjobs')->middleware('role:ROLE_ADMIN');
            Route::post('listinvoices', 'BillController@listinvoices')->middleware('role:ROLE_ADMIN');
            Route::get('downloadbill', 'BillController@downloadbill')->middleware('role:ROLE_ADMIN');
            Route::post('printcreateAction', 'BillController@printcreateAction')->middleware('role:ROLE_ADMIN');
            Route::post('printeditAction', 'BillController@printeditAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('qr-code-group')->group(function () {
            Route::post('create', 'GroupController@create')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'GroupController@list')->middleware('role:ROLE_ADMIN');
            Route::post('edit', 'GroupController@edit')->middleware('role:ROLE_ADMIN');
            Route::get('delete/{id}', 'GroupController@delete')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('messageboard')->group(function () {
            Route::post('create', 'MessageBoardController@create')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'MessageBoardController@list')->middleware('role:ROLE_ADMIN');
            Route::post('addTask', 'MessageBoardController@addTask')->middleware('role:ROLE_ADMIN');
            Route::post('taskList', 'MessageBoardController@taskList')->middleware('role:ROLE_ADMIN');
            Route::post('taskhistory', 'MessageBoardController@taskhistory')->middleware('role:ROLE_ADMIN');
            Route::post('list', 'MessageBoardController@list')->middleware('role:ROLE_ADMIN');
        });

        // for Send Text
        Route::prefix('send-text')->group(function () {
            Route::post('customer-phones/{custId}', 'SMSController@customerPhoneNumbers')->middleware('role:ROLE_ADMIN');
            Route::post('employees', 'SMSController@employees')->middleware('role:ROLE_ADMIN');
            Route::post('send/{custId}', 'SMSController@sendTextMsg')->middleware('role:ROLE_ADMIN');
        });

        // for Scheduled Services
        Route::prefix('scheduled-services')->group(function () {
            Route::post('list/{custId}', 'ServiceController@showScheduledServicesAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('message')->group(function () {
            Route::post('send', 'SMSController@sendSMS')->middleware('role:ROLE_ADMIN');
            Route::post('{cust_id}/text-employee', 'SMSController@textEmployees')->middleware('role:ROLE_ADMIN');
            Route::post('history', 'SMSController@SmsHistory')->middleware('role:ROLE_ADMIN');
            Route::post('listNumber', 'SMSController@listNumber')->middleware('role:ROLE_ADMIN');
            Route::post('customerAssociatedJobs', 'SMSController@customerAssociatedJobs')->middleware('role:ROLE_ADMIN');

            Route::post('leadsend', 'SMSController@leadSendSMS')->middleware('role:ROLE_ADMIN');
            Route::post('leadhistory', 'SMSController@leadSmsHistory')->middleware('role:ROLE_ADMIN');
            Route::post('leadlistNumber', 'SMSController@leadlistNumber')->middleware('role:ROLE_ADMIN');
        });

        // Quick Add Job
        Route::prefix('quickAddService')->group(function () {
            Route::post('{custId}/create', 'QuickAddServiceController@create')->middleware('role:ROLE_ADMIN');
            Route::post('getBestFitAction', 'QuickAddServiceController@getBestFitAction')->middleware('role:ROLE_ADMIN');
            Route::post('contractList', 'QuickAddServiceController@contractList')->middleware('role:ROLE_ADMIN');
            Route::get('serviceTypes/{id}', 'QuickAddServiceController@serviceTypes')->middleware('role:ROLE_ADMIN');
            Route::get('listServices/{id}', 'QuickAddServiceController@listServices')->middleware('role:ROLE_ADMIN');
            // Route::post('listschduleServices', 'QuickAddServiceController@listschduleServices')->middleware('role:ROLE_ADMIN');
            Route::post('addDiscuount', 'QuickAddServiceController@addDiscuount')->middleware('role:ROLE_ADMIN');
            Route::get('paymentDetails/{id}', 'QuickAddServiceController@paymentDetails')->middleware('role:ROLE_ADMIN');
            Route::post('editInvoiceItem', 'QuickAddServiceController@editInvoiceItem')->middleware('role:ROLE_ADMIN');
            Route::get('deleteInvoiceItem/{id}', 'QuickAddServiceController@deleteInvoiceItem')->middleware('role:ROLE_ADMIN');
            Route::get('cancelInvoice/{id}', 'QuickAddServiceController@cancelInvoice')->middleware('role:ROLE_ADMIN');
            Route::post('addInvoiceItem', 'QuickAddServiceController@addInvoiceItem')->middleware('role:ROLE_ADMIN');
            Route::post('editNote', 'QuickAddServiceController@editNote')->middleware('role:ROLE_ADMIN');
            Route::post('noteDetails', 'QuickAddServiceController@noteDetails')->middleware('role:ROLE_ADMIN');
            Route::post('bulkNewAction', 'InvoiceItemController@bulkNewAction')->middleware('role:ROLE_ADMIN');
            Route::post('editBulk', 'InvoiceItemController@editBulk')->middleware('role:ROLE_ADMIN');
            Route::post('listinvoices', 'InvoiceItemController@listinvoices')->middleware('role:ROLE_ADMIN');
            Route::post('editService', 'QuickAddServiceController@editService')->middleware('role:ROLE_ADMIN');
            Route::post('removeHardScheduleAction', 'ServiceController@removeHardScheduleAction')->middleware('role:ROLE_ADMIN');
            Route::post('updateHardScheduleAction', 'ServiceController@updateHardScheduleAction')->middleware('role:ROLE_ADMIN');

            Route::post('findRoutesByDay', 'ServiceController@findRoutesByDay')->middleware('role:ROLE_ADMIN|ROLE_CUSTOMER_WRITE');
        });

        Route::prefix('quick-invoice')->group(function () {
            Route::post('quickchargeCreate', 'QuickChargeController@quickchargeCreate')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('upcoming-invoice')->group(function () {
            Route::post('list', 'QuickInvoiceController@list')->middleware('role:ROLE_ADMIN');
            Route::post('edit', 'QuickInvoiceController@edit')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('receive-money')->group(function () {
            Route::post('createAction/{custId}', 'ReceiveMoneyController@createAction')->middleware('role:ROLE_ADMIN');
            Route::post('listjobs', 'ReceiveMoneyController@listjobs')->middleware('role:ROLE_ADMIN');
            Route::post('listinvoices', 'ReceiveMoneyController@listinvoices')->middleware('role:ROLE_ADMIN');
            Route::post('listpayments', 'ReceiveMoneyController@listpayments')->middleware('role:ROLE_ADMIN');
        });

        /** Service invoice routes */
        Route::prefix('service-invoice')->group(function () {
            Route::post('list', 'ServiceInvoiceController@list')->middleware('role:ROLE_ADMIN');
            Route::post('get/{job_id}', 'ServiceInvoiceController@get')->middleware('role:ROLE_ADMIN');
            Route::post('start/{customer_id}', 'ServiceInvoiceController@startService')->middleware('role:ROLE_ADMIN');
            Route::post('get-form-data', 'ServiceInvoiceController@getFormData')->middleware('role:ROLE_ADMIN');
            Route::post('previous-chem/{custId}', 'ServiceInvoiceController@previousChemical')->middleware('role:ROLE_ADMIN');
            Route::post('get-weather/{customer_id}', 'ServiceInvoiceController@getWeather')->middleware('role:ROLE_ADMIN');
            Route::post('save-complete/{job_id}', 'ServiceInvoiceController@saveAndComplete')->middleware('role:ROLE_ADMIN');
            Route::post('update-changes', 'ServiceInvoiceController@updateChanges')->middleware('role:ROLE_ADMIN');
            Route::post('remove-chemsheets', 'ServiceInvoiceController@removeChemsheets')->middleware('role:ROLE_ADMIN');
            Route::post('charge-complete/{job_id}', 'ServiceInvoiceController@chargeAndCompleteJobAction')->middleware('role:ROLE_ADMIN');
            Route::post('enroute-sms/{job_id}', 'ServiceInvoiceController@enrouteSms')->middleware('role:ROLE_ADMIN');
            Route::post('resend-mail/get/{custId}', 'ServiceInvoiceController@resendMailForm')->middleware('role:ROLE_ADMIN');
            Route::post('items/{job_id}', 'ServiceInvoiceController@invoiceItems')->middleware('role:ROLE_ADMIN');
            Route::post('update-item/{itemId}', 'ServiceInvoiceController@updateinvoiceItem')->middleware('role:ROLE_ADMIN');
            //CustomerController@resendEmail
        });

        Route::prefix('download')->group(function () {
            Route::get('serviceRecordAction', 'DownloadController@serviceRecordAction')->middleware('role:ROLE_ADMIN');
            Route::get('invoiceAction', 'DownloadController@invoiceAction')->middleware('role:ROLE_ADMIN');
            Route::get('miscInvoiceAction', 'DownloadController@miscInvoiceAction')->middleware('role:ROLE_ADMIN');
            Route::get('estimateDownloadCustomerAction/{customer_id}/{estimate_id}/{print}', 'DownloadController@estimateDownloadCustomerAction')->middleware('role:ROLE_ADMIN');
            Route::get('customerPaymentHistoryDownloadAction/{customer_id}', 'DownloadController@customerPaymentHistoryDownloadAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('service-history')->group(function () {
            Route::post('showServiceHistoryAction', 'ServiceController@showServiceHistoryAction')->middleware('role:ROLE_ADMIN');
            Route::post('paidServiceHistorySummaryStart', 'ServiceController@paidServiceHistorySummaryStart')->middleware('role:ROLE_ADMIN');
            Route::get('getCustomerServiceHistorySummaryAction', 'ServiceController@getCustomerServiceHistorySummaryAction')->middleware('role:ROLE_ADMIN');
            Route::get('getCustomerServiceHistorySummaryActionImproved', 'ServiceController@getCustomerServiceHistorySummaryActionImproved')->middleware('role:ROLE_ADMIN');
            Route::post('serviceHistoryAction/{custId}', 'DownloadController@serviceHistoryAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('invoice-history')->group(function () {
            Route::post('showInvoiceHistoryAction', 'InvoiceController@showInvoiceHistoryAction')->middleware('role:ROLE_ADMIN');
            Route::get('invoiceHistoryAction', 'InvoiceController@invoiceHistoryAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('email-history')->group(function () {
            Route::post('indexAction', 'EmailHistoryController@indexAction')->middleware('role:ROLE_ADMIN');
            Route::post('showAction', 'EmailHistoryController@showAction')->middleware('role:ROLE_ADMIN');
            Route::post('resendAction', 'EmailHistoryController@resendAction')->middleware('role:ROLE_ADMIN');
            Route::post('leadindexAction', 'EmailHistoryController@leadindexAction')->middleware('role:ROLE_ADMIN');
            Route::post('leadshowAction', 'EmailHistoryController@leadshowAction')->middleware('role:ROLE_ADMIN');
            Route::post('leadresendAction', 'EmailHistoryController@leadresendAction')->middleware('role:ROLE_ADMIN');
        });

        Route::prefix('activity-history')->group(function () {
            Route::post('indexAction', 'ActivityHistoryController@indexAction')->middleware('role:ROLE_ADMIN');
            Route::post('leadindexAction', 'ActivityHistoryController@leadindexAction')->middleware('role:ROLE_ADMIN');
        });

        /** estimate routes */
        Route::prefix('customerEstimate')->group(function () {
            Route::post('newAction', 'CustomerEstimateController@newAction')->middleware('role:ROLE_ADMIN');
            Route::post('taxcodelist', 'CustomerEstimateController@taxcodelist')->middleware('role:ROLE_ADMIN');
            Route::post('itemlist', 'CustomerEstimateController@itemlist')->middleware('role:ROLE_ADMIN');
            Route::post('servicelist', 'CustomerEstimateController@servicelist')->middleware('role:ROLE_ADMIN');
            Route::post('indexAction', 'CustomerEstimateController@indexAction')->middleware('role:ROLE_ADMIN');
            Route::post('updateStatusAction', 'CustomerEstimateController@updateStatusAction')->middleware('role:ROLE_ADMIN');
            Route::post('deleteEstimateAction', 'CustomerEstimateController@deleteEstimateAction')->middleware('role:ROLE_ADMIN');
            Route::post('updateAction', 'CustomerEstimateController@updateAction')->middleware('role:ROLE_ADMIN');
            Route::post('sendEstimateAction', 'CustomerEstimateController@sendEstimateAction')->middleware('role:ROLE_ADMIN');
        });
    });

    Route::post('logout', 'API\AuthController@logout');
    Route::post('getUserDetails', 'API\AuthController@getUserDetails');
    Route::post('getRoleGroups', 'API\AuthController@getRoleGroups');
    Route::post('getSystemAllRoles', 'API\AuthController@getSystemAllRoles');

    /**Sales person panel profile APIs */
    Route::post('getStatisticsReportDetails', 'API\Pocomos\SalesTracker\SalesPeopleController@getStatisticsReportDetails')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
    Route::post('getStatisticsDetails', 'API\Pocomos\SalesTracker\SalesPeopleController@getStatisticsDetails')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
    Route::post('editSalesProfile', 'API\Pocomos\SalesTracker\SalesPeopleController@editSalesProfile')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
    Route::post('getSalesProfile', 'API\Pocomos\SalesTracker\SalesPeopleController@getSalesProfile')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
    Route::post('saveAndGetCalculatorDetail', 'API\Pocomos\SalesTracker\SalesPeopleController@saveAndGetCalculatorDetail')->middleware('role:ROLE_ADMIN|ROLE_COMMISSION_REPORT');
    Route::post('salesPersonSpots', 'API\Pocomos\SalesTracker\SalesPeopleController@salesPersonSpots')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
    Route::post('regenerate-report-data/{ouId}', 'API\Pocomos\SalesTracker\SalesPeopleController@regenerateStateAction')->middleware('role:ROLE_ADMIN|ROLE_SALESPERSON');
    /**End sales person panel profile APIs */

    Route::post('getUserGroupRolesAndOffices/{id}', 'API\UserController@getUserGroupRolesAndOffices');
    Route::post('switchSalesPersonOrAdminView', 'API\UserController@switchSalesPersonOrAdminView')->middleware('role:ROLE_ADMIN|ROLE_SUPPRESS_SALESPERSON_VIEW|ROLE_SECRETARY');

    Route::post('admin/companies/{id}/exportTransactions', 'API\Pocomos\Admin\OfficeExportController@exportTransactions')->middleware('role:ROLE_ADMIN');
    Route::post('admin/companies/{id}/exportUnpaidJobsOrInvoices', 'API\Pocomos\Admin\OfficeExportController@exportUnpaidJobsOrInvoices')->middleware('role:ROLE_ADMIN');
    Route::post('admin/companies/{id}/exportAgreements', 'API\Pocomos\Admin\OfficeExportController@exportAgreements')->middleware('role:ROLE_ADMIN');
    Route::post('admin/companies/{id}/deactivateAllCustomers', 'API\Pocomos\Admin\OfficeExportController@deactivateAllCustomers')->middleware('role:ROLE_ADMIN');
});
