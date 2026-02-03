<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;

class ThemeRenderer
{
    public function render(array $pageConfig, array $context = []): string
    {
        $sections = Arr::get($pageConfig, 'sections', []);
        $output = '';

        foreach ($sections as $section) {
            $type = $section['type'] ?? 'unknown';
            $view = "sections.$type";
            if (!View::exists($view)) {
                continue;
            }

            $output .= View::make($view, [
                'settings' => $section['settings'] ?? [],
                'blocks' => $section['blocks'] ?? [],
                'context' => $context,
            ])->render();
        }

        return $output;
    }
}
