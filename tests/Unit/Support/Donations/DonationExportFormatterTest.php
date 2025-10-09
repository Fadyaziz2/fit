<?php

namespace Tests\Unit\Support\Donations;

use App\Support\Donations\DonationExportFormatter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

class DonationExportFormatterTest extends TestCase
{
    public function test_it_uses_initial_date_for_instapay_donations(): void
    {
        $donation = [
            'initial_collection_date' => '2024-05-01',
            'actual_collection_date' => '2024-05-05',
            'payment_method' => 'Insta Pay',
        ];

        $formatted = DonationExportFormatter::updateActualCollectionDate($donation);

        $this->assertSame('2024-05-01', $formatted['actual_collection_date']);
    }

    public function test_it_falls_back_to_initial_date_when_actual_is_missing(): void
    {
        $initialDate = new DateTimeImmutable('2024-05-01 12:00:00');

        $donation = [
            'initial_collection_date' => $initialDate,
            'actual_collection_date' => null,
            'payment_method' => 'Bank Transfer',
        ];

        $formatted = DonationExportFormatter::updateActualCollectionDate($donation);

        $this->assertSame($initialDate, $formatted['actual_collection_date']);
    }

    public function test_it_keeps_existing_actual_date_for_other_payment_methods(): void
    {
        $donation = new stdClass();
        $donation->initial_collection_date = '2024-05-01';
        $donation->actual_collection_date = '2024-05-06';
        $donation->payment_method = 'Cash';

        $formatted = DonationExportFormatter::updateActualCollectionDate($donation);

        $this->assertSame('2024-05-06', $formatted->actual_collection_date);
    }
}
