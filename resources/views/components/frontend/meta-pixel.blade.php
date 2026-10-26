@php
    $fbPixelEnabled = (bool) \App\Models\Setting::get('fb_pixel_enabled', false);
    $fbPixelId = trim((string) \App\Models\Setting::get('fb_pixel_id', ''));
    $fbTrackPageView = (bool) \App\Models\Setting::get('fb_track_page_view', true);

    $advUserData = [];
    if (auth()->check()) {
        $u = auth()->user();
        if (!empty($u->email)) {
            $advUserData['em'] = strtolower(trim($u->email));
        }
        if (!empty($u->phone)) {
            $p = preg_replace('/[^0-9]/', '', (string)$u->phone);
            if (strlen($p) === 11 && str_starts_with($p, '01')) {
                $p = '88' . $p;
            }
            $advUserData['ph'] = $p;
        }
        if (!empty($u->name)) {
            $parts = explode(' ', trim($u->name), 2);
            $advUserData['fn'] = strtolower(trim($parts[0]));
            if (!empty($parts[1])) {
                $advUserData['ln'] = strtolower(trim($parts[1]));
            }
        }
        $advUserData['external_id'] = 'usr_' . $u->id;
    }
@endphp

@if($fbPixelEnabled && !empty($fbPixelId))
<!-- Meta Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    
    fbq('init', '{{ $fbPixelId }}'{!! !empty($advUserData) ? ', ' . json_encode($advUserData) : '' !!});
    @if($fbTrackPageView)
    fbq('track', 'PageView');
    @endif

    // Global Helper for hybrid deduplicated event tracking
    window.trackFbEvent = function(eventName, params, eventId) {
        if (typeof window.fbq === 'function') {
            if (eventId) {
                window.fbq('track', eventName, params || {}, { eventID: eventId });
            } else {
                window.fbq('track', eventName, params || {});
            }
        }
    };
</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->
@endif
