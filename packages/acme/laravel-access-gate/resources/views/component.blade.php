<div id="{{ $id }}" class="paywall-ui-wrapper {{ $id }}-wrapper @if($class) {{ $class }} @endif">
    @if($shouldDisplay())
        @if($customView)
            @include($customView)
        @else
            @if($mode === 'blur')
                @include('paywall-ui::modes.blur')
            @else
                @include('paywall-ui::modes.solid')
            @endif
        @endif
    @else
        {{ $slot }}
    @endif
</div>

<style>
    .paywall-ui-wrapper {
        padding: 0;
        margin: 0;
    }
    .paywall-ui-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: var(--paywall-opacity, 1);
        transition: opacity 0.3s ease;
    }
    .paywall-ui-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    .paywall-ui-content-center {
        position: relative;
        z-index: 1;
    }
    .paywall-ui-title {
        margin: 0 0 1rem 0;
        font-size: 2rem;
        font-weight: 700;
        color: #111827;
    }
    .paywall-ui-message {
        margin: 0 0 1rem 0;
        font-size: 1.125rem;
        line-height: 1.75;
        color: #4b5563;
    }
    .paywall-ui-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1.5rem;
    }
    .paywall-ui-icon svg {
        width: 100%;
        height: 100%;
    }
</style>
