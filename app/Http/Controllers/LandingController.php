<?php

namespace App\Http\Controllers;

use App\Models\ContactLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    /**
     * Display the public landing page.
     */
    public function index()
    {
        return view('landing');
    }

    /**
     * Handle contact form submission.
     */
    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'company'      => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'service_tier' => 'nullable|string|max:50',
            'message'      => 'required|string|max:2000',
        ]);

        $lead = ContactLead::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'company'      => $validated['company'] ?? null,
            'phone'        => $validated['phone'] ?? null,
            'service_tier' => $validated['service_tier'] ?? 'due_diligence',
            'message'      => $validated['message'],
            'ip_address'   => $request->ip(),
            'status'       => 'new',
        ]);

        Log::info("Nuevo prospecto de contacto en AvalID: {$lead->name} ({$lead->email}) - Empresa: {$lead->company}");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '¡Gracias por contactarnos! Un consultor especialista de AvalID se comunicará contigo en breve.',
            ]);
        }

        return redirect()->back()->with('contact_success', '¡Gracias por contactarnos! Un consultor especialista de AvalID se comunicará contigo en breve.');
    }
}
