import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Cormorant Garamond', 'Playfair Display', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                // Primary: Maroon/Burgundy (#5A0D23) - Tazkiyah Brand
                primary: {
                    50: '#FDF2F4',
                    100: '#FCE8EC',
                    200: '#F9D0D9',
                    300: '#F4A8B8',
                    400: '#EC7A93',
                    500: '#DD4D6E',
                    600: '#C73A5A',
                    700: '#A52D48',
                    800: '#872540',
                    900: '#5A0D23',  // Base brand color
                    950: '#3D0818',
                },
                // Secondary: Gold (Excellence, Ihsan)
                secondary: {
                    50: '#FFFDF5',
                    100: '#FEF9E7',
                    200: '#FDF0C3',
                    300: '#FCE49A',
                    400: '#F9D56E',
                    500: '#D4A847',  // Rich Gold
                    600: '#B8922E',
                    700: '#9A7A25',
                    800: '#7D631F',
                    900: '#5C4917',
                    950: '#3D3010',
                },
                // Neutral: Warm Gray (Elegant, Clean)
                neutral: {
                    50: '#FAFAF9',
                    100: '#F5F5F4',
                    200: '#E7E5E4',
                    300: '#D6D3D1',
                    400: '#A8A29E',
                    500: '#78716C',
                    600: '#57534E',
                    700: '#44403C',
                    800: '#292524',
                    900: '#1C1917',
                    950: '#0C0A09',
                },
            },
        },
    },

    plugins: [forms],
};
