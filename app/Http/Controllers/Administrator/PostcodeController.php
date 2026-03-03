<?php

namespace Horsefly\Http\Controllers\Administrator;

use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Applicant;
use Horsefly\Unit;
use Horsefly\Cv_note;
use Horsefly\Specialist_job_titles;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use DateTime;

class PostcodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
//        $this->middleware('permission:postcode-finder_search', ['only' => ['index','getPostcodeResults']]);
    }

    public function index(){
        return view('administrator.postcode.index');
    }
	
  // This function is used to get the postcode results based on the given parameters
	public function getPostcodeResults(Request $request)
	{
		$today = Carbon::parse(date("Y-m-d"));

		// Validate the request
		$validator = Validator::make($request->all(), [
			'postcode' => 'required',
			'radius' => 'required',
			'job_category' => 'required'
		])->validate();

		$postcode = $request->Input('postcode');
		$radius = $request->Input('radius');
		$job_category = $request->Input('job_category');
		$job_result = null;

		// Helper function to check if lat/lng are valid
		$isValidCoordinates = function ($lat, $lng) {
			return !empty($lat) && $lat != 0.000000 && !empty($lng) && $lng != 0.000000;
		};

		// Retrieve job result matching the postcode in Sale
		$job_result = Sale::where('postcode', $postcode)->first();

		// If not found in Sale or lat/lng are invalid, try in Applicant
		if (!$job_result || !$isValidCoordinates($job_result->lat, $job_result->lng)) {
			$job_result = Applicant::where('applicant_postcode', $postcode)->first();

			// If still not found in Applicant or lat/lng are invalid, fetch coordinates using Google Maps API
			if (!$job_result || !$isValidCoordinates($job_result->lat, $job_result->lng)) {
				$postcode_para = urlencode($postcode) . ',UK';
				$postcode_api = config('app.postcode_api');
				$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$postcode_para}&key={$postcode_api}";

				try {
					// Call Google Maps API to fetch coordinates
					$resp_json = file_get_contents($url);
					$resp = json_decode($resp_json, true);

					if ($resp['status'] === 'OK') {
						// Extract latitude and longitude from API response
						$lati = $resp['results'][0]['geometry']['location']['lat'] ?? null;
						$longi = $resp['results'][0]['geometry']['location']['lng'] ?? null;

						// Create a new object for the job result if no valid record was found
						$job_result = new \stdClass();
						$job_result->lat = $lati;
						$job_result->lng = $longi;
					} else {
						// Handle case where Google Maps API does not return valid data
						return response()->json(['error' => 'Failed to fetch postcode coordinates from Google Maps API']);
					}
				} catch (\Exception $e) {
					// Handle exception in case the API call fails
					return response()->json(['error' => 'Failed to fetch postcode coordinates due to API error']);
				}
			}
		}

		// Initialize coordinate results
		$data['cordinate_results'] = [];

		// If job_result contains valid lat and lng, proceed with further processing
		if ($job_result && $isValidCoordinates($job_result->lat, $job_result->lng)) {
			$lati = $job_result->lat;
			$longi = $job_result->lng;

			// Get coordinate results based on distance and job category
			$data['cordinate_results'] = $this->distance($lati, $longi, $radius, $job_category);

			if ($data['cordinate_results']->isNotEmpty()) {
				foreach ($data['cordinate_results'] as &$job) {
					$cv_limit = Cv_note::where(['sale_id' => $job->id, 'status' => 'active'])->count();
					$job['cv_limit'] = $cv_limit;

					$newDate = Carbon::parse($job->posted_date);
					$different_days = $today->diffInDays($newDate);

					$office_id = $job['head_office'];
					$unit_id = $job['head_office_unit'];

					$office = Office::select("office_name")
						->where(["id" => $office_id, "status" => "active"])
						->first();
					$office = $office->office_name;

					$unit = Unit::select("unit_name")
						->where(["id" => $unit_id, "status" => "active"])
						->first();
					$unit = $unit->unit_name;

					$job['office_name'] = $office;
					$job['unit_name'] = $unit;

					$job['days_diff'] = $different_days <= 7 ? 'true' : 'false';

					if ($job['job_title_prof']) {
						$job_title_prof = Specialist_job_titles::select("specialist_prof")
							->where("id", $job['job_title_prof'])
							->first();
						$job['job_title_prof_res'] = $job_title_prof->specialist_prof;
					}
				}
			} else {
				$data['cordinate_results'] = [];
			}
		}

		return view('administrator.postcode.index', compact('data', 'postcode', 'radius', 'job_category'));
	}

    function distance($lat, $lon, $radius, $job_category)
    {
		if($job_category == 'nursery'){
			$location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
					COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
					AS distance"))->having("distance", "<", $radius)
				->orderBy("distance")
				->where("status", "active")
				->where("is_on_hold", "0")
				->where('job_category','nursery')
				->get();
                
		}elseif($job_category == 'nonnurse'){
			 $location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
                AS distance"))->having("distance", "<", $radius)
			->orderBy("distance")
			->where("status", "active")
			->where("is_on_hold", "0")
			->where('job_category',$job_category)
            ->whereNotIn('job_title', ['nonnurse specialist'])
			->get();
		
        }elseif($job_category == 'specialist'){
			 $location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
                AS distance"))->having("distance", "<", $radius)
			->orderBy("distance")
			->where("status", "active")
			->where("is_on_hold", "0")
            ->whereIn('job_category',['nonnurse','nurse'])
            ->whereIn('job_title',['nonnurse specialist','nurse specialist'])
            ->whereNotIn('job_title_prof',[214,225,236,24,121,45,61,125,126,127,208,221,237])
			->get();
		
		}else{//nurse, chef
			 $location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
                AS distance"))->having("distance", "<", $radius)
			->orderBy("distance")
			->where("status", "active")
			->where("is_on_hold", "0")
			->where('job_category',$job_category)
            ->whereNotIn('job_title',['nonnurse specialist','nurse specialist'])
			->get();
		}
			
        return $location_distance;
    }
}
