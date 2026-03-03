<?php

/**
 * format: module_permission-name, module_sub-module_permission-name
 */

return [
    'dashboard' => [
        'dashboard_statistics'
    ],
    'role' => [
        'role_list',
        'role_create',
        'role_view',
        'role_edit',
        'role_delete',
        'role_assign-role'
    ],
    'applicant' => [
        'applicant_list',
        'applicant_import',
        'applicant_create',
        'applicant_edit',
        'applicant_view',
        'applicant_history',
        'applicant_note-create',
        'applicant_note-history'
    ],
    'user' => [
        'user_list',
        'user_create',
        'user_edit',
        'user_enable-disable',
        'user_activity-log'
    ],
    'office' => [
        'office_list',
        'office_import',
        'office_create',
        'office_edit',
        'office_view',
        'office_note-history',
        'office_note-create'
    ],
    'unit' => [
        'unit_list',
        'unit_import',
	    'unit_create',
	    'unit_edit',
	    'unit_view',
	    'unit_note-create',
	    'unit_note-history'
    ],
    'sale' => [
        'sale_list',
        'sale_import',
	    'sale_create',
	    'sale_edit',
	    'sale_view',
	    'sale_open',
	    'sale_close',
	    'sale_history',
	    'sale_notes',
	    'sale_note-create',
	    'sale_note-history',
        'sale_closed-sales-list',
        'sale_closed-sale-notes',
        'sale_psl-offices-list',
        'sale_psl-office-details',
        'sale_psl-office-units',
        'sale_non-psl-offices-list',
        'sale_non-psl-office-details',
        'sale_non-psl-office-units'
    ],
    'resource' => [
        'resource_Nurses-list',
        'resource_Non-Nurses-list',
        'resource_Last-7-Days-Applicants',
        'resource_Last-21-Days-Applicants',
        'resource_All-Applicants',
        'resource_Crm-Rejected-Applicants',
        'resource_Crm-Request-Rejected-Applicants',
        'resource_Crm-Not-Attended-Applicants',
        'resource_Crm-Start-Date-Hold-Applicants',
		'resource_Crm-Paid-Applicants',

        // resource_no-nursing-home
        'resource_No-Nursing-Home_list',
        'resource_No-Nursing-Home_revert-no-nursing-home',

        'resource_Non-Interested-Applicants',

        // resource_potential-callback
        'resource_Potential-Callback_list',
        'resource_Potential-Callback_revert-callback'
    ],
    'quality' => [

        // CVs
        'quality_CVs_list',
        'quality_CVs_cv-download',
        'quality_CVs_job-detail',
        'quality_CVs_cv-clear',
        'quality_CVs_cv-reject',

        // CVs-Rejected
        'quality_CVs-Rejected_list',
        'quality_CVs-Rejected_job-detail',
        'quality_CVs-Rejected_cv-download',
        'quality_CVs-Rejected_revert-quality-cv',

        // CVs-Cleared
        'quality_CVs-Cleared_list',
        'quality_CVs-Cleared_job-detail',
        'quality_CVs-Cleared_cv-download',
		
		// Sales
        'quality_Sales_list',
        'quality_Sales_sale-clear',
        'quality_Sales_sale-reject',

        // Sales-Cleared
        'quality_Sales-Cleared_list',

        // Sales-Rejected
        'quality_Sales-Rejected_list'
    ],
    'CRM' => [
        'CRM_Sent-CVs_list',

        // CRM_Sent-CVs
        'CRM_Sent-CVs_request',
        'CRM_Sent-CVs_save',
        'CRM_Sent-CVs_reject',

        // CRM_rejected-cv
        'CRM_Rejected-CV_list',
        'CRM_Rejected-CV_revert-sent-cv',

        // CRM_request
        'CRM_Request_list',
        'CRM_Request_reject',
        'CRM_Request_confirm',
        'CRM_Request_save',
        'CRM_Request_schedule-interview',

        // CRM_rejected-by-request
        'CRM_Rejected-By-Request_list',
        'CRM_Rejected-By-Request_revert-sent-cv',
        'CRM_Rejected-By-Request_revert-request',

        // CRM_confirmation
        'CRM_Confirmation_list',
        'CRM_Confirmation_revert-request',
        'CRM_Confirmation_not-attended',
        'CRM_Confirmation_attend',
		'CRM_Confirmation_rebook',
        'CRM_Confirmation_save',

		// CRM_rebook
        'CRM_Rebook_list',
        'CRM_Rebook_not-attended',
        'CRM_Rebook_attend',
        'CRM_Rebook_save',
		
        // CRM_attended-to-pre-start-date
        'CRM_Attended_list',
		'CRM_Attended_decline',
        'CRM_Attended_start-date',
        'CRM_Attended_save',

		// CRM_declined
        'CRM_Declined_list',
        'CRM_Declined_revert-to-attended',
		
        // CRM_not-attended
        'CRM_Not-Attended_list',
        'CRM_Not-Attended_revert-to-attended',

        // CRM_start-date
        'CRM_Start-Date_list',
        'CRM_Start-Date_invoice',
        'CRM_Start-Date_start-date-hold',
        'CRM_Start-Date_save',

        // CRM_start-date-hold
        'CRM_Start-Date-Hold_list',
        'CRM_Start-Date-Hold_revert-start-date',
        'CRM_Start-Date-Hold_save',

        // CRM_invoice
        'CRM_Invoice_list',
        'CRM_Invoice_paid',
        'CRM_Invoice_dispute',
        'CRM_Invoice_save',

        // CRM_dispute
        'CRM_Dispute_list',
        'CRM_Dispute_revert-invoice',

        // CRM_paid
        'CRM_Paid_list',
		'CRM_Paid_open-close-cv'
    ],
    'ip-address' => [
        'ip-address_list',
        'ip-address_create',
        'ip-address_edit',
        'ip-address_enable-disable',
        'ip-address_delete'
    ],
    'postcode-finder' => [
        'postcode-finder_search'
	],
    'common' => [
        'available-applicants-for-sale',
        'resource-available-sales-for-applicant',
        'crm-available-sales-for-applicant'	
    ]
];

