<?php

namespace BertMaurau\URLShortener\Core;

use BertMaurau\URLShortener\Models AS Models;
use GeoIp2\Database\Reader;

/**
 * Description of UrlTracker
 *
 * @author bertmaurau
 */
class UrlTracker
{

    /**
     * Track the request
     *
     * @param int $urlId The ID of the URL
     * @param int $urlAliasId The ID of the URL Alias
     *
     * @return void
     */
    public static function track(int $urlId, int $urlAliasId = null)
    {

        $trackRequest = (new Models\UrlRequest)
                -> setUrlId($urlId)
                -> setUrlAliasId($urlAliasId);

        if ($remoteAddress = Auth::getRemoteAddress()) {

            $trackRequest
                    -> setRemoteAddress($remoteAddress);

            // get location info for remote address
            try {

                $locationData = self::getLocationData($remoteAddress);

                // if any data available..
                if ($locationData && isset($locationData -> country)) {
                    $trackRequest
                            -> setCountryIso($locationData -> country -> isoCode)
                            -> setCountryName($locationData -> country -> name);
                }
                if ($locationData && isset($locationData -> mostSpecificSubdivision)) {
                    $trackRequest
                            -> setDivisionIso($locationData -> mostSpecificSubdivision -> isoCode)
                            -> setDivisionName($locationData -> mostSpecificSubdivision -> name);
                }
                if ($locationData && isset($locationData -> city)) {
                    $trackRequest
                            -> setCity($locationData -> city -> name);
                }
                if ($locationData && isset($locationData -> postal)) {
                    $trackRequest
                            -> setPostalCode($locationData -> postal -> code);
                }
                if ($locationData && isset($locationData -> location)) {
                    $trackRequest
                            -> setLatitude($locationData -> location -> latitude)
                            -> setLongitude($locationData -> location -> longitude);
                }
            } catch (\Exception $ex) {

            }
        }

        $trackRequest
                -> insert();

        return;
    }

    /**
     * Get location based on remote address
     *
     * @param string $remoteAddress
     *
     * @return \GeoIp2\Model\City
     */
    public static function getLocationData(string $remoteAddress)
    {
        $reader = new Reader(Config::getInstance() -> Paths() -> statics . 'geo-data/GeoLite2-City.mmdb');

        return $reader -> city($remoteAddress);
    }

}
