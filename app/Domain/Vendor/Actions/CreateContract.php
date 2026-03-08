<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Models\Contract;
use App\Domain\Vendor\Models\Vendor;
use Carbon\CarbonInterface;

class CreateContract
{
    public function execute(
        Vendor $vendor,
        string $title,
        CarbonInterface $startsAt,
        ?CarbonInterface $endsAt = null,
        ?int $valueAmount = null,
        ?string $currency = null,
    ): Contract {
        return Contract::create([
            'vendor_id' => $vendor->id,
            'title' => $title,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'value_amount' => $valueAmount,
            'currency' => $currency,
        ]);
    }
}
