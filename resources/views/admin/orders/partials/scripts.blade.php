<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderActions', (orderId, currentStatus, isBooked, cid, trackingCode, customerPhone, initialFraudData, isOrderOlderThan10Days, isStockDeducted, orderItems) => ({
            orderId: orderId,
            currentStatus: currentStatus,
            selectedStatus: currentStatus,
            isSteadfastBooked: Boolean(isBooked),
            steadfastCid: cid || '',
            steadfastTracking: trackingCode || '',
            customerPhone: customerPhone || '',
            sendToSteadfast: false,
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
            codCharge: {{ (float) ($order->steadfast_cod_charge ?: 0) }},
            receivableAmount: {{ (float) ($order->steadfast_receivable_amount ?: 0) }},
            openStatusModal: false,
            openConfirmModal: false,
            openDeleteModal: false,
            deletingOrder: false,
            showPrintLabelModal: false,
            loading: false,
            nextStatus: null,
            steps: ['pending', 'confirmed', 'processing', 'shipped', 'delivered'],
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
                this.restockItems = (this.orderItems || []).map(item => ({
                    id: item.id,
                    name: item.name,
                    sku: item.sku,
                    quantity: item.quantity,
                    return_qty: item.quantity,
                    selected: true,
                }));
                this.showRestockModal = true;
            },

            selectFullRestock() {
                this.restockItems.forEach(item => {
                    item.selected = true;
                    item.return_qty = item.quantity;
                });
            },

            selectNoRestock() {
                this.restockItems.forEach(item => {
                    item.selected = false;
                    item.return_qty = 0;
                });
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
                        restock_reason: this.restockReason
                    });
                    this.showRestockModal = false;
                } finally {
                    this.submittingRestock = false;
                }
            },

            getStatusLabel(status) {
                if (!status) return '';
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
                    'bg-red-50 text-red-600': status === 'cancelled' || status === 'returned',
                };
            },

            get isTerminalBad() {
                return ['cancelled', 'returned'].includes(this.currentStatus);
            },

            isDone(step) {
                const current = this.steps.indexOf(this.currentStatus);
                return current !== -1 && this.steps.indexOf(step) < current;
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
            },

            closePrintLabelModal() {
                this.showPrintLabelModal = false;
            },

            printShippingLabel() {
                window.print();
            },

            async updateStatus(newStatus, restockPayload = null) {
                if (!newStatus || this.loading) return;

                if (this.currentStatus === 'shipped' && newStatus === 'cancelled') {
                    this.showToast('Orders cannot be cancelled once shipped. Please choose Returned or Delivered.', 'error');
                    return;
                }

                if (newStatus === this.currentStatus && !(this.currentStatus === 'processing' && newStatus === 'shipped' && this.sendToSteadfast && !this.isSteadfastBooked)) {
                    this.openStatusModal = false;
                    this.openConfirmModal = false;
                    return;
                }

                this.loading = true;

                try {
                    const shouldSendToSteadfast = (this.currentStatus === 'processing' && newStatus === 'shipped' && this.sendToSteadfast && !this.isSteadfastBooked);
                    const payload = { 
                        status: newStatus,
                        send_to_steadfast: shouldSendToSteadfast
                    };

                    if (restockPayload) {
                        if (restockPayload.restock_items) {
                            payload.restock_items = restockPayload.restock_items;
                        }
                        if (restockPayload.restock_reason) {
                            payload.restock_reason = restockPayload.restock_reason;
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

            async syncTracking() {
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
                    if (data.status) {
                        this.currentStatus = data.status;
                        this.selectedStatus = data.status;
                        this.calculateNextStatus();
                    }
                    if (data.payout) {
                        this.collectedAmount = data.payout.cod_amount;
                        this.deliveryCharge = data.payout.delivery_charge;
                        this.codCharge = data.payout.cod_charge;
                        this.receivableAmount = data.payout.net_receivable;
                    }
                    this.showToast(data.message || 'Tracking logs synced successfully!');
                } catch (error) {
                    this.showToast(error.message, 'error');
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
