<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
Route::get('/', function () {
    return view('auth.login');
});

/***
Route::get('/crm-permissions', function () {
    return config('crm-permissions');

    // fetch mac address test code
    ob_start(); // Turn on output buffering
    system('ifconfig');
    $mycom=ob_get_contents();
    ob_clean();
    $findme = "Physical";

    $pmac = strpos($mycom, $findme);
    $mac=substr($mycom,($pmac+36),17);
    echo $mac;
});
*/
Route::GET('send-emails-crons', 'Administrator\ApplicantMessageController@sendEmailsWithCrons');

Route::middleware('ip_address')->group(function () {
    Auth::routes();

	Route::get('/clear-cache', function() {
		//Artisan::call('schedule:work');
		//Artisan::call('emails:send');
	   	//Artisan::call('cache:clear');  
		// Artisan::call('config:clear');  
		//Artisan::call('config:cache');
		//Artisan::call('route:clear');
		//Artisan::call('optimize:clear');
    	return "Cache cleared successfully!";
	});
    /*** Roles and permission routes */
    Route::resource('roles', 'Administrator\RoleController');
    Route::resource('user-roles', 'Administrator\UserRoleController');
    Route::get('/search','Administrator\RoleController@office_search');
    Route::get('/office_create','Administrator\RoleController@office_create')->name('roles.office_create');
    Route::post('/office_store','Administrator\RoleController@office_store')->name('roles.office_store');
    Route::patch('/update_office/{id}','Administrator\RoleController@update_office')->name('roles.update_office');
	//    Route::get('seeding', 'Administrator\SeedController@storePermissions');
	Route::get('test1','Administrator\ResourceController@test1')->name('test1');

    /*** dashboard routes */
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/daily-stats/{date}', 'HomeController@dailyStats')->name('daily-stats');
    Route::get('userTest', 'HomeController@userTest')->name('userTest');
    Route::get('/weekly-stats/{sdate}/{edate}', 'HomeController@weeklyStats')->name('weekly-stats');
    Route::get('/monthly-stats/{month}/{year}', 'HomeController@monthlyStats')->name('monthly-stats');
    Route::get('/custom-stats/{month}/{year}', 'HomeController@customStats')->name('custom-stats');
    Route::get('/callback-applicants', 'HomeController@callbackApplicants')->name('callback-applicants');
    Route::post('/get-callback-applicants', 'HomeController@getCallbackApplicants')->name('get-callback-applicants');
    Route::post('/user-statistics', 'HomeController@userStatistics')->name('userStatistics');
	Route::GET('/applicant-details-stats', 'HomeController@applicantDetailStats')->name('applicant_details_stats');
	Route::GET('/applicant-home-details-stats', 'HomeController@applicantHomeDetailStats')->name('applicant_home_details_stats');

	/*** for temporary data **/
	Route::get('/temporary-data', 'Administrator\ApplicantController@temporaryDataIndex')->name('temporary_data');
    Route::post('import-temporary-data', 'Administrator\ApplicantController@importTemporaryData')->name('temporary_data_import');
    Route::get('getTemporaryData', 'Administrator\ApplicantController@getTemporaryData');
    Route::post('update-temporary-data-notes', 'Administrator\ApplicantController@update_temporary_data_notes')->name('update_temporary_data_notes');

    /*** user module routes */
    Route::resource('/users', 'Administrator\UsersController');
    Route::post('/assign-role-to-users', 'Administrator\UsersController@assignRoleToUsers')->name('assign-role-to-users');

    Route::get('/change-user-status/{id}', 'Administrator\UsersController@getUserStatusChange')->name('userStatus');
    Route::resource('/applicants', 'Administrator\ApplicantController');
	    Route::get('getIdleApplicants', 'Administrator\ApplicantController@getIdleApplicants');
	Route::get('getIdleApplicantsNonNurse', 'Administrator\ApplicantController@getIdleApplicantsNonNurse');
	    Route::get('getIdleApplicantsNonNurseSpecialist', 'Administrator\ApplicantController@getIdleApplicantsNonNurseSpecialist');

    Route::get('getApplicants', 'Administrator\ApplicantController@getApplicants');
	Route::PATCH('applicants_update/{id}', 'Administrator\ApplicantController@update')->name('applicants.update.applicant');

    // Route::get('getUsersList', 'ApplicantController@getuserslist')->name('applicantList');
    Route::get('non-interested-applicants','Administrator\ApplicantController@getNonInterestedApplicants')->name('nonInterestedApplicants');
	Route::get('export-non-interested-last-applicants','Administrator\ApplicantController@exportNonInterestedLastApplicants')->name('export_non_interested_last_applicants_cv');
    Route::get('getNonInterestAppAjax','Administrator\ApplicantController@getNonInterestAppAjax');
	Route::get('export_csv','Administrator\ApplicantController@export_csv')->name('applicants.export_csv');
	    Route::get('export_idel_applicants','Administrator\ApplicantController@idelApplicantExport')->name('export_idel_applicants');
	    Route::get('export_idel_applicants_specialist','Administrator\ApplicantController@idelSpecialistApplicantExport')->name('export_idel_applicants_specialist');
	
	Route::get('run-manual','Administrator\ApplicantController@runManual');
    Route::post('export','Administrator\ApplicantController@export')->name('export');
	Route::get('exportApplicantsWithNoLatLng', 'Administrator\ApplicantController@exportApplicantsWithNoLatLng')->name('exportApplicantsWithNoLatLng');
	Route::get('exportSalesWithNoLatLng', 'Administrator\SaleController@exportSalesWithNoLatLng')->name('exportSalesWithNoLatLng');
	Route::get('exportHeadOfficesWithNoLatLng', 'Administrator\OfficeController@exportHeadOfficesWithNoLatLng')->name('exportHeadOfficesWithNoLatLng');
	
	Route::post('idle-applicants-export','Administrator\ApplicantController@exportIdelApplicants')->name('idle_applicants_export');
    Route::post('applicant-csv-file','Administrator\ApplicantController@getUploadApplicantCsv')->name('applicantCsv');
	Route::post('import-applicant-cv-file','Administrator\ApplicantController@UploadApplicantCV')->name('import_applicantCv');
    Route::post('office-csv-file','Administrator\OfficeController@getUploadOfficeCsv')->name('officeCsv');
    Route::post('unit-csv-file','Administrator\UnitController@getUploadUnitCsv')->name('unitCsv');
	Route::post('email-csv-file','Administrator\UnitController@getUploadEmailCsv')->name('emailCsv');
	Route::get('export_units_email','Administrator\UnitController@export_email')->name('units.export_email');
	Route::get('download-applicant-cv/{cv_id}','Administrator\ApplicantController@getDownloadApplicantCv')->name('downloadApplicantCv');
    Route::get('download-updated-applicant-cv/{cv_id}','Administrator\ApplicantController@getUpdatedDownloadApplicantCv')->name('downloadUpdatedApplicantCv');
    Route::post('sale-csv-file','Administrator\SaleController@getUploadSaleCsv')->name('saleCsv');
    Route::get('nurse-home-applicants','Administrator\ApplicantController@getNurseHomeApplicants')->name('nurseHomeApplicants');
	Route::get('export-nurse-home-applicants','Administrator\ApplicantController@exportNurseHomeApplicants')->name('export_nursing_home_applicants_cv');
    Route::get('getNurseHomeApplicantsAjax','Administrator\ApplicantController@getNurseHomeApplicantsAjax');
    Route::get('applicant-history/{applicant__history_id}','Administrator\ApplicantController@getApplicantHistory')->name('applicantHistory');
    Route::get('applicant-full-history/{sale_id}/{applicant_id}','Administrator\ApplicantController@getApplicantFullHistory')->name('applicantHistoryFull');
    Route::get('sent-to-nurse-home','Administrator\ApplicantController@getNurseHomeApplicant')->name('sentToNurseHome');
    Route::get('sent-to-nurse-home-from-days','Administrator\ApplicantController@getNurseHomeApplicantFromDays')->name('sentToNurseHomeFromDays');
    Route::get('revert-nurse-home-applicant','Administrator\ApplicantController@nurse-home-applicants')->name('revertNurseApplicant');
    Route::get('applicant-cv-to-quality/{applicant_cv_id}','Administrator\ApplicantController@getApplicantCvSendToQuality')->name('sendCV');
    Route::get('nurse-home-note','Administrator\ApplicantController@getApplicantSendToNurseHome')->name('nurseHomeNote');
    Route::resource('/clients', 'Administrator\ClientController');
    Route::resource('/offices', 'Administrator\OfficeController');
    Route::get('getOffices', 'Administrator\OfficeController@getOffices');
    Route::resource('/units', 'Administrator\UnitController');
    Route::get('getUnits','Administrator\UnitController@getUnits');
    Route::post('revert-applicants', 'Administrator\ApplicantController@revertApplicants')->name('revert-applicants');
	    Route::get('idle-applicants', 'Administrator\ApplicantController@idle')->name('idle_applicants');
	Route::get('idle-applicants-non-nurse', 'Administrator\ApplicantController@idleNonNurse')->name('idle_applicants_non_nurse');
	    Route::get('idle-applicants-non-nurse-specialist', 'Administrator\ApplicantController@idleNonNurseSpecialist')->name('idle_applicants_non_nurse_specialist');
	Route::get('no-job-applicants', 'Administrator\ApplicantController@getNoJobApplicants')->name('get_no_job_applicants');
    Route::get('get_no_job_applicants_ajax', 'Administrator\ApplicantController@getNoJobApplicantsAjax');
	Route::get('no-responsed-applicants', 'Administrator\ApplicantController@getNoResponsedApplicants')->name('get_no_responsed_applicants');
    Route::get('get_no_responsed_applicants_ajax', 'Administrator\ApplicantController@getNoResponsedApplicantsAjax');
	Route::POST('no-responed-revert-all', 'Administrator\ApplicantController@noResponedRevertAll')->name('noResponedRevertAll');
	Route::get('email-templates', 'Administrator\ApplicantController@emailTemplates')->name('emailTemplates');
    Route::post('send-app-email', 'Administrator\ApplicantController@sendAppGenEmail')->name('send_app_email');
	Route::post('send-app-random-email', 'Administrator\ApplicantController@sendAppRandomEmail')->name('send_app_random_email');
	
	// Not updated applicants routes
    Route::get('export_not_update_csv/{category}','Administrator\ApplicantController@exportNotUpdateCsv')->name('applicants.export_not_update_csv');
    Route::post('export_not_updated_applicants','Administrator\ApplicantController@exportNotUpdatedApplicants')->name('export_not_updated_applicants');
	Route::post('/send-applicant-email', 'Administrator\ApplicantController@sendApplicantEmail')->name('send_email');

    /*** Sale routes */
    Route::resource('/sales', 'Administrator\SaleController'); //, ['except' => 'update']);
    Route::post('user-offices', 'Administrator\SaleController@userOffices')->name('userOffices');
    Route::post('setUnitID', 'Administrator\SaleController@setSessionUnitID')->name('setUnitID');
    Route::get('getUnitID', 'Administrator\SaleController@getSessionUnitID')->name('getUnitID');
    Route::post('setTitleID', 'Administrator\SaleController@setSessionTitleID')->name('setTitleID');
    Route::get('getTitleID', 'Administrator\SaleController@getSessionTitleID')->name('getTitleID');
    Route::get('getSales/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getSales');
    Route::get('sale-history/{sale__history_id}','Administrator\SaleController@getSaleHistory')->name('saleHistory');
    Route::get('sale-full-history/{applicant_id}/{sale_id}','Administrator\SaleController@getSaleFullHistory')->name('saleFullHistory');
	Route::delete('/sales/removeAttachment/{sale}', 'Administrator\SaleController@removeAttachment')->name('sales.removeAttachment');
	Route::get('/get-job-attachments/{jobId}', 'Administrator\SaleController@getAttachments')->name('getJobAttachments');
	
	    /****** direct sales */
    Route::get('sales/direct/nurse-sales', 'Administrator\SaleController@directNurseSales')->name('nurse-sales');
    Route::get('sales/direct/non-nurse-sales', 'Administrator\SaleController@directNonNurseSales')->name('non-nurse-sales');
    Route::get('sales/direct/specialist-sales', 'Administrator\SaleController@directSpecialistSales')->name('specialist-sales');
    Route::get('sales/direct/chef-sales', 'Administrator\SaleController@directChefSales')->name('chef-sales');
    Route::get('sales/direct/nursery-sales', 'Administrator\SaleController@directNurserySales')->name('nursery-sales');
    Route::get('getDirectActiveSalesAjax/{job_category?}', 'Administrator\SaleController@getDirectActiveSalesAjax');

	
//    Route::POST('close-or-open-sale','Administrator\SaleController@getCloseOrOpenSale')->name('closeOrOpenSale');
    Route::POST('close-sale','Administrator\SaleController@getCloseSale')->name('closeSale');
	Route::POST('on-hold-sale','Administrator\SaleController@onHoldSale')->name('onHoldSale');
	Route::POST('un-hold-sale','Administrator\SaleController@unHoldSale')->name('unHoldSale');
    Route::POST('open-sale','Administrator\SaleController@getOpenSale')->name('openSale');
    Route::get('all-psl-client-sale','Administrator\SaleController@getAllPslClientSale')->name('psl');
    Route::get('all-psl-client-sale/{id}','Administrator\SaleController@getAllPslUnitDetails')->name('pslClientUnitDetails');
    Route::get('all-non-psl-client-sale','Administrator\SaleController@getAllNonPslClientSale')->name('nonpsl');
    Route::get('all-non-psl-client-sale/{id}','Administrator\SaleController@getAllNonPslUnitDetails')->name('nonPslClientUnitDetails');
    
	Route::get('all-on-hold-sales','Administrator\SaleController@getOnHoldSales')->name('on_hold_sales_data');
	Route::get('get-all-on-hold-sales/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getAllOnHoldSales');
	Route::get('onhold-sale-history/{sale_id}','Administrator\SaleController@getSaleHistory')->name('onhold-sale-history');
	Route::get('pending-onhold-sales','Administrator\SaleController@pendingApprovalOnHoldSales')->name('pending.onhold_sales');
  						Route::get('getPendingApprovalOnHoldSales/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getPendingApprovalOnHoldSales');
	Route::get('approve-on-hold-sale/{id}/{status}','Administrator\SaleController@approveOnHoldSale')->name('approve.onHold_sale');
	Route::post('update-closed-sales-notes','Administrator\SaleController@updateClosedSalesNote')->name('updateClosedSalesNote');
	Route::get('closed-sales','Administrator\SaleController@allClosedSales');
	Route::get('export_closed_sales_email','Administrator\SaleController@export_email')->name('sales.export_email');
	Route::get('export_open_sales_email','Administrator\SaleController@export_open_sales_email')->name('sales.export_open_sales_email');

	 /** Last 21days open sales ***/
    Route::get('last-21-days-open-sales','Administrator\SaleController@last21DaysopenSales')->name('last-21-days-open-sales');
    Route::get('getLast21daysOpenSalesAjax/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getLast21daysOpenSalesAjax');

    /** Last 3 months open sales ***/
    Route::get('last-3-months-open-sales','Administrator\SaleController@last3MonthsopenSales')->name('last-3-months-open-sales');
    Route::get('getLast3monthsOpenSalesAjax/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getLast3monthsOpenSalesAjax');

    /** Last 6 months open sales **/
    Route::get('last-6-months-open-sales','Administrator\SaleController@last6MonthsopenSales')->name('last-6-months-open-sales');
    Route::get('getLast6monthsOpenSalesAjax/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getLast6monthsOpenSalesAjax');

    /** Last 12 months open sales ***/
    Route::get('last-12-months-open-sales','Administrator\SaleController@last12MonthsopenSales')->name('last-12-months-open-sales');
    Route::get('getLast12monthsOpenSalesAjax/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getLast12monthsOpenSalesAjax');

    /** 12 months old open sales ***/
    Route::get('12-months-old-open-sales','Administrator\SaleController@old12MonthsopenSales')->name('12-months-old-open-sales');
    Route::get('getOld12monthsOpenSalesAjax/{job_category?}/{user?}/{office?}/{cv_sent_option?}','Administrator\SaleController@getOld12monthsOpenSalesAjax');
	
	// All Closed Sales
	Route::get('all-closed-sales','Administrator\SaleController@getAllClosedSales')->name('close_sales');
	Route::get('closed-sales-nurse','Administrator\SaleController@allClosedSalesNurse')->name('closed-sales-nurse');
    Route::get('closed-sales-nonnurse','Administrator\SaleController@allClosedSalesNonNurse')->name('closed-sales-nonnurse');
    Route::get('closed-sales-specialist','Administrator\SaleController@allClosedSalesSpecialist')->name('closed-sales-specialist');
    Route::get('filter-closed-sales-nurse','Administrator\SaleController@allClosedSalesNurseFilter')->name('filter-closed-sales-nurse');
    Route::get('filter-closed-sales-nonnurse','Administrator\SaleController@allClosedSalesNonNurseFilter')->name('filter-closed-sales-nonnurse');
    Route::get('filter-closed-sales-specialist','Administrator\SaleController@allClosedSalesSpecialistFilter')->name('filter-closed-sales-specialist');
	
	//Last 21days closed sales
    Route::get('last-21-days-closed-sales','Administrator\SaleController@getLast21DaysClosedSales')->name('last_21days_close_sales');
    Route::get('last-21days-closed-sales-nurse-ajax','Administrator\SaleController@last21daysClosedSalesNurseAjax')->name('last-21days-closed-sales-nurse');
    Route::get('last-21days-closed-sales-nonnurse-ajax','Administrator\SaleController@last21daysClosedSalesNonnurseAjax')->name('last-21days-closed-sales-nonnurse');
    Route::get('last-21days-closed-sales-specialist-ajax','Administrator\SaleController@last21daysClosedSalesSpecialistAjax')->name('last-21days-closed-sales-specialist');
    Route::get('last-21days-filter-closed-sales-nurse','Administrator\SaleController@last21daysClosedSalesNurseFilter')->name('last-21days-filter-closed-sales-nurse');
    Route::get('last-21days-filter-closed-sales-nonnurse','Administrator\SaleController@last21daysClosedSalesNonNurseFilter')->name('last-21days-filter-closed-sales-nonnurse');
    Route::get('last-21days-filter-closed-sales-specialist','Administrator\SaleController@last21daysClosedSalesSpecialistFilter')->name('last-21days-filter-closed-sales-specialist');
    
    //Last 3 months closed sales
    Route::get('last-3-months-closed-sales','Administrator\SaleController@getLast3MonthsClosedSales')->name('last_3months_close_sales');
    Route::get('last-3months-closed-sales-nurse-ajax','Administrator\SaleController@last3monthsClosedSalesNurseAjax')->name('last-3months-closed-sales-nurse');
    Route::get('last-3months-closed-sales-nonnurse-ajax','Administrator\SaleController@last3monthsClosedSalesNonnurseAjax')->name('last-3months-closed-sales-nonnurse');
    Route::get('last-3months-closed-sales-specialist-ajax','Administrator\SaleController@last3monthsClosedSalesSpecialistAjax')->name('last-3months-closed-sales-specialist');
    Route::get('last-3months-filter-closed-sales-nurse','Administrator\SaleController@last3monthsClosedSalesNurseFilter')->name('last-3months-filter-closed-sales-nurse');
    Route::get('last-3months-filter-closed-sales-nonnurse','Administrator\SaleController@last3monthsClosedSalesNonNurseFilter')->name('last-3months-filter-closed-sales-nonnurse');
    Route::get('last-3months-filter-closed-sales-specialist','Administrator\SaleController@last3monthsClosedSalesSpecialistFilter')->name('last-3months-filter-closed-sales-specialist');
    
    //Last 6 months closed sales
    Route::get('last-6-months-closed-sales','Administrator\SaleController@getLast6MonthsClosedSales')->name('last_6months_close_sales');
    Route::get('last-6months-closed-sales-nurse-ajax','Administrator\SaleController@last6monthsClosedSalesNurseAjax')->name('last-6months-closed-sales-nurse');
    Route::get('last-6months-closed-sales-nonnurse-ajax','Administrator\SaleController@last6monthsClosedSalesNonnurseAjax')->name('last-6months-closed-sales-nonnurse');
    Route::get('last-6months-closed-sales-specialist-ajax','Administrator\SaleController@last6monthsClosedSalesSpecialistAjax')->name('last-6months-closed-sales-specialist');
    Route::get('last-6months-filter-closed-sales-nurse','Administrator\SaleController@last6monthsClosedSalesNurseFilter')->name('last-6months-filter-closed-sales-nurse');
    Route::get('last-6months-filter-closed-sales-nonnurse','Administrator\SaleController@last6monthsClosedSalesNonNurseFilter')->name('last-6months-filter-closed-sales-nonnurse');
    Route::get('last-6months-filter-closed-sales-specialist','Administrator\SaleController@last6monthsClosedSalesSpecialistFilter')->name('last-6months-filter-closed-sales-specialist');
    
    //Last 12 months closed sales
    Route::get('last-12-months-closed-sales','Administrator\SaleController@getLast12MonthsClosedSales')->name('last_12months_close_sales');
    Route::get('last-12months-closed-sales-nurse-ajax','Administrator\SaleController@last12monthsClosedSalesNurseAjax')->name('last-12months-closed-sales-nurse');
    Route::get('last-12months-closed-sales-nonnurse-ajax','Administrator\SaleController@last12monthsClosedSalesNonnurseAjax')->name('last-12months-closed-sales-nonnurse');
    Route::get('last-12months-closed-sales-specialist-ajax','Administrator\SaleController@last12monthsClosedSalesSpecialistAjax')->name('last-12months-closed-sales-specialist');
    Route::get('last-12months-filter-closed-sales-nurse','Administrator\SaleController@last12monthsClosedSalesNurseFilter')->name('last-12months-filter-closed-sales-nurse');
    Route::get('last-12months-filter-closed-sales-nonnurse','Administrator\SaleController@last12monthsClosedSalesNonNurseFilter')->name('last-12months-filter-closed-sales-nonnurse');
    Route::get('last-12months-filter-closed-sales-specialist','Administrator\SaleController@last12monthsClosedSalesSpecialistFilter')->name('last-12months-filter-closed-sales-specialist');
	
	/*** 12 months old closed sales ***/
    Route::get('12-months-old-closed-sales','Administrator\SaleController@get12MonthsOldClosedSales')->name('12months_old_close_sales');
    Route::get('12months-old-closed-sales-nurse-ajax','Administrator\SaleController@old12monthsClosedSalesNurseAjax')->name('12months-old-closed-sales-nurse');
    Route::get('12months-old-closed-sales-nonnurse-ajax','Administrator\SaleController@old12monthsClosedSalesNonnurseAjax')->name('12months-old-closed-sales-nonnurse');
    Route::get('12months-old-closed-sales-specialist-ajax','Administrator\SaleController@old12monthsClosedSalesSpecialistAjax')->name('12months-old-closed-sales-specialist');
    Route::get('12months-old-filter-closed-sales-nurse','Administrator\SaleController@old12monthsClosedSalesNurseFilter')->name('12months-old-filter-closed-sales-nurse');
    Route::get('12months-old-filter-closed-sales-nonnurse','Administrator\SaleController@old12monthsClosedSalesNonNurseFilter')->name('12months-old-filter-closed-sales-nonnurse');
    Route::get('12months-old-filter-closed-sales-specialist','Administrator\SaleController@old12monthsClosedSalesSpecialistFilter')->name('12months-old-filter-closed-sales-specialist');
	
	
    Route::get('all-open-sales-notes/{open_sale_note_id}','Administrator\SaleController@getAllOpenedSalesNotes')->name('viewAllOpenNotes');
    Route::get('all-close-sales-notes/{close_sale_note_id}','Administrator\SaleController@getAllClosedSalesNotes')->name('viewAllCloseNotes');
    Route::get('direct-nurse-resource','Administrator\ResourceController@getNurseSales')->name('getDirectNurse');
    Route::get('getNursingJob','Administrator\ResourceController@getNursingJob');
    Route::get('getNonNursingJob','Administrator\ResourceController@getNonNursingJob');
	    Route::get('getNonNursingSpecialistJob','Administrator\ResourceController@getNonNursingSpecialistJob');
    Route::get('direct-non-nurse-resource','Administrator\ResourceController@getNonNurseSales')->name('getDirectNonNurse');
	    Route::get('direct-non-nurse-specialist-resource','Administrator\ResourceController@getNonNurseSpecialistSales')->name('getDirectNonNurseSpecialist');
	
	/**** Indirect Resources */
    Route::get('resources/indirect/nurse-resource', 'Administrator\ResourceController@getIndirectNurseResource')->name('getIndirectNurseResource');
    Route::get('resources/indirect/non-nurse-resource', 'Administrator\ResourceController@getIndirectNonNurseResource')->name('getIndirectNonNurseResource');
    Route::get('resources/indirect/specialist-resource', 'Administrator\ResourceController@getIndirectSpecialistResource')->name('getIndirectSpecialistResource');
    Route::get('resources/indirect/chef-resource', 'Administrator\ResourceController@getIndirectChefResource')->name('getIndirectChefResource');
    Route::get('resources/indirect/nursery-resource', 'Administrator\ResourceController@getIndirectNurseryResource')->name('getIndirectNurseryResource');
    Route::get('getIndirectResourcesAjax/{category?}/{status?}', 'Administrator\ResourceController@getIndirectResourcesAjax');
	/**** Indirect Resources */	

    Route::get('potential-call-back-applicants','Administrator\ResourceController@potentialCallBackApplicants')->name('potential-call-back-applicants');
	Route::get('export-potential-call-back-applicants','Administrator\ResourceController@exportPotentialCallBackApplicants')->name('export_callback_applicants_cv');
    Route::get('get-call-back-applicants','Administrator\ResourceController@getPotentialCallBackApplicants')->name('get-call-back-applicants');
    Route::get('sent-applicant-to-call-back-list','Administrator\ResourceController@getApplicantSentToCallBackList')->name('sentToCallBackList');
    //Route::get('sent-applicant-to-call-back-list-from-days','Administrator\ResourceContactive-applicants-within-25-kmToCallBackListFromDays')->name('sentToCallBackListFromDays');
    Route::get('revert-applicant-to-search-list','Administrator\ResourceController@getApplicantRevertToSearchList')->name('revertCallBackApplicants');
    Route::get('applicants-within-15-km/{id}/{radius?}','Administrator\ResourceController@get15kmApplicants')->name('range');
    Route::get('get15kmApplicantsAjax/{id}/{radius?}/{type?}','Administrator\ResourceController@get15kmApplicantsAjax');
    Route::post('/sale-update-history', 'Administrator\SaleController@updateHistory')->name('sale-update-history');

    //Route::get('callback-applicants-notes/{applicant_id}/{sale_id}','Administrator\ApsendCVllbackApplicantsNotes')->name('callBackNotes');
    //Route::get('no-nursing-applicants-notes/{applicant_id}/{sale_id}','Administrator\ApplicantController@getNoNiursingApplicantsNotes')->name('nursingNotes');
    Route::get('active-applicants-within-15-km/{id}','Administrator\ResourceController@getActive15kmApplicants')->name('15kmrange');
    Route::get('available-jobs/{id}/{radius?}','Administrator\ResourceController@get15kmAvailableJobs')->name('jobs');
    Route::get('get15kmJobsAvailableAjax/{id}/{radius?}','Administrator\ResourceController@get15kmJobsAvailableAjax');
	Route::get('export_15_km_applicants/{id}','Administrator\ResourceController@export_15_km_applicants')->name('export_15km_applicants');

    Route::post('mark-applicant','Administrator\ResourceController@getMarkApplicant')->name('markApplicant');
    Route::get('applicant-non-interest-reason-details/{detail_id}','Administrator\ResourceController@getNotInterestedNoteReason')->name('details');
    //Route::get('applicants-added-in-last-7-days','Administrator\ResourceController@getLast7DaysApplicantAdded')->name('last7days');
    //Route::get('getlast7DaysApp','Administrator\ResourceController@get7DaysApplicants');
	//Route::get('7_days_export_applicants','Administrator\ResourceController@export_7_days_applicants')->name('export7_days_applicants');
	Route::post('7_days_export_applicants','Administrator\ResourceController@export_7_days_applicants_date')->name('export_7days_applicants_date');
    //Route::get('applicants-added-in-last-21-days','Administrator\ResourceController@getLast21DaysApplicantAdded')->name('last21days');
	    Route::get('export_applicants-added-in-last-21-days','Administrator\ResourceController@export_Last21DaysApplicantAdded')->name('export21_days_applicants');
    //Route::get('getlast21DaysApp','Administrator\ResourceController@get21DaysApplicants');
    //Route::get('applicants-added-in-last-2-months','Administrator\ResourceController@getLast2MonthsApplicantAdded')->name('last2months');
	Route::get('applicants-blocked-in-last-2-months','Administrator\ResourceController@getLast2MonthsBlockedApplicantAdded')->name('last2monthsBlockedApplicants');
	Route::get('getlast2MonthsBlockedAppAjax','Administrator\ResourceController@getLast2MonthsBlockedApplicantAddedAjax');
	    Route::get('export_applicants-added-in-last-2-months','Administrator\ResourceController@export_Last2MonthsApplicantAdded')->name('export2_months_applicants');
    //Route::get('getlast2MonthsApp','Administrator\ResourceController@get2MonthsApplicants');
	Route::post('applicant_notes_block_casual','Administrator\ApplicantController@store_block_or_casual_notes')->name('block_or_casual_notes');
	    Route::post('no_job_to_applicant','Administrator\ApplicantController@store_no_job_to_applicant')->name('no_job_to_applicant');

	   // Route::get('temp-not-interested-applicants','Administrator\ResourceController@getTempNotInterestedApplicants')->name('TempNotInterestedApplicants');
	Route::get('getTempNotInterestedApplicantsAjax','Administrator\ResourceController@get_temp_not_interested_applicants_ajax');
	
	
  Route::get('crm_export_email/{tab}','Administrator\CrmController@export_email')->name('crm.export_email');
	Route::post('export_block_applicants','Administrator\ApplicantController@export_block_applicants')->name('export_blocked_applicants');
	Route::post('export_not_interested_applicants','Administrator\ApplicantController@exportNotInterestedApplicants')->name('export_temp_not_interested_applicants');
	    

	Route::post('unblock_block_applicants','Administrator\ApplicantController@ajax_unblock_applicants')->name('unblockBlockApplicants');
	    Route::post('unblock-notes','Administrator\ResourceController@storeUnblockNotes')->name('unblock_notes');
    Route::get('all_crm-rejected-applicants-cv','Administrator\ResourceController@getAllCrmRejectedApplicantCv')->name('allCrmRejectedCv');
	
    

	    Route::post('export_all_crm-rejected-applicants-cv','Administrator\ResourceController@exportAllCrmRejectedApplicantCv')->name('export_all_rejected_applicants');

	Route::get('export_all_crm-rejected-applicants-cv','Administrator\ResourceController@exportAllCrmRejectedApplicantCv')->name('export_all_rejected_applicants');
	    Route::post('applicant_notes_unblock','Administrator\ResourceController@store_interested_notes')->name('interested_notes');

    //Route::get('crm-rejected-applicants-cv','Administrator\ResourceController@getCrmRejectedApplicantCv')->name('crmRejectedCv');
	Route::get('export-crm-rejected-applicants-cv','Administrator\ResourceController@Export_CrmRejectedApplicantCv')->name('export_crm_rejected_applicants_cv');
    Route::get('getallCrmRejectedApplicantCvAjax','Administrator\ResourceController@getallCrmRejectedApplicantCvAjax');
    Route::get('getCrmRejectedApplicantCvAjax','Administrator\ResourceController@getCrmRejectedApplicantCvAjax');
    //Route::get('crm-request-rejected-applicants-cv','Administrator\ResourceController@getCrmRequestRejectedApplicantCv')->name('crmRequestRejectedCv');
	Route::get('export-crm-request-rejected-applicants-cv','Administrator\ResourceController@exportCrmRequestRejectedApplicantCv')->name('export_crm_request_rejected_applicants_cv');
    Route::get('getCrmRequestRejectedApplicantsCvAjax','Administrator\ResourceController@getCrmRequestRejectedApplicantCvAjax');

    //Route::get('crm-not-attended-applicants-cv','Administrator\ResourceController@getCrmNotAttendedApplicantCv')->name('crmNotAttendedCv');
	Route::get('export-not-attended-applicants-cv','Administrator\ResourceController@exportCrmNotAttendedApplicantCv')->name('export_crm_not_attended_applicants_cv');
    Route::get('getCrmNotAttendedApplicantsCvAjax','Administrator\ResourceController@getCrmNotAttendedApplicantCvAjax');
    //Route::get('crm-start-date-hold-applicants-cv','Administrator\ResourceController@getCrmStartDateHoldApplicantCv')->name('crmStartDateHoldCv');
	Route::get('export-start-date-hold-applicants-cv','Administrator\ResourceController@exportCrmStartDateHoldApplicantCv')->name('export_crm_start_date_hold_applicants_cv');
    Route::get('getCrmStartDateHoldApplicantsCvAjax','Administrator\ResourceController@getCrmStartDateHoldApplicantCvAjax');
    Route::get('crm-paid-applicants-cv','Administrator\ResourceController@getCrmPaidApplicantCv')->name('crmPaidCv');
	Route::get('export-paid-applicants-cv','Administrator\ResourceController@exportCrmPaidApplicantCv')->name('export_crm_paid_applicants_cv');
    Route::get('getCrmPaidApplicantsCvAjax/{job_category?}','Administrator\ResourceController@getCrmPaidApplicantCvAjax');
	Route::get('rejected-app-date-wise/{id}/{month}','Administrator\ResourceController@getRejectedAppDateWise')->name('rejected_app_date_wise');

Route::get('/get-rejected-app-date-wise-ajax/{id}/{month}','Administrator\ResourceController@getRejectedAppDateWiseAjax');
    
    /*** Quality routes */
    Route::get('all-applicants-sent-cv-list','Administrator\QualityController@getAllApplicantWithSentCv')->name('applicantWithSentCv');
    Route::get('all-applicants-add-cv-list', 'Administrator\QualityController@getAllApplicantWithAddCv')->name('applicantWithAddCv');
    Route::get('reject-cv/{id}/{viewString}','Administrator\QualityController@updateCVReject')->name('updateToRejectedCV');
    Route::get('all-applicants-reject-cv-list', 'Administrator\QualityController@getAllApplicantWithRejectedCv')->name('applicantWithRejectedCV');
    Route::get('download-cv/{cv_id}','Administrator\QualityController@getDownloadApplicantCv')->name('downloadCv');
    Route::post('send-email-to-manager','Administrator\QualityController@sendEmailToManager')->name('sendEmailToManager');
    Route::get('update-confirm-interview/{id}/{viewString}','Administrator\QualityController@updateConfirmInterview')->name('updateToInterviewConfirmed');
    Route::get('cleared-applicants-cv', 'Administrator\QualityController@getAllApplicantsWithConfirmedInterview')->name('applicantsWithConfirmedInterview');
    Route::get('update-attend-interview/{id}/{viewString}','Administrator\QualityController@updateAttendInterview')->name('updateToInterviewAttended');
    Route::get('applicants-attend-interview','Administrator\QualityController@getAllApplicantsWithAttendedInterview')->name('applicantsWithAttendedInterview');
    Route::get('applicants-sent-cv/{id}/{viewString}','Administrator\QualityController@updateSentCV')->name('updateToSentCV');
    Route::get('applicants-add-cv/{id}/{viewString}','Administrator\QualityController@updateAddCV')->name('updateToAddCV');
    Route::POST('revert-quality-cv', 'Administrator\QualityController@revertQualityCv')->name('revertQualityCv');
    Route::get('applicants-job-offer','Administrator\QualityController@getAllApplicantsWithJobOffer')->name('applicantsWithJobOffer');
    Route::get('get-quality-cv-applicants', 'Administrator\QualityController@getQualityCVApplicants');
    Route::get('get-reject-cv-applicants', 'Administrator\QualityController@getRejectCVApplicants');
    Route::get('get-confirm-cv-applicants', 'Administrator\QualityController@getConfirmCVApplicants');
    Route::get('quality-sales', 'Administrator\QualityController@qualitySales')->name('quality-sales');
    Route::get('get-quality-sales', 'Administrator\QualityController@getQualitySales')->name('get-quality-sales');
    Route::post('clear-reject-sale', 'Administrator\QualityController@clearRejectSale')->name('clear-reject-sale');
    Route::get('quality-sales-cleared', 'Administrator\QualityController@clearedSales')->name('quality-sales-cleared');
    Route::get('get-cleared-sales', 'Administrator\QualityController@getClearedSales')->name('get-cleared-sales');
	 Route::get('get-cleared-sales-nurse', 'Administrator\QualityController@getClearedSalesNurse')->name('get-cleared-sales-nurse');
    Route::get('get-cleared-sales-nonnurse', 'Administrator\QualityController@getClearedSalesNonnurse')->name('get-cleared-sales-nonnurse');
    Route::get('get-cleared-sales-specialist', 'Administrator\QualityController@getClearedSalesSpecialist')->name('get-cleared-sales-specialist');
    Route::get('filter-cleared-sales-nurse','Administrator\QualityController@allClearedSalesNurseFilter')->name('filter-cleared-sales-nurse');
    Route::get('filter-cleared-sales-nonnurse','Administrator\QualityController@allClearedSalesNonNurseFilter')->name('filter-cleared-sales-nonnurse');
    Route::get('filter-cleared-sales-specialist','Administrator\QualityController@allClearedSalesSpecialistFilter')->name('filter-cleared-sales-specialist');
    Route::get('quality-sales-rejected', 'Administrator\QualityController@rejectedSales')->name('quality-sales-rejected');
    Route::get('get-rejected-sales', 'Administrator\QualityController@getRejectedSales')->name('get-rejected-sales');

    Route::get('sale-office-units/{office_id}','Administrator\UnitController@getAllOfficeUnits')->name('getOfficeUnits');

    /*** CRM Routes */   
	    Route::get('crm-test', 'Administrator\CrmController@crmTest');
	Route::get('/crm', 'Administrator\CrmController@index')->name('index');

    Route::get('/crm-notes/{crm_applicant_id}/{crm_sale_id}', 'Administrator\CrmController@getCrmNotesDetails')->name('viewAllCrmNotes');
	Route::POST('revert-cv-quality/{applicant_cv_id}', 'Administrator\QualityController@updateCVRejectRevertSentCV')->name('revertInQuality');
    Route::POST('process-sent-cv', 'Administrator\CrmController@store')->name('processCv');    
	Route::get('store-app-rev', 'Administrator\CrmController@store_applicant_revert_manual')->name('store_app_rev');

    Route::get('revert-to-cv-sent/{revertCvId}/{stringComing}', 'Administrator\CrmController@getRevertToCvSent')->name('revertCv');
    Route::POST('schedule-interview', 'Administrator\CrmController@getInterviewSchedule')->name('scheduleInterview');
	Route::get('/crm-open-cv', 'Administrator\CrmController@crmOpenCv')->name('crmOpenCv');
    Route::get('/crm-sent-cv', 'Administrator\CrmController@crmSentCv')->name('crmSentCv');
	Route::get('/crm-sent-cv-nurse', 'Administrator\CrmController@crmSentCvNurse')->name('crmSentCvNurse');
    Route::get('/crm-sent-cv-nonnurse', 'Administrator\CrmController@crmSentCvNonNurse')->name('crmSentCvNonNurse');
	
    Route::post('sent-cv-action', 'Administrator\CrmController@sentCvAction')->name('sentCvAction');
	Route::post('sent-cv-no-job-action', 'Administrator\CrmController@sentCvNoJobAction')->name('sentCvNoJobAction');
    Route::post('revert-sent-cv-action', 'Administrator\CrmController@revertSentCvAction')->name('revertSentCvAction');
    Route::get('/crm-reject-cv', 'Administrator\CrmController@crmRejectCv')->name('crmRejectCv');
    Route::get('/crm-request', 'Administrator\CrmController@crmRequest')->name('crmRequest');
	Route::get('/crm-request-nurse', 'Administrator\CrmController@crmRequestNurse')->name('crmRequestNurse');
    Route::get('/crm-request-nonnurse', 'Administrator\CrmController@crmRequestNonNurse')->name('crmRequestNonNurse');
    Route::post('/request-action', 'Administrator\CrmController@requestAction')->name('requestAction');
    Route::get('/crm-reject-by-request', 'Administrator\CrmController@crmRejectByRequest')->name('crmRejectByRequest');
    Route::post('/reject-by-request-action', 'Administrator\CrmController@rejectByRequestAction')->name('rejectByRequestAction');
    Route::get('/crm-confirmation', 'Administrator\CrmController@crmConfirmation')->name('crmConfirmation');
	Route::get('crm_confirmation_search', ['uses'=>'Administrator\CrmController@crmConfirmationSearch', 'as'=>'crm_confirmation_search']);
    Route::post('/after-interview-action', 'Administrator\CrmController@afterInterviewAction')->name('afterInterviewAction');
    Route::get('/crm-rebook', 'Administrator\CrmController@crmRebook')->name('crmRebook');
    Route::post('/rebook-action', 'Administrator\CrmController@rebookAction')->name('rebookAction');
    Route::get('/crm-pre-start-date', 'Administrator\CrmController@crmPreStartDate')->name('crmPreStartDate');
    Route::post('/attended-to-pre-start-action', 'Administrator\CrmController@attendedToPreStartAction')->name('attendedToPreStartAction');
    Route::get('/crm-declined', 'Administrator\CrmController@crmDeclined')->name('crmDeclined');
    Route::post('/declined-action', 'Administrator\CrmController@declinedAction')->name('declinedAction');
    Route::get('/crm-not-attended', 'Administrator\CrmController@crmNotAttended')->name('crmNotAttended');
    Route::post('/not-attended-action', 'Administrator\CrmController@notAttendedAction')->name('notAttendedAction');
    Route::get('/crm-start-date', 'Administrator\CrmController@crmStartDate')->name('crmStartDate');
    Route::post('/start-date-action', 'Administrator\CrmController@startDateAction')->name('startDateAction');
    Route::get('/crm-start-date-hold', 'Administrator\CrmController@crmStartDateHold')->name('crmStartDateHold');
    Route::post('/start-date-hold-action', 'Administrator\CrmController@startDateHoldAction')->name('startDateHoldAction');
    Route::get('/crm-invoice', 'Administrator\CrmController@crmInvoice')->name('crmInvoice');
	Route::get('/crm-invoice-final-sent', 'Administrator\CrmController@crmInvoiceFinalSent')->name('crmInvoiceFinalSent');
    Route::post('/invoice-action', 'Administrator\CrmController@invoiceAction')->name('invoiceAction');
	Route::post('/invoice-action-sent', 'Administrator\CrmController@invoiceActionSent')->name('invoiceActionSent');
    Route::get('/crm-dispute', 'Administrator\CrmController@crmDispute')->name('crmDispute');
    Route::post('/dispute-action', 'Administrator\CrmController@disputeAction')->name('disputeAction');
    Route::get('/crm-paid', 'Administrator\CrmController@crmPaid')->name('crmPaid');
    Route::post('/paid-action', 'Administrator\CrmController@paidAction')->name('paidAction');
	Route::get('/open-to-paid-applicants', 'Administrator\CrmController@openToPaidApplicants')->name('open-to-paid-applicants');

    Route::get('post-code-finder', 'Administrator\PostcodeController@index')->name('postcodeFinder');
    Route::post('post-code-search-results', 'Administrator\PostcodeController@getPostcodeResults')->name('postcodeFinderResults');
    //AJAX ROUTES
    Route::post('notes','Administrator\NoteController@store')->name('notes');
    Route::POST('getUnits','Administrator\UnitController@getAjaxUnitListing')->name('getUnits');

    /*** User Log routes */
    Route::get('activity-logs/{id}','Administrator\UsersController@activityLogs')->name('activityLogs');
    Route::get('activity-logs/user-logs/{id}','Administrator\UsersController@userLogs');

    /*** IP Address routes */
    Route::get('/ip-addresses','Administrator\IpAddressController@index')->name('ip-addresses.index');
    Route::get('/ip-addresses/create','Administrator\IpAddressController@create')->name('ip-addresses.create');
    Route::post('/ip-addresses/store','Administrator\IpAddressController@store')->name('ip-addresses.store');
    Route::get('/ip-addresses/edit/{ip_address}','Administrator\IpAddressController@edit')->name('ip-addresses.edit');
    Route::patch('/ip-addresses/{ip_address}','Administrator\IpAddressController@update')->name('ip-addresses.update');
    Route::get('/ip-delete/{ip_address}','Administrator\IpAddressController@destroy')->name('ip-delete.destroy');
    Route::get('/ip-addresses/all-ip','Administrator\IpAddressController@ipAddresses')->name('all-ip');
    Route::get('/change-ip-address-status/{ip_address}', 'Administrator\IpAddressController@ipAddressStatus')->name('ipAddressStatus');

    /*** Applicant's Rejected History routes */
    Route::post('/applicant-rejected-history', 'Administrator\ResourceController@applicantRejectedHistory')->name('rejectedHistory');

    /*** Module note routes */
    Route::post('/module-note/store','Administrator\ModuleNoteController@store')->name('module_note.store');
    Route::post('/module-notes-history', 'Administrator\ModuleNoteController@index')->name('notesHistory');
	Route::post('/unhold-sales-module-notes', 'Administrator\ModuleNoteController@unhold_sales_notes')->name('unholdSalesNotes');
    Route::post('/update-history', 'Administrator\ApplicantController@updateHistory')->name('updateHistory');
	
	 /*** Region Routes */
	Route::GET('get-all-regions', 'Administrator\RegionController@index')->name('getAllRegions');
    Route::get('get-region-sales','Administrator\RegionController@getRegionSales')->name('getRegionPositions');
    Route::get('/get-region-applicants/{id}/{category}','Administrator\RegionController@getRegionApplicants');
	Route::get('/get-region-app-nonnurse-spec/{id}/{category}','Administrator\RegionController@getRegionAppNonNurseSpecialist');
Route::get('/region-app-nonnurse-spec/{id}/{category}','Administrator\RegionController@regionAppNonNurseSpec')->name('regionAppNonNurseSpec');
	
    // Route::get('test-region/{id}/{category}','Administrator\RegionController@getRegionNursesSales');
	
    Route::get('/region-applicants/{id}','Administrator\RegionController@regionApplicants')->name('regionApplicants');
    Route::get('/region-applicants/{id}/{category}','Administrator\RegionController@regionApplicants')->name('regionApp');
    Route::get('region-export_csv/{id}','Administrator\RegionController@regionExport_csv')->name('region.exportcsv');
    Route::POST('region-applicants-export_csv','Administrator\RegionController@exportRegionApplicants')->name('region.applicants.export');
    Route::GET('region-sales','Administrator\RegionController@regionSales')->name('region.sales');
    Route::GET('/region-nurses-sales/{id}/{category}','Administrator\RegionController@regionNursesSales')->name('region.nurses.sales');
    Route::GET('/get-region-nurses-sales/{id}/{category}','Administrator\RegionController@getRegionNursesSales');
//new routes sales double remove
	Route::GET('/get-region-nurses-sales_test/{id}/{category}','Administrator\RegionController@getRegionSalesRemoveDouble');
	    Route::get('sale-region/export/{id}/{job_category}',  'Administrator\RegionController@export')->name('sale_region.export');
	    Route::GET('/region-nurses-close-sales/{id}/{category}','Administrator\RegionController@regionCloseSales')->name('region.nurses.sales.close');

	    Route::GET('/get-region-nurses-sales_close/{id}/{category}','Administrator\RegionController@getRegionSalesRemoveCloseDouble');
Route::post('/sales/{id}/notes', 'Administrator\RegionController@storeNotes')->name('sales.notes.store');
    Route::get('region-applicants-within-15-km/{id}/{radius?}','Administrator\RegionController@get15kmApplicantsRegion')->name('region');


Route::get('/notifications', 'Administrator\ApplicantMessageController@getNotifications')->name('notifications.get');
	
	Route::GET('specialist_titles-index','Administrator\SpecialistTitleController@index')->name('specialist_titles.index');
    Route::GET('specialist_titles-create','Administrator\SpecialistTitleController@create')->name('specialist_titles.create');
    Route::GET('specialist_titles-edit/{id}','Administrator\SpecialistTitleController@edit')->name('specialist_title.edit');
    Route::PATCH('specialist_titles-update/{id}','Administrator\SpecialistTitleController@update')->name('specialist_titles.update');
    Route::POST('specialist_title-store','Administrator\SpecialistTitleController@store')->name('specialist_title.store');
    Route::POST('get_specialist_titles','Administrator\SpecialistTitleController@get');
    Route::POST('get_all_specialist_titles','Administrator\SpecialistTitleController@get_all_titles');
    Route::POST('app_get_all_specialist_titles','Administrator\SpecialistTitleController@app_get_all_titles');
	
	
	
	Route::GET('message-inbox','Administrator\ApplicantMessageController@index')->name('message_inbox');
	Route::get('/pagination/fetch_data', 'Administrator\ApplicantMessageController@fetch_data');
    Route::GET('get_user_messages', 'Administrator\ApplicantMessageController@getUserMessages')->name('get-user-messages');
	Route::GET('get-crm-app-messages', 'Administrator\ApplicantMessageController@getCrmAppMessages')->name('get-crm-app-messages');
    Route::post('store_user_messages', 'Administrator\ApplicantMessageController@storeUserMessages')->name('store-user-message');
    Route::get('message_receive', 'Administrator\ApplicantMessageController@messageReceive');
	Route::get('mark-msg-as-read', 'Administrator\ApplicantMessageController@markMessageAsRead')->name('mark_msg_as_read');
	Route::get('send-message', 'Administrator\ApplicantMessageController@sendMessage')->name('send_message');
	Route::post('send-messages-applicants', 'Administrator\ApplicantMessageController@sendMessagesApplicants')->name('send_messages_applicants');
	    Route::post('save-send-messages-applicants', 'Administrator\ApplicantMessageController@saveSendMessagesApplicants')->name('save_send_messages_applicants');
	Route::post('send-non-nurse-req-sms', 'Administrator\ApplicantMessageController@sendMessagesApplicants')->name('send_non_nurse_req_sms');
    Route::post('save-req-send-sms', 'Administrator\ApplicantMessageController@saveReqSendMessage')->name('save_req_send_sms');




	Route::post('send_sms_ajax', 'Administrator\QualityController@sendSmsCurl')->name('send-sms-ajax');


	       Route::get('user_type_detail_stats/{user_home_type}/{no_of_app}/{stats_type_stage}/{home}/{range}/{month_date}/{updateRecord?}/{unknown_src?}', 'HomeController@userTypeDetailsStats')->name('user_home_detail_stats');
	Route::get('app_crm_type_detail_stats/{user_home_type}/{stats_date}/{range}/{stats_type}/{unknown_src?}', 'HomeController@appCrmTypeDetailsStats')->name('app_crm_home_detail_stats');
	Route::post('rebook_confirm_revert', 'Administrator\CrmController@reebokConfirmRevert')->name('rebook-confirm-revert');
	Route::get('applicants-stats-details-export', 'HomeController@applicantStatsDetailExport')->name('applicants_stats_details_export');

	Route::get('testing-find-job-applicants','Administrator\ResourceController@testing_find_job_applicants');
    // login details routes

    Route::get('login-details', 'Administrator\LoginDetailControlle@index')->name('login_details');
    Route::get('view-login-details/{id}', 'Administrator\LoginDetailControlle@showUserLoginDetails')->name('view_login_details');
    Route::get('reports/users-report', 'Administrator\UsersController@usersDailyReport')->name('users_daily_report');
    Route::get('resourcesReportAjax', 'Administrator\UsersController@resourcesReportAjax')->name('resourcesReportAjax');
	Route::get('dataEntryReportAjax', 'Administrator\UsersController@dataEntryReportAjax')->name('dataEntryReportAjax');
	Route::get('qualityReportAjax', 'Administrator\UsersController@qualityReportAjax')->name('qualityReportAjax');
	Route::get('crmReportAjax', 'Administrator\UsersController@crmReportAjax')->name('crmReportAjax');
	Route::get('saleReportAjax', 'Administrator\UsersController@saleReportAjax')->name('saleReportAjax');
	
    // Emails routes
    Route::get('sent-emails', 'Administrator\EmailController@index')->name('sent_emails');
	Route::Post('sent-emails-update', 'Administrator\EmailController@sentEmailUpdate')->name('sent_emails.update');
    Route::DELETE('sent-emails-delete', 'Administrator\EmailController@sendEmailDelete')->name('sent_emails.delete');
    Route::get('get-emails', 'Administrator\EmailController@getEmails')->name('get_emails');
    Route::POST('get-email-details', 'Administrator\EmailController@getEmailDetails')->name('get_email_details');
   // export email route
    Route::get('export_email','Administrator\ApplicantController@export_email')->name('applicants.export_email');
    Route::post('export_email_data','Administrator\ApplicantController@emailExportApplicant')->name('export_email_data');
    //appicant edited by histroy routes
	 Route::get('get_edited_by_history_applicant/{id}', 'Administrator\ApplicantController@edited_by_history');
     Route::get('get_edited_by_history/{id}', 'Administrator\ApplicantController@editedByData');
	
	// job again sent to applicant
    Route::get('sent-email-applicants/{id}', 'Administrator\EmailController@emailTemplate')->name('sent-email-applicants');
    Route::post('sent-email-applicant', 'Administrator\EmailController@sentEmailJobToApplicants')->name('sent-email-applicant');
	Route::post('export_email_job', 'Administrator\EmailController@exportEmails')->name('export_email_job');

	
	   // applicant date wise show non norse by history check routes
    Route::get('applicants-added-in-last-7-days/{id}','Administrator\ResourceController@getLast7DaysApplicantAdded');
    Route::get('getlast7DaysApp/{id}','Administrator\ResourceController@get7DaysApplicants');
	Route::get('getlast7DaysAppNotInterested/{id}','Administrator\ResourceController@getlast7DaysAppNotInterested');
    Route::get('getlast7DaysAppBlocked/{id}','Administrator\ResourceController@getlast7DaysAppBlocked');
    Route::get('applicants-added-in-last-21-days/{id}','Administrator\ResourceController@getLast21DaysApplicantAdded');
    Route::get('getlast21DaysApp/{id}','Administrator\ResourceController@get21DaysApplicants');
	Route::get('getlast21DaysAppNotInterested/{id}','Administrator\ResourceController@getlast21DaysAppNotInterested');
    Route::get('getlast21DaysAppBlocked/{id}','Administrator\ResourceController@getlast21DaysAppBlocked');
    Route::get('applicants-added-in-last-2-months/{id}', 'Administrator\ResourceController@getLast2MonthsApplicantAdded');
	Route::get('getlast2MonthsApp/{id}','Administrator\ResourceController@get2MonthsApplicants');
	Route::get('getlast2MonthsAppNotInterested/{id}','Administrator\ResourceController@getlast2MonthsAppNotInterested');
    Route::get('getlast2MonthsAppBlocked/{id}','Administrator\ResourceController@getlast2MonthsAppBlocked');
		
	//new
	Route::get('applicants-added-in-last-2-months-new/{id}', 'Administrator\ResourceController@getLast2monthsNewApplicantAdded');
	Route::get('getlast2MonthsApplicationAjax/{id}', 'Administrator\ResourceController@getlast2MonthsApplicationAjax');
	Route::get('getlast2MonthsNotInterestedApplicationAjax/{id}', 'Administrator\ResourceController@getlast2MonthsNotInterestedApplicationAjax');
    Route::get('getlast2MonthsBlockedApplicationAjax/{id}', 'Administrator\ResourceController@getlast2MonthsBlockedApplicationAjax');
	
	Route::get('applicants-added-in-last-2-months-new-temp/{id}', 'Administrator\ResourceController@getLast2monthsNewApplicantAddedTemp');
	Route::get('getlast2MonthsApplicationAjaxTemp/{id}', 'Administrator\ResourceController@getlast2MonthsApplicationAjaxTemp');
	Route::get('getlast2MonthsNotInterestedApplicationAjaxTemp/{id}', 'Administrator\ResourceController@getlast2MonthsNotInterestedApplicationAjaxTemp');
	Route::get('getlast2MonthsBlockedApplicationAjaxTemp/{id}', 'Administrator\ResourceController@getlast2MonthsBlockedApplicationAjaxTemp');
	
	Route::get('applicants-added-in-last-2-months-new-temp2/{id}', 'Administrator\ResourceController@getLast2monthsNewApplicantAddedTemp2');
	Route::get('getlast2MonthsApplicationAjaxTemp2/{id}', 'Administrator\ResourceController@getlast2MonthsApplicationAjaxTemp2');
	Route::get('getlast2MonthsNotInterestedApplicationAjaxTemp2/{id}', 'Administrator\ResourceController@getlast2MonthsNotInterestedApplicationAjaxTemp2');
	Route::get('getlast2MonthsBlockedApplicationAjaxTemp2/{id}', 'Administrator\ResourceController@getlast2MonthsBlockedApplicationAjaxTemp2');
	
    Route::get('applicants-added-in-last-3-months/{id}', 'Administrator\ResourceController@getLast3monthsApplicantAdded');
	Route::get('getlast3MonthsApplicationAjax/{id}', 'Administrator\ResourceController@getlast3MonthsApplicationAjax');
	Route::get('getlast3MonthsNotInterestedApplicationAjax/{id}', 'Administrator\ResourceController@getlast3MonthsNotInterestedApplicationAjax');
    Route::get('getlast3MonthsBlockedApplicationAjax/{id}', 'Administrator\ResourceController@getlast3MonthsBlockedApplicationAjax');
	
    Route::get('applicants-added-in-last-6-months/{id}', 'Administrator\ResourceController@getLast6monthsApplicantAdded');
	Route::get('getlast6MonthsApplicationAjax/{id}', 'Administrator\ResourceController@getlast6MonthsApplicationAjax');
	Route::get('getlast6MonthsNotInterestedApplicationAjax/{id}', 'Administrator\ResourceController@getlast6MonthsNotInterestedApplicationAjax');
    Route::get('getlast6MonthsBlockedApplicationAjax/{id}', 'Administrator\ResourceController@getlast6MonthsBlockedApplicationAjax');
	
    Route::get('applicants-added-in-last-12-months/{id}', 'Administrator\ResourceController@getLast12monthsApplicantAdded');
    Route::get('getlast12MonthsApplicationAjax/{id}', 'Administrator\ResourceController@getlast12MonthsApplicationAjax');
	Route::get('getlast12MonthsNotInterestedApplicationAjax/{id}', 'Administrator\ResourceController@getlast12MonthsNotInterestedApplicationAjax');
    Route::get('getlast12MonthsBlockedApplicationAjax/{id}', 'Administrator\ResourceController@getlast12MonthsBlockedApplicationAjax');
	//end
	
	Route::get('/crm-sent-cv-chef', 'Administrator\CrmController@crmSentCvChef')->name('crmSentCvChef');
    Route::get('/crm-request-chef', 'Administrator\CrmController@crmRequestChef')->name('crmRequestChef');
    Route::get('direct-chef-resource','Administrator\ResourceController@getChefSales')->name('getDirectChef');
    Route::get('getChefJob','Administrator\ResourceController@getChefJob');
	Route::get('/jobDetail/{id}', 'Administrator\SaleController@getJobDescription');
	Route::get('direct-nursery-resource', 'Administrator\ResourceController@getNurserySales')->name('getDirectNursery');
    Route::get('getNurseryJob', 'Administrator\ResourceController@getNurseryJob');
	
	//Route::get('chatNewDesign','Administrator\ApplicantMessageController@chatNewDesign');
	// Route::get('inbox','Administrator\ApplicantMessageController@chatNewDesign')->name('inbox');
    Route::get('applicantChatHistory/{id}','Administrator\ApplicantMessageController@applicantChatHistory');
    Route::get('sendMessageApplcant','Administrator\ApplicantMessageController@sendMessageApplcant');
	
	Route::get('open_sale', 'HomeController@openSaleDetails');
	Route::get('close_sale', 'HomeController@closeSaleDetails');
   	Route::get('open_sale_detail/{date}', 'HomeController@saleDetails');
    Route::get('close_sale_detail/{date}', 'HomeController@closeDetailSale');
	
	Route::get('open_sale_weekly', 'HomeController@openSaleDetailsWeekly');
    Route::get('open_sale_detail_weekly/{start_date}/{end_date}', 'HomeController@saleDetailsWeekly');
    Route::get('close_sale_weekly', 'HomeController@closeSaleDetailWeekly');
    Route::get('close_sale_weekly/{start_date}/{end_date}', 'HomeController@closeDetailSaleWeekly');

    //monthly stat
    Route::get('open_sale_monthly', 'HomeController@openSaleDetailsMonthly');
    Route::get('open_sale_monthly/{month}/{year}', 'HomeController@openDetailSaleMonthly');
    Route::get('close_sale_monthly', 'HomeController@closeSaleMonthly');
    Route::get('close_sale_detail_monthly/{month}/{year}', 'HomeController@closeDetailSaleMonthly');
	
	//sale updated
	 Route::post('open_sale_update', 'HomeController@openSaleDetailsUpdate');
    Route::get('open_sale_detail_update/{date}', 'HomeController@saleDetailsUpdate');
    Route::post('close_sale_update', 'HomeController@closeSaleDetailsUpdate');
    Route::get('close_sale_detail_update/{date}', 'HomeController@closeDetailSaleUpdate');
	//new routes to sale reopen stats
	 Route::post('re_open_sale', 'HomeController@reOpenSaleDetailsUpdate');
     Route::get('re_open_sale_detail_update/{date}', 'HomeController@saleReOpenDetailsUpdate');

	
	    Route::get('statsDetailNurse/{stats_date}/{range}/{stats_type}/{job_category}', 'HomeController@statsDetailNurse')->name('statsDetailNurse');
	    Route::get('positionCheck/{type}/{stats_date}/{range}/{job_category}', 'HomeController@positionCheck')->name('positionCheck');
	    Route::get('revertCv/{type}/{stats_date}/{range}/{job_category}/{page_name}', 'HomeController@revertCv')->name('revertCv');
	//Quality hold route
	Route::get('hold_cv_quality/{id}/{viewString}','Administrator\QualityController@updateCVHoldRevertSentCV')->name('updateToCVHoldSentCV');
    Route::get('get-quality-hold-cv-applicants', 'Administrator\QualityController@getQualityHOldCVApplicants');
    Route::get('get-quality-notes', 'Administrator\QualityController@qualityNotesHistory')->name('get-quality-notes');
	Route::POST('revert-hold-quality-cv', 'Administrator\QualityController@revertHoldQualityCv')->name('revertHoldQualityCv');
	
	//message routes crm tab sent messages 
	    Route::post('store-user-message_open_vox', 'Administrator\ApplicantMessageController@storeUserMessagesOpenVox')->name('store-user-message_open_vox');
	
	//no job routes 
	 Route::POST('no-job-revert-all', 'Administrator\ApplicantController@noJobRevertAll')->name('noJobRevertAll');
    Route::get('available-no-jobs/{id}','Administrator\NoJobController@get15kmAvailableNoJobs')->name('noJobs');
    Route::get('get15kmNoJobsAvailableAjax/{id}','Administrator\NoJobController@get15kmNoJobsAvailableAjax');
    Route::get('applicant-no-job-cv-to-quality/{applicant_cv_id}','Administrator\NoJobController@getApplicantNoJobCvSendToQuality')->name('sendNoJobCV');
	  Route::get('get-quality-no-job-cv-applicants', 'Administrator\QualityController@getQualityNoJobCVApplicants');
    Route::get('update-confirm-no-job-interview/{id}/{viewString}','Administrator\NoJobController@updateCvCLearNoJobInterview')->name('updateToInterviewNoJobConfirmed');

    
	Route::POST('/blocked-applicant-revert-all', 'Administrator\ApplicantController@blockedApplicantRevertAll')->name('blockedApplicantRevertAll');
    // new routes not interested applicant revert
    Route::POST('non-interested-revert-all', 'Administrator\ApplicantController@nonInterestedRevertAll')->name('noJobRevertAll');
	
	Route::get('follow-up-nurse-sample','Administrator\ApplicantController@nursefollowUpSheetSample')->name('followUP.nurseSample');
	Route::get('follow-up-nurse','Administrator\ApplicantController@nursefollowUpSheet')->name('followUP.nurse');
	Route::get('follow-up-non-nurse','Administrator\ApplicantController@nonnursefollowUpSheet')->name('followUP.nonnurse');
    Route::get('getFollowUpApplicants/{id}', 'Administrator\ApplicantController@getFollowUpApplicants')->name('getFollowUpApplicants');
	Route::get('getFollowUpApplicantsSample/{id}', 'Administrator\ApplicantController@getFollowUpApplicantsSample')->name('getFollowUpApplicantsSample');
	
	Route::get('follow-up-postcodes', 'Administrator\ApplicantController@followUpPostcodes')->name('followUp.postcodes');
    Route::get('/get-postcode-history', 'Administrator\ApplicantController@getPostCodeHistory');
    Route::get('/unique-postcodes-nurse', 'Administrator\ApplicantController@getUniquePostcodesNurse');
    Route::get('/unique-postcodes-nonnurse', 'Administrator\ApplicantController@getUniquePostcodesNonnurse');
    Route::post('/add-postcodes-nurse', 'Administrator\ApplicantController@addPostcodesNurse');
    Route::post('/add-postcodes-non-nurse', 'Administrator\ApplicantController@addPostcodesNonnurse');

});