/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.tsx",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors:{
        "ciano-escuro": "#0E7490",
        "cinza-carvao": "#262626",
        "azul-petroleo": "#1E3A5F",
        "branco-gelo": "#F0F4F8",
        "violeta-escuro": "#5B21B6",
        "cinza-grafite": "#3D3D3D",
        "vermelho-rubi": "#9B111E"
      },
      keyframes: {
              shine: {
                '0%': { 'background-position': '100%' },
                '100%': { 'background-position': '-100%' },
              },
            },
            animation: {
              shine: 'shine 5s linear infinite',
            },
    },
  },
  plugins: [],
}
