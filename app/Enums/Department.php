<?php

namespace App\Enums;

enum Department: string
{
    case IT = 'IT';
    case RECEIVING = 'Receiving';
    case BARANGAY_AFFAIRS = 'Barangay Affairs';
    case FINANCIAL_ASSISTANCE = 'Financial Assistance';
    case USE_OF_FACILITIES = 'Use of Facilities';
    case APPOINTMENT_MEETING = 'Appointment Meeting';
    case USE_OF_VEHICLE = 'Use of Vehicle';
    case OTHER_REQUEST = 'Other Request';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
