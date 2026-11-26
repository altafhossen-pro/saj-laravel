<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderActions', (orderId, currentStatus, isBooked, cid, trackingCode, customerPhone, initialFraudData, isOrderOlderThan10Days, isStockDeducted, orderItems, initialFbSentAt, initialStatusTimestamps, isPartialDelivery, orderTotal) => ({
            orderId: orderId,
            currentStatus: currentStatus,
            selectedStatus: currentStatus,
            statusTimestamps: initialStatusTimestamps || {},
            isPartialDelivery: Boolean(isPartialDelivery),
            orderTotal: Number(orderTotal || 0),
            isSteadfastBooked: Boolean(isBooked),
            steadfastCid: cid || '',
            steadfastTracking: trackingCode || '',
            customerPhone: customerPhone || '',
            sendToSteadfast: false,
            sendToMeta: true, // Default ON when confirming order
            fbPurchaseSentAt: initialFbSentAt || null,
            showMetaPurchaseModal: false,
            sendingMetaPurchase: false,
            metaCaptchaDigit1: 0,
            metaCaptchaDigit2: 0,
            metaCaptchaExpected: 0,
            metaCaptchaAnswer: '',
            metaCaptchaError: '',
            steadfastLoading: false,
            liveStatus: null,
            checkingLiveStatus: false,
            fraudCheckData: initialFraudData || null,
            checkingFraud: false,
            isOrderOlderThan10Days: Boolean(isOrderOlderThan10Days),
            isStockDeducted: Boolean(isStockDeducted),
            orderItems: orderItems || [],
            showRestockModal: false,
            restockTargetStatus: 'cancelled',
            restockReason: '',
            restockItems: [],
            manualCollectedAmount: 0,
            suggestedCollectionAmount: 0,
            submittingRestock: false,
            showFraudCaptchaModal: false,
            fraudCaptchaDigit1: 0,
            fraudCaptchaDigit2: 0,
            fraudCaptchaExpected: 0,
            fraudCaptchaAnswer: '',
            fraudCaptchaError: '',
            trackingLogs: @json($trackingList),
            syncingTracking: false,
            adminNotes: @json($adminNotesList),
            showAddNote: false,
            newAdminNote: '',
            savingAdminNote: false,
            collectedAmount: {{ (float) ($order->steadfast_collected_amount ?: ($order->payment_status === 'paid' ? 0 : $order->total)) }},
            deliveryCharge: {{ (float) ($order->steadfast_delivery_charge ?: ($order->shipping_cost ?: 70)) }},
            codCharge: {{ (float) ($order->steadfast_cod_charge ?: round(($order->steadfast_collected_amount ?: ($order->payment_status === 'paid' ? 0 : $order->total)) * 0.01, 2)) }},
            receivableAmount: {{ (float) ($order->steadfast_receivable_amount ?: max(0, round(($order->steadfast_collected_amount ?: ($order->payment_status === 'paid' ? 0 : $order->total)) - ($order->steadfast_delivery_charge ?: ($order->shipping_cost ?: 70)) - (($order->steadfast_collected_amount ?: ($order->payment_status === 'paid' ? 0 : $order->total)) * 0.01), 2))) }},
            openStatusModal: false,
            openConfirmModal: false,
            openDeleteModal: false,
            deletingOrder: false,
            showPrintLabelModal: false,
            labelSize: '3x3', // '3x3' (Square: 76.2mm x 76.2mm) or '2x3' (Compact: 50.8mm x 76.2mm)
            printLayoutMode: 'default', // 'default' (Center / Thermal / Standalone) or 'a4' (A4 Sheet Quadrant)
            a4Position: 'top-left', // 'top-left', 'top-right', 'bottom-left', 'bottom-right'
            includeLabelNotes: false, // Default: No notes in label
            loading: false,
            nextStatus: null,
            get steps() {
                if (this.currentStatus === 'returned') {
                    if (this.statusTimestamps && this.statusTimestamps['delivered']) {
                        return ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'returned'];
                    }
                    return ['pending', 'confirmed', 'processing', 'shipped', 'returned'];
                }
                if (this.currentStatus === 'cancelled') {
                    if (this.statusTimestamps && this.statusTimestamps['processing']) {
                        return ['pending', 'confirmed', 'processing', 'cancelled'];
                    }
                    if (this.statusTimestamps && this.statusTimestamps['confirmed']) {
                        return ['pending', 'confirmed', 'cancelled'];
                    }
                    return ['pending', 'cancelled'];
                }
                return ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
            },
            toast: { show: false, message: '', type: 'success' },
            toastTimer: null,
            previewImageUrl: null,
            previewImageTitle: '',
            copied: false,
            copiedTimer: null,

            init() {
                this.calculateNextStatus();
                if (this.currentStatus === 'processing' && !this.isSteadfastBooked) {
                    this.sendToSteadfast = true;
                }

                if (this.customerPhone) {
                    if (!this.fraudCheckData && !this.isOrderOlderThan10Days) {
                        this.checkFraudScore(false);
                    }
                }

                // Auto-fetch Steadfast live tracking milestones on order page load (only for active orders, never overwrite returned/cancelled)
                if ((this.isSteadfastBooked || this.steadfastCid || this.steadfastTracking) && !['returned', 'cancelled'].includes(this.currentStatus)) {
                    this.syncTracking(true);
                }
            },

            handleStatusSelect(status) {
                if (this.currentStatus === 'shipped' && status === 'cancelled') {
                    this.showToast('Orders cannot be cancelled once shipped. Please choose Returned or Delivered.', 'error');
                    return;
                }

                if ((status === 'cancelled' || status === 'returned') && this.isStockDeducted) {
                    this.openStatusModal = false;
                    this.openConfirmModal = false;
                    this.initRestockModal(status);
                    return;
                }

                this.openStatusModal = false;
                this.openConfirmModal = false;
                this.updateStatus(status);
            },

            initRestockModal(targetStatus) {
                this.restockTargetStatus = targetStatus;
                this.restockReason = '';
                this.restockItems = (this.orderItems || []).map(item => {
                    const maxQty = item.remaining_qty !== undefined ? item.remaining_qty : item.quantity;
                    return {
                        id: item.id,
                        name: item.name,
                        sku: item.sku,
                        price: Number(item.price || 0),
                        quantity: item.quantity,
                        already_returned: item.already_returned || 0,
                        remaining_qty: maxQty,
                        return_qty: maxQty,
                        selected: maxQty > 0,
                    };
                });
                this.recalculateSuggestedCollection();
                this.showRestockModal = true;
            },

            isPartialRestock() {
                if (!this.restockItems || this.restockItems.length === 0) return false;
                let anyReturned = false;
                let anyKept = false;
                this.restockItems.forEach(item => {
                    const retQty = item.selected ? Number(item.return_qty || 0) : 0;
                    const totalQty = Number(item.quantity || 0);
                    if (retQty > 0) anyReturned = true;
                    if (totalQty > retQty) anyKept = true;
                });
                return anyReturned && anyKept;
            },

            recalculateSuggestedCollection() {
                if (!this.isPartialRestock()) {
                    this.suggestedCollectionAmount = 0;
                    this.manualCollectedAmount = 0;
                    return;
                }
                let keptItemsValue = 0;
                this.restockItems.forEach(item => {
                    const retQty = item.selected ? Number(item.return_qty || 0) : 0;
                    const totalQty = Number(item.quantity || 0);
                    const keptQty = Math.max(0, totalQty - retQty);
                    keptItemsValue += keptQty * Number(item.price || 0);
                });
                this.suggestedCollectionAmount = Math.max(0, Math.round(keptItemsValue));
                this.manualCollectedAmount = this.suggestedCollectionAmount;
            },

            selectFullRestock() {
                this.restockItems.forEach(item => {
                    const maxQty = item.remaining_qty !== undefined ? item.remaining_qty : item.quantity;
                    item.selected = maxQty > 0;
                    item.return_qty = maxQty;
                });
                this.recalculateSuggestedCollection();
            },

            selectNoRestock() {
                this.restockItems.forEach(item => {
                    item.selected = false;
                    item.return_qty = 0;
                });
                this.recalculateSuggestedCollection();
            },

            async confirmRestockSubmit() {
                if (this.submittingRestock) return;

                const selectedItems = this.restockItems
                    .filter(i => i.selected && Number(i.return_qty) > 0)
                    .map(i => ({
                        item_id: i.id,
                        quantity: Number(i.return_qty)
                    }));

                this.submittingRestock = true;
                try {
                    await this.updateStatus(this.restockTargetStatus, {
                        restock_items: selectedItems,
                        restock_reason: this.restockReason,
                        collected_amount: Number(this.manualCollectedAmount || 0)
                    });
                    this.showRestockModal = false;
                } finally {
                    this.submittingRestock = false;
                }
            },

            getStatusLabel(status) {
                if (!status) return '';
                if (status === 'delivered' && this.isPartialDelivery) {
                    return 'Partial Delivered';
                }
                return status
                    .toString()
                    .toLowerCase()
                    .replace(/[_-]+/g, ' ')
                    .replace(/\b\w/g, c => c.toUpperCase());
            },

            courierStatusBadgeClass(status) {
                const s = (status || '').toLowerCase().replace(/[_-]+/g, '');
                if (s.includes('deliver') && !s.includes('out') && !s.includes('partial')) {
                    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                }
                if (s.includes('outfordelivery') || s.includes('rider')) {
                    return 'bg-blue-50 text-blue-700 border-blue-200';
                }
                if (s.includes('transit') || s.includes('hub') || s.includes('warehouse')) {
                    return 'bg-indigo-50 text-indigo-700 border-indigo-200';
                }
                if (s.includes('cancel') || s.includes('return') || s.includes('fail')) {
                    return 'bg-rose-50 text-rose-700 border-rose-200';
                }
                if (s.includes('review') || s.includes('pending') || s.includes('hold')) {
                    return 'bg-amber-50 text-amber-700 border-amber-200';
                }
                return 'bg-gray-100 text-gray-700 border-gray-200';
            },

            badgeClass(status) {
                return {
                    'bg-amber-50 text-amber-600': status === 'pending',
                    'bg-blue-50 text-blue-600': status === 'confirmed',
                    'bg-indigo-50 text-indigo-600': status === 'processing',
                    'bg-purple-50 text-purple-600': status === 'shipped',
                    'bg-emerald-50 text-emerald-600': status === 'delivered',
                    'bg-rose-50 text-rose-600': status === 'cancelled',
                    'bg-amber-50 text-amber-700': status === 'returned',
                };
            },

            get isTerminalBad() {
                return ['cancelled', 'returned'].includes(this.currentStatus);
            },

            isDone(step) {
                const stepList = this.steps;
                const current = stepList.indexOf(this.currentStatus);
                const stepIndex = stepList.indexOf(step);
                return current !== -1 && stepIndex !== -1 && stepIndex < current;
            },

            calculateNextStatus() {
                const flow = {
                    'pending': 'confirmed',
                    'confirmed': 'processing',
                    'processing': 'shipped',
                    'shipped': 'delivered'
                };
                this.nextStatus = flow[this.currentStatus] || null;
                if (this.currentStatus === 'processing' && this.nextStatus === 'shipped' && !this.isSteadfastBooked) {
                    this.sendToSteadfast = true;
                } else {
                    this.sendToSteadfast = false;
                }
            },

            showToast(message, type = 'success') {
                clearTimeout(this.toastTimer);
                this.toast = { show: true, message, type };
                this.toastTimer = setTimeout(() => this.toast.show = false, 3500);
            },

            async copyOrderId() {
                await this.copyText(this.orderId, 'Order ID copied');
            },

            async copyText(text, successMsg = 'Copied to clipboard') {
                if (!text) return;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        const input = document.createElement('textarea');
                        input.value = text;
                        input.style.position = 'fixed';
                        input.style.opacity = '0';
                        document.body.appendChild(input);
                        input.select();
                        const ok = document.execCommand('copy');
                        document.body.removeChild(input);
                        if (!ok) throw new Error('copy failed');
                    }
                    this.copied = true;
                    this.showToast(successMsg);
                    clearTimeout(this.copiedTimer);
                    this.copiedTimer = setTimeout(() => this.copied = false, 2000);
                } catch (e) {
                    this.showToast('Could not copy text', 'error');
                }
            },

            openPrintLabelModal() {
                this.showPrintLabelModal = true;
                this.$nextTick(() => {
                    this.renderLabelBarcodes();
                });
            },

            renderLabelBarcodes() {
                const rawCid = this.steadfastCid || '{{ $order->steadfast_consignment_id }}';
                const codeValue = rawCid ? String(rawCid).trim() : '{{ $order->order_id }}';

                // Render Barcode via JsBarcode
                if (window.JsBarcode && document.getElementById('label-barcode-svg')) {
                    try {
                        window.JsBarcode('#label-barcode-svg', codeValue, {
                            format: 'CODE128',
                            lineColor: '#000000',
                            width: 1.6,
                            height: 28,
                            displayValue: false,
                            margin: 2
                        });
                    } catch (e) {
                        console.error('Barcode generation error:', e);
                    }
                }

                // Render QR Code via QRCode.js
                const qrContainer = document.getElementById('label-qrcode-container');
                if (window.QRCode && qrContainer) {
                    qrContainer.innerHTML = '';
                    try {
                        new window.QRCode(qrContainer, {
                            text: codeValue,
                            width: 40,
                            height: 40,
                            colorDark: '#000000',
                            colorLight: '#ffffff',
                            correctLevel: window.QRCode.CorrectLevel.M
                        });
                    } catch (e) {
                        console.error('QR code generation error:', e);
                    }
                }
            },

            closePrintLabelModal() {
                this.showPrintLabelModal = false;
            },

            printShippingLabel() {
                const labelEl = document.getElementById('printable-shipping-label');
                if (!labelEl) return;

                let printFrame = document.getElementById('shipping-print-iframe');
                if (printFrame) {
                    printFrame.remove();
                }

                printFrame = document.createElement('iframe');
                printFrame.id = 'shipping-print-iframe';
                printFrame.style.position = 'fixed';
                printFrame.style.right = '0';
                printFrame.style.bottom = '0';
                printFrame.style.width = '0';
                printFrame.style.height = '0';
                printFrame.style.border = '0';
                printFrame.style.zIndex = '-9999';
                document.body.appendChild(printFrame);

                const is3x3 = this.labelSize === '3x3';
                const labelWidth = '76.2mm';
                const labelHeight = is3x3 ? '76.2mm' : '50.8mm';
                const labelPadding = is3x3 ? '2.5mm' : '2mm';
                const labelContent = labelEl.innerHTML;
                const isA4 = this.printLayoutMode === 'a4';
                const pos = this.a4Position || 'top-left';

                let pageContainerStyle = '';
                let printLabelBoxExtra = '';

                if (isA4) {
                    // Position inside 1 of 4 quadrants of A4 (210mm x 297mm)
                    let posRules = '';
                    if (pos === 'top-left') {
                        posRules = 'top: 10mm; left: 10mm;';
                    } else if (pos === 'top-right') {
                        posRules = 'top: 10mm; right: 10mm;';
                    } else if (pos === 'bottom-left') {
                        posRules = 'bottom: 15mm; left: 10mm;';
                    } else if (pos === 'bottom-right') {
                        posRules = 'bottom: 15mm; right: 10mm;';
                    }

                    pageContainerStyle = 'width: 210mm; min-height: 297mm; position: relative; margin: 0; padding: 0;';
                    printLabelBoxExtra = 'position: absolute; ' + posRules;
                } else {
                    // Default mode: Perfectly centered on page or standalone thermal size
                    pageContainerStyle = 'width: 100%; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 10mm; margin: 0;';
                    printLabelBoxExtra = 'margin: auto;';
                }

                const frameDoc = printFrame.contentWindow.document;
                frameDoc.open();
                frameDoc.write('<!DOCTYPE html>' +
'<html>' +
'<head>' +
'    <meta charset="utf-8">' +
'    <title>Shipping Label</title>' +
'    <link rel="preconnect" href="https://fonts.googleapis.com">' +
'    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' +
'    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">' +
'    <script src="https://cdn.tailwindcss.com"><\/script>' +
'    <style>' +
'        @page { size: ' + (isA4 ? 'A4 portrait' : 'auto') + '; margin: 0; }' +
'        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }' +
'        html, body { margin: 0; padding: 0; background: #ffffff; font-family: "Plus Jakarta Sans", sans-serif; color: #000000; }' +
'        .font-bn { font-family: "Hind Siliguri", "Segoe UI", Arial, sans-serif !important; }' +
'        .page-container { ' + pageContainerStyle + ' }' +
'        .print-label-box {' +
'            width: ' + labelWidth + ';' +
'            height: ' + labelHeight + ';' +
'            max-width: ' + labelWidth + ';' +
'            max-height: ' + labelHeight + ';' +
'            padding: ' + labelPadding + ';' +
'            border: 2px solid #000000;' +
'            border-radius: 4px;' +
'            display: flex;' +
'            flex-direction: column;' +
'            justify-content: space-between;' +
'            background: #ffffff;' +
'            overflow: hidden;' +
             printLabelBoxExtra +
'        }' +
'    </style>' +
'</head>' +
'<body>' +
'    <div class="page-container">' +
'        <div class="print-label-box">' +
             labelContent +
'        </div>' +
'    </div>' +
'</body>' +
'</html>');
                frameDoc.close();

                setTimeout(() => {
                    printFrame.contentWindow.focus();
                    printFrame.contentWindow.print();
                    setTimeout(() => {
                        printFrame.remove();
                    }, 2000);
                }, 400);
            },

            async updateStatus(newStatus, restockPayload = null) {
                if (!newStatus || this.loading) return;

                if (this.currentStatus === 'shipped' && newStatus === 'cancelled') {
                    this.showToast('Orders cannot be cancelled once shipped. Please choose Returned or Delivered.', 'error');
                    return;
                }

                if (newStatus === this.currentStatus && !restockPayload && !(this.currentStatus === 'processing' && newStatus === 'shipped' && this.sendToSteadfast && !this.isSteadfastBooked)) {
                    this.openStatusModal = false;
                    this.openConfirmModal = false;
                    return;
                }

                this.loading = true;

                try {
                    const shouldSendToSteadfast = (this.currentStatus === 'processing' && newStatus === 'shipped' && this.sendToSteadfast && !this.isSteadfastBooked);
                    const payload = { 
                        status: newStatus,
                        send_to_steadfast: shouldSendToSteadfast,
                        send_to_meta: this.sendToMeta
                    };

                    if (restockPayload) {
                        if (restockPayload.restock_items) {
                            payload.restock_items = restockPayload.restock_items;
                        }
                        if (restockPayload.restock_reason) {
                            payload.restock_reason = restockPayload.restock_reason;
                        }
                        if (restockPayload.collected_amount !== undefined) {
                            payload.collected_amount = restockPayload.collected_amount;
                        }
                    }

                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to update status');
                    }

                    const previousStatus = this.currentStatus;
                    this.currentStatus = data.status || newStatus;
                    this.selectedStatus = this.currentStatus;
                    if (!this.statusTimestamps) {
                        this.statusTimestamps = {};
                    }
                    this.statusTimestamps[this.currentStatus] = new Date().toISOString();
                    if (data.is_stock_deducted !== undefined) {
                        this.isStockDeducted = Boolean(data.is_stock_deducted);
                    }
                    this.calculateNextStatus();

                    if (data.is_added_into_steadfast) {
                        this.isSteadfastBooked = true;
                        this.steadfastCid = data.steadfast_consignment_id || this.steadfastCid;
                        this.steadfastTracking = data.steadfast_tracking_code || this.steadfastTracking;
                        this.sendToSteadfast = false;
                    }

                    if (data.is_added_into_steadfast || (previousStatus === 'processing' && this.currentStatus === 'shipped' && this.isSteadfastBooked)) {
                        this.$nextTick(() => {
                            this.openPrintLabelModal();
                        });
                    }

                    this.openStatusModal = false;
                    this.openConfirmModal = false;
                    this.showToast(data.message || ('Order status updated to ' + this.getStatusLabel(this.currentStatus)));

                    if (data.reload || newStatus === 'returned' || newStatus === 'cancelled' || restockPayload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 700);
                    }
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.loading = false;
                }
            },

            async bookSteadfastDirect() {
                if (this.steadfastLoading || this.isSteadfastBooked) return;

                this.steadfastLoading = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/send-to-steadfast`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to book in Steadfast');
                    }

                    this.isSteadfastBooked = true;
                    this.steadfastCid = data.consignment_id || data.consignment?.consignment_id || '';
                    this.steadfastTracking = data.tracking_code || data.consignment?.tracking_code || '';
                    if (data.status) {
                        this.currentStatus = data.status;
                        this.selectedStatus = data.status;
                        this.calculateNextStatus();
                    }
                    this.showToast(data.message || 'Successfully booked into Steadfast!');
                    this.$nextTick(() => {
                        this.openPrintLabelModal();
                    });
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.steadfastLoading = false;
                }
            },

            async checkSteadfastLiveStatus() {
                if (this.checkingLiveStatus) return;
                this.checkingLiveStatus = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/steadfast-status`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Could not fetch status from Steadfast');
                    }
                    const statusVal = data.data?.delivery_status || data.data?.status || 'Unknown';
                    this.liveStatus = statusVal;
                    this.showToast('Steadfast Status: ' + statusVal);
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.checkingLiveStatus = false;
                }
            },

            openMetaPurchaseModal() {
                if (this.fbPurchaseSentAt) return;
                this.refreshMetaCaptcha();
                this.metaCaptchaAnswer = '';
                this.metaCaptchaError = '';
                this.showMetaPurchaseModal = true;
                this.$nextTick(() => {
                    const el = document.getElementById('metaCaptchaInput');
                    if (el) el.focus();
                });
            },

            refreshMetaCaptcha() {
                this.metaCaptchaDigit1 = Math.floor(Math.random() * 9) + 1;
                this.metaCaptchaDigit2 = Math.floor(Math.random() * 9) + 1;
                this.metaCaptchaExpected = this.metaCaptchaDigit1 + this.metaCaptchaDigit2;
                this.metaCaptchaAnswer = '';
                this.metaCaptchaError = '';
            },

            async confirmSendMetaPurchase() {
                const answer = parseInt(this.metaCaptchaAnswer, 10);
                if (isNaN(answer) || answer !== this.metaCaptchaExpected) {
                    this.metaCaptchaError = 'Incorrect calculation. Please try again.';
                    this.refreshMetaCaptcha();
                    return;
                }

                this.sendingMetaPurchase = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/send-meta-purchase`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to send Purchase event to Meta.');
                    }

                    this.fbPurchaseSentAt = data.sent_at || 'Just now';
                    this.showMetaPurchaseModal = false;
                    this.showToast(data.message || 'Purchase event sent to Meta successfully! 🎉');
                } catch (error) {
                    this.metaCaptchaError = error.message;
                    this.showToast(error.message, 'error');
                } finally {
                    this.sendingMetaPurchase = false;
                }
            },

            openFraudRefreshModal() {
                this.refreshFraudCaptcha();
                this.fraudCaptchaAnswer = '';
                this.fraudCaptchaError = '';
                this.showFraudCaptchaModal = true;
                this.$nextTick(() => {
                    const el = document.getElementById('fraudCaptchaInput');
                    if (el) el.focus();
                });
            },

            refreshFraudCaptcha() {
                this.fraudCaptchaDigit1 = Math.floor(Math.random() * 9) + 1;
                this.fraudCaptchaDigit2 = Math.floor(Math.random() * 9) + 1;
                this.fraudCaptchaExpected = this.fraudCaptchaDigit1 + this.fraudCaptchaDigit2;
                this.fraudCaptchaAnswer = '';
                this.fraudCaptchaError = '';
            },

            confirmFraudRefresh() {
                const answer = parseInt(this.fraudCaptchaAnswer, 10);
                if (isNaN(answer) || answer !== this.fraudCaptchaExpected) {
                    this.fraudCaptchaError = 'Incorrect calculation. Please try again.';
                    this.refreshFraudCaptcha();
                    return;
                }
                this.showFraudCaptchaModal = false;
                this.checkFraudScore(true);
            },

            async checkFraudScore(force = false) {
                if (!this.customerPhone || this.checkingFraud) return;
                this.checkingFraud = true;
                try {
                    const url = `/admin/dashboard/api/orders/steadfast-fraud-check?phone=${encodeURIComponent(this.customerPhone)}&order_id=${encodeURIComponent(this.orderId)}${force ? '&force=1' : ''}`;
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to check Steadfast record');
                    }
                    if (data.data) {
                        this.fraudCheckData = data.data;
                        if (force) {
                            this.showToast('Customer delivery record updated from Steadfast!');
                        } else if (!data.is_cached) {
                            this.showToast('Customer record retrieved from Steadfast');
                        }
                    } else if (data.is_old_order && !data.data) {
                        if (force) {
                            this.showToast('No record found on Steadfast for this customer.', 'info');
                        }
                    }
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.checkingFraud = false;
                }
            },

            async syncTracking(silent = false) {
                if (this.syncingTracking) return;
                this.syncingTracking = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/sync-steadfast-tracking`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                        }
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to sync Steadfast tracking');
                    }
                    if (data.tracking) {
                        this.trackingLogs = data.tracking;
                    }
                    if (data.status && !['returned', 'cancelled'].includes(this.currentStatus)) {
                        this.currentStatus = data.status;
                        this.selectedStatus = data.status;
                        this.calculateNextStatus();
                    }
                    if (data.payout && !['returned', 'cancelled'].includes(this.currentStatus)) {
                        this.collectedAmount = data.payout.cod_amount;
                        this.deliveryCharge = data.payout.delivery_charge;
                        this.codCharge = data.payout.cod_charge;
                        this.receivableAmount = data.payout.net_receivable;
                    }
                    if (!silent) {
                        this.showToast(data.message || 'Tracking logs synced successfully!');
                    }
                } catch (error) {
                    if (!silent) {
                        this.showToast(error.message, 'error');
                    } else {
                        console.warn('Auto-sync Steadfast tracking:', error.message);
                    }
                } finally {
                    this.syncingTracking = false;
                }
            },

            async saveAdminNote() {
                const noteText = this.newAdminNote.trim();
                if (!noteText || this.savingAdminNote) return;
                this.savingAdminNote = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/admin-notes`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ note: noteText })
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to save admin note');
                    }
                    if (data.notes) {
                        this.adminNotes = data.notes;
                    }
                    this.newAdminNote = '';
                    this.showAddNote = false;
                    this.showToast(data.message || 'Admin note saved successfully!');
                } catch (err) {
                    this.showToast(err.message, 'error');
                } finally {
                    this.savingAdminNote = false;
                }
            },

            openImagePreview(url, title = '') {
                if (!url) return;
                this.previewImageUrl = url;
                this.previewImageTitle = title;
            },

            closeImagePreview() {
                this.previewImageUrl = null;
                this.previewImageTitle = '';
            },

            async deleteOrder() {
                if (this.deletingOrder) return;
                this.deletingOrder = true;
                try {
                    const response = await fetch(`/admin/dashboard/all-orders/${this.orderId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                        }
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to delete order.');
                    }
                    this.showToast(data.message || 'Order moved to trash successfully.');
                    setTimeout(() => {
                        window.location.href = "{{ route('admin.orders') }}";
                    }, 800);
                } catch (err) {
                    this.showToast(err.message, 'error');
                    this.deletingOrder = false;
                }
            }
        }));
    });
</script>
