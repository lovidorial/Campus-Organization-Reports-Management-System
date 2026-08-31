<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class PublicOrgChartController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::where('is_active', true)->with('organizationMembers')->orderBy('name');

        if ($request->filled('organization')) {
            $query->where('id', $request->organization);
        }

        $organizations = $query->get();

        return view('public.org-chart', [
            'organizations' => $organizations,
            'selectedOrganization' => $request->organization,
        ]);
    }
}
