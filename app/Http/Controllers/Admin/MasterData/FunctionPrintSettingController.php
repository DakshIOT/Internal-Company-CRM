<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MasterData\UpdateFunctionPrintSettingRequest;
use App\Models\PrintSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FunctionPrintSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.master-data.function-print-settings.edit', [
            'settings' => PrintSetting::current(),
        ]);
    }

    public function update(UpdateFunctionPrintSettingRequest $request): RedirectResponse
    {
        $settings = PrintSetting::current();
        $settings->update([
            'function_terms_and_conditions' => trim((string) $request->validated('function_terms_and_conditions'))
                ?: PrintSetting::defaultFunctionTerms(),
        ]);

        return redirect()
            ->route('admin.master-data.function-print-settings.edit')
            ->with('status', 'Function print settings updated.');
    }
}

