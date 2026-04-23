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
            colors: {
                cream: '#FFF8EE',
                'cream-dark': '#F5EDDB',
                ink: '#1A1100',
                orange: '#FD4401',
                yellow: '#FDCB40',
            },
            fontFamily: {
                sans: ['Pretendard', 'Inter', 'sans-serif'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
