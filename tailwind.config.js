/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            animation: {
                // Вариант 1: Быстрый и частый (онлайн)
                "pulse-fast":
                    "pulse-fast 6s cubic-bezier(0, 0.54, 0.53, 1) infinite",
                "pulse-micro":
                    "pulse-micro 5s cubic-bezier(0, 0.54, 0.53, 1) infinite",
                // Вариант 2: Медленный и плавный (файлы)
                "pulse-slow":
                    "pulse-slow 7s cubic-bezier(0.2, 0.6, 0.4, 1) infinite",
                // Вариант 3: Очень широкий размах (скачивания)
                "pulse-wide":
                    "pulse-wide 6.6s cubic-bezier(0, 0.7, 0.3, 1) infinite",
                "pulse-fast-once":
                    "pulseFast 4s cubic-bezier(0, 0.54, 0.53, 1) forwards",
                "pulse-fast-once-wide":
                    "pulse-wide 4s cubic-bezier(0, 0.54, 0.53, 1) forwards",

                "pulse-micro-once":
                    "pulseMicro 2s cubic-bezier(0, 0.54, 0.53, 1) forwards",

                "pulse-micro-1":
                    "pulse-micro 1.2s cubic-bezier(0, 0.51, 0.53, 1) infinite",
                "pulse-fast-1":
                    "pulse-fast 5s cubic-bezier(0, 0.51, 0.52, 1) infinite",

            },
            keyframes: {
                "pulse-fast": {
                    "0%": { transform: "scale(0.1)", opacity: "0.7" },
                    "100%": { transform: "scale(2.4)", opacity: "0" },
                },
                "pulse-micro": {
                    "0%": { transform: "scale(0.1)", opacity: "0.6" },
                    "100%": { transform: "scale(1.4)", opacity: "0" },
                },
                "pulse-slow": {
                    "0%": { transform: "scale(0.1)", opacity: "0.6" },
                    "100%": { transform: "scale(1.6)", opacity: "0" },
                },
                "pulse-wide": {
                    "0%": { transform: "scale(0.1)", opacity: "0.9" },
                    "100%": { transform: "scale(1.4)", opacity: "0" }, // ← сильно раздувается!
                },
                pulseFast: {
                    "0%": { transform: "scale(0.1)", opacity: "0.7" },
                    "100%": { transform: "scale(2.4)", opacity: "0" },
                },
                pulseMicro: {
                    "0%": { transform: "scale(0.1)", opacity: "0.7" },
                    "100%": { transform: "scale(0.6)", opacity: "0" },
                },
            },
            fontFamily: {
                // Заменяем стандартные семейства ИЛИ добавляем новые
                "sans": ["Roboto", "sans-serif"], // например, делаем Roboto основным sans-serif
                "poppins": ["Poppins", "sans-serif"],
                "roboto-condensed": ["Roboto Condensed", "sans-serif"],
                "source-code": ["Source Code Pro", "monospace"],
                "jura": ["Jura", "sans-serif"],
                "tiktok-sans": ["TikTok Sans", "sans-serif"],
            },
        },
    },
    plugins: [],
};