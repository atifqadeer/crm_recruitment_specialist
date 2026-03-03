<?php

namespace Horsefly\Http\Controllers;

use Illuminate\Http\Request;
use Horsefly\Applicant;
use DB;


class ApiController extends Controller
{
    // public function getApplicantContacts()
    // {
    //     $contacts = Applicant::pluck('applicant_phone')->toArray();

        
    //     return response()->json(['success' => true, 'data' => $contacts]);
    // }

 public function fetchContacts()
{
    // Initialize cURL session
    $curl = curl_init();

    // Set cURL options
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://specialist.bilalmedicalcentre.com/api/getContacts",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
        ],
    ]);

    // Execute cURL request and get the response
    $response = curl_exec($curl);

    // Check for cURL errors
    if (curl_errno($curl)) {
        curl_close($curl); // Ensure to close the cURL session
        return response()->json(['message' => 'cURL error: ' . curl_error($curl)], 500);
    }

    // Close cURL session
    curl_close($curl);

    // Check if response is valid and log it for debugging
    if ($response === false) {
        return response()->json(['message' => 'Invalid cURL response.'], 500);
    }

    // Log or return the raw response for debugging
    // You can use a logger or return the response to inspect
    // Log::info('Raw API Response: ' . $response);
    return response()->json(['raw_response' => $response]); // For debugging

    // Decode the JSON response to an array
    $contactsResponse = json_decode($response, true);

    // Check if json_decode() was successful
    if ($contactsResponse === null && json_last_error() !== JSON_ERROR_NONE) {
        return response()->json(['message' => 'JSON decode error: ' . json_last_error_msg(), 'raw_response' => $response], 500);
    }

    // Check if data is received correctly and if 'data' exists
    if (isset($contactsResponse['success']) && $contactsResponse['success'] === true && is_array($contactsResponse['data'])) {
        // Loop through each contact (phone number)
        foreach ($contactsResponse['data'] as $phone) {
            // Check if the phone number already exists in the database
            $exists = DB::table('temp_contacts_spkbp')->where('phone', $phone)->exists();

            if (!$exists) {
                // Insert phone number only if it doesn't exist
                DB::table('temp_contacts_spkbp')->insert([
                    'phone' => $phone,
                ]);
            }
        }

        return response()->json(['message' => 'Contacts imported successfully.']);
    } else {
        return response()->json(['message' => 'Invalid data format received from API.', 'raw_response' => $response], 500);
    }
}


}
