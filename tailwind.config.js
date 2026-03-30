const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#ecfeff',
                    100: '#cffafe',
                    300: '#67e8f9',
                    500: '#06b6d4',
                    700: '#0e7490',
                    950: '#083344',
                },
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
