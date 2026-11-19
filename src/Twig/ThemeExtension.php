<?php

declare(strict_types=1);

namespace Byteworks\SonataThemeBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Theme options as the Twig global "adminlte4", and helpers for Sonata values that carry AdminLTE 2 classes.
 */
final class ThemeExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * AdminLTE 2 colors without a Bootstrap 5 counterpart of the same name.
     */
    private const COLORS = ['default' => 'secondary', 'aqua' => 'info', 'green' => 'success', 'yellow' => 'warning', 'red' => 'danger', 'blue' => 'primary', 'gray' => 'secondary'];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $options,
    ) {
    }

    public function getGlobals(): array
    {
        return ['adminlte4' => $this->options];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('adminlte4_card_class', $this->cardClass(...)),
            new TwigFilter('adminlte4_color', $this->color(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('adminlte4_unsupported', $this->unsupported(...)),
        ];
    }

    /**
     * Card classes for a group's box_class: "box box-primary" (Sonata's default, a colored top border) becomes
     * "card card-primary card-outline", "box box-solid box-danger" (a colored header) "card card-danger". Values
     * that already use card classes, and other classes, are kept.
     */
    public function cardClass(?string $boxClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $boxClass), -1, \PREG_SPLIT_NO_EMPTY) ?: ['box'];
        if (\in_array('card', $classes, true)) {
            return implode(' ', $classes);
        }

        $solid  = \in_array('box-solid', $classes, true);
        $result = ['card'];
        foreach ($classes as $class) {
            if ('box' === $class || 'box-solid' === $class) {
                continue;
            }

            if (str_starts_with($class, 'box-')) {
                $result[] = 'card-' . $this->color(substr($class, 4));
                if (!$solid) {
                    $result[] = 'card-outline';
                }

                continue;
            }

            $result[] = $class;
        }

        return implode(' ', array_unique($result));
    }

    /**
     * Bootstrap 5 color name for an AdminLTE 2 color ("aqua" → "info", "bg-red" → "danger").
     */
    public function color(?string $color): string
    {
        $color = preg_replace('/^(bg|label|btn|text)-/', '', (string) $color) ?? '';

        return self::COLORS[$color] ?? ('' === $color ? 'secondary' : $color);
    }

    /**
     * Fails loudly where a template has not been ported to AdminLTE 4 yet, instead of rendering Bootstrap 3 markup.
     */
    public function unsupported(string $template): never
    {
        throw new \LogicException(\sprintf('The template "%s" is not ported to AdminLTE 4 yet.', $template));
    }
}
