<?php

declare(strict_types=1);

namespace App\Core\View;

use RuntimeException;

/**
 * View
 *
 * Render engine sederhana berbasis native PHP, mendukung:
 *   - Layout (extend layout utama, mirip @extends di Blade)
 *   - Section (mirip @section/@yield)
 *   - Include partial/component
 *   - Escape otomatis lewat helper e()
 *
 * Pemakaian di dalam file view:
 *
 *   <?php $this->layout('layouts.app', ['title' => 'Login']) ?>
 *
 *   <?php $this->section('content') ?>
 *     <h1>Halo</h1>
 *   <?php $this->endSection() ?>
 *
 * Di file layout:
 *
 *   <title><?= $title ?></title>
 *   <?= $this->yield('content') ?>
 */
class View
{
    private string $viewPath;
    private array $data;

    private ?string $layoutName = null;
    private array $layoutData = [];

    private array $sections = [];
    private array $sectionStack = [];

    public function __construct(string $viewPath, array $data = [])
    {
        $this->viewPath = $viewPath;
        $this->data     = $data;
    }

    public static function render(string $name, array $data = []): string
    {
        $path = self::resolvePath($name);
        $instance = new self($path, $data);

        return $instance->renderContent();
    }

    /**
     * Resolve nama view jadi path file fisik.
     * Mendukung namespace module: 'Auth::login' atau view biasa: 'layouts.app'
     */
    public static function resolvePath(string $name): string
    {
        if (str_contains($name, '::')) {
            [$module, $viewName] = explode('::', $name, 2);
            $path = base_path("src/Modules/{$module}/Presentation/Views/" . str_replace('.', '/', $viewName) . '.php');
        } else {
            $path = base_path('resources/views/' . str_replace('.', '/', $name) . '.php');
        }

        if (! file_exists($path)) {
            throw new RuntimeException("View tidak ditemukan: {$path}");
        }

        return $path;
    }

    private function renderContent(): string
    {
        extract($this->data);

        ob_start();
        require $this->viewPath;
        $content = ob_get_clean();

        // Jika view ini extend layout, render layout dengan section yang sudah terisi.
        if ($this->layoutName !== null) {
            $layoutData = array_merge($this->data, $this->layoutData);
            $layoutPath = self::resolvePath($this->layoutName);

            $layoutInstance = new self($layoutPath, $layoutData);
            $layoutInstance->sections = $this->sections;

            return $layoutInstance->renderLayoutOnly();
        }

        return $content;
    }

    /**
     * Render khusus untuk layout (tidak cek layoutName lagi, supaya tidak infinite loop).
     */
    private function renderLayoutOnly(): string
    {
        extract($this->data);

        ob_start();
        require $this->viewPath;

        return ob_get_clean();
    }

    // ===================== LAYOUT & SECTION API (dipanggil via $this dari dalam view) =====================

    protected function layout(string $name, array $data = []): void
    {
        $this->layoutName = $name;
        $this->layoutData = $data;
    }

    protected function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    protected function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    protected function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    protected function include(string $name, array $data = []): string
    {
        return self::render($name, array_merge($this->data, $data));
    }

    protected function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }
}