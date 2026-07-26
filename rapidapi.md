Google Map Places (New V2) endpoints
Nearby Search:
<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://google-map-places-new-v2.p.rapidapi.com/v1/places:searchNearby",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => json_encode([
		'languageCode' => '',
		'regionCode' => '',
		'includedTypes' => [
				
		],
		'excludedTypes' => [
				
		],
		'includedPrimaryTypes' => [
				
		],
		'excludedPrimaryTypes' => [
				
		],
		'maxResultCount' => 1,
		'locationRestriction' => [
				'circle' => [
								'center' => [
																'latitude' => 40,
																'longitude' => -110
								],
								'radius' => 10000
				]
		],
		'rankPreference' => 0
	]),
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"X-Goog-FieldMask: *",
		"x-rapidapi-host: google-map-places-new-v2.p.rapidapi.com",
		"x-rapidapi-key: e70e489e2fmsh1dd3841aef801f4p1809c1jsna2cd3c44e531"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}

Text Search:

<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://google-map-places-new-v2.p.rapidapi.com/v1/places:searchText",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => json_encode([
		'textQuery' => 'restaurants',
		'languageCode' => '',
		'regionCode' => '',
		'rankPreference' => 0,
		'includedType' => '',
		'openNow' => null,
		'minRating' => 0,
		'maxResultCount' => 1,
		'priceLevels' => [
				
		],
		'strictTypeFiltering' => null,
		'locationBias' => [
				'circle' => [
								'center' => [
																'latitude' => 40,
																'longitude' => -110
								],
								'radius' => 10000
				]
		],
		'evOptions' => [
				'minimumChargingRateKw' => 0,
				'connectorTypes' => [
								
				]
		]
	]),
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"X-Goog-FieldMask: *",
		"x-rapidapi-host: google-map-places-new-v2.p.rapidapi.com",
		"x-rapidapi-key: e70e489e2fmsh1dd3841aef801f4p1809c1jsna2cd3c44e531"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}

Place Details:
<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://google-map-places-new-v2.p.rapidapi.com/v1/places/ChIJj61dQgK6j4AR4GeTYWZsKWw",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"X-Goog-FieldMask: *",
		"x-rapidapi-host: google-map-places-new-v2.p.rapidapi.com",
		"x-rapidapi-key: e70e489e2fmsh1dd3841aef801f4p1809c1jsna2cd3c44e531"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}

Place Photo:
<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://google-map-places-new-v2.p.rapidapi.com/v1/places/ChIJ2fzCmcW7j4AR2JzfXBBoh6E/photos/AUacShh3_Dd8yvV2JZMtNjjbbSbFhSv-0VmUN-uasQ2Oj00XB63irPTks0-A_1rMNfdTunoOVZfVOExRRBNrupUf8TY4Kw5iQNQgf2rwcaM8hXNQg7KDyvMR5B-HzoCE1mwy2ba9yxvmtiJrdV-xBgO8c5iJL65BCd0slyI1/media?maxWidthPx=400&maxHeightPx=400&skipHttpRedirect=true",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"x-rapidapi-host: google-map-places-new-v2.p.rapidapi.com",
		"x-rapidapi-key: e70e489e2fmsh1dd3841aef801f4p1809c1jsna2cd3c44e531"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}

Autocomplete:
<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://google-map-places-new-v2.p.rapidapi.com/v1/places:autocomplete",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => json_encode([
		'input' => 'Restaurant',
		'locationBias' => [
				'circle' => [
								'center' => [
																'latitude' => 40,
																'longitude' => -110
								],
								'radius' => 10000
				]
		],
		'includedPrimaryTypes' => [
				
		],
		'includedRegionCodes' => [
				
		],
		'languageCode' => '',
		'regionCode' => '',
		'origin' => [
				'latitude' => 0,
				'longitude' => 0
		],
		'inputOffset' => 0,
		'includeQueryPredictions' => null,
		'sessionToken' => ''
	]),
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"X-Goog-FieldMask: *",
		"x-rapidapi-host: google-map-places-new-v2.p.rapidapi.com",
		"x-rapidapi-key: e70e489e2fmsh1dd3841aef801f4p1809c1jsna2cd3c44e531"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}

this is all for places.