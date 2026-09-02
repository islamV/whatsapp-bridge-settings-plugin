<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class InstallTailwindSourcesCommand extends Command
{
    protected $signature = 'whatsapp-bridge-settings:install-tailwind
                            {--css=resources/css/app.css : Fallback CSS file when no Filament theme is found}
                            {--force : Re-inject even if the @source lines are already present}';

    protected $description = 'Inject the required Tailwind CSS v4 @source directives for the WhatsApp Bridge Settings plugin';

    // Relative to resources/css/app.css (2 levels up = project root)
    private const SOURCES_APP = [
        "@source '../../vendor/islamv/whatsapp-bridge-settings-plugin/resources/views/**/*.blade.php';",
        "@source '../../vendor/islamv/whatsapp-bridge-settings-plugin/src/**/*.php';",
    ];

    // Relative to resources/css/filament/{panel}/theme.css (4 levels up = project root)
    private const SOURCES_THEME = [
        "@source '../../../../vendor/islamv/whatsapp-bridge-settings-plugin/resources/views/**/*.blade.php';",
        "@source '../../../../vendor/islamv/whatsapp-bridge-settings-plugin/src/**/*.php';",
    ];

    public function handle(): int
    {
        $themes = $this->detectFilamentThemes();

        if ($themes->isNotEmpty()) {
            return $this->injectIntoThemes($themes);
        }

        // No Filament theme found — fall back to the generic CSS file
        $this->line('  <comment>No Filament panel themes found. Falling back to: '.$this->option('css').'</comment>');
        $this->newLine();

        return $this->injectIntoFile(
            base_path($this->option('css')),
            $this->option('css'),
            self::SOURCES_APP
        );
    }

    // ── Theme detection ───────────────────────────────────────────────────────

    /**
     * Discover all resources/css/filament/{panel}/theme.css files.
     *
     * @return Collection<int, array{path: string, label: string}>
     */
    private function detectFilamentThemes(): Collection
    {
        $dir = resource_path('css/filament');

        if (! is_dir($dir)) {
            return collect();
        }

        return collect(glob("{$dir}/*/theme.css") ?: [])
            ->map(function (string $path) use ($dir): array {
                $panel = basename(dirname($path));
                $label = "resources/css/filament/{$panel}/theme.css";

                return compact('path', 'label');
            });
    }

    // ── Injection helpers ─────────────────────────────────────────────────────

    private function injectIntoThemes(Collection $themes): int
    {
        $overall = self::SUCCESS;

        foreach ($themes as ['path' => $path, 'label' => $label]) {
            $result = $this->injectIntoFile($path, $label, self::SOURCES_THEME);

            if ($result === self::FAILURE) {
                $overall = self::FAILURE;
            }
        }

        return $overall;
    }

    /**
     * @param  string[]  $sources
     */
    private function injectIntoFile(string $absolutePath, string $displayLabel, array $sources): int
    {
        if (! file_exists($absolutePath)) {
            $this->error("File not found: {$absolutePath}");
            $this->line('  Pass the correct path with <comment>--css=path/to/app.css</comment>');

            return self::FAILURE;
        }

        $contents = file_get_contents($absolutePath);

        // ── Already present check ─────────────────────────────────────────────
        $alreadyPresent = collect($sources)
            ->every(fn (string $line) => str_contains($contents, $line));

        if ($alreadyPresent && ! $this->option('force')) {
            $this->info("✓ @source directives already present in: {$displayLabel}");

            return self::SUCCESS;
        }

        // ── Only add missing lines ────────────────────────────────────────────
        $toAdd = collect($sources)
            ->reject(fn (string $line) => str_contains($contents, $line))
            ->values();

        if ($toAdd->isEmpty()) {
            $this->info("✓ All @source directives already present in: {$displayLabel}");

            return self::SUCCESS;
        }

        // ── Smart insertion: after last existing @source, else append ─────────
        $block = "\n".$toAdd->join("\n")."\n";

        if (preg_match('/^@source [^\n]+\n/m', $contents)) {
            preg_match_all('/^@source [^\n]+\n/m', $contents, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($matches[0]);
            $insertAt = $lastMatch[1] + strlen($lastMatch[0]);
            $newContents = substr($contents, 0, $insertAt).$block.substr($contents, $insertAt);
        } else {
            $newContents = rtrim($contents)."\n".$block;
        }

        file_put_contents($absolutePath, $newContents);

        $this->info("✓ Added @source directives to: {$displayLabel}");
        $this->newLine();

        foreach ($toAdd as $line) {
            $this->line("  <fg=green>+</> {$line}");
        }

        $this->newLine();
        $this->line('  <comment>Run <fg=white>npm run build</> to recompile your CSS assets.</comment>');

        return self::SUCCESS;
    }
}
