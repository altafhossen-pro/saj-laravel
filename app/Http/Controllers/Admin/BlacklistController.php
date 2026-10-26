<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Blacklist;
use Carbon\Carbon;

class BlacklistController extends Controller
{
    /**
     * Display IP and Phone Blacklist management
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'ip'); // 'ip' or 'phone'

        $ipQuery = Blacklist::where('type', 'ip')->with('creator:id,name');
        $phoneQuery = Blacklist::where('type', 'phone')->with('creator:id,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $ipQuery->where(function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
            $phoneQuery->where(function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $ipList = $ipQuery->latest()->paginate(15, ['*'], 'ip_page');
        $phoneList = $phoneQuery->latest()->paginate(15, ['*'], 'phone_page');

        $activeIpCount = Blacklist::active()->where('type', 'ip')->count();
        $activePhoneCount = Blacklist::active()->where('type', 'phone')->count();

        return view('admin.security.blacklist', compact(
            'tab',
            'ipList',
            'phoneList',
            'activeIpCount',
            'activePhoneCount'
        ));
    }

    /**
     * Store new IP/CIDR or Phone block rule
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:ip,phone',
            'value' => 'required|string|max:64',
            'reason' => 'nullable|string|max:255',
            'custom_message' => 'nullable|string|max:1000',
            'duration_type' => 'required|in:permanent,custom',
            'duration_value' => 'nullable|required_if:duration_type,custom|numeric|min:1',
            'duration_unit' => 'nullable|required_if:duration_type,custom|in:minutes,hours,days,months,years',
        ]);

        $type = $request->type;
        $value = trim($request->value);

        // Validation for Phone (Clean and ensure valid 11 digits format)
        if ($type === 'phone') {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (strlen($value) < 11) {
                return back()->with('error', 'Phone number must be at least 11 digits.')->withInput();
            }
        }

        // Calculate expires_at
        $expiresAt = null;
        if ($request->duration_type === 'custom') {
            $amount = (int)$request->duration_value;
            $unit = $request->duration_unit;
            $expiresAt = match ($unit) {
                'minutes' => Carbon::now()->addMinutes($amount),
                'hours' => Carbon::now()->addHours($amount),
                'days' => Carbon::now()->addDays($amount),
                'months' => Carbon::now()->addMonths($amount),
                'years' => Carbon::now()->addYears($amount),
                default => null,
            };
        }

        Blacklist::create([
            'type' => $type,
            'value' => $value,
            'reason' => $request->reason,
            'custom_message' => $request->custom_message,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', ucfirst($type) . ' successfully added to blacklist.');
    }

    /**
     * Update an existing blacklist rule
     */
    public function update(Request $request, Blacklist $blacklist)
    {
        $request->validate([
            'value' => 'required|string|max:64',
            'reason' => 'nullable|string|max:255',
            'custom_message' => 'nullable|string|max:1000',
            'duration_type' => 'required|in:permanent,custom,keep',
            'duration_value' => 'nullable|required_if:duration_type,custom|numeric|min:1',
            'duration_unit' => 'nullable|required_if:duration_type,custom|in:minutes,hours,days,months,years',
        ]);

        $value = trim($request->value);
        if ($blacklist->type === 'phone') {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (strlen($value) < 11) {
                return back()->with('error', 'Phone number must be at least 11 digits.');
            }
        }

        $expiresAt = $blacklist->expires_at;
        if ($request->duration_type === 'permanent') {
            $expiresAt = null;
        } elseif ($request->duration_type === 'custom') {
            $amount = (int)$request->duration_value;
            $unit = $request->duration_unit;
            $expiresAt = match ($unit) {
                'minutes' => Carbon::now()->addMinutes($amount),
                'hours' => Carbon::now()->addHours($amount),
                'days' => Carbon::now()->addDays($amount),
                'months' => Carbon::now()->addMonths($amount),
                'years' => Carbon::now()->addYears($amount),
                default => null,
            };
        }

        $blacklist->update([
            'value' => $value,
            'reason' => $request->reason,
            'custom_message' => $request->custom_message,
            'expires_at' => $expiresAt,
        ]);

        return back()->with('success', 'Blacklist rule updated successfully.');
    }

    /**
     * Toggle active status of a blacklist rule
     */
    public function toggle(Blacklist $blacklist)
    {
        $blacklist->update(['is_active' => !$blacklist->is_active]);
        $status = $blacklist->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Blacklist rule {$status} successfully.");
    }

    /**
     * Delete a blacklist rule
     */
    public function destroy(Blacklist $blacklist)
    {
        $blacklist->delete();
        return back()->with('success', 'Blacklist rule deleted successfully.');
    }
}
