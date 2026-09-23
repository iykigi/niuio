/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/blade-ui-kit/blade-heroicons/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Inter var"', 'Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
            },
            colors: {
                // Portway's primary brand hue — a saturated violet, in the
                // register modern developer platforms use, and distinct from
                // any existing hosting brand's palette. The scale is tuned so
                // 600 on white and 300/400 on the dark surfaces both clear
                // WCAG AA for body text.
                harbor: {
                    50: '#f5f3ff',
                    100: '#ebe7fe',
                    200: '#d9d2fe',
                    300: '#bcaefc',
                    400: '#9b83f9',
                    500: '#7d5cf3',
                    600: '#6b3ae8',
                    700: '#5a29cc',
                    800: '#4b23a7',
                    900: '#3f2186',
                    950: '#26125c',
                },
                // Secondary accent — a warm coral used sparingly, for "new"
                // badges and the occasional highlight. It has to sit next to
                // the violet without fighting it, so it leans pink-warm
                // rather than orange.
                beacon: {
                    50: '#fff5f2',
                    100: '#ffe8e1',
                    200: '#ffd0c3',
                    300: '#ffae97',
                    400: '#ff8163',
                    500: '#fa5a38',
                    600: '#e73f1c',
                    700: '#c22f14',
                    800: '#9e2a15',
                    900: '#822818',
                    950: '#471107',
                },
                // Neutrals carry a faint violet cast so white space reads as
                // part of the palette rather than as plain grey.
                surface: {
                    0: '#ffffff',
                    50: '#faf9fc',
                    100: '#f4f2f8',
                    200: '#e7e4ef',
                    800: '#191527',
                    900: '#120f1d',
                    950: '#0a0812',
                },
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 6px -1px rgb(15 23 42 / 0.06)',
                lift: '0 8px 24px -8px rgb(15 23 42 / 0.16)',
            },
            borderRadius: {
                xl2: '1.25rem',
            },
            animation: {
                'fade-in': 'fadeIn 150ms ease-out',
                'slide-up': 'slideUp 200ms ease-out',
            },
            keyframes: {
                fadeIn: { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
                slideUp: { '0%': { opacity: 0, transform: 'translateY(6px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
