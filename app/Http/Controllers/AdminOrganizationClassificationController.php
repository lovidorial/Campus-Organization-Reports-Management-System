<?php

namespace App\Http\Controllers;

use App\Models\OrganizationClassification;
use App\Services\OrganizationClassifierService;
use Illuminate\Http\Request;

class AdminOrganizationClassificationController extends Controller
{
    public function index()
    {
        $classifications = OrganizationClassification::orderBy('org_name')->paginate(20);
        return view('admin.organization-classifications.index', compact('classifications'));
    }

    public function create()
    {
        return view('admin.organization-classifications.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'org_name' => 'required|string|max:255|unique:organization_classifications,org_name',
            'aliases' => 'nullable|string|max:1000',
            'classification' => 'required|string|max:100',
            'college_area' => 'required|string|max:255',
        ]);

        OrganizationClassification::create([
            'org_name' => $validated['org_name'],
            'aliases' => $this->aliasesFromString($validated['aliases']),
            'classification' => $validated['classification'],
            'college_area' => $validated['college_area'],
        ]);

        return redirect()->route('admin.organization-classifications.index')
            ->with('success', 'Organization classification saved successfully.');
    }

    public function edit(OrganizationClassification $organizationClassification)
    {
        return view('admin.organization-classifications.edit', compact('organizationClassification'));
    }

    public function update(Request $request, OrganizationClassification $organizationClassification)
    {
        $validated = $request->validate([
            'org_name' => 'required|string|max:255|unique:organization_classifications,org_name,' . $organizationClassification->id,
            'aliases' => 'nullable|string|max:1000',
            'classification' => 'required|string|max:100',
            'college_area' => 'required|string|max:255',
        ]);

        $organizationClassification->update([
            'org_name' => $validated['org_name'],
            'aliases' => $this->aliasesFromString($validated['aliases']),
            'classification' => $validated['classification'],
            'college_area' => $validated['college_area'],
        ]);

        return redirect()->route('admin.organization-classifications.index')
            ->with('success', 'Organization classification updated successfully.');
    }

    public function destroy(OrganizationClassification $organizationClassification)
    {
        $organizationClassification->delete();

        return redirect()->route('admin.organization-classifications.index')
            ->with('success', 'Organization classification removed.');
    }

    public function classify(Request $request, OrganizationClassifierService $service)
    {
        $name = $request->query('name', '');

        return response()->json($service->classify($name));
    }

    private function aliasesFromString(?string $aliases): ?array
    {
        if (empty($aliases)) {
            return null;
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $aliases))));
    }
}
