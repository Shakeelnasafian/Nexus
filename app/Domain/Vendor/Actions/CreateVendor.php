<?php

namespace App\Domain\Vendor\Actions;

use App\Domain\Vendor\Models\Vendor;

class CreateVendor
{
    public function execute(
        string $name,
        string $code,
        ?string $contactName = null,
        ?string $contactEmail = null,
        ?string $notes = null,
    ): Vendor {
        return Vendor::create([
            'name' => $name,
            'code' => $code,
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'notes' => $notes,
        ]);
    }
}
