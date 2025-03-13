/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.jsx",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors:{
        "color_gray_200":"#F0F4F8",
        "color_gray_400":"#3D3D3D",
        "color_gray_500":"#02060c",
        "ciano_escuro": "#0E7490",
        "azul_petroleo": "#274C7C"
      }
    },
  },
  plugins: [],
}
