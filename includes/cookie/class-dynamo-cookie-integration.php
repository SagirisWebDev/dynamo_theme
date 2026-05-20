<?php
declare(strict_types=1);

class Dynamo_Cookie_Integration {

    private ?Dynamo_Cookie_Driver $driver = null;

    /** @var callable(): bool */
    private $complianz_detector;

    /** @var callable(): bool */
    private $borlabs_detector;

    public function __construct(
        ?callable $complianz_detector = null,
        ?callable $borlabs_detector   = null
    ) {
        $this->complianz_detector = $complianz_detector
            ?? static fn(): bool => function_exists('cmplz_get_value') || class_exists('COMPLIANZ');

        $this->borlabs_detector = $borlabs_detector
            ?? static fn(): bool => class_exists('BorlabsCookie\Cookie\Cookie') || class_exists('BorlabsCookie');
    }

    public static function boot(): void {
        add_action('after_setup_theme', [new self(), 'detect_and_register'], 11);
    }

    public function detect_and_register(): void {
        $has_complianz = ($this->complianz_detector)();
        $has_borlabs   = ($this->borlabs_detector)();

        if ($has_complianz && $has_borlabs) {
            _doing_it_wrong(
                self::class . '::detect_and_register',
                'Both Complianz and Borlabs Cookie are active. Complianz will be used. Deactivate one to silence this notice.',
                '1.1.0'
            );
            $this->driver = new Dynamo_Cookie_Driver_Complianz();
        } elseif ($has_complianz) {
            $this->driver = new Dynamo_Cookie_Driver_Complianz();
        } elseif ($has_borlabs) {
            $this->driver = new Dynamo_Cookie_Driver_Borlabs();
        }

        if ($this->driver !== null) {
            $this->driver->register_palette_sync_hooks();
            $this->driver->register_embed_hooks();
            add_action('rest_api_init', function () {
                register_rest_route('dynamo/v1', '/cookie-categories', [
                    'methods'             => 'GET',
                    'callback'            => fn() => $this->driver->get_consent_categories(),
                    'permission_callback' => fn() => current_user_can('edit_posts'),
                ]);
            });
        }
    }

    public function get_active_driver(): ?Dynamo_Cookie_Driver {
        return $this->driver;
    }
}
