<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GoogleMaps\Facade\GoogleMapsFacade as GoogleMaps;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\isNull;

class GoogleMapController extends Controller
{
    protected $googleApiKey;

    public function __construct()
    {
        $this->googleApiKey = Config::get('googlemaps.key');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return $this->jsonResponse(Config::get("googlemaps.service"));
    }

    /**
     * For available service and params, please check at 'config/googlemaps.php'
     * @param Request $request
     * @param string $serviceName
     * @return \Illuminate\Http\JsonResponse
     */
    public function service(Request $request, $serviceName)
    {
        try {
            $params = $request->all();
            $paramsString = $this->paramsToString($params);
            // Check cache first before call service.
            // Call google api service default response as json.
            if (Cache::store('redis')->tags([$serviceName])->has($paramsString)) {
                $googleMapService = Cache::store('redis')->tags([$serviceName])->get($paramsString);
            } else {
                $googleMapService = GoogleMaps::load($serviceName)
                    ->setParam($params)
                    ->get();
                // Cache into redis use parameter as string for separate search result.
                Cache::store('redis')->tags([$serviceName])->put($paramsString, $googleMapService, now()->addDay());
            }
            $googleMapService = json_decode($googleMapService);
            $googleMapService = $this->transformResult($googleMapService);

            return $this->jsonResponse($googleMapService);
        } catch (\Exception $exception) {
            return $this->jsonResponse(
                $exception->getTrace(),
                $exception->getCode(),
                $exception->getMessage()
            );
        }
    }

    /**
     * Cache photo from google api as base64 and return as png image.
     * @param string $photoreference
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function photo($photoreference)
    {
        $url = Config::get("googlemaps.service.placephoto.url") . "maxwidth=300&photoreference=$photoreference&key={$this->googleApiKey}";
        $contents = file_get_contents($url);
        $image = base64_encode($contents);
        Cache::store('file')->put($photoreference, $image, now()->addDay());
        $rawImageString = base64_decode($image);
        return response($rawImageString)->header('Content-Type', 'image/png');
    }

    /**
     * Price level reference from google api.
     * @param int $level
     * https://developers.google.com/places/web-service/details
     * @return string
     */
    private function getPriceRateText($level)
    {
        if (is_null($level)) {
            $text = "ไม่มีข้อมูล";
        } else {
            $textArray = ['ฟรี', 'ราคาไม่แพง', 'ปานกลาง', 'เเพง', 'แพงมาก'];
            $text = $textArray[$level];
        }
        return $text;
    }

    /**
     * Custom response of some service og google api.
     * For this support service text auto complete.
     * Modify object add new property photo src and price rate.
     * @param object $googleMapService
     * @return mixed
     */
    private function transformResult($googleMapService)
    {
        if (isset($googleMapService->results) && is_array($googleMapService->results)) {
            foreach ($googleMapService->results as &$location) {
                if (isset($location->photos)) {
                    foreach ($location->photos as &$photo) {
                        $photo->src = url('api/google-maps/place/photo/' . $photo->photo_reference);
                    }
                }
                if (isset($location->price_level)) {
                    $location->price_rate = $this->getPriceRateText($location->price_level);
                } else {
                    $location->price_rate = $this->getPriceRateText(null);
                }
                if (isset($location->opening_hours)) {
                    if ($location->opening_hours->open_now) {
                        $location->opening_hours_text = 'เปิดบริการ';
                    } else {
                        $location->opening_hours_text = 'ปิดบริการ';
                    }
                } else {
                    $location->opening_hours_text = 'ไม่มีข้อมูล';
                }
            }
        }
        return $googleMapService;
    }
}
