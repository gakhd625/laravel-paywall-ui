@if($slot && !$slot->isEmpty())
    <div class="paywall-ui-background-content">
        @if($gibberish && $opacity > 0.7)
            {!! $htmlToGibberish((string) $slot) !!}
        @else
            {{ $slot }}
        @endif
    </div>
@endif

<div class="paywall-ui-overlay paywall-ui-blur" style="--paywall-opacity: {{ $opacity }};">
    <div class="paywall-ui-backdrop"></div>
    <div class="paywall-ui-content-wrapper">
        <div class="paywall-ui-content-center paywall-ui-card">
            <div class="paywall-ui-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            @if($title)
                <h2 class="paywall-ui-title">{{ $title }}</h2>
            @endif
            <p class="paywall-ui-message">{{ $message }}</p>
        </div>
    </div>
</div>

<style>
    .paywall-ui-blur {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        background-color: rgba(0, 0, 0, 0.3);
    }
    .paywall-ui-card {
        background: white;
        border-radius: 1rem;
        padding: 3rem 2rem;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
</style>
