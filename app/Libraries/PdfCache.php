<?php

namespace App\Libraries;

/**
 * Filesystem cache for generated evaluation PDFs (writable/pdfs).
 *
 * Two different renderers can produce these files, and they do NOT look alike:
 *
 *   - Word COM  — opens the real .doc template, so the letterhead, fonts and
 *                 spacing are the genuine article. Windows only.
 *   - DomPDF    — renders an HTML replica of the form. The only option on the
 *                 Linux shared host, where PHP_OS is not WIN and exec() is off.
 *
 * Historically both wrote to the same filename, so whichever renderer happened
 * to run first for a given employee froze that employee's look forever — one
 * employee would download a Word-rendered form and the next a DomPDF one, with
 * no way to tell them apart on disk. Keeping the two engines under separate
 * names fixes that: an authoritative render always wins, a fallback render is
 * clearly marked as provisional, and dropping in a Word render supersedes the
 * fallback immediately.
 */
final class PdfCache
{
    /** Rendered from the Word template — authoritative, always preferred. */
    public const ENGINE_WORD = 'word';

    /** Rendered by DomPDF — provisional, used only when no Word render exists. */
    public const ENGINE_FALLBACK = 'fallback';

    /** Cache key for a single evaluation sheet. */
    public static function keyForEvaluation(int $evaluationId): string
    {
        return 'eval_' . $evaluationId;
    }

    /** Cache key for an employee's combined (ke-1 + ke-2) document. */
    public static function keyForEmployee(int $employeeId): string
    {
        return 'eval_all_' . $employeeId;
    }

    public static function dir(): string
    {
        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'pdfs';
    }

    /**
     * Absolute path a given engine's render is stored at.
     *
     * The Word render keeps the bare '<key>.pdf' name so the PDFs pre-rendered
     * on Windows and uploaded to the server by deploy-scripts/_generate-upload-pdf.js
     * keep landing exactly where this class looks for them.
     */
    public static function path(string $key, string $engine): string
    {
        $suffix = $engine === self::ENGINE_FALLBACK ? '.dompdf' : '';

        return self::dir() . DIRECTORY_SEPARATOR . $key . $suffix . '.pdf';
    }

    /**
     * Best available render for $key, or null when nothing is cached.
     * A Word render always wins over a fallback one.
     */
    public static function resolve(string $key): ?string
    {
        foreach ([self::ENGINE_WORD, self::ENGINE_FALLBACK] as $engine) {
            $path = self::path($key, $engine);
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Store $contents as the $engine render of $key and return its path.
     * Storing a Word render drops any fallback it supersedes.
     */
    public static function put(string $key, string $engine, string $contents): string
    {
        self::ensureDir();
        $path = self::path($key, $engine);
        file_put_contents($path, $contents);

        if ($engine === self::ENGINE_WORD) {
            @unlink(self::path($key, self::ENGINE_FALLBACK));
        }

        return $path;
    }

    /**
     * Move an already-written PDF into the cache as the $engine render of $key.
     * Used by the Word COM path, which exports straight to a temp file.
     */
    public static function adopt(string $key, string $engine, string $sourcePath): string
    {
        self::ensureDir();
        $path = self::path($key, $engine);
        rename($sourcePath, $path);

        if ($engine === self::ENGINE_WORD) {
            @unlink(self::path($key, self::ENGINE_FALLBACK));
        }

        return $path;
    }

    /** Drop every render of $key, whichever engine produced it. */
    public static function forget(string $key): void
    {
        foreach ([self::ENGINE_WORD, self::ENGINE_FALLBACK] as $engine) {
            @unlink(self::path($key, $engine));
        }
    }

    private static function ensureDir(): void
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
