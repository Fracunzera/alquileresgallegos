<?php

declare(strict_types=1);

namespace App;

final class View
{
    /** @param array<string, mixed> $datos */
    public static function render(string $plantilla, array $datos = [], ?string $layout = 'layout'): string
    {
        $contenido = self::parcial($plantilla, $datos);
        if ($layout === null) {
            return $contenido;
        }
        return self::parcial($layout, ['contenido' => $contenido] + $datos);
    }

    /** @param array<string, mixed> $datos */
    public static function parcial(string $__plantilla, array $datos = []): string
    {
        extract($datos, EXTR_SKIP);
        ob_start();
        try {
            require RAIZ . '/templates/' . $__plantilla . '.php';
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
