<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /** @return array<string, mixed> */
    protected function flattenTranslations(): array
    {
        $translations = [];

        foreach (File::allFiles(lang_path('id')) as $file) {
            $group = $file->getBasename('.php');

            /** @var array<string, mixed> $lines */
            $lines = require $file->getPathname();

            foreach ($lines as $key => $value) {
                $translations["{$group}.{$key}"] = $value;
            }
        }

        return $translations;
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'username' => $request->user()->username,
                    'role' => $request->user()->role->value,
                    'work_unit_id' => $request->user()->work_unit_id,
                    'employee_id' => $request->user()->employee_id,
                    'must_change_password' => $request->user()->must_change_password,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'lang' => fn () => $this->flattenTranslations(),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
