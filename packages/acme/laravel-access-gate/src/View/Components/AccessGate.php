<?php

namespace Acme\AccessGate\View\Components;

use Acme\AccessGate\AccessGateService;
use Acme\AccessGate\Support\GibberishObfuscator;
use Acme\AccessGate\Support\SafeViewPathValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AccessGate extends \Illuminate\View\Component
{
    public bool $isRestricted;

    public float $opacity;

    public string $mode;

    public string $message;

    public ?string $title;

    public string $id;

    public ?string $class;

    public ?string $customView;

    public bool $gibberish;

    public ?string $expiresAt;

    public ?string $feature;

    public ?string $scope;

    public function __construct(
        ?string $expiresAt = null,
        ?string $mode = null,
        ?string $feature = null,
        ?string $scope = null,
        bool $useApiMessage = true,
        ?string $customView = null,
        ?string $id = null,
        ?string $class = null,
        bool $gibberish = false,
        protected ?AccessGateService $accessGate = null,
        protected ?SafeViewPathValidator $viewValidator = null,
        protected ?GibberishObfuscator $gibberishObfuscator = null,
    ) {
        $this->accessGate = $accessGate ?? app(AccessGateService::class);
        $this->viewValidator = $viewValidator ?? app(SafeViewPathValidator::class);
        $this->gibberishObfuscator = $gibberishObfuscator ?? app(GibberishObfuscator::class);

        $this->expiresAt = $expiresAt ?: config('paywall-ui.expiration.default_expires_at');
        $this->feature = $feature;
        $this->scope = $scope;
        $this->isRestricted = $this->accessGate->isRestricted($scope, $feature, $this->expiresAt);
        $this->opacity = $this->accessGate->getOverlayOpacity($scope, $feature, $this->expiresAt);
        $this->mode = $this->validateMode($mode ?? config('paywall-ui.ui.mode', 'blur'));
        $this->customView = $this->viewValidator->validate($customView ?? config('paywall-ui.component.custom_view'));
        $this->message = $useApiMessage
            ? $this->accessGate->getMessage($scope, $feature)
            : config('paywall-ui.messages.message', 'This content is temporarily unavailable.');
        $this->title = config('paywall-ui.messages.title');
        $this->id = $this->sanitizeId($id ?? 'ag-' . Str::random(4));
        $this->class = $this->sanitizeClass($class);
        $this->gibberish = $gibberish ?: config('paywall-ui.ui.gibberish', false);
    }

    public function shouldDisplay(): bool
    {
        return $this->isRestricted || $this->opacity > 0;
    }

    public function htmlToGibberish(string $html): HtmlString
    {
        return $this->gibberishObfuscator->obfuscate($html);
    }

    public function render(): View
    {
        return view('paywall-ui::component');
    }

    protected function validateMode(?string $mode): string
    {
        return in_array($mode, ['blur', 'solid'], true) ? $mode : 'blur';
    }

    protected function sanitizeId(?string $id): string
    {
        if ($id === null || $id === '') {
            return 'ag-' . Str::random(4);
        }
        $sanitized = preg_replace('/[^a-zA-Z0-9-]/', '', $id);
        return $sanitized !== '' ? $sanitized : 'ag-' . Str::random(4);
    }

    protected function sanitizeClass(?string $class): ?string
    {
        if ($class === null || $class === '') {
            return null;
        }
        return preg_replace('/[^a-zA-Z0-9\s_-]/', '', $class) ?: null;
    }
}
