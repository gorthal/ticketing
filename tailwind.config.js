import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./app/Filament/**/*.php",
        "./app/Livewire/**/*.php",
        "./vendor/filament/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                danger: colors => ({
                    50: colors.red[50],
                    100: colors.red[100],
                    200: colors.red[200],
                    300: colors.red[300],
                    400: colors.red[400],
                    500: colors.red[500],
                    600: colors.red[600],
                    700: colors.red[700],
                    800: colors.red[800],
                    900: colors.red[900],
                    950: colors.red[950],
                }),
                primary: colors => ({
                    50: colors.indigo[50],
                    100: colors.indigo[100],
                    200: colors.indigo[200],
                    300: colors.indigo[300],
                    400: colors.indigo[400],
                    500: colors.indigo[500],
                    600: colors.indigo[600],
                    700: colors.indigo[700],
                    800: colors.indigo[800],
                    900: colors.indigo[900],
                    950: colors.indigo[950],
                }),
                success: colors => ({
                    50: colors.green[50],
                    100: colors.green[100],
                    200: colors.green[200],
                    300: colors.green[300],
                    400: colors.green[400],
                    500: colors.green[500],
                    600: colors.green[600],
                    700: colors.green[700],
                    800: colors.green[800],
                    900: colors.green[900],
                    950: colors.green[950],
                }),
                warning: colors => ({
                    50: colors.amber[50],
                    100: colors.amber[100],
                    200: colors.amber[200],
                    300: colors.amber[300],
                    400: colors.amber[400],
                    500: colors.amber[500],
                    600: colors.amber[600],
                    700: colors.amber[700],
                    800: colors.amber[800],
                    900: colors.amber[900],
                    950: colors.amber[950],
                }),
            },
        },
    },
    safelist: [
        {
            pattern: /bg-(primary|success|warning|danger)-(50|100|200|300|400|500|600|700|800|900|950)/,
            variants: ['hover'],
        },
        {
            pattern: /text-(primary|success|warning|danger)-(50|100|200|300|400|500|600|700|800|900|950)/,
            variants: ['hover'],
        },
        {
            pattern: /border-(primary|success|warning|danger)-(50|100|200|300|400|500|600|700|800|900|950)/,
            variants: ['hover'],
        },
    ],
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