/********** Permissions applied in sidebar for CRM

'CRM_Sent-CVs_list',
'CRM_Rejected-CV_list',
'CRM_Request_list',
'CRM_Rejected-By-Request_list',
'CRM_Confirmation_list',
'CRM_Rebook_list',
'CRM_Attended_list',
'CRM_Declined_list',
'CRM_Not-Attended_list',
'CRM_Start-Date_list',
'CRM_Start-Date-Hold_list',
'CRM_Invoice_list',
'CRM_Dispute_list',
'CRM_Paid_list',

Permissions applied in sidebar for CRM ****************

*************************** Permissions NOT Implemented

'CRM' => [

    // CRM_Sent-CVs
    'CRM_Sent-CVs_applicant-postcode-search',
    'CRM_Sent-CVs_job-detail',
    'CRM_Sent-CVs_manager-detail',
    'CRM_Sent-CVs_all-notes',

    // CRM_rejected-cv
    'CRM_Rejected-CV_applicant-postcode-search',
    'CRM_Rejected-CV_job-detail',
    'CRM_Rejected-CV_manager-detail',
    'CRM_Rejected-CV_all-notes',

    // CRM_request
    'CRM_Request_applicant-postcode-search',
    'CRM_Request_job-detail',
    'CRM_Request_manager-detail',
    'CRM_Request_all-notes',

    // CRM_rejected-by-request
    'CRM_Rejected-By-Request_applicant-postcode-search',
    'CRM_Rejected-By-Request_job-detail',
    'CRM_Rejected-By-Request_manager-detail',
    'CRM_Rejected-By-Request_all-notes',

    // CRM_confirmation
    'CRM_Confirmation_applicant-postcode-search',
    'CRM_Confirmation_job-detail',
    'CRM_Confirmation_manager-detail',
    'CRM_Confirmation_all-notes',

    // CRM_attended-to-pre-start-date
    'CRM_Attended_applicant-postcode-search',
    'CRM_Attended_job-detail',
    'CRM_Attended_manager-detail',
    'CRM_Attended_all-notes',

    // CRM_not-attended
    'CRM_Not-Attended_applicant-postcode-search',
    'CRM_Not-Attended_job-detail',
    'CRM_Not-Attended_manager-detail',
    'CRM_Not-Attended_all-notes',

    // CRM_start-date
    'CRM_Start-Date_applicant-postcode-search',
    'CRM_Start-Date_job-detail',
    'CRM_Start-Date_manager-detail',
    'CRM_Start-Date_all-notes',

    // CRM_start-date-hold
    'CRM_Start-Date-Hold_applicant-postcode-search',
    'CRM_Start-Date-Hold_job-detail',
    'CRM_Start-Date-Hold_manager-detail',
    'CRM_Start-Date-Hold_all-notes',

    // CRM_invoice
    'CRM_Invoice_applicant-postcode-search',
    'CRM_Invoice_job-detail',
    'CRM_Invoice_manager-detail',
    'CRM_Invoice_all-notes',

    // CRM_dispute
    'CRM_Dispute_applicant-postcode-search',
    'CRM_Dispute_job-detail',
    'CRM_Dispute_manager-detail',
    'CRM_Dispute_all-notes',

    // CRM_paid
    'CRM_Paid_job-detail',
    'CRM_Paid_manager-detail',
    'CRM_Paid_all-notes'
],
'sale' => [
    'sale_manager-detail',
],
'quality' => [
    // CVs
    'quality_CVs_manager-detail',
    // CVs-Rejected
    'quality_CVs-Rejected_manager-detail',
    // CVs-Cleared
    'quality_CVs-Cleared_manager-detail'
],
'resource' => [

    // Direct > Nurses
    'resource_Nurses_applicant-postcode-search',
    'resource_Nurses_job-postcode-search',

    // Direct > Non Nurses
    'resource_Non-Nurses_manager-detail',
    'resource_Non-Nurses_applicant-postcode-search',
    'resource_Non-Nurses_job-postcode-search',

    // last-7-days
    'resource_Last-7-Days_applicant-postcode-search',
    'resource_Last-7-Days_rejected-history',

    // Last-21-days
    'resource_Last-21-Days_applicant-postcode-search',
    'resource_Last-21-Days_rejected-history',

    // all-applicants' =>
    'resource_All-Applicants_applicant-postcode-search',
    'resource_All-Applicants_rejected-history',

    // resource-crm-rejected
    'resource_Crm-Rejected_rejected-history',
    'resource_Crm-Rejected_applicant-postcode-search',

    // resource_crm-request-rejected
    'resource_Crm-Request-Rejected_rejected-history',
    'resource_Crm-Request-Rejected_applicant-postcode-search',

    // resource_crm-not-attended
    'resource_Crm-Not-Attended_rejected-history',
    'resource_Crm-Not-Attended_applicant-postcode-search',

    // resource_crm-start-date-hold
    'resource_Crm-Start-Date-Hold_rejected-history',
    'resource_Crm-Start-Date-Hold_applicant-postcode-search',

    // resource_no-nursing-home
    'resource_No-Nursing-Home_rejected-history',
    'resource_No-Nursing-Home_applicant-postcode-search',

    // resource_potential-callback
    'resource_Potential-Callback_rejected-history',
    'resource_Potential-Callback_applicant-postcode-search',

    // resource_not-interested
    'resource_Not-Interested_rejected-history',
    'resource_Not-Interested_applicant-postcode-search',
    'resource_Not-Interested_job-detail'
],
'common-links' => [
    'common-links_not-interested',
    'common-links_callback',
    'common-links_no-nursing-home',
    'common-links_send-cv',
]
******* Permissions NOT Implemented  *******************/