<?php

namespace PluginMaster\Foundation\SideMenu;

use PluginMaster\Contracts\Foundation\ApplicationInterface;
use PluginMaster\Contracts\SideMenu\SideMenuHandlerInterface;
use PluginMaster\Contracts\SideMenu\SideMenuInterface;
use PluginMaster\Foundation\Resolver\CallbackResolver;
use WP_Error;

class SideMenuHandler implements SideMenuHandlerInterface
{

    /**
     * @var ApplicationInterface
     */
    public ApplicationInterface $appInstance;

    /**
     * @var bool
     */
    public bool $fileLoad = false;

    /**
     * @var string
     */
    protected string $controllerNamespace = "";

    /**
     * @var string
     */
    protected string $methodSeparator = "@";


    /**
     * @var array
     */
    protected array $parentSlug = [];

    /**
     * @param  ApplicationInterface  $instance
     * @return $this
     */
    public function setAppInstance(ApplicationInterface $instance): self
    {
        $this->appInstance = $instance;
        return $this;
    }


    /**
     * @param  string  $namespace
     * @return SideMenuHandler
     */
    public function setNamespace(string $namespace): self
    {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    /**
     * @param  string  $sidemenu
     * @return SideMenuHandler
     */
    public function loadMenuFile(string $sidemenu): self
    {
        $this->fileLoad = true;

        require $sidemenu;

        $this->fileLoad = false;
        return $this;
    }

    /**
     * @param SideMenuInterface $sideMenuObject
     */
    public function setSideMenu(SideMenuInterface $sideMenuObject): void
    {
        foreach ($sideMenuObject->getData() as $sidemenu){

            $pageTitle = __($sidemenu['title'], $this->appInstance->config('slug'));
            $menuTitle = __($sidemenu['menu_title'], $this->appInstance->config('slug'));

            if(isset($sidemenu['submenu'])){

                add_submenu_page(
                    $sidemenu['parent_slug'],
                    $pageTitle,
                    $menuTitle,
                    $options['capability'] ?? 'manage_options',
                    $sidemenu['slug'],
                    $sidemenu['callback'] ?? $this->getCallback($sidemenu['callback']),
                    $options['position'] ?? 10
                );

            }else{

                add_menu_page(
                    $pageTitle,
                    $menuTitle,
                    $sidemenu['capability'] ?? 'manage_options',
                    $sidemenu['slug'],
                    $sidemenu['callback'] ?? $this->getCallback($sidemenu['callback']),
                    $options['icon'] ?? '',
                    $options['position'] ?? 100
                );
            }
        }
    }


    public function addMenuPage(string $slug, array $options): void
    {
        if ($this->fileLoad) {
            $this->registerParentMenu($options, $slug);
        } else {
            add_action('admin_menu', function () use ($options, $slug) {
                $this->registerParentMenu($options, $slug);
            });
        }
    }

    /**
     * @param  array  $options
     * @param  string  $slug
     */
    public function registerParentMenu(array $options, string $slug): void
    {
        $pageTitle = __($options['title'] ?? $options['page_title'], $this->appInstance->config('slug'));
        $menuTitle = __($options['menu_title'] ?? $pageTitle, $this->appInstance->config('slug'));

        add_menu_page(
            $pageTitle,
            $menuTitle,
            $options['capability'] ?? 'manage_options',
            $slug,
            $this->getCallback($options),
            $options['icon'] ?? '',
            $options['position'] ?? 100
        );

        $this->parentSlug[] = $slug;
    }

    private function getCallback(array $options)
    {
        $callback = $options['as'] ?? $options['callback'];
        return $callback ? CallbackResolver::resolve($callback, $this->callbackResolverOptions()) : '';
    }

    private function callbackResolverOptions(): array
    {
        return [
            "methodSeparator" => $this->methodSeparator,
            'namespace' => $this->controllerNamespace,
            'container' => $this->appInstance
        ];
    }

    /**
     *remove first sub-menu
     */
    public function removeFirstSubMenu(): void
    {
        foreach ($this->parentSlug as $slug) {
            remove_submenu_page($slug, $slug);
        }
    }

    public function validateOptions(array $options, bool $parent = true): void
    {
        $requiredOption = [];

        if (!isset($options['title']) || !isset($options['page_title'])) {
            $requiredOption[] = 'title/page_title';
        }

        if (!isset($options['as']) || !isset($options['callback'])) {
            $requiredOption[] = 'as/callback';
        }

        if (!$parent && (!isset($options['parent']) || !isset($options['parent_slug']))) {
            $requiredOption[] = 'parent/parent_slug';
        }

        if (!empty($requiredOption)) {
            new WP_Error('option_missing', "SideNav Option missing. Required Options: ".implode(', ', $requiredOption));
        }
    }

    /**
     * @param  string  $slug
     * @param  array  $options
     * @param  string  $parentSlug
     * @return void
     */
    public function addSubMenuPage(string $slug, array $options, string $parentSlug = ''): void
    {
        $pageTitle = __($options['title'] ?? $options['page_title'], $this->appInstance->config('slug'));
        $menuTitle = __($options['menu_title'] ?? $pageTitle, $this->appInstance->config('slug'));

        add_submenu_page(
            $parentSlug ? $parentSlug : ($options['parent'] ?? $options['parent_slug']),
            $pageTitle,
            $menuTitle,
            $options['capability'] ?? 'manage_options',
            $slug,
            $this->getCallback($options),
            $options['position'] ?? 10
        );
    }
}
