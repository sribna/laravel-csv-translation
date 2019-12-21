<?php

namespace Sribna\CsvTranslation;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Class CsvTranslationServiceProvider
 * @package Sribna\CsvTranslation
 */
class CsvTranslationServiceProvider extends ServiceProvider
{
    /**
     * Boot application functions
     */
    public function boot()
    {
        $this->setCsvTranslator();
        $this->setBladeDirectives();
    }

    /**
     * Register application services.
     */
    public function register()
    {
        $this->app->singleton('csv-translator', Translation::class);
    }

    /**
     * Set custom Blade directives
     */
    protected function setBladeDirectives()
    {
        Blade::directive('trans_context', function ($expression) {

            if ($expression === "") {
                $context = null;
            } else {
                $context = str_replace(array('"', "'"), '', $expression);
            }

            trans_context($context);
        });

        Blade::directive('t', function ($expression) {
            return "<?php echo t($expression); ?>";
        });

        Blade::directive('tp', function ($expression) {
            return "<?php echo tp($expression); ?>";
        });
    }

    /**
     * Set up the translator
     */
    protected function setCsvTranslator()
    {
        /** @var Translation $translator */
        $translator = $this->app['csv-translator'];

        $translator->directory($this->app->langPath())
            ->locale($this->app->getLocale())
            ->context('common')
            ->init();

        $this->app['router']->matched(function ($router) use ($translator) {

            $context = $router->route->uri();

            if ($context === '/') {
                $context = 'front';
            }

            $translator->context($context);
        });
    }

}