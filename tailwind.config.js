import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            /*
             * Identidad visual del sistema: negro grafito + turquesa.
             * "brand" concentra el acento de acción y "ink" los tonos oscuros
             * del sidebar, encabezados y textos fuertes.
             */
            colors: {
                brand: {
                    50: '#ecfdf9',
                    100: '#d0faf0',
                    200: '#a4f3e3',
                    300: '#6ce7d3',
                    400: '#34d3bf',
                    500: '#14b8a6',
                    600: '#0d9488',
                    700: '#0f766e',
                    800: '#115e59',
                    900: '#134e4a',
                    950: '#042f2e',
                },
                ink: {
                    50: '#f6f7f9',
                    100: '#eceef2',
                    200: '#d6dae2',
                    300: '#b1b9c6',
                    400: '#8592a6',
                    500: '#66748b',
                    600: '#505c72',
                    700: '#414b5e',
                    800: '#2b3240',
                    900: '#111827',
                    950: '#0a0f1a',
                },
            },

            boxShadow: {
                card: '0 1px 2px rgba(10, 15, 26, 0.04), 0 12px 32px -18px rgba(10, 15, 26, 0.25)',
                glow: '0 0 0 1px rgba(20, 184, 166, 0.25), 0 12px 40px -12px rgba(20, 184, 166, 0.45)',
            },
        },
    },

    plugins: [forms],
};
