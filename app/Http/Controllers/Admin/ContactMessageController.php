<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\ContactMessage::query();

        // Search by name, phone, email, subject, or message content, or IP address
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if (in_array($status, ['new', 'read', 'replied'])) {
                $query->where('status', $status);
            }
        }

        // Date Sorting
        $sort = $request->input('sort', 'desc');
        $query->orderBy('created_at', $sort === 'asc' ? 'asc' : 'desc');

        $messages = $query->paginate(15)->withQueryString();

        // Summary counts
        $counts = [
            'total' => \App\Models\ContactMessage::count(),
            'new' => \App\Models\ContactMessage::where('status', 'new')->count(),
            'read' => \App\Models\ContactMessage::where('status', 'read')->count(),
            'replied' => \App\Models\ContactMessage::where('status', 'replied')->count(),
        ];

        return view('admin.contact-messages.index', compact('messages', 'counts'));
    }

    public function updateStatus(Request $request, \App\Models\ContactMessage $contactMessage)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,read,replied',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $contactMessage->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $contactMessage->admin_notes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Message status updated successfully.',
                'item' => $contactMessage
            ]);
        }

        return back()->with('success', 'Message status updated successfully.');
    }

    public function destroy(\App\Models\ContactMessage $contactMessage)
    {
        $contactMessage->delete();
        return back()->with('success', 'Contact message deleted successfully.');
    }
}
