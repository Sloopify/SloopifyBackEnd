<?php

namespace App\Utils;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

class PhoneNumberHelper {
    private static $phoneUtil;

    private static function getPhoneUtil() {
        if (!self::$phoneUtil) {
            self::$phoneUtil = PhoneNumberUtil::getInstance();
        }
        return self::$phoneUtil;
    }

    public static function parsePhoneNumber($phone) {
        try {
            $phoneUtil = self::getPhoneUtil();
            
            // Remove spaces and check if number starts with + or 00
            $phone = preg_replace('/\s+/', '', $phone);
            if (substr($phone, 0, 2) === '00') {
                $phone = '+' . substr($phone, 2);
            }

            // Try to parse the number (defaulting to US if no country code)
            $defaultRegion = substr($phone, 0, 1) === '+' ? null : 'US';
            $phoneNumber = $phoneUtil->parse($phone, $defaultRegion);

            if ($phoneUtil->isValidNumber($phoneNumber)) {
                return [
                    'code' => '+' . $phoneNumber->getCountryCode(),
                    'number' => $phoneNumber->getNationalNumber(),
                    'formatted' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL),
                    'valid' => true
                ];
            }

            return [
                'code' => null,
                'number' => $phone,
                'formatted' => $phone,
                'valid' => false
            ];

        } catch (NumberParseException $e) {
            return [
                'code' => null,
                'number' => $phone,
                'formatted' => $phone,
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function validatePhoneNumber($phone, $defaultRegion = null) {
        try {
            $phoneUtil = self::getPhoneUtil();
            
            // Clean the number
            $phone = preg_replace('/\s+/', '', $phone);
            if (substr($phone, 0, 2) === '00') {
                $phone = '+' . substr($phone, 2);
            }

            // Parse and validate
            $phoneNumber = $phoneUtil->parse($phone, $defaultRegion);
            return $phoneUtil->isValidNumber($phoneNumber);

        } catch (NumberParseException $e) {
            return false;
        }
    }

    public static function formatPhoneNumber($phone, $format = PhoneNumberFormat::INTERNATIONAL) {
        try {
            $phoneUtil = self::getPhoneUtil();
            $phoneNumber = $phoneUtil->parse($phone);
            return $phoneUtil->format($phoneNumber, $format);
        } catch (NumberParseException $e) {
            return $phone;
        }
    }
}
