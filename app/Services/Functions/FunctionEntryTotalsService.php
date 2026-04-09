<?php

namespace App\Services\Functions;

use App\Models\FunctionEntry;
use App\Models\FunctionPackage;
use App\Models\FunctionServiceLine;

class FunctionEntryTotalsService
{
    public function calculateLineTotalMinor(string $personInputMode, int $persons, int $rateMinor, int $extraChargeMinor): int
    {
        $quantity = $personInputMode === FunctionServiceLine::PERSON_MODE_NONE ? 1 : max(0, $persons);

        return $quantity * max(0, $rateMinor) + max(0, $extraChargeMinor);
    }

    public function recalculate(FunctionEntry $functionEntry): FunctionEntry
    {
        $functionEntry->loadMissing([
            'packages.serviceLines',
            'extraCharges',
            'installments',
            'discounts',
            'user.venues',
        ]);

        $packageTotalMinor = 0;

        $functionEntry->packages->each(function (FunctionPackage $functionPackage) use (&$packageTotalMinor) {
            $computedTotalMinor = $functionPackage->serviceLines
                ->sum(fn (FunctionServiceLine $line) => $line->is_selected ? (int) $line->line_total_minor : 0);

            if ((int) $functionPackage->total_minor !== $computedTotalMinor) {
                $functionPackage->forceFill(['total_minor' => $computedTotalMinor])->save();
            }

            $packageTotalMinor += $computedTotalMinor;
        });

        $extraChargeTotalMinor = (int) $functionEntry->extraCharges->sum('amount_minor');
        $discountTotalMinor = (int) $functionEntry->discounts->sum('amount_minor');
        $paidTotalMinor = (int) $functionEntry->installments->sum('amount_minor');
        $functionTotalMinor = $packageTotalMinor + $extraChargeTotalMinor - $discountTotalMinor;
        $pendingTotalMinor = $functionTotalMinor - $paidTotalMinor;
        $frozenFundMinor = $functionEntry->user->supportsFrozenFund()
            ? $functionEntry->user->frozenFundMinorForVenue((int) $functionEntry->venue_id)
            : 0;

        $functionEntry->forceFill([
            'package_total_minor' => $packageTotalMinor,
            'extra_charge_total_minor' => $extraChargeTotalMinor,
            'discount_total_minor' => $discountTotalMinor,
            'function_total_minor' => $functionTotalMinor,
            'paid_total_minor' => $paidTotalMinor,
            'pending_total_minor' => $pendingTotalMinor,
            'frozen_fund_minor' => $frozenFundMinor,
            'net_total_after_frozen_fund_minor' => $functionTotalMinor - $frozenFundMinor,
        ])->save();

        return $functionEntry->fresh([
            'user',
            'venue',
            'packages.serviceLines',
            'extraCharges.attachments',
            'installments.attachments',
            'discounts.attachments',
            'attachments',
        ]);
    }
}
