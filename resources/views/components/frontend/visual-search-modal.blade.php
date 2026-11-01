<div x-data="visualSearchModal()"
     x-cloak
     x-show="isOpen"
     @open-visual-search.window="openModal()"
     @keydown.escape.window="closeModal()"
     style="display: none;"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs">

    <!-- Modal Card -->
    <div @click.away="closeModal()"
         class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden transition-all transform animate-in fade-in zoom-in-95 duration-200">

        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 via-white to-gray-50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="ph-bold ph-camera"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base leading-tight">Visual Product Search</h3>
                    <p class="text-xs text-gray-500">Find products instantly by uploading or taking a photo</p>
                </div>
            </div>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 overflow-y-auto flex-1 space-y-4">

            <!-- Camera Permission / Access Helper Card -->
            <template x-if="cameraPermissionBlocked">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-3 animate-in fade-in">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-lg shadow-xs">
                            <i class="ph-bold ph-video-camera-slash"></i>
                        </div>
                        <div class="flex-1 text-xs">
                            <h4 class="font-bold text-amber-900 text-sm leading-tight mb-1">Camera Permission Needed</h4>
                            <p class="text-amber-800 leading-relaxed">
                                To scan products directly with your camera/webcam, your browser needs camera access. Click the button below, or click the lock/settings icon 🔒 in your browser's address bar next to the URL to set Camera to <strong>"Allow"</strong>.
                            </p>
                        </div>
                        <button type="button" @click="cameraPermissionBlocked = false" class="text-amber-400 hover:text-amber-700 cursor-pointer">
                            <i class="ph ph-x text-base"></i>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 pt-1 flex-wrap">
                        <button type="button"
                                @click="requestCameraPermission()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                            <i class="ph-bold ph-shield-check text-sm"></i>
                            <span>Grant Permission</span>
                        </button>
                        <button type="button"
                                @click="$refs.galleryInput.click(); cameraPermissionBlocked = false"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-amber-100/70 border border-amber-300 text-amber-900 text-xs font-semibold shadow-xs transition cursor-pointer">
                            <i class="ph ph-upload-simple text-sm"></i>
                            <span>Choose Photo from Files</span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- In-Modal Dismissable Error Card -->
            <template x-if="errorMessage">
                <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2.5 text-xs text-red-800 animate-in fade-in">
                    <i class="ph-bold ph-warning-circle text-base text-red-500 shrink-0 mt-0.5"></i>
                    <div class="flex-1">
                        <span class="font-bold block" x-text="errorMessage"></span>
                        <span class="text-red-500 text-[11px]">Please select or snap another clear photo of the product.</span>
                    </div>
                    <button type="button" @click="errorMessage = null" class="text-red-400 hover:text-red-600 cursor-pointer">
                        <i class="ph ph-x text-sm"></i>
                    </button>
                </div>
            </template>

            <!-- Live WebRTC Camera Viewfinder (Desktop Webcam & Mobile Live Stream) -->
            <div x-show="!previewUrl && isCameraActive" x-cloak class="space-y-3">
                <div class="relative w-full aspect-video sm:aspect-[4/3] bg-gray-950 rounded-2xl overflow-hidden shadow-xl border-2 border-brand-500 flex items-center justify-center">
                    <!-- Persistent Video Element (playsinline + webkit-playsinline for mobile iOS/Android) -->
                    <video x-ref="liveVideo"
                           autoplay
                           playsinline
                           webkit-playsinline
                           muted
                           class="w-full h-full object-cover">
                    </video>

                    <!-- Camera Initializing Loader (Prevents Black Screen Void) -->
                    <div x-show="cameraLoading"
                         class="absolute inset-0 flex flex-col items-center justify-center bg-gray-950/95 text-white z-10 transition-opacity">
                        <div class="w-9 h-9 border-3 border-brand-400 border-t-transparent rounded-full animate-spin mb-2"></div>
                        <span class="text-xs font-semibold text-gray-200">Starting camera...</span>
                        <span class="text-[11px] text-gray-400 mt-0.5">Please allow camera access if prompted</span>
                    </div>

                    <!-- Centered Target Overlay Frame -->
                    <div x-show="!cameraLoading" class="absolute inset-0 pointer-events-none flex items-center justify-center p-6 sm:p-8">
                        <div class="w-full h-full max-w-xs max-h-64 border-2 border-dashed border-white/70 rounded-2xl shadow-lg relative flex items-center justify-center">
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-black/80 backdrop-blur-xs text-white text-[11px] font-medium px-3 py-0.5 rounded-full shadow-xs whitespace-nowrap">
                                Center product here
                            </span>
                        </div>
                    </div>

                    <!-- Viewfinder Top Bar (Flip Camera & Close) -->
                    <div class="absolute top-3 inset-x-3 flex items-center justify-between z-20 pointer-events-auto">
                        <!-- Camera Flip (Front / Back toggle) -->
                        <button type="button"
                                @click="toggleCameraFacing()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-black/60 hover:bg-black/80 text-white text-xs font-medium backdrop-blur-xs transition cursor-pointer shadow-md">
                            <i class="ph-bold ph-arrows-clockwise text-sm"></i>
                            <span x-text="cameraFacingMode === 'environment' ? 'Flip to Front' : 'Flip to Back'"></span>
                        </button>

                        <!-- Close Camera button -->
                        <button type="button"
                                @click="stopLiveCamera()"
                                class="w-8 h-8 rounded-full bg-black/60 hover:bg-black text-white flex items-center justify-center transition cursor-pointer shadow-md">
                            <i class="ph ph-x text-base"></i>
                        </button>
                    </div>

                </div>

                <!-- Live Camera Controls -->
                <div class="flex items-center justify-between gap-2 px-2 pt-1">
                    <button type="button"
                            @click="stopLiveCamera()"
                            class="text-xs font-semibold text-gray-500 hover:text-gray-800 px-3 py-2 rounded-xl hover:bg-gray-100 transition cursor-pointer">
                        &larr; Upload File
                    </button>

                    <button type="button"
                            @click="captureLivePhoto()"
                            :disabled="cameraLoading"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white text-xs font-bold shadow-md shadow-brand-600/30 active:scale-95 transition cursor-pointer">
                        <i class="ph-bold ph-camera text-base"></i>
                        <span>Snap Photo</span>
                    </button>

                    <div class="w-16"></div>
                </div>
            </div>

            <!-- Upload Area (when no image selected and camera inactive) -->
            <div x-show="!previewUrl && !isCameraActive" class="space-y-4">
                <div @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)"
                     @click="$refs.galleryInput.click()"
                     :class="isDragging ? 'border-brand-500 bg-brand-50/50 scale-[0.99]' : 'border-gray-200 hover:border-brand-400 hover:bg-gray-50/70'"
                     class="border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center cursor-pointer transition-all duration-200 group">
                    
                    <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-xs">
                        <i class="ph-bold ph-image text-2xl"></i>
                    </div>
                    
                    <h4 class="font-bold text-gray-800 text-sm mb-1">Upload or Take Product Photo</h4>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto">Snap with your camera or upload from files to find matching products</p>
                    
                    <!-- Clean 2 Buttons (Take Photo & Choose File) -->
                    <div class="mt-4 flex items-center justify-center gap-2.5 flex-wrap" @click.stop>
                        <button type="button"
                                @click.stop="openCamera()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20 transition active:scale-95 cursor-pointer">
                            <i class="ph-bold ph-camera text-base"></i>
                            <span>Take Photo</span>
                        </button>

                        <button type="button"
                                @click.stop="$refs.galleryInput.click()"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-gray-50 border border-gray-200 text-xs font-semibold text-gray-700 shadow-xs transition active:scale-95 cursor-pointer">
                            <i class="ph ph-upload-simple text-base"></i>
                            <span>Choose File</span>
                        </button>
                    </div>
                    
                    <!-- Standard Gallery / File Picker -->
                    <input type="file"
                           x-ref="galleryInput"
                           accept="image/*,.heic,.heif"
                           @change="handleFileSelect($event)"
                           class="hidden">

                    <!-- Direct Live Mobile Camera Input (Environment / Back Camera) -->
                    <input type="file"
                           x-ref="cameraInput"
                           accept="image/*"
                           capture="environment"
                           @change="handleFileSelect($event)"
                           class="hidden">
                </div>

                <!-- Quick hints -->
                <div class="bg-gray-50 rounded-xl p-3 text-[11px] text-gray-500 flex items-start gap-2 border border-gray-100">
                    <i class="ph-bold ph-lightbulb text-amber-500 text-base shrink-0 mt-0.5"></i>
                    <span><strong>Tip:</strong> For best accuracy, snap a photo centered on the product or clean screenshot with good lighting.</span>
                </div>
            </div>

            <!-- Scanning & Analyzing State -->
            <template x-if="previewUrl && isSearching">
                <div class="py-6 text-center space-y-4">
                    <div class="relative w-36 h-36 mx-auto rounded-2xl overflow-hidden shadow-md border-2 border-brand-500">
                        <img :src="previewUrl" class="w-full h-full object-cover">
                        <!-- Animated Scanner Line -->
                        <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-brand-400 to-transparent shadow-lg shadow-brand-500/50 animate-bounce"></div>
                        <div class="absolute inset-0 bg-brand-600/10 backdrop-blur-[1px]"></div>
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">
                            <i class="ph ph-spinner animate-spin"></i>
                            <span>Scanning image features & matching catalog...</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Comparing visual gradients, shapes, and color signatures</p>
                    </div>
                </div>
            </template>

            <!-- Results View -->
            <template x-if="previewUrl && !isSearching">
                <div class="space-y-4">
                    <!-- Query Info Bar -->
                    <div class="flex items-center justify-between bg-gray-50 p-2.5 rounded-xl border border-gray-100 gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <img :src="previewUrl" class="w-10 h-10 rounded-lg object-cover border border-gray-200 shrink-0">
                            <div class="truncate">
                                <span class="text-xs font-bold text-gray-900 block leading-tight">Searched Image</span>
                                <span class="text-[11px] text-gray-500" x-text="results.length + ' matching product(s) found'"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <template x-if="pageUrl">
                                <a :href="pageUrl" class="inline-flex items-center gap-1 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 px-3 py-1.5 rounded-lg shadow-xs transition">
                                    <span>Full Page</span> &rarr;
                                </a>
                            </template>
                            <button type="button" @click="resetSearch()" class="text-xs font-semibold text-brand-600 hover:text-brand-800 px-2 py-1 rounded-lg hover:bg-brand-50 transition cursor-pointer">
                                <i class="ph ph-arrow-counter-clockwise mr-1"></i> Try Another
                            </button>
                        </div>
                    </div>

                    <!-- No Results Found -->
                    <template x-if="results.length === 0">
                        <div class="py-8 text-center space-y-3">
                            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-xl">
                                <i class="ph ph-magnifying-glass"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">No close visual match found</h4>
                                <p class="text-xs text-gray-500 max-w-xs mx-auto mt-1">We couldn't find a matching product with this photo. Try taking a photo with more light or a closer crop of the item.</p>
                            </div>
                            <button type="button" @click="resetSearch()" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                                Upload Another Photo
                            </button>
                        </div>
                    </template>

                    <!-- Matching Products Grid -->
                    <template x-if="results.length > 0">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[55vh] overflow-y-auto p-1">
                            <template x-for="item in results" :key="item.id">
                                <a :href="item.url"
                                   class="group block bg-white border border-gray-100 hover:border-brand-500 rounded-xl overflow-hidden shadow-xs hover:shadow-md transition-all duration-200">
                                    <!-- Product Image & Match Badge -->
                                    <div class="aspect-square bg-gray-50 relative overflow-hidden">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                <i class="ph ph-image text-2xl"></i>
                                            </div>
                                        </template>

                                        <!-- Match Score Badge -->
                                        <div class="absolute top-1.5 left-1.5">
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold shadow-xs"
                                                  :class="item.score >= 80 ? 'bg-emerald-600 text-white' : (item.score >= 60 ? 'bg-indigo-600 text-white' : 'bg-gray-800/80 text-white backdrop-blur-xs')">
                                                <i class="ph-bold ph-sparkle text-[9px]"></i>
                                                <span x-text="item.score_label"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="p-2.5">
                                        <h5 class="text-xs font-bold text-gray-800 line-clamp-2 group-hover:text-brand-600 transition-colors leading-snug" x-text="item.title"></h5>
                                        <div class="mt-1.5 flex items-center justify-between">
                                            <span class="text-xs font-bold text-brand-600 font-mono" x-text="'৳' + item.base_price"></span>
                                            <span class="text-[10px] text-gray-400 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-all">
                                                View &rarr;
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    function visualSearchModal() {
        return {
            isOpen: false,
            isDragging: false,
            isSearching: false,
            isMobile: false,
            isCameraActive: false,
            cameraLoading: false,
            cameraFacingMode: 'environment',
            showNativeCameraFallback: false,
            cameraPermissionBlocked: false,
            cameraStream: null,
            fallbackTimer: null,
            previewUrl: null,
            pageUrl: null,
            errorMessage: null,
            results: [],

            init() {
                this.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                // Lazily load ZXing universal barcode reader (fast 110KB library, decodes EAN-13, EAN-8, UPC, Code128, QR)
                if (!window.ZXing && !document.getElementById('zxing-cdn-script')) {
                    const z = document.createElement('script');
                    z.id = 'zxing-cdn-script';
                    z.src = 'https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
                    z.async = true;
                    document.head.appendChild(z);
                }

                // Lazily load Tesseract.js in background so OCR is ready on demand without slowing page load
                if (!window.Tesseract && !document.getElementById('tesseract-cdn-script')) {
                    const s = document.createElement('script');
                    s.id = 'tesseract-cdn-script';
                    s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
                    s.async = true;
                    document.head.appendChild(s);
                }
            },

            openModal() {
                this.isOpen = true;
                this.errorMessage = null;
                this.cameraPermissionBlocked = false;
            },

            closeModal() {
                this.stopLiveCamera();
                this.cameraPermissionBlocked = false;
                this.isOpen = false;
            },

            resetSearch() {
                this.stopLiveCamera();
                this.cameraPermissionBlocked = false;
                this.previewUrl = null;
                this.pageUrl = null;
                this.errorMessage = null;
                this.results = [];
                this.isSearching = false;
                if (this.$refs.galleryInput) {
                    this.$refs.galleryInput.value = '';
                }
                if (this.$refs.cameraInput) {
                    this.$refs.cameraInput.value = '';
                }
            },

            async requestCameraPermission() {
                this.cameraPermissionBlocked = false;
                this.errorMessage = null;
                await this.openCamera();
            },

            useNativeCamera() {
                this.stopLiveCamera();
                if (this.$refs.cameraInput) {
                    this.$refs.cameraInput.click();
                }
            },

            async toggleCameraFacing() {
                this.cameraFacingMode = this.cameraFacingMode === 'environment' ? 'user' : 'environment';
                await this.startCameraStream();
            },

            /**
             * Open Live Camera Viewfinder (Desktop Webcam & Mobile WebRTC)
             */
            async openCamera() {
                this.errorMessage = null;
                this.cameraPermissionBlocked = false;

                // Check for Insecure Context (HTTP instead of HTTPS):
                if (!window.isSecureContext && location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    if (this.isMobile && this.$refs.cameraInput) {
                        this.$refs.cameraInput.click();
                        return;
                    }
                    this.cameraPermissionBlocked = true;
                    this.errorMessage = 'Live camera requires an encrypted HTTPS connection. Please access via HTTPS or select a photo from your files.';
                    return;
                }

                // Fallback if browser does not support getUserMedia:
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    if (this.isMobile && this.$refs.cameraInput) {
                        this.$refs.cameraInput.click();
                    } else {
                        this.cameraPermissionBlocked = true;
                    }
                    return;
                }

                await this.startCameraStream();
            },

            async startCameraStream() {
                this.stopLiveCameraStreamOnly();
                this.isCameraActive = true;
                this.cameraLoading = true;
                this.showNativeCameraFallback = false;
                this.errorMessage = null;

                if (this.fallbackTimer) clearTimeout(this.fallbackTimer);
                this.fallbackTimer = setTimeout(() => {
                    if (this.isCameraActive && this.cameraLoading) {
                        this.showNativeCameraFallback = true;
                    }
                }, 3500);

                try {
                    let stream = null;

                    // Request HD stream with fallback to standard resolution
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            audio: false,
                            video: {
                                facingMode: { ideal: this.cameraFacingMode },
                                width: { ideal: 1920, min: 1280 },
                                height: { ideal: 1080, min: 720 }
                            }
                        });
                    } catch (errHd) {
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: {
                                    facingMode: { ideal: this.cameraFacingMode }
                                }
                            });
                        } catch (err1) {
                            console.warn('Ideal facingMode constraint failed, falling back to basic video constraint:', err1);
                            stream = await navigator.mediaDevices.getUserMedia({
                                audio: false,
                                video: true
                            });
                        }
                    }

                    this.cameraStream = stream;
                    this.cameraPermissionBlocked = false;

                    const video = this.$refs.liveVideo;
                    if (video) {
                        // Critical mobile properties (must be set in JS DOM):
                        video.muted = true;
                        video.defaultMuted = true;
                        video.playsInline = true;
                        video.setAttribute('playsinline', '');
                        video.setAttribute('webkit-playsinline', '');
                        video.srcObject = stream;

                        const onVideoActive = () => {
                            this.cameraLoading = false;
                            this.showNativeCameraFallback = false;
                            if (this.fallbackTimer) clearTimeout(this.fallbackTimer);
                        };

                        video.onloadedmetadata = () => {
                            video.play().then(onVideoActive).catch(e => {
                                console.warn('Video play error on loadedmetadata:', e);
                            });
                        };

                        video.onplaying = onVideoActive;
                        video.onloadeddata = onVideoActive;

                        // Immediate play attempt
                        video.play().then(onVideoActive).catch(e => {
                            // Autoplay may wait for loadedmetadata
                        });
                    }
                } catch (err) {
                    console.warn('Live camera stream error:', err);
                    this.stopLiveCamera();

                    // Fallback to phone's native camera app if on mobile
                    if (this.isMobile && this.$refs.cameraInput) {
                        this.$refs.cameraInput.click();
                        return;
                    }

                    this.cameraPermissionBlocked = true;
                }
            },

            /**
             * Stop Live Camera stream and release hardware without resetting UI flags
             */
            stopLiveCameraStreamOnly() {
                if (this.cameraStream) {
                    try {
                        this.cameraStream.getTracks().forEach(track => track.stop());
                    } catch (e) {}
                    this.cameraStream = null;
                }
                if (this.$refs.liveVideo) {
                    try {
                        this.$refs.liveVideo.srcObject = null;
                    } catch (e) {}
                }
            },

            /**
             * Stop Live Camera stream and return UI to idle
             */
            stopLiveCamera() {
                this.stopLiveCameraStreamOnly();
                this.isCameraActive = false;
                this.cameraLoading = false;
                this.showNativeCameraFallback = false;
                if (this.fallbackTimer) {
                    clearTimeout(this.fallbackTimer);
                    this.fallbackTimer = null;
                }
            },

            /**
             * Snap a high-res photo from the live video stream
             */
            captureLivePhoto() {
                if (!this.$refs.liveVideo) return;
                const video = this.$refs.liveVideo;

                if (!video.videoWidth || video.videoWidth === 0) {
                    this.errorMessage = 'Camera is still initializing. Please wait a moment and tap again.';
                    return;
                }

                const width = video.videoWidth;
                const height = video.videoHeight;

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, width, height);

                this.stopLiveCamera();

                canvas.toBlob((blob) => {
                    if (blob) {
                        const file = new File([blob], 'snapshot_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                        this.processImage(file);
                    }
                }, 'image/jpeg', 0.92);
            },

            handleFileSelect(event) {
                const file = event.target.files && event.target.files[0];
                if (file) {
                    this.processImage(file);
                }
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
                if (file) {
                    this.processImage(file);
                }
            },

            /**
             * Smart Client-Side Canvas Compression:
             * Resizes raw 2MB - 25MB phone photos / iPhone HEIC to an optimized crisp HD JPEG.
             * High clarity (1400px, 90% quality) avoids blurriness and makes barcodes easily readable.
             */
            compressImageFile(file, maxDimension = 1400, quality = 0.90) {
                return new Promise((resolve) => {
                    if (!file) return resolve(file);

                    // If file is already tiny JPEG (< 180KB), skip canvas step
                    if (file.size < 180000 && (file.type === 'image/jpeg' || file.type === 'image/jpg')) {
                        return resolve(file);
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = new Image();
                        img.onload = () => {
                            let width = img.width;
                            let height = img.height;

                            if (width > maxDimension || height > maxDimension) {
                                if (width > height) {
                                    height = Math.round((height * maxDimension) / width);
                                    width = maxDimension;
                                } else {
                                    width = Math.round((width * maxDimension) / height);
                                    height = maxDimension;
                                }
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = Math.max(1, width);
                            canvas.height = Math.max(1, height);
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0, width, height);

                            canvas.toBlob((blob) => {
                                if (blob) {
                                    const safeName = (file.name || 'image').replace(/\.[^/.]+$/, "") + ".jpg";
                                    resolve(new File([blob], safeName, { type: 'image/jpeg' }));
                                } else {
                                    resolve(file);
                                }
                            }, 'image/jpeg', quality);
                        };
                        img.onerror = () => resolve(file);
                        img.src = e.target.result;
                    };
                    reader.onerror = () => resolve(file);
                    reader.readAsDataURL(file);
                });
            },

            async extractDetectedText(file) {
                let detected = '';

                // 1. High-Precision Barcode Detection (EAN-13, EAN-8, UPC-A, Code 128, QR)
                // Native BarcodeDetector (Chrome Android / Chromium)
                if ('BarcodeDetector' in window) {
                    try {
                        const formats = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'qr_code'];
                        const detector = new BarcodeDetector({ formats });
                        const imgBitmap = await createImageBitmap(file);
                        const barcodes = await detector.detect(imgBitmap);
                        if (barcodes && barcodes.length > 0) {
                            detected += barcodes.map(b => b.rawValue).join(' ') + ' ';
                        }
                    } catch (e) {}
                }

                // Universal ZXing Reader (works universally on iOS Safari, Android, and Desktop)
                if (!detected.trim() && window.ZXing && window.ZXing.BrowserMultiFormatReader) {
                    try {
                        const codeReader = new window.ZXing.BrowserMultiFormatReader();
                        const imgUrl = URL.createObjectURL(file);
                        try {
                            const result = await codeReader.decodeFromImageUrl(imgUrl);
                            if (result && result.getText()) {
                                detected += result.getText() + ' ';
                            }
                        } catch (e) {
                            // No barcode decoded
                        } finally {
                            URL.revokeObjectURL(imgUrl);
                        }
                    } catch (e) {}
                }

                // 2. Client-side OCR for packaging text / brand names (e.g. NIVEA, Dove, Vaseline)
                if (window.Tesseract && typeof window.Tesseract.recognize === 'function') {
                    try {
                        const ocrPromise = window.Tesseract.recognize(file, 'eng');
                        const timeoutPromise = new Promise(resolve => setTimeout(() => resolve(null), 3000));
                        const res = await Promise.race([ocrPromise, timeoutPromise]);
                        if (res && res.data && res.data.text) {
                            detected += ' ' + res.data.text;
                        }
                    } catch (e) {}
                }

                return detected.trim();
            },

            async processImage(file) {
                this.errorMessage = null;

                if (!file) {
                    this.errorMessage = 'Please select an image file to search.';
                    return;
                }

                this.previewUrl = URL.createObjectURL(file);
                this.isSearching = true;
                this.results = [];

                try {
                    // Compress raw high-res mobile/camera file to lightweight standard JPEG
                    const uploadFile = await this.compressImageFile(file);

                    // Extract text, brand, SKU or barcode from image if present
                    let ocrText = '';
                    try {
                        ocrText = await this.extractDetectedText(file);
                    } catch (e) {}

                    const formData = new FormData();
                    formData.append('image', uploadFile);
                    if (ocrText) {
                        formData.append('ocr_text', ocrText);
                    }

                    const response = await fetch('{{ route('api.search.image') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success && data.page_url) {
                        this.pageUrl = data.page_url;
                        this.results = data.results || [];
                        // Redirect directly to the search page with visual results
                        window.location.href = data.page_url;
                    } else if (data.success) {
                        this.results = data.results || [];
                        this.isSearching = false;
                    } else {
                        this.errorMessage = data.message || 'Visual search could not process this image. Please try another photo.';
                        this.isSearching = false;
                        this.previewUrl = null;
                    }
                } catch (err) {
                    console.error('Visual search error:', err);
                    this.errorMessage = 'Connection or processing error. Please try another photo.';
                    this.isSearching = false;
                    this.previewUrl = null;
                }
            }
        };
    }
</script>

