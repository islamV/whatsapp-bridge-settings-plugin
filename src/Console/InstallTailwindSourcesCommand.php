<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Console;

use Illuminate\Console\Command;

class InstallTailwindSourcesCommand extends Command
{
    protected $signature = 'whatsapp-bridge-settings:install-tailwind
                            {--css=resources/css/app.css : Path to your Tailwind CSS entry file}
                            {--force : Overwrite existing @source lines if already present}';

    protected $description = 'Append the required Tailwind CSS v4 @source directives for the WhatsApp Bridge Settings plugin';

    /** Lines to inject */
    private const SOURCES = [
        "@source '../../vendor/islamv/whatsapp-bridge-settings-plugin/resources/views/**/*.blade.php';",
        "@source '../../vendor/islamv/whatsapp-bridge-settings-plugin/src/**/*.php';",
    ];

    public function handle(): int
    {
        $cssPath = base_path($this->option('css'));

        if (! file_exists($cssPath)) {
            $this->error("CSS file not found: {$cssPath}");
            $this->line('  Pass the correct path with <comment>--css=path/to/app.css</comment>');

            return self::FAILURE;
        }

        $contents = file_get_contents($cssPath);

        // ── Already present check ─────────────────────────────────────────────
        $alreadyPresent = collect(self::SOURCES)
            ->every(fn (string $line) => str_contains($contents, $line));

        if ($alreadyPresent && ! $this->option('force')) {
            $this->info('✓ Tailwind @source directives are already present in: ' . $this->option('css'));

            return self::SUCCESS;
        }

        // ── Filter out lines that are already in the file ─────────────────────
        $toAdd = collect(self::SOURCES)
            ->reject(fn (string $line) => str_contains($contents, $line))
            ->values();

        if ($toAdd->isEmpty()) {
            $this->info('✓ All @source directives are already present.');

            return self::SUCCESS;
        }

        // ── Build the block to append ─────────────────────────────────────────
        $block = "\n" . $toAdd->join("\n") . "\n";

        // Insert after the last existing @source line if any exist, otherwise append.
        if (preg_match('/(^@source [^\n]+\n)/m', $contents)) {
            // Find the position right after the last @source line
            preg_match_all('/^@source [^\n]+\n/m', $contents, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch  = end($matches[0]);
            $insertAt   = $lastMatch[1] + strlen($lastMatch[0]);
            $newContents = substr($contents, 0, $insertAt) . $block . substr($contents, $insertAt);
        } else {
            // No existing @source lines — append at end
            $newContents = rtrim($contents) . "\n" . $block;
        }

        file_put_contents($cssPath, $newContents);

        $this->info('✓ Added Tailwind @source directives to: ' . $this->option('css'));
        $this->newLine();

        foreach ($toAdd as $line) {
            $this->line("  <fg=green>+</> {$line}");
        }

        $this->newLine();
        $this->line('  <comment>Run <fg=white>npm run build</> to recompile your CSS assets.</comment>');

        return self::SUCCESS;
    }
}
