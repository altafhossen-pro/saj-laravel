@extends('layouts.admin')

@section('header_title', 'Visitor Analytics')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Visitor Analytics</h1>
            <p class="text-sm text-gray-500 mt-1">Geographic visitor tracking, bounce rates, and traffic sources.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center max-w-xl mx-auto my-12">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl mx-auto mb-4">
            <i class="ph ph-globe-hemisphere-west"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Visitor Analytics Coming Soon</h3>
        <p class="text-sm text-gray-500 mb-6">
            Detailed 15-day/30-day historical visitor charts, device breakdown (Mobile vs Desktop), and traffic acquisition channels will be live here.
        </p>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
            <i class="ph ph-sparkle"></i> Under Active Development
        </span>
    </div>
</div>
@endsection
