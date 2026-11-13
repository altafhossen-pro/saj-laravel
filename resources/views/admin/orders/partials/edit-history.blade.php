<!-- Order Edit History -->
@if($order->logs->count() > 0)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Order Edit History</h2>
    </div>
    <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
        @foreach($order->logs as $log)
        <div class="p-6 hover:bg-gray-50 transition-colors">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p class="font-medium text-gray-900">
                        @if(str_contains(strtolower($log->action ?? ''), 'steadfast'))
                            <span class="inline-flex items-center gap-1 text-pink-600 font-bold"><i class="ph-bold ph-truck"></i> Steadfast Courier Webhook</span>
                        @else
                            Edited by {{ $log->user ? $log->user->name : 'System/Admin' }}
                        @endif
                    </p>
                    <p class="text-xs text-gray-500">{{ $log->created_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            @php
                $old = $log->old_data ?? ($log->old_values ?? []);
                $new = $log->new_data ?? ($log->new_values ?? []);
                $changes = [];
                
                $fieldsToCheck = ['status', 'payment_status', 'shipping_cost', 'discount', 'coupon_discount', 'total', 'order_notes', 'admin_notes'];
                foreach ($fieldsToCheck as $field) {
                    if (isset($old[$field]) && isset($new[$field]) && $old[$field] != $new[$field]) {
                        $changes[] = [
                            'field' => ucfirst(str_replace('_', ' ', $field)),
                            'old' => is_array($old[$field]) ? json_encode($old[$field]) : $old[$field],
                            'new' => is_array($new[$field]) ? json_encode($new[$field]) : $new[$field]
                        ];
                    }
                }

                // Check shipping address phone or name change cleanly
                if (isset($old['shipping_address']) && isset($new['shipping_address']) && $old['shipping_address'] != $new['shipping_address']) {
                    $oldShipPhone = is_array($old['shipping_address']) ? ($old['shipping_address']['phone'] ?? '') : '';
                    $newShipPhone = is_array($new['shipping_address']) ? ($new['shipping_address']['phone'] ?? '') : '';
                    if ($oldShipPhone !== $newShipPhone) {
                        $changes[] = [
                            'field' => 'Shipping Phone',
                            'old' => $oldShipPhone ?: 'None',
                            'new' => $newShipPhone ?: 'None'
                        ];
                    } else {
                        $changes[] = [
                            'field' => 'Shipping Address',
                            'old' => 'Modified',
                            'new' => 'Updated'
                        ];
                    }
                }
                
                // Check if items changed
                $oldItems = collect($old['items'] ?? [])->keyBy('id');
                $newItems = collect($new['items'] ?? [])->keyBy('id');
                
                if ($oldItems->count() !== $newItems->count() || $oldItems->toJson() !== $newItems->toJson()) {
                    $changes[] = [
                        'field' => 'Order Items',
                        'old' => 'Modified',
                        'new' => 'Updated'
                    ];
                }
            @endphp

            @if(count($changes) > 0)
            <div class="mt-3 bg-white rounded-lg p-0 border border-gray-200 overflow-hidden">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 w-1/3">Field</th>
                            <th class="px-4 py-2 w-1/3">Previous Value</th>
                            <th class="px-4 py-2 w-1/3">New Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($changes as $change)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $change['field'] }}</td>
                            <td class="px-4 py-2 text-red-600 line-through truncate max-w-[150px]" title="{{ $change['old'] }}">{{ $change['old'] ?: 'Empty' }}</td>
                            <td class="px-4 py-2 text-green-600 font-medium truncate max-w-[150px]" title="{{ $change['new'] }}">{{ $change['new'] ?: 'Empty' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="mt-2 text-sm text-gray-500 italic">No specific field changes tracked in this update.</p>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
